<?php


namespace Maatoo\WooCommerce\Service\WooCommerce;


use Maatoo\WooCommerce\Entity\MtoProduct;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;

class ProductHooks
{
    private static ?MtoConnector $connector = null;

    protected static function getConnector(){
        if(is_null(self::$connector)){
            self::$connector = new MtoConnector(new MtoUser());
        }

        return self::$connector;
    }

    public function __construct()
    {
        add_action('save_post', [$this, 'saveProduct']);
    }

    public function saveProduct($postId)
    {
        // Check to see if we are autosaving
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $product = new MtoProduct($postId);
        if (!$product) {
            return;
        }

        if(!$product->getLastSyncDate()){
            $endpoint = MtoConnector::getApiEndPoint('product')->create;
        } else {
            $endpoint = MtoConnector::getApiEndPoint('product')->edit;
        }
        $state = self::getConnector()->sendProducts([$postId], $endpoint);

        if(!$state){
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