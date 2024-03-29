<?php

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\LogErrors\LogData;

$mtoUser = new MtoUser();
$logs = LogData::downloadLogLinks();
$marketingCta = stripcslashes($mtoUser->getMarketingCta()) ?: __(
    'I want to receive emails with special offers',
    'mto-woocommerce'
);
?>
    <div class="mto-dashboard">
    <div style="width: 100%; text-align: center; padding-top: 30px; padding-bottom: 30px;">
        <svg width="228" height="51" viewBox="0 0 151 34" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0)">
                <path d="M16.9435 0C7.56598 0 0 7.56598 0 16.9435C0 26.3211 7.56598 33.8871 16.9435 33.8871C26.3211 33.8871 33.8871 26.3211 33.8871 16.9435C33.8871 7.56598 26.3211 0 16.9435 0ZM10.6563 27.5999H7.88567V22.0586H10.6563V27.5999ZM15.5582 27.5999H12.7876V16.6239H15.5582V27.5999ZM20.4601 27.5999H17.6895V11.0826H20.4601V27.5999ZM23.2308 11.0826V8.31193H20.4601V5.54128H26.0014V8.31193V8.0988V8.52505V11.0826H23.2308Z" fill="#00c1db"/>
                <path d="M58.2907 10.7629C56.9054 10.7629 55.6266 11.1891 54.4545 11.9351C53.9216 12.2548 53.4954 12.7876 53.0691 13.2138C52.7494 12.681 52.3232 12.2548 51.8969 11.8285C50.9379 11.0826 49.8722 10.7629 48.5935 10.7629C46.995 10.7629 45.6097 11.1891 44.5441 12.0416V11.1891H41.7734V27.7064H44.5441V16.837C44.5441 16.1976 44.7572 15.6648 45.0769 15.132C45.5031 14.5992 45.9294 14.1729 46.5688 13.7466C47.2082 13.427 47.741 13.2138 48.3804 13.2138C49.3394 13.2138 50.0854 13.5335 50.6182 14.2795C51.2576 15.0254 51.5772 16.091 51.5772 17.4764V27.5999H54.3479V17.2632C54.3479 16.5173 54.561 15.8779 54.8807 15.2385C55.2004 14.5992 55.7332 14.1729 56.3726 13.7466C57.012 13.427 57.6513 13.2138 58.3973 13.2138C59.3564 13.2138 59.9957 13.5335 60.6351 14.1729C61.1679 14.8123 61.4876 15.6648 61.4876 16.837V27.4933H64.2583V16.837C64.2583 14.9188 63.7254 13.427 62.6598 12.2548C61.5942 11.2957 60.1023 10.7629 58.2907 10.7629Z" fill="black"/>
                <path d="M80.9888 13.0007C80.5625 12.5745 80.1363 12.2548 79.6035 11.9351C76.8328 10.2301 73.4228 10.3367 70.7587 11.8285C69.48 12.5745 68.3078 13.6401 67.5618 15.0254C66.8159 16.3042 66.3896 17.7961 66.3896 19.288C66.3896 20.7798 66.8159 22.2717 67.5618 23.5505C68.3078 24.8292 69.3734 25.8949 70.7587 26.7474C72.1441 27.4933 73.5294 27.9196 75.1278 27.9196C76.7263 27.9196 78.2181 27.4933 79.4969 26.7474C80.0297 26.4277 80.5625 26.0014 80.9888 25.5752V27.5999H83.7594V11.0826H80.9888V13.0007ZM80.2429 16.4108C80.7757 17.3698 81.0954 18.3289 81.0954 19.3945C81.0954 20.4602 80.7757 21.5258 80.3494 22.3783C79.8166 23.3374 79.0707 24.0833 78.2182 24.6161C76.4066 25.6818 74.1688 25.6818 72.3572 24.6161C71.3981 24.0833 70.6522 23.3374 70.1194 22.3783C69.5865 21.4192 69.2669 20.4602 69.2669 19.3945C69.2669 18.3289 69.5865 17.3698 70.1194 16.4108C70.6522 15.4517 71.3981 14.7057 72.3572 14.1729C73.3163 13.6401 74.2753 13.3204 75.3409 13.3204C76.4066 13.3204 77.3656 13.6401 78.3247 14.1729C78.9641 14.7057 79.71 15.4517 80.2429 16.4108Z" fill="black"/>
                <path d="M100.596 13.0007C100.17 12.5745 99.7437 12.2548 99.2109 11.9351C96.4403 10.2301 93.0302 10.2301 90.3662 11.8285C89.0874 12.5745 87.9152 13.6401 87.1693 15.0254C86.4233 16.3042 85.9971 17.7961 85.9971 19.288C85.9971 20.7798 86.4233 22.2717 87.1693 23.5505C87.9152 24.8292 88.9808 25.8949 90.3662 26.7474C91.7515 27.4933 93.1368 27.9196 94.7353 27.9196C96.3337 27.9196 97.8256 27.4933 99.1043 26.7474C99.6372 26.4277 100.17 26.0014 100.596 25.5752V27.5999H103.367V11.0826H100.596V13.0007ZM99.8503 16.4108C100.383 17.3698 100.703 18.3289 100.703 19.3945C100.703 20.4602 100.383 21.5258 99.9568 22.3783C99.424 23.3374 98.6781 24.0833 97.8256 24.6161C96.014 25.6817 93.7762 25.6817 91.9646 24.6161C91.0055 24.0833 90.2596 23.3374 89.7268 22.3783C89.194 21.4192 88.8743 20.4602 88.8743 19.3945C88.8743 18.3289 89.194 17.3698 89.7268 16.4108C90.2596 15.4517 91.0055 14.7057 91.9646 14.1729C92.9237 13.6401 93.8827 13.3204 94.9484 13.3204C96.014 13.3204 96.9731 13.6401 97.9321 14.1729C98.5715 14.7057 99.3175 15.4517 99.8503 16.4108Z" fill="black"/>
                <path d="M109.867 24.1899C109.121 23.5505 108.801 22.5914 108.801 21.4192V13.7467H113.383V11.1892H108.801V5.11505H106.03V21.4192C106.03 23.4439 106.67 24.9358 107.948 26.108C109.121 27.2802 110.719 27.813 112.531 27.813H113.383V25.2555H112.531C111.465 25.2555 110.506 24.9358 109.867 24.1899Z" fill="black"/>
                <path d="M127.344 11.722C126.065 10.9761 124.573 10.5498 122.975 10.5498C121.376 10.5498 119.884 10.9761 118.499 11.722C117.22 12.4679 116.154 13.5336 115.409 14.9189C114.663 16.3042 114.236 17.6895 114.236 19.288C114.236 20.8864 114.663 22.3783 115.409 23.6571C116.154 25.0424 117.22 26.108 118.499 26.854C119.884 27.7065 121.376 28.1327 122.975 28.1327C124.68 28.1327 126.171 27.7065 127.45 26.9605C128.729 26.2146 129.795 25.0424 130.54 23.7636C131.286 22.3783 131.606 20.8864 131.606 19.288C131.606 17.6895 131.18 16.3042 130.434 14.9189C129.688 13.6401 128.729 12.5745 127.344 11.722ZM122.975 25.5752C121.802 25.5752 120.843 25.2555 119.884 24.7227C118.925 24.1899 118.179 23.4439 117.646 22.4849C117.114 21.5258 116.794 20.5667 116.794 19.3945C116.794 18.3289 117.114 17.2633 117.646 16.3042C118.179 15.3451 118.925 14.5992 119.884 13.9598C120.843 13.427 121.802 13.1073 122.868 13.1073C123.934 13.1073 124.999 13.427 125.852 13.9598C126.811 14.4926 127.45 15.2386 127.983 16.1977C128.516 17.1567 128.729 18.2224 128.729 19.288C128.729 20.4602 128.516 21.4192 127.983 22.3783C127.45 23.3374 126.704 24.0833 125.852 24.6161C125.106 25.2555 124.04 25.5752 122.975 25.5752Z" fill="black"/>
                <path d="M149.935 14.9189C149.189 13.5336 148.123 12.4679 146.845 11.722C145.566 10.9761 144.074 10.5498 142.475 10.5498C140.877 10.5498 139.385 10.9761 138 11.722C136.721 12.4679 135.655 13.5336 134.909 14.9189C134.164 16.3042 133.737 17.6895 133.737 19.288C133.737 20.8864 134.164 22.3783 134.909 23.6571C135.655 25.0424 136.721 26.108 138 26.854C139.385 27.7065 140.877 28.1327 142.475 28.1327C144.18 28.1327 145.672 27.7065 146.951 26.9605C148.23 26.2146 149.296 25.0424 150.041 23.7636C150.787 22.3783 151.107 20.8864 151.107 19.288C151.001 17.6895 150.681 16.3042 149.935 14.9189ZM142.369 25.5752C141.197 25.5752 140.238 25.2555 139.279 24.7227C138.32 24.1899 137.574 23.4439 137.041 22.4849C136.508 21.5258 136.188 20.5667 136.188 19.3945C136.188 18.3289 136.508 17.2633 137.041 16.3042C137.574 15.3451 138.32 14.5992 139.279 13.9598C140.238 13.427 141.197 13.1073 142.262 13.1073C143.328 13.1073 144.394 13.427 145.246 13.9598C146.205 14.4926 146.845 15.2386 147.377 16.1977C147.91 17.1567 148.123 18.2224 148.123 19.288C148.123 20.4602 147.91 21.4192 147.377 22.3783C146.845 23.3374 146.099 24.0833 145.246 24.6161C144.5 25.2555 143.541 25.5752 142.369 25.5752Z" fill="black"/>
            </g>
            <defs>
                <clipPath id="clip0">
                    <rect width="151" height="33.8871" fill="white"/>
                </clipPath>
            </defs>
        </svg>
    </div>

        <form class="mto-credentials js-mto-credentials">
            <div class="status-bar js-status-bar">
                <?php
                if (MTO_STORE_ID) : ?>
                    <span class="success dashicons-before"><?php
                        _e('Your credentials are valid', 'mto-woocommerce'); ?></span>
                    <div class="sync-info">
                        <div class="sync-info_value dashicons-before">
                            <?php
                            if ($lastFullSync) : ?>
                                <?php
                                if (function_exists("wp_date")) {
                                    $lastFullSyncDate =  wp_date('m/d/Y H:i:s', strtotime($lastFullSync)); 
                                } else {
                                    $lastFullSyncDate =  date('m/d/Y H:i:s', strtotime($lastFullSync)); 
                                }
                                printf(
                                  /* translators: %s is replaced with "timestamp of last full sync" */
                                  __('The latest full sync was done %s', 'mto-woocommerce'),
                                  $lastFullSyncDate
                                ); ?>
                            <?php
                            else:
                                _e(
                                  'The very first synchronization have been scheduled and will be done automatically by WordPress Event manager'
                                );
                            endif; ?>
                        </div>
                    </div>
                <?php
                else : ?>
                    <span class="success hidden dashicons-before"></span>
                <?php
                endif; ?>
                <span class="error hidden dashicons-before"></span>
            </div>
            <label for="url"><?php
                _e('API URL', 'mto-woocommerce'); ?>
                <input type="url" id="url" name="url" value="<?php
                echo $mtoUser->getUrl() ?? ''; ?>" required/>
            </label>
            <label for="login"><?php
                _e('API Token', 'mto-woocommerce'); ?>
                <input type="text" id="login" name="login" value="<?php
                echo $mtoUser->getUsername() ?? ''; ?>" required/>
            </label>

            <label for="password"><?php
                _e('API Key', 'mto-woocommerce'); ?>
                <input type="password" name="password" id="password" value="<?php
                echo $mtoUser->getPassword() ?? ''; ?>" required/>
            </label>
            <label for="marketing">
                <input type="checkbox" name="add_marketing_field" id="marketing" <?php
                echo $mtoUser->isMarketingEnabled() ? 'checked' : ''; ?>/> <?php
                _e('Add marketing opt-in to checkout page', 'mto-woocommerce'); ?>
            </label>
            <span><a href="#" id="toggle_marketing_optin_settings">> <?php _e('Marketing Opt-in settings', 'mto-woocommerce'); ?></a></span>
            <section class="container" id="marketing_optin_options" style="display: none;">
            <label for="marketing_checked">
                <input type="checkbox" name="marketing_field_checked_default" id="marketing_checked" <?php
                echo $mtoUser->isMarketingCheckedEnabled() ? 'checked' : ''; ?>/> <?php
                _e('Check marketing opt-in checkbox by default', 'mto-woocommerce'); ?>
            </label>
            <label for="marketing_cta"><?php
                _e('Message for the opt-in checkbox', 'mto-woocommerce'); ?>
                <textarea type="url" id="marketing_cta" name="marketing_cta" /><?php echo($marketingCta); ?></textarea>
                <span class="footnote"><?php _e(
                    'Allowed HTML Tags:',
                    'mto-woocommerce'
                ); ?> &lt;a href="" target=""&gt;&lt;a&gt;, &lt;b /&gt;, &lt;br /&gt;</span>
            </label>
            <label for="marketing_position"><?php
                _e('Marketing Opt-in checkbox position', 'mto-woocommerce'); ?>
                <input type="text" id="marketing_position" name="marketing_position" value="<?php
                echo $mtoUser->getMarketingPosition(); ?>" />
                <span class="footnote"><?php _e(
                    'Valid WooCommerce form action required',
                    'mto-woocommerce'
                ); ?></span>
            </label>
            </section>
            <label for="birthday">
                <input type="checkbox" name="add_birthday_field" id="birthday" <?php
                echo $mtoUser->isBirthdayEnabled() ? 'checked' : ''; ?>/> <?php
                _e('Add Birthday Field to checkout page', 'mto-woocommerce'); ?>
            </label>
            <section class="container" id="advanced_options">
                <label for="product_image_sync_quality">
                    <?php _e('Product Image Size', 'mto-woocommerce'); ?>
                    <select id="product_image_sync_quality" name="product_image_sync_quality">
                        <?php
                        $productImageSyncQuality = $mtoUser->getProductImageSyncQuality() == "" ? "thumbnail" : $mtoUser->getProductImageSyncQuality();
                        foreach ($imageSizesList as $key => $value ) {
                            echo '<option value="' . esc_attr( $key ) . '" ' . selected($key == $productImageSyncQuality, true, false ) . '>' . esc_html( $value ) . '</option>';
                        }
                        ?>
                    </select>
                </label>
            </section>
            <input type="submit" name="save" value="<?php
            _e('Update Configuration', 'mto-woocommerce'); ?>">
        </form>
        <?php
        if (MTO_STORE_ID): ?>
            <div class="sync-info-next dashicons-before">
                <?php
                /* translators: %s is replaced with "timestamp of next full sync" */
                printf(__('The next synchronization is scheduled for <br/>%s', 'mto-woocommerce'), $nextEvent); ?>
            </div>
        <?php
        endif;
        if (!empty($logs)) :
            echo $logs;
        endif; ?>
    </div>
<?php
