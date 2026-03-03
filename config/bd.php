<?php

return [
    'api_base_url' => rtrim(env('BD_BASE_URL', ''), '/'),
    'api_key' => env('BD_API_KEY', ''),
    'auth_header' => env('BD_AUTH_HEADER', 'X-API-Key'),
];
