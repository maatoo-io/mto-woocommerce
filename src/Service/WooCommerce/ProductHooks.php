<?php

namespace Maatoo\WooCommerce\Service\WooCommerce;

use Maatoo\WooCommerce\Entity\MtoProduct;
use Maatoo\WooCommerce\Entity\MtoUser;
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
        add_action('save_post', [$this, 'saveProduct']);
        add_action('before_delete_post', [$this, 'removeProduct']);
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
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || get_post_status($postId) === 'trash' || is_null(self::$connector) ) {
            return;
        }

        $product = new MtoProduct($postId);
        if (is_null($product)) {
            return;
        }

        if (!$product->getLastSyncDate()) {
            $endpoint = MtoConnector::getApiEndPoint('product')->create;
        } else {
            $endpoint = MtoConnector::getApiEndPoint('product')->edit;
        }

        $state = self::getConnector()->sendProducts([$postId], $endpoint);

        if (!$state) {
            //TODO put to log
        }
    }

    /**
     * Remove Product.
     *
     * @param $postId
     */
    public function removeProduct($postId)
    {
        $product = new MtoProduct($postId);
        if (!$product || !$product->getId()) {
            return;
        }
        $state = self::getConnector()->sendProducts([$postId], MtoConnector::getApiEndPoint('product')->delete);

        if (!$state) {
            //TODO put to log
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

        foreach ($productIds as $productId) {
            $product = new MtoProduct($productId);
            if (!$product) {
                $toDelete[] = $productIds;
                $f = true;
                continue;
            }

            if (!$product->getLastSyncDate()) {
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

        $mtoConnector = self::getConnector();
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

}