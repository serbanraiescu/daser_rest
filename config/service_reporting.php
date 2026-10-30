<?php

return [
    // Separate read-only credential for each installation; keep empty to disable the API.
    'api_token' => env('SERVICE_REPORT_API_TOKEN', ''),
    'timezone' => env('SERVICE_REPORT_TIMEZONE', 'Europe/Bucharest'),
];
