<?php

return [
    'name' => 'CPanel',
    'hostname' => env('WHM_HOSTNAME', 'https://garuda6.fastcloud.id:2087'),
    'username' => env('CPANEL_USERNAME', 'root'),
    'token' => env('CPANEL_TOKEN', 'OM6F7X7Q0M5V3KWLJVOMLS4R5J98FTNH'),
    'output' => 'json',
    'verify_ssl' => false,
    'debug' => env('CPANEL_DEBUG', false),
    'timeout' => 30
];