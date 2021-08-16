<?php

namespace Maatoo\WooCommerce\Registry;

abstract class AbstractAssets
{
    protected string $handle = 'mto';
    protected ?string $handleLibs = 'mto-libs';

    public function __invoke()
    {
        $this->styles();
        $this->scripts();
    }

    abstract protected function styles();
    abstract protected function scripts();
}