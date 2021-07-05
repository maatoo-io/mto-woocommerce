<?php

namespace Maatoo\WooCommerce\Entity;

abstract class AbstractMtoEntity
{
    protected ?string $lastSyncDate = null;
    protected ?string $lastModifiedDate = null;

    public function __construct($id)
    {
        $this->lastModifiedDate = get_the_modified_date(DATE_W3C, $id);
        $this->lastSyncDate = get_post_meta($id, '_mto_last_sync', true);
    }

    /**
     * @return string|null
     */
    public function getLastModifiedDate(): ?string
    {
        return $this->lastModifiedDate;
    }

    /**
     * @return string|null
     */
    public function getLastSyncDate(): ?string
    {
        return $this->lastSyncDate;
    }

    /**
     * @param string|null $lastModifiedDate
     */
    public function setLastModifiedDate(?string $lastModifiedDate): void
    {
        $this->lastModifiedDate = $lastModifiedDate;
    }

    public function isSyncRequired(): bool
    {
        if (!$this->getLastModifiedDate() || $this->getLastModifiedDate() > $this->getLastSyncDate()) {
            return true;
        }

        return false;
    }

    abstract public function toArray();
}