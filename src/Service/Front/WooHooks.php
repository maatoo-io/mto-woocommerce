<?php

namespace Maatoo\WooCommerce\Service\Front;

use Maatoo\WooCommerce\Entity\MtoUser;

class WooHooks
{
    public function __construct()
    {
        $mtoUser = new MtoUser();
        if($mtoUser && $mtoUser->isMarketingEnabled()){
            $marketingPosition = $mtoUser->getMarketingPosition();
            add_action($marketingPosition, [$this, 'addSubscriptionCheckbox']);
        }
    }

    public function addSubscriptionCheckbox($html)
    {
        $mtoUser = new MtoUser();
        $checked = (int)$mtoUser->isMarketingCheckedEnabled() ? "checked " : "";
        $marketingCta = stripcslashes($mtoUser->getMarketingCta()) ?: __('I want to receive emails with special offers', 'mto-woocommerce' );
        $checkbox = '<div class="mto-option-wrap"><label for="mto-email-subscription" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                    <input type="checkbox" id="mto-email-subscription" name="mto_email_subscription" class="woocommerce-form__input-checkbox input-checkbox" value="1" '.$checked.'/>
                    <input type="hidden" value="' . ($_COOKIE['mtc_id'] ?? false) . '"/>' . $marketingCta . '</label></div>';
        echo apply_filters( 'maatoo_woocommerce_newsletter_field', $checkbox, $mtoUser->isMarketingCheckedEnabled());
    }
}