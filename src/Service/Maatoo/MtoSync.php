<?php

namespace Maatoo\WooCommerce\Service\Maatoo;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Store\MtoStoreManger;
use Maatoo\WooCommerce\Service\WooCommerce\OrderHooks;
use Maatoo\WooCommerce\Service\WooCommerce\ProductHooks;

/**
 * Class MtoSync
 *
 * @package Maatoo\WooCommerce\Service\Maatoo
 */
class MtoSync
{
    public const SUPPORTED_LOCALES = [ "af", "af_NA", "af_ZA", "ak", "ak_GH", "am", "am_ET", "ar", "ar_AE", "ar_BH", "ar_DJ", "ar_DZ", "ar_EG", "ar_EH", "ar_ER", "ar_IL", "ar_IQ", "ar_JO", "ar_KM", "ar_KW", "ar_LB", "ar_LY", "ar_MA", "ar_MR", "ar_OM", "ar_PS", "ar_QA", "ar_SA", "ar_SD", "ar_SO", "ar_SS", "ar_SY", "ar_TD", "ar_TN", "ar_YE", "as", "as_IN", "az", "az_AZ", "az_Cyrl", "az_Cyrl_AZ", "az_Latn", "az_Latn_AZ", "be", "be_BY", "bg", "bg_BG", "bm", "bm_ML", "bn", "bn_BD", "bn_IN", "bo", "bo_CN", "bo_IN", "br", "br_FR", "bs", "bs_BA", "bs_Cyrl", "bs_Cyrl_BA", "bs_Latn", "bs_Latn_BA", "ca", "ca_AD", "ca_ES", "ca_FR", "ca_IT", "ce", "ce_RU", "cs", "cs_CZ", "cy", "cy_GB", "da", "da_DK", "da_GL", "de", "de_AT", "de_BE", "de_CH", "de_DE", "de_IT", "de_LI", "de_LU", "dz", "dz_BT", "ee", "ee_GH", "ee_TG", "el", "el_CY", "el_GR", "en", "en_AE", "en_AG", "en_AI", "en_AS", "en_AT", "en_AU", "en_BB", "en_BE", "en_BI", "en_BM", "en_BS", "en_BW", "en_BZ", "en_CA", "en_CC", "en_CH", "en_CK", "en_CM", "en_CX", "en_CY", "en_DE", "en_DG", "en_DK", "en_DM", "en_ER", "en_FI", "en_FJ", "en_FK", "en_FM", "en_GB", "en_GD", "en_GG", "en_GH", "en_GI", "en_GM", "en_GU", "en_GY", "en_HK", "en_IE", "en_IL", "en_IM", "en_IN", "en_IO", "en_JE", "en_JM", "en_KE", "en_KI", "en_KN", "en_KY", "en_LC", "en_LR", "en_LS", "en_MG", "en_MH", "en_MO", "en_MP", "en_MS", "en_MT", "en_MU", "en_MW", "en_MY", "en_NA", "en_NF", "en_NG", "en_NL", "en_NR", "en_NU", "en_NZ", "en_PG", "en_PH", "en_PK", "en_PN", "en_PR", "en_PW", "en_RW", "en_SB", "en_SC", "en_SD", "en_SE", "en_SG", "en_SH", "en_SI", "en_SL", "en_SS", "en_SX", "en_SZ", "en_TC", "en_TK", "en_TO", "en_TT", "en_TV", "en_TZ", "en_UG", "en_UM", "en_US", "en_VC", "en_VG", "en_VI", "en_VU", "en_WS", "en_ZA", "en_ZM", "en_ZW", "eo", "es", "es_AR", "es_BO", "es_BR", "es_BZ", "es_CL", "es_CO", "es_CR", "es_CU", "es_DO", "es_EA", "es_EC", "es_ES", "es_GQ", "es_GT", "es_HN", "es_IC", "es_MX", "es_NI", "es_PA", "es_PE", "es_PH", "es_PR", "es_PY", "es_SV", "es_US", "es_UY", "es_VE", "et", "et_EE", "eu", "eu_ES", "fa", "fa_AF", "fa_IR", "ff", "ff_CM", "ff_GN", "ff_Latn", "ff_Latn_BF", "ff_Latn_CM", "ff_Latn_GH", "ff_Latn_GM", "ff_Latn_GN", "ff_Latn_GW", "ff_Latn_LR", "ff_Latn_MR", "ff_Latn_NE", "ff_Latn_NG", "ff_Latn_SL", "ff_Latn_SN", "ff_MR", "ff_SN", "fi", "fi_FI", "fo", "fo_DK", "fo_FO", "fr", "fr_BE", "fr_BF", "fr_BI", "fr_BJ", "fr_BL", "fr_CA", "fr_CD", "fr_CF", "fr_CG", "fr_CH", "fr_CI", "fr_CM", "fr_DJ", "fr_DZ", "fr_FR", "fr_GA", "fr_GF", "fr_GN", "fr_GP", "fr_GQ", "fr_HT", "fr_KM", "fr_LU", "fr_MA", "fr_MC", "fr_MF", "fr_MG", "fr_ML", "fr_MQ", "fr_MR", "fr_MU", "fr_NC", "fr_NE", "fr_PF", "fr_PM", "fr_RE", "fr_RW", "fr_SC", "fr_SN", "fr_SY", "fr_TD", "fr_TG", "fr_TN", "fr_VU", "fr_WF", "fr_YT", "fy", "fy_NL", "ga", "ga_IE", "gd", "gd_GB", "gl", "gl_ES", "gu", "gu_IN", "gv", "gv_IM", "ha", "ha_GH", "ha_NE", "ha_NG", "he", "he_IL", "hi", "hi_IN", "hr", "hr_BA", "hr_HR", "hu", "hu_HU", "hy", "hy_AM", "ia", "id", "id_ID", "ig", "ig_NG", "ii", "ii_CN", "is", "is_IS", "it", "it_CH", "it_IT", "it_SM", "it_VA", "ja", "ja_JP", "jv", "jv_ID", "ka", "ka_GE", "ki", "ki_KE", "kk", "kk_KZ", "kl", "kl_GL", "km", "km_KH", "kn", "kn_IN", "ko", "ko_KP", "ko_KR", "ks", "ks_IN", "ku", "ku_TR", "kw", "kw_GB", "ky", "ky_KG", "lb", "lb_LU", "lg", "lg_UG", "ln", "ln_AO", "ln_CD", "ln_CF", "ln_CG", "lo", "lo_LA", "lt", "lt_LT", "lu", "lu_CD", "lv", "lv_LV", "mg", "mg_MG", "mi", "mi_NZ", "mk", "mk_MK", "ml", "ml_IN", "mn", "mn_MN", "mr", "mr_IN", "ms", "ms_BN", "ms_MY", "ms_SG", "mt", "mt_MT", "my", "my_MM", "nb", "nb_NO", "nb_SJ", "nd", "nd_ZW", "ne", "ne_IN", "ne_NP", "nl", "nl_AW", "nl_BE", "nl_BQ", "nl_CW", "nl_NL", "nl_SR", "nl_SX", "nn", "nn_NO", "no", "no_NO", "om", "om_ET", "om_KE", "or", "or_IN", "os", "os_GE", "os_RU", "pa", "pa_Arab", "pa_Arab_PK", "pa_Guru", "pa_Guru_IN", "pa_IN", "pa_PK", "pl", "pl_PL", "ps", "ps_AF", "ps_PK", "pt", "pt_AO", "pt_BR", "pt_CH", "pt_CV", "pt_GQ", "pt_GW", "pt_LU", "pt_MO", "pt_MZ", "pt_PT", "pt_ST", "pt_TL", "qu", "qu_BO", "qu_EC", "qu_PE", "rm", "rm_CH", "rn", "rn_BI", "ro", "ro_MD", "ro_RO", "ru", "ru_BY", "ru_KG", "ru_KZ", "ru_MD", "ru_RU", "ru_UA", "rw", "rw_RW", "sd", "sd_PK", "se", "se_FI", "se_NO", "se_SE", "sg", "sg_CF", "sh", "sh_BA", "si", "si_LK", "sk", "sk_SK", "sl", "sl_SI", "sn", "sn_ZW", "so", "so_DJ", "so_ET", "so_KE", "so_SO", "sq", "sq_AL", "sq_MK", "sq_XK", "sr", "sr_BA", "sr_Cyrl", "sr_Cyrl_BA", "sr_Cyrl_ME", "sr_Cyrl_RS", "sr_Cyrl_XK", "sr_Latn", "sr_Latn_BA", "sr_Latn_ME", "sr_Latn_RS", "sr_Latn_XK", "sr_ME", "sr_RS", "sr_XK", "sv", "sv_AX", "sv_FI", "sv_SE", "sw", "sw_CD", "sw_KE", "sw_TZ", "sw_UG", "ta", "ta_IN", "ta_LK", "ta_MY", "ta_SG", "te", "te_IN", "tg", "tg_TJ", "th", "th_TH", "ti", "ti_ER", "ti_ET", "tk", "tk_TM", "to", "to_TO", "tr", "tr_CY", "tr_TR", "tt", "tt_RU", "ug", "ug_CN", "uk", "uk_UA", "ur", "ur_IN", "ur_PK", "uz", "uz_AF", "uz_Arab", "uz_Arab_AF", "uz_Cyrl", "uz_Cyrl_UZ", "uz_Latn", "uz_Latn_UZ", "uz_UZ", "vi", "vi_VN", "wo", "wo_SN", "xh", "xh_ZA", "yi", "yo", "yo_BJ", "yo_NG", "zh", "zh_CN", "zh_HK", "zh_Hans", "zh_Hans_CN", "zh_Hans_HK", "zh_Hans_MO", "zh_Hans_SG", "zh_Hant", "zh_Hant_HK", "zh_Hant_MO", "zh_Hant_TW", "zh_MO", "zh_SG", "zh_TW", "zu", "zu_ZA" ];

