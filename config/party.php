<?php

return [
    'slug' => 'garuda',
    'name' => 'Partai Garuda',
    'short_name' => 'Garuda',
    'full_name' => 'Partai Garda Republik Indonesia',
    'app_name' => env('PARTY_APP_NAME', 'BASIS DATA PEROLEHAN SUARA PARTAI GARUDA'),
    'tagline' => 'Sistem Rekap dan Saksi Partai Garuda',
    'active_year' => 2026,
    'copyright_year' => 2026,

    'historical_numbers' => [
        2024 => 11,
    ],

    'election_types' => [
        'dpr_ri',
        'dprd_prov',
        'dprd_kab',
    ],

    'assets' => [
        'logo' => 'images/logo-garuda.png',
    ],

    'colors' => [
        'primary' => '#E63946',
        'primary_dark' => '#BB152C',
        'primary_soft' => 'rgba(230, 57, 70, .1)',
        'bg_dark' => env('PARTY_COLOR_BG_DARK', '#140404'),
        'korcam' => '#F4A261',
        'kordes' => '#2EC4B6',
        'saksi_tps' => '#7DD3FC',
    ],

    'roles' => [
        'admin_partai' => 'Admin Partai',
        'korcam' => 'Korcam',
        'kordes' => 'Kordes',
        'saksi_tps' => 'Saksi TPS',
    ],

    'main_simap_url' => env('MAIN_SIMAP_URL', 'http://simap.test'),
];
