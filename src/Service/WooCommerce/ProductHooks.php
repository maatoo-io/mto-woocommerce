<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoProduct;
use Maatoo\WooCommerce\Entity\MtoProductCategory;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use WC_Product_Variable;

/**
 * Class ProductHooks
 *
 * @package Maatoo\WooCommerce\Service\WooCommerce
 */
class ProductHooks
{
    /**
     * Connector.
     *
     * @var MtoConnector|null
     */
    private static ?MtoConnector $connector = null;

    /**
     * ProductHooks constructor.
     */
    public function __construct()
    {
        add_action('woocommerce_update_product', [$this, 'saveProduct']);
        add_action('woocommerce_product_duplicate', [$this, 'duplicateProduct']);
        add_action('before_delete_post', [$this, 'removeProduct']);
        add_action('mto_background_product_sync', [$this, 'singleProductSync'], 10, 1);
    }

    /**
     * Get Connector.
     *
     * @return MtoConnector|null
     */
    protected static function getConnector()
    {
        if (is_null(self::$connector)) {
            self::$connector = MtoConnector::getInstance(new MtoUser());
        }

        return self::$connector;
    }

    /**
     * Save Product.
     *
     * @param $postId
     */
    public function saveProduct($postId)
    {
        // Check to see if we are autosaving
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || get_post_status($postId) !== 'publish' || is_null(
            self::getConnector()
          )) {
            return;
        }
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        try {
            $product = new MtoProduct($postId);

            if ($product->getLastModifiedDate()) {
                if (is_null($product)) {
                    return;
                }
            }
            wp_schedule_single_event(time() - 1, 'mto_background_product_sync', [$product]);
            remove_action('woocommerce_update_product', [$this, 'saveProduct']);
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    /**
     * Update a product when it is duplicated to remove mto metadata
     *
     * @param $product
     */
    public function duplicateProduct($product)
    {
        $mtoProduct = new MtoProduct($product->get_id());
        // When duplicating a product we need to make sure that the mto_id and mto_last_sync will not get copied over to the new product
        if ($product instanceof WC_Product_Variable) {
            $productVariations = MtoStoreManger::getVariationsForProduct($product->get_id());
            foreach($productVariations as $productVariation) {
                $mtoProductVariated = new MtoProduct($productVariation->get_id());
                if ($mtoProductVariated->getId()) {
                    update_post_meta((int) $productVariation->get_id(), '_mto_id', '');
                    update_post_meta((int) $productVariation->get_id(), '_mto_last_sync', '');
                    $this->saveProduct($productVariation->get_id());
                }
            }
        }

        if($mtoProduct->getId()) {
            update_post_meta((int) $product->get_id(), '_mto_id', '');
            update_post_meta((int) $product->get_id(), '_mto_last_sync', '');
            $this->saveProduct($product->get_id());
        }
    }

    /**
     * Remove Product.
     *
     * @param $postId
     */
    public function removeProduct($postId)
    {
        try {
            if (!in_array(get_post_type($postId), ['product', 'product_variation'])) {
                return;
            }

            $state = self::getConnector()->sendProducts([$postId], MtoConnector::getApiEndPoint('product')->delete);
            if (!$state) {
                LogData::writeApiErrors('Product sync was failed: ' . $state);
            }
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    public static function isProductsSynced(array $productIds, $forceUpdate = false): bool
    {
        if (empty($productIds)) {
            return true;
        }
        $productIds = array_unique($productIds);
        $toUpdate = [];
        $toCreate = [];
        $toDelete = [];
        $f = false;
        $mtoConnector = self::getConnector();

        $categories = [];
        foreach ($productIds as $productId) {
            $wooProduct = wc_get_product($productId);
            if($wooProduct instanceof WC_Product_Variable){
                $productVariations = MtoStoreManger::getVariationsForProduct($productId);
                foreach($productVariations as $productVariation){
                    $mtoProductVariated = new MtoProduct($productVariation->get_id());
                    if (!$mtoProductVariated) {
                        continue;
                    }
                    if (!$mtoProductVariated->getId()) {
                        $toCreate[] = $productVariation->get_id();
                        $f = true;
                        continue;
                    }
                    if ($mtoProductVariated->isSyncRequired()) {
                        $toUpdate[] = $productVariation->get_id();
                        $f = true;
                    }
                }
            }

            $product = new MtoProduct($productId);
            $categories[] = $product->getCategory();
            if (!$product) {
                continue;
            }
            if (!$product->getId()) {
                $toCreate[] = $productId;
                $f = true;
                continue;
            }

            if ($product->isSyncRequired()) {
                $toUpdate[] = $productId;
                $f = true;
            }
        }

        if (!$f && !$forceUpdate) {
            return true;
        }

        self::syncProductCategories($categories);
        $isCreatedStatus = $isUpdatedStatus = $isDelStatus = true;

        if (!empty($toCreate)) {
            $isCreatedStatus = $mtoConnector->sendProducts(
              $toCreate,
              MtoConnector::getApiEndPoint('product')->create ?? null
            );
        }

        if (!empty($toUpdate)) {
            $isUpdatedStatus = $mtoConnector->sendProducts(
              $toUpdate,
              MtoConnector::getApiEndPoint('product')->edit ?? null
            );
        }

        if (!empty($toDelete)) {
            $isDelStatus = $mtoConnector->sendProducts(
              $toDelete,
              MtoConnector::getApiEndPoint('product')->delete ?? null
            );
        }

        if ($isCreatedStatus && $isUpdatedStatus && $isDelStatus) {
            return true;
        }

        return false;
    }

    public function singleProductSync(MtoProduct $product): bool
    {
        if (!$product) {
            return false;
        }

        try {
            $toCreate = $toUpdate = [];
            $updateEndpoint = MtoConnector::getApiEndPoint('product')->edit ?? null;
            $createEndpoint = MtoConnector::getApiEndPoint('product')->create ?? null;
            $isCreatedStatus = $isUpdatedStatus =  true;
            $wooProduct = wc_get_product($product->getExternalProductId());

            if (!$product->getLastSyncDate()) {
                $toCreate[] = $product->getExternalProductId();
            } else {
                $toUpdate[] = $product->getExternalProductId();
            }

            if ($wooProduct instanceof WC_Product_Variable) {
                $productVariations = MtoStoreManger::getVariationsForProduct($wooProduct->get_id());
                foreach($productVariations as $productVariation){
                    $mtoProductVariated = new MtoProduct($productVariation->get_id());
                    if (!$mtoProductVariated) {
                        continue;
                    }
                    if (!$mtoProductVariated->getId()) {
                        $toCreate[] = $productVariation->get_id();
                        continue;
                    }
                    if ($mtoProductVariated->isSyncRequired()) {
                        $toUpdate[] = $productVariation->get_id();
                    }
                }
            }

            self::syncProductCategories([$product->getCategory()]);
            if(!empty($toCreate)){
                $isCreatedStatus = self::getConnector()->sendProducts($toCreate, $createEndpoint);
            }
            if(!empty($toUpdate)){
                $isUpdatedStatus = self::getConnector()->sendProducts($toUpdate, $updateEndpoint);
            }

            return $isCreatedStatus && $isUpdatedStatus;

        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }

        return false;
    }

    public static function syncProductCategories(array $categoryIds)
    {
        if (empty($categoryIds)) {
            return;
        }
        $categoryIds = array_unique($categoryIds);
        $toCreate = $toUpdate = [];
        foreach ($categoryIds as $categoryId) {
            $category = new MtoProductCategory($categoryId);
            if(!$category->getId()) {
                $toCreate[] = $categoryId;
            }

            if ($category->getId() && $category->isSyncRequired()) {
                $toUpdate[] = $categoryId;
            }
        }

        if (!empty($toUpdate)) {
            try {
                $state = self::getConnector()->sendProductCategories(
                  $toUpdate,
                  MtoConnector::getApiEndPoint(
                    'category'
                  )->edit ?? null
                );

                if (!$state) {
                    LogData::writeApiErrors($state);
                }
            } catch (\Exception $exception) {
                LogData::writeTechErrors($exception->getMessage());
            }
        }
        if (!empty($toCreate)) {
            try {
                $state = self::getConnector()->sendProductCategories(
                  $toCreate,
                  MtoConnector::getApiEndPoint(
                    'category'
                  )->create ?? null
                );
                if (!$state) {
                    LogData::writeApiErrors($state);
                }
            } catch (\Exception $exception) {
                LogData::writeTechErrors($exception->getMessage());
            }
        }
    }
}
