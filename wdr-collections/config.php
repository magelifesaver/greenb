<?php
return [
    'debug' => false,

    'plugin' => [
        'name' => "Woo Discount Rules: Collections",
        'slug' => "wdr-collections",
        'version' => "1.2.2",
        'prefix' => "wdr_col_",
    ],

    'require' => [
        'php' => ">=5.6",
        'wordpress' => ">=5.2",
        'plugins' => [
            [
                'name' => "WooCommerce",
                'version' => ">=4.2",
                'file' => "woocommerce/woocommerce.php",
                'url' => "https://woocommerce.com",
            ],
            [
                'name' => "Woo Discount Rules",
                'version' => ">=2.4.4",
                'file' => "woo-discount-rules/woo-discount-rules.php",
                'url' => "https://flycart.org",
            ],
            [
                'name' => "Woo Discount Rules PRO",
                'version' => ">=2.4.4",
                'file' => "woo-discount-rules-pro/woo-discount-rules-pro.php",
                'url' => "https://flycart.org",
            ],
        ],
    ],
];