<?php

namespace Maatoo\WooCommerce\Entity;

/**
 * Class MtoStore
 *
 * @package Maatoo\WooCommerce\Entity
 */
class MtoStore
{
    private ?int $id;
    private ?string $name;
    private ?string $shortName;
    private ?string $currency;
    private ?string $externalStoreId;
    private ?string $platform;
    private ?string $domain;

    /**
     * To Mto Store.
     *
     * @param $name
     * @param $shortName
     * @param $currency
     * @param $externalStoreId
     * @param $platform
     *
     * @return MtoStore
     */
    public static function toMtoStore(
        $name,
        $shortName,
        $currency,
        $externalStoreId,
        $domain,
        $id = null,
        $platform = 'woocommerce'
    ): MtoStore {
        $store = new MtoStore();
        return $store->setName($name)
                     ->setShortName($shortName)
                     ->setCurrency($currency)
                     ->setExternalStoreId($externalStoreId)
                     ->setPlatform($platform)
                     ->setId($id)
                     ->setDomain($domain);
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set Id.
     *
     * @param int|null $id
     *
     * @return $this
     */
    public function setId(?int $id): MtoStore
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Set Domain.
     *
     * @param string|null $domain
     *
     * @return $this
     */
    public function setDomain(?string $domain): MtoStore
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     *
     * @return MtoStore
     */
    public function setName(?string $name): MtoStore
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    /**
     * @param string|null $shortName
     *
     * @return MtoStore
     */
    public function setShortName(?string $shortName): MtoStore
    {
        $this->shortName = $shortName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency
     *
     * @return MtoStore
     */
    public function setCurrency(?string $currency): MtoStore
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getExternalStoreId(): ?string
    {
        return $this->externalStoreId;
    }

    /**
     * @return string|null
     */
    public function getPlatform(): ?string
    {
        return $this->platform;
    }


    /**
     * @param string|null $externalStoreId
     *
     * @return MtoStore
     */
    public function setExternalStoreId(?string $externalStoreId): MtoStore
    {
        $this->externalStoreId = $externalStoreId;
        return $this;
    }

    /**
     * @param string|null $platform
     *
     * @return MtoStore
     */
    public function setPlatform(?string $platform): MtoStore
    {
        $this->platform = $platform;
        return $this;
    }

    public function toArray(){
        return [
            'domain' => $this->getDomain(),
            'name' => $this->getName(),
            'shortName' => $this->getShortName(),
            'currency' => $this->getCurrency(),
            'externalStoreId' => $this->getExternalStoreId(),
            'platform' => $this->getPlatform(),
        ];
    }
}