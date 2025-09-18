<?php
namespace BulkSMS\SMSNET24\Unified\Support;

final class Logger {
    public static function error(string $tag, array $data = []): void {
        $msg = '[smsnet24-unified][' . $tag . '] ' . wp_json_encode($data);
        if (function_exists('wc_get_logger')) {
            wc_get_logger()->error($msg, ['source' => 'smsnet24-unified']);
        } else {
            error_log($msg);
        }
    }
}
