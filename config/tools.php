<?php

/**
 * Tool registry: slug, name, type (service|direct), version, product_type, and widget definitions.
 * - type: service = license + encrypted; direct = no license, plain assets.
 * - version: developer-set (e.g. 1, 1.1, 2). Minor = 1.1; major = 2.
 * - product_type (BDGS): service | quick_service | flagship_service | tool.
 *   Installer usage: Free → quick_service; Fixed Price → any; Subscription → service/flagship_service (sometimes tool).
 * - help_text: Optional help text shown after install button in installer form.
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
            'help_text' => '<p class="font-medium mb-1">FAQ: Updating code &amp; admin panel</p>
                            <ul class="list-disc list-inside space-y-0.5 text-slate-600">
                                <li><strong>New code not showing?</strong> After editing <code class="bg-slate-200 px-1 rounded">plugin-assets/</code>, run <code class="bg-slate-200 px-1 rounded">php artisan tools:encrypt faq</code> then run Install (or Update existing) here so the new payload is sent to BD.</li>
                                <li><strong>Works on front but not in admin?</strong> Set the license token so it is available in admin too: e.g. <code class="bg-slate-200 px-1 rounded">define(\'FAQ_LICENSE_TOKEN\', \'your-token\');</code> or <code class="bg-slate-200 px-1 rounded">$GLOBALS[\'faq_license_token\'] = \'your-token\';</code> in a file that loads for both front and admin (e.g. theme functions or global include).</li>
                            </ul>',
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
            'help_text' => null,
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
        'related-posts' => [
            'slug' => 'related-posts',
            'name' => 'Related Posts',
            'type' => 'service',
            'version' => '1.0',
            'product_type' => 'service',
            'help_text' => null,
            'widgets' => [
                [
                    'widget_name' => 'related-posts-input',
                    'widget_data_key' => 'form-input.php',
                    'widget_style_key' => 'form-input.css',
                    'widget_javascript_key' => null,
                    'widget_viewport' => 'admin',
                ],
                [
                    'widget_name' => 'related-posts-display',
                    'widget_data_key' => 'frontend-display.php',
                    'widget_style_key' => 'frontend-display.css',
                    'widget_javascript_key' => null,
                    'widget_viewport' => 'front',
                ],
            ],
        ],
    ],
];
