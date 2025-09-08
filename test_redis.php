<?php
require vendor/autoload.php;

// Bootstrap Laravel
$app = require_once __DIR__./bootstrap/app.php;
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $result = Illuminate\Support\Facades\Redis::ping();
    echo Redis
