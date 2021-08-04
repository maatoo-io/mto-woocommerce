<?php

namespace Maatoo\WooCommerce\Service\Front;

use Maatoo\WooCommerce\Entity\MtoUser;

class WooHooks
{
    public function __construct()
    {
        $mtoUser = new MtoUser();
        if($mtoUser && $mtoUser->isMarketingEnabled()){
            add_action('woocommerce_order_button_html', [$this, 'addSubscriptionCheckbox']);
        }
    }

    public function addSubscriptionCheckbox($html)
    {
        return '<div class="mto-option-wrap"><label for="mto-email-subscription" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                    <input type="checkbox" id="mto-email-subscription" name="mto_email_subscription" checked class="woocommerce-form__input-checkbox input-checkbox" value="1" />
                    <input type="hidden" value="' . ($_COOKIE['mtc_id'] ?? false) . '"/>' . __(
                'I want to receive emails with special offers',
                'mto-woocommerce'
            ) . '</label></div>' . $html;
    }
}