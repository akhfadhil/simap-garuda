<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rekap Cache TTL
    |--------------------------------------------------------------------------
    |
    | Cache ini dipakai untuk agregat, chart, dan ringkasan beranda rekap.
    | Hari H butuh data cepat berubah, jadi default dibuat pendek. Set 0 untuk
    | mematikan cache rekap kalau butuh benar-benar realtime.
    |
    */
    'cache_ttl_seconds' => (int) env('REKAP_CACHE_TTL_SECONDS', 30),
];
