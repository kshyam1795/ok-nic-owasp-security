<?php

return [
    'rate_limit' => env('OWASP_RATE_LIMIT', 60),
    'allowed_origins' => env('OWASP_ALLOWED_ORIGINS', '*'),
];
