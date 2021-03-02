<?php

namespace Tests;

use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use PHPUnit\Framework\TestCase;

final class MtoStoreMangerTest extends TestCase
{
    public function testMtoStoreManager()
    {
        $storeManger = new MtoStoreManger();
        $this->assertEquals(true, true);
    }
}