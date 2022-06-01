<?php

namespace Maatoo\WooCommerce\Entity;

class MtoProduct extends AbstractMtoEntity
{
    private ?int $id = null;
    private string $externalProductId;
    private float $price = 0;
    private string $url;
    private string $title;
    private string $description;
    private string $shortDescription;
    private string $sku;
    private string $imageUrl;
    private ?int $productCategory;
    private ?int $categoryId; //maatoo category id

    public function __construct($product_id = null)
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return null;
        }
        parent::__construct($product_id);
        $this->id = get_post_meta($product_id, '_mto_id', true) ?: null;
        $this->lastSyncDate = get_post_meta($product_id, '_mto_last_sync', true) ?: null;
        $this->sku = $product->get_sku();
        $this->externalProductId = (string)$product_id;
        $this->price = $product->get_price() ?: 0;
        $this->url = $product->get_permalink();
        $this->title = $product->get_title() ?: 'not set';
        $this->description = $product->get_description() ?: '';
        $this->shortDescription = $product->get_short_description() ?: '';
        $this->imageUrl = wp_get_attachment_image_url($product->get_image_id()) ?: '';
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
        if ($this->shortDescription !== '') {
            return $this->shortDescription;
        } else {
            return $this->description;
        }
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
     * If possible take sister category and not mother if both are chosen
     * @return false | int
     */
    public function getCategory()
    {
        if (!$this->externalProductId) {
            return false;
        }
        $terms = wp_get_post_terms($this->getExternalProductId(), MtoProductCategory::$taxonomy);
        if (!$terms) {
            return false;
        }

        foreach ($terms as $term) {
            if ($term->parent !== 0) {
                return $term->term_id;
            }
        }
        return $terms[0]->term_id;
    }

    /**
     * To Array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $category = $this->getCategory();
        $mtoCategory = new MtoProductCategory($category);
        return [
          'store' => $this->getStore(),
          'externalProductId' => $this->getExternalProductId(),
          'price' => $this->getPrice(),
          'url' => $this->getUrl(),
          'title' => $this->getTitle(),
          'description' => $this->getDescription(),
          'sku' => $this->getSku() ?: null,
          'imageUrl' => $this->getImageUrl() ?: wc_placeholder_img_src(),
          'productCategory' => $mtoCategory ? $mtoCategory->getId() : null,
        ];
    }

    public static function isProductHasBeenSynced($productId){
        if(get_post_meta($productId, '_mto_id', true)){
            return true;
        }

        return false;
    }
}