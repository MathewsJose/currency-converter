<?php

namespace App\Console\Commands;

use App\Services\InfluxDBService;
use Illuminate\Console\Command;

class CheckInfluxDBConnection extends Command
{
    protected $signature = 'influxdb:check';
    protected $description = 'Check InfluxDB connection status';

    public function handle(InfluxDBService $influxDBService): int
    {
        $this->info('Checking InfluxDB connection...');

        if ($influxDBService->healthCheck()) {
            $this->info('✅ InfluxDB connection is healthy');
            return 0;
        }

        $this->error('❌ InfluxDB connection failed');
        return 1;
    }
}