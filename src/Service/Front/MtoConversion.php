<?php

namespace Maatoo\WooCommerce\Service\Front;

use Maatoo\WooCommerce\Entity\MtoUser;
use Maatoo\WooCommerce\Service\LogErrors\LogData;
use Maatoo\WooCommerce\Service\Maatoo\MtoConnector;

class MtoConversion
{
    public function __invoke()
    {
        add_action('wp_body_open', [$this, 'insertTracker']);
        $this->retrieveUserData();
    }

    public function insertTracker()
    {
        $mtoUser = new MtoUser();
        if (!$mtoUser->getUrl()) {
            return;
        }
        $mtcScript = $mtoUser->getUrl() . '/mtc.js';
        ob_start(); ?>
        <script type="text/javascript">
          (function (w, d, t, u, n, a, m) {
            w['MauticTrackingObject'] = n
            w[n] = w[n] || function () {(w[n].q = w[n].q || []).push(arguments)}, a = d.createElement(t),
              m = d.getElementsByTagName(t)[0]
            a.async = 1
            a.src = u
            m.parentNode.insertBefore(a, m)
          })(window, document, 'script', '<?php echo $mtcScript; ?>', 'mt')
          mt('send', 'pageview')
        </script>
        <?php
        echo ob_get_clean();
    }

    public function retrieveUserData()
    {
        try {
            if (isset($_GET['ct'])) {;
                wc_setcookie('mto_conversion', $_GET['ct']);

                $mtoConnector = MtoConnector::getInstance(new MtoUser());
                if(!$mtoConnector){
                    return;
                }
                $data = unserialize(base64_decode($_COOKIE['mto_conversion']));
                $mtoConnector->saveConversion($data['lead'], $data['email']);
            }
        }
        catch (\Exception $exception){
            LogData::writeTechErrors($exception->getMessage());
        }
    }
}