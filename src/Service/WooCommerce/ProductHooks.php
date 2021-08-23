<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoProduct;
use Maatoo\WooCommerce\Entity\MtoProductCategory;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;

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
     * Remove Product.
     *
     * @param $postId
     */
    public function removeProduct($postId)
    {
        try {
            if ('product' !== get_post_type($postId)) {
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

        $remoteProducts = $mtoConnector->getRemoteList($mtoConnector::getApiEndPoint('product'));
        $categories = [];
        foreach ($productIds as $productId) {
            $product = new MtoProduct($productId);
            $categories[] = $product->getCategory();
            if (!$product) {
                $toDelete[] = $productIds;
                $f = true;
                continue;
            }
            $isExistRemote = array_key_exists($product->getId(), $remoteProducts['products']);
            if (!$isExistRemote) {
                $toCreate[] = $productId;
                $f = true;
                continue;
            }

            if ($product->isSyncRequired()) {
                $toUpdate[] = $productId;
                $f = true;
                continue;
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

    public function singleProductSync(MtoProduct $product)
    {
        if (!$product) {
            return;
        }
        try {
            self::syncProductCategories($product->getCategory());
            if (!$product->getLastSyncDate()) {
                $endpoint = MtoConnector::getApiEndPoint('product')->create ?? null;
            } else {
                $endpoint = MtoConnector::getApiEndPoint('product')->edit ?? null;
            }

            $state = self::getConnector()->sendProducts([$product->getExternalProductId()], $endpoint);

            if (!$state) {
                LogData::writeApiErrors($state);
            }
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }

    public static function syncProductCategories($categoryIds)
    {
        if (!$categoryIds) {
            return;
        }
        $mtoConnector = self::getConnector();
        $remoteCategories = $mtoConnector->getRemoteList($mtoConnector::getApiEndPoint('category'));
        $remoteCategoriesIds = array_column($remoteCategories, 'externalCategoryId', 'id');
        $categoryIds = array_unique($categoryIds);
        $toCreate = $toUpdate = [];
        foreach ((array)$categoryIds as $categoryId) {
            $category = new MtoProductCategory($categoryId);
            if ($category->isSyncRequired() && in_array($categoryId, $remoteCategoriesIds)) {
                $toUpdate[] = $categoryId;
            } else {
                $toCreate[] = $categoryId;
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