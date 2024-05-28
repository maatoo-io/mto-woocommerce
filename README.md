# mto-woocommerce
maatoo Plugin for WooCommerce

# Build
[![Test and Build mto-woocommerce](https://github.com/maatoo-io/mto-woocommerce/actions/workflows/main.yaml/badge.svg)](https://github.com/maatoo-io/mto-woocommerce/actions/workflows/main.yaml)



# Development
Package uses a prefixer for vendor packages in order to not conflict with other existing dependencies used by other plugins
In order to update any of the packages please check `composer_vendor.json`

Install globally `ilab/namespacer`

`composer global require ilab/namespacer`

Run
`~/.composer/vendor/bin/namespacer --composer composer_vendor.json  --package mtodep --namespace Maatoo\Dependencies build/`

For vendor updates
