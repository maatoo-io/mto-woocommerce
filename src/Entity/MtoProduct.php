<?php

namespace Maatoo\WooCommerce\Entity;

use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use WC_Product_Variable;
use WC_Product_Variation;

class MtoProduct extends AbstractMtoEntity
{
    private ?int $id;
    private string $externalProductId;
    private float $price = 0;
    private float $regularPrice = 0;
    private string $url;
    private string $title;
    private string $description;
    private string $shortDescription;
    private string $sku;
    private string $imageUrl;
    private string $datePublished = '';
    private bool $isVisible = true;
    private bool $isProductVariable = false;
    private array $productVariations = [];

    public function __construct($product_id = null)
    {
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        $options = get_option('mto');
        parent::__construct($product_id);

        $this->id = get_post_meta($product_id, '_mto_id', true) ?: null;
        $this->lastSyncDate = get_post_meta($product_id, '_mto_last_sync', true) ?: null;
        $this->sku = $product->get_sku();
        $this->externalProductId = (string)$product_id;
        $this->price = $product->get_price() ?: 0;
        $this->regularPrice = $product->get_regular_price() ?: 0;
        $this->url = $product->get_permalink();
        $this->title = $product->get_title() ?: 'not set';
        $this->description = $product->get_description() ?: '';
        $this->shortDescription = $product->get_short_description() ?: '';
        $this->imageUrl = wp_get_attachment_image_url($product->get_image_id(), !empty($options['product_image_sync_quality']) ? $options['product_image_sync_quality'] : MTO_DEFAULT_PRODUCT_IMAGE_SYNC_QUALITY) ?: '';
        $this->datePublished = (string)$product->get_date_created() ?: null;
        $this->isVisible = $product->is_visible();

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
        }

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
     * @return float|int
     */
    public function getRegularPrice()
    {
        if ($this->regularPrice !== $this->getPrice()) {
            return $this->regularPrice;
        }

        return 0;
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
     * @return string|null
     */
    public function getDatePublished(): ?string
    {
        return date('Y-m-d H:i:s', strtotime($this->datePublished));
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->isVisible;
    }

    public function isProductVariable(): bool
    {
        return $this->isProductVariable;
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
          'regularPrice' => $this->getRegularPrice(),
          'url' => $this->getUrl(),
          'title' => $this->getTitle(),
          'description' => $this->getDescription(),
          'sku' => $this->getSku() ?: null,
          'imageUrl' => $this->getImageUrl() ?: wc_placeholder_img_src(),
          'productCategory' => $mtoCategory ? $mtoCategory->getId() : null,
          'externalDatePublished' => $this->getDatePublished() ?: null,
          'isVisible' => $this->isVisible()
        ];
    }

    public function getProductVariations(): array
    {
        return $this->productVariations;
    }

    public static function isProductHasBeenSynced($productId){
        if(get_post_meta($productId, '_mto_id', true)){
            return true;
        }

        return false;
    }
}
