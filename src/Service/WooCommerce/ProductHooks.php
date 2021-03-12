<?php


namespace Maatoo\WooCommerce\Service\WooCommerce;


use Maatoo\WooCommerce\Entity\MtoProduct;
use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;

class ProductHooks
{
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

        $mtoConnector = new MtoConnector(new MtoUser());
        $isCreatedStatus = $isUpdatedStatus = $isDelStatus = true;
        if (!empty($toCreate)) {
            $isCreatedStatus = $mtoConnector->sendProducts($toCreate, MtoConnector::getApiEndPoint('product')->create);
        }

        if(!empty($toUpdate)){
            $isUpdatedStatus = $mtoConnector->sendProducts($toUpdate, MtoConnector::getApiEndPoint('product')->edit);
        }

        if(!empty($toUpdate)){
            $isDelStatus = $mtoConnector->sendProducts($toDelete, MtoConnector::getApiEndPoint('product')->delete);
        }

        if($isCreatedStatus && $isUpdatedStatus && $isDelStatus){
            return true;
        }

        return false;
    }

}