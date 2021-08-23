<?php

namespace Maatoo\WooCommerce\Entity;

class MtoProductCategory extends AbstractMtoEntity
{
    private string $name;
    private string $alias;
    private ?string $url;
    private int $externalId; // local id
    private ?int $id = null; // id stored in maatoo service
    public static string $taxonomy = 'product_cat';


    public function __construct($id)
    {
        $term = get_term_by('id', (int)$id, self::$taxonomy);
        if(!$term){
            return  null;
        }
        $this->id = get_term_meta($term->term_id, '_mto_id', true) ?:null;
        $this->name = $term->name;
        $this->alias = $term->slug;
        $this->url = get_term_link($term, self::$taxonomy);
        $this->externalId = $term->term_id;
        $this->lastSyncDate = get_term_meta($term->term_id, '_mto_last_sync', true) ?: null;
        $this->lastModifiedDate = get_the_modified_date(DATE_W3C, $id);
    }

    public function getId(){
        return $this->id;
    }

    public function getStore(): ?int
    {
        return MTO_STORE_ID;
    }

    /**
     * @return int
     */
    public function getExternalId(): int
    {
        return $this->externalId;
    }

    public function toArray()
    {
        return [
          'store' => $this->getStore(),
          'name'=> $this->name,
          'alias' => $this->alias,
          'url' => $this->url,
          'externalCategoryId' => $this->externalId
        ];
    }
}