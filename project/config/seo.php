<?php

use RalphJSmit\Laravel\SEO\Models\SEO;

return [

    'model' => SEO::class,

    'site_name' => 'FM Macedonia',

    'sitemap' => null,

    'canonical_link' => true,

    'robots' => [
        'default'       => 'max-snippet:-1,max-image-preview:large,max-video-preview:-1',
        'force_default' => false,
    ],

    'favicon' => 'favicon.png',

    'title' => [
        'infer_title_from_url' => false,
        'suffix'               => ' | FM Macedonia',
        'homepage_title'       => 'FM Macedonia — Stream Macedonian Radio Live',
    ],

    'description' => [
        'fallback' => 'Stream 33 Macedonian FM radio stations live in your browser. Pop, folk, rock, news and more — all in one place.',
    ],

    'image' => [
        'fallback' => 'images/og-image.png',
    ],

    'author' => [
        'fallback' => null,
    ],

    'twitter' => [
        '@username' => null,
    ],
];
