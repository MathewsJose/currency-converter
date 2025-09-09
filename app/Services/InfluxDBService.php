<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class InfluxDBService
{
    private bool $enabled = true;

    public function __construct()
    {
        if (empty(config('services.influxdb.token'))) {
            $this->enabled = false;
            Log::warning('InfluxDB token not configured, disabling metrics');
        }
    }

    public function writeMeasurement(string $measurement, array $tags, array $fields): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            $lineProtocol = $this->buildLineProtocol($measurement, $tags, $fields);                        
            $config = $this->getConfig();
            $url = $config['url'] . '/api/v2/write?bucket=' . $config['bucket'] . '&org=' . $config['org'] . '&precision=ns';

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Token ' . $config['token'],
                    'Content-Type' => 'text/plain; charset=utf-8',
                ])
                ->withBody($lineProtocol, 'text/plain')
                ->post($url);

            if (!$response->successful()) {
                Log::error('InfluxDB write failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'line_protocol' => $lineProtocol
                ]);
            } else {
                Log::debug('InfluxDB write successful', ['measurement' => $measurement]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to write to InfluxDB', ['error' => $e->getMessage()]);
        }
    }

    private function buildLineProtocol(string $measurement, array $tags, array $fields): string
    {        
        $line = $measurement;
        
        if (!empty($tags)) {
            $tagParts = [];
            foreach ($tags as $key => $value) {
                if (is_scalar($value) && $value !== '') {
                    $escapedValue = str_replace([',', '=', ' '], ['\,', '\=', '\ '], (string)$value);
                    $tagParts[] = $key . '=' . $escapedValue;
                }
            }
            if (!empty($tagParts)) {
                $line .= ',' . implode(',', $tagParts);
            }
        }
                
        $line .= ' ';
                
        $fieldParts = [];
        foreach ($fields as $key => $value) {
            if ($value === null) continue;
            
            if (is_float($value)) {
                $fieldParts[] = $key . '=' . $value;
            } elseif (is_int($value)) {
                $fieldParts[] = $key . '=' . $value . 'i';
            } elseif (is_bool($value)) {
                $fieldParts[] = $key . '=' . ($value ? 'true' : 'false');
            } elseif (is_scalar($value)) {
                $fieldParts[] = $key . '="' . str_replace('"', '\"', (string)$value) . '"';
            }
        }
        $line .= implode(',', $fieldParts);                
        $line .= ' ';                
        $line .= (int)(microtime(true) * 1000000000);        
        return $line;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    private function getConfig(): array
    {
        return [
            'url' => config('services.influxdb.url'),
            'token' => config('services.influxdb.token'),
            'bucket' => config('services.influxdb.bucket'),
            'org' => config('services.influxdb.org'),
        ];
    }
}