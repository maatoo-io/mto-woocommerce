<?php

namespace Maatoo\WooCommerce\Entity;

class MtoDraftOrder
{
    private int $id;
    private string $externalId;
    private ?int $mtoId;
    private int $mtoCustomerId;
    private string $cart;
    private string $dateCreated;
    private string $dateModified;

    public function getByKey(string $externalId)
    {
        //todo
    }

    public function getById(int $id)
    {
        //todo
    }

    public function save(){
        //todo
    }

    public function delete(){

    }

    public function update(){
        //todo
    }
}