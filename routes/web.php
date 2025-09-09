<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug-influxdb', function () {
    try {
        $config = [
            'url' => config('services.influxdb.url'),
            'token' => config('services.influxdb.token'),
            'bucket' => config('services.influxdb.bucket'),
            'org' => config('services.influxdb.org'),
        ];

        // Check each config value
        $configCheck = [
            'url' => config('services.influxdb.url'),
            'token' => config('services.influxdb.token') ? 'SET' : 'MISSING',
            'bucket' => config('services.influxdb.bucket'),
            'org' => config('services.influxdb.org'),
        ];

        return response()->json([
            'config' => $configCheck,
            'env' => [
                'INFLUXDB_URL' => env('INFLUXDB_URL'),
                'INFLUXDB_TOKEN' => env('INFLUXDB_TOKEN') ? 'SET' : 'MISSING',
                'INFLUXDB_BUCKET' => env('INFLUXDB_BUCKET'),
                'INFLUXDB_ORG' => env('INFLUXDB_ORG'),
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/test-influx-connection', function () {
    try {
        $service = app(\App\Services\InfluxDBService::class);
        return response()->json([
            'success' => true,
            'enabled' => $service->isEnabled(),
            'message' => 'InfluxDBService resolved successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/debug-influx-data', function () {
    try {
        $token = env('INFLUXDB_TOKEN');
        $url = "http://influxdb:8086";
        $bucket = env('INFLUXDB_BUCKET');
        $org = env('INFLUXDB_ORG');

        // Create proper line protocol format (without quotes!)
        $lineProtocol = 'test_measurement1,service=test value=42.0,success=true ' . (time() * 1000000000);
        
        $response = Http::timeout(5)
            ->withHeaders([
                'Authorization' => 'Token ' . $token,
                'Content-Type' => 'text/plain; charset=utf-8', // Important: text/plain, not application/json
            ])
            ->withBody($lineProtocol, 'text/plain') // Send as plain text, not JSON
            ->post("{$url}/api/v2/write?bucket={$bucket}&org={$org}&precision=ns");

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'message' => 'Direct HTTP write successful',
                'data' => $lineProtocol,
                'response' => $response->body()
            ]);
        }

        return response()->json([
            'success' => false,
            'status' => $response->status(),
            'body' => $response->body(),
            'url' => "{$url}/api/v2/write?bucket={$bucket}&org={$org}",
            'data_sent' => $lineProtocol
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

Route::get('/check-influx-data', function () {
    try {
        $token = env('INFLUXDB_TOKEN');
        $url = env('INFLUXDB_URL');
        
        // Check if any data exists
        $response = Http::withHeaders([
            'Authorization' => 'Token ' . $token,
            'Content-Type' => 'application/vnd.flux',
            'Accept' => 'application/csv',
        ])->post("{$url}/api/v2/query?org=currency-converter", '
            from(bucket: "currency_converter")
              |> range(start: -1h)
              |> filter(fn: (r) => r._measurement == "test_measurement")
              |> count()
        ');

        return response()->json([
            'success' => true,
            'response' => $response->body(),
            'status' => $response->status()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});


Route::get('/test-native-client', function () {
    try {
        $service = app(App\Services\InfluxDBService::class);
        
        if (!$service->isEnabled()) {
            return response()->json(['success' => false, 'message' => 'Service disabled']);
        }

        // Test DNS resolution
        $dnsWorking = $service->testDnsResolution();
        
        // Test native client
        $testPoint = $service->createPoint('native_test')
            ->addTag('test_type', 'direct')
            ->addField('value', 42.0);

        $service->writePoint($testPoint);

        return response()->json([
            'success' => true,
            'message' => 'Native client test completed',
            'dns_resolution' => $dnsWorking,
            'client_status' => $service->getClientStatus()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});