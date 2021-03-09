<?php

namespace Maatoo\WooCommerce\Entity;

class MtoProduct
{
    private ?int $store = null;
    private string $externalProductId;
    private float $price = 0;
    private string $url;
    private string $title;
    private string $description;
    private string $sku;

    public function __construct($product_id = null)
    {
        $product = wc_get_product($product_id);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getExternalProductId(): string
    {
        return $this->externalProductId;
    }

    /**
     * @return float|int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function getSku(): string
    {
        return $this->sku;
    }

    /**
     * @return int|null
     */
    public function getStore(): ?int
    {
        return $this->store;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

}