    public static function normalizeLocale($locale) {
        $locale = str_replace("_formal", "", $locale);

        if (!in_array($locale, self::SUPPORTED_LOCALES)) {
            $locale = locale_get_default();
        }

        return $locale;
    }

    protected static function checkConnections()
    {
        try {
            $store = MtoStoreManger::getStoreData();

            if (!$store || !$store->getId()) {
                return false;
            }

            $connector = MtoConnector::getInstance(new MtoUser());

            if (!$connector->healthCheck()) {
                return false;
            }
        } catch (\Exception $ex) {
            LogData::writeTechErrors($ex->getMessage());
        }
        return true;
    }

    public static function runProductSync($start = 0, $limit = 20)
    {
        try {
            $state = self::checkConnections();
            if(!$state || !function_exists('as_schedule_single_action')){
                update_option('_mto_sync_status_product', 'failed');
            }

            $products = MtoStoreManger::getAllProducts(false, $start, $limit);

            $statusProduct = ProductHooks::isProductsSynced($products->have_posts() ? $products->posts : []);
            if(!$statusProduct){
                $key = 'mto_product_sync_attempt_' . $start . '_' . $limit;
                $prev = (int)get_option($key, 0);
                $msg = 'Product Sync failed[offset: ' . $start . '; limit: ' . $limit . ';]';
                if ($prev < MTO_MAX_ATTEMPTS) {
                    update_option($key, ++$prev);
                    throw new \Exception($msg);
                } else {
                    LogData::writeDebug($msg . '. 3 attempts were used to sync data' . PHP_EOL);
                    delete_option($key);
                }
            }
            $start = $start + $limit;
            if($products->found_posts >= $start && ! as_next_scheduled_action( 'mto_sync_products', [$start, $limit])){
                as_schedule_single_action(time() -1, 'mto_sync_products', [$start, $limit]);
            }
            update_option('_mto_last_sync_products', $statusProduct);
        } catch (\Exception $ex) {
            LogData::writeDebug($ex->getMessage());
            return as_schedule_single_action(time() - 1, 'mto_sync_products', [$start, $limit]);
        }
        self::updateLastSyncDate();
    }


