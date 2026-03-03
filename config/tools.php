<?php

/**
 * Tool registry: slug, name, type (service|direct), version, product_type, and widget definitions.
 * - type: service = license + encrypted; direct = no license, plain assets.
 * - version: developer-set (e.g. 1, 1.1, 2). Minor = 1.1; major = 2.
 * - product_type (BDGS): service | quick_service | flagship_service | tool.
 *   Installer usage: Free → quick_service; Fixed Price → any; Subscription → service/flagship_service (sometimes tool).
 */
return [
    'registry' => [
        'faq' => [
            'slug' => 'faq',
            'name' => 'FAQ Tool',
            'type' => 'service',
            'version' => '1.0',
            'product_type' => 'service',
            'delivery_mode' => 'server_fetch',
            'server_fetch' => [
                'fetch_keys' => [], // asset keys fetched from server (HTML/JS); empty = use server file list from DB
                'css_plain' => true, // true = leave CSS as plain; false = wrap in PHP base64 decode
            ],
            'widgets' => [
                [
                    'widget_name' => 'FAQ Management Plugin',
                    'widget_data_key' => 'admin.php',
                    'widget_style_key' => 'admin.css',
                    'widget_javascript_key' => null,
                    'widget_viewport' => 'admin',
                    // Only lines 1..3793 are PHP (eval'd); remainder (HTML/CSS/JS) output as-is for testing
                    'php_only_until_line' => 3793,
                ],
                [
                    'widget_name' => 'FAQ Global Renderer',
                    'widget_data_key' => 'global-renderer.php',
                    'widget_style_key' => 'global-renderer.css',
                    'widget_javascript_key' => null,
                    'widget_viewport' => 'front',
                ],
            ],
        ],
        'weather' => [
            'slug' => 'weather',
            'name' => 'Weather Widget',
            'type' => 'service',
            'version' => '1.0',
            'product_type' => 'quick_service',
            'widgets' => [
                [
                    'widget_name' => 'Weather Widget',
                    'widget_data_key' => 'weather-widget.php',
                    'widget_style_key' => 'weather-widget.css',
                    'widget_javascript_key' => 'weather-widget.js',
                    'widget_viewport' => 'front',
                ],
            ],
        ],
    ],
];
