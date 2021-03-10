<?php

namespace Maatoo\WooCommerce\Entity;

class MtoProduct
{
    private ?int $id = null;
    private string $externalProductId;
    private float $price = 0;
    private string $url;
    private string $title;
    private string $description;
    private string $sku;
    private string $imageUrl;

    public function __construct($product_id = null)
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return null;
        }

        $this->id = get_post_meta($product_id, '_mto_id', true) ?: null;
        $this->sku = $product->get_sku();
        $this->externalProductId = (string)$product_id;
        $this->price = $product->get_price() ? : 0;
        $this->url = $product->get_permalink();
        $this->title = $product->get_title() ? : 'untitled';
        $this->description = $product->get_description() ? : '';
        $this->imageUrl = wp_get_attachment_image_url($product->get_image_id()) ? : '';
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
        return MTO_STORE_ID;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string|null
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @return int|mixed|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * To Array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'store' => $this->getStore(),
            'externalProductId' => $this->getExternalProductId(),
            'price' => $this->getPrice(),
            'url' => $this->getUrl(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'sku' => $this->getSku(),
            'imageUrl' => $this->getImageUrl(),
        ];
    }
}