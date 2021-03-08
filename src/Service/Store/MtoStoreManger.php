<?php

namespace Maatoo\WooCommerce\Service\Store;

use Maatoo\WooCommerce\Entity\MtoStore;

class MtoStoreManger
{
    public function create(){
        return new MtoStore();
    }

    public function sendData(MtoStore $store){

        return 'test';
    }
}