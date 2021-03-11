<?php

namespace Maatoo\WooCommerce\Service\Front;

class MtoConversion
{
    public function __invoke()
    {
        add_action('wp_body_open', [$this, 'insertTracker']);
    }

    public function insertTracker()
    {
        ob_start(); ?>
        <script type="text/javascript">
          (function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
            w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
              m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)
          })(window,document,'script','https://m.nelocom.com/mtc.js','mt');

          mt('send', 'pageview');
        </script>
        <?php
        echo ob_get_clean();
    }

}