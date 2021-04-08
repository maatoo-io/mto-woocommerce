<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoProduct;
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

    public static function isProductsSynced(array $productIds): bool
    {
        if (empty($productIds)) {
            return true;
        }
        $toUpdate = [];
        $toCreate = [];
        $toDelete = [];
        $f = false;
        $mtoConnector = self::getConnector();

        $remoteProducts = $mtoConnector->getRemoteList($mtoConnector::getApiEndPoint('product'));

        foreach ($productIds as $productId) {
            $product = new MtoProduct($productId);
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

        if (!$f) {
            return true;
        }

        $isCreatedStatus = $isUpdatedStatus = $isDelStatus = true;
        if (!empty($toCreate)) {
            $isCreatedStatus = $mtoConnector->sendProducts($toCreate, MtoConnector::getApiEndPoint('product')->create);
        }

        if (!empty($toUpdate)) {
            $isUpdatedStatus = $mtoConnector->sendProducts($toUpdate, MtoConnector::getApiEndPoint('product')->edit);
        }

        if (!empty($toUpdate)) {
            $isDelStatus = $mtoConnector->sendProducts($toDelete, MtoConnector::getApiEndPoint('product')->delete);
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
            if (!$product->getLastSyncDate()) {
                $endpoint = MtoConnector::getApiEndPoint('product')->create;
            } else {
                $endpoint = MtoConnector::getApiEndPoint('product')->edit;
            }

            $state = self::getConnector()->sendProducts([$product->getExternalProductId()], $endpoint);

            if (!$state) {
                LogData::writeApiErrors($state);
            }
        } catch (\Exception $exception) {
            LogData::writeTechErrors($exception->getMessage());
        }
    }
}