    public static function runOrderSync($start = 0, $limit = 20)
    {
        try {
            $state = self::checkConnections();
            if(!$state || !function_exists('as_schedule_single_action')){
                update_option('_mto_sync_status_order', 'failed');
            }
            $orders = MtoStoreManger::getAllOrders(false, $start, $limit);
            $statusOrder = OrderHooks::isOrderSynced($orders->have_posts() ? $orders->posts : []);
            if(!$statusOrder || !(int)get_option('mto_order_sync_status', 1)){
                $key = 'mto_order_sync_attempt_' . $start . '_' . $limit;
                $prev = (int)get_option($key, 0);
                $msg = 'Order Sync is failed[offset: ' . $start . '; limit: ' . $limit . ';]';
                if ($prev < MTO_MAX_ATTEMPTS) {
                    update_option($key, ++$prev);
                    throw new \Exception($msg);
                } else {
                    LogData::writeDebug($msg . '. 3 attempts were used to sync data' . PHP_EOL);
                    delete_option($key);
                }
            } else {
                LogData::writeDebug('OrderHooks::isOrderSynced completed[offset: ' . $start . '; limit: ' . $limit . ';]' . PHP_EOL);
            }
            $start = $start + $limit;
            if ($orders->found_posts >= $start && ! as_next_scheduled_action( 'mto_sync_orders', [$start, $limit])) {
                as_schedule_single_action(time() - 1, 'mto_sync_orders', [$start, $limit]);
            }
            update_option('_mto_sync_status_order', $statusOrder);
        } catch (\Exception $ex) {
            LogData::writeDebug($ex->getMessage());
            return as_schedule_single_action(time() - 1, 'mto_sync_orders', [$start, $limit]);
        }
        self::updateLastSyncDate();
    }

    protected static function updateLastSyncDate()
    {
        update_option('_mto_last_sync', date(DATE_W3C));
    }
}
