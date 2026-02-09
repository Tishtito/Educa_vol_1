<?php

declare(strict_types=1);

namespace App\Support;

class FileCache
{
    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $baseDir = $cacheDir ?? dirname(__DIR__, 2) . '/cache';
        $this->cacheDir = rtrim($baseDir, '/');

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    public function remember(string $key, int $ttlSeconds, callable $callback)
    {
        if ($ttlSeconds <= 0) {
            return $callback();
        }

        $path = $this->pathForKey($key);
        $now = time();

        if (is_file($path)) {
            $payload = @file_get_contents($path);
            if ($payload !== false) {
                $decoded = @unserialize($payload);
                if (is_array($decoded) && isset($decoded['time']) && array_key_exists('data', $decoded)) {
                    if (($decoded['time'] + $ttlSeconds) >= $now) {
                        return $decoded['data'];
                    }
                }
            }
        }

        $data = $callback();
        $payload = serialize([
            'time' => $now,
            'data' => $data,
        ]);
        @file_put_contents($path, $payload, LOCK_EX);

        return $data;
    }

    private function pathForKey(string $key): string
    {
        return $this->cacheDir . '/' . sha1($key) . '.cache';
    }
}
