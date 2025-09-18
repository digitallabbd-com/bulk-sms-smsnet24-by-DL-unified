<?php
namespace BulkSMS\SMSNET24\Unified;

final class Autoloader {
    public static function register(string $prefix, string $baseDir): void {
        spl_autoload_register(function ($class) use ($prefix, $baseDir) {
            if (strpos($class, $prefix . '\\') !== 0) return;
            $rel = substr($class, strlen($prefix) + 1);
            $path = $baseDir . '/' . str_replace('\\', '/', $rel) . '.php';
            if (is_readable($path)) require $path;
        });
    }
}
