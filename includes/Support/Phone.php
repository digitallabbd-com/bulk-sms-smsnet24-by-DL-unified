<?php
namespace BulkSMS\SMSNET24\Unified\Support;

final class Phone {
    public static function normalizeBD(?string $raw): ?string {
        if (!$raw) return null;
        $d = preg_replace('/\D+/', '', $raw);
        if (preg_match('/^8801[3-9]\d{8}$/', $d)) return $d;
        if (preg_match('/^01[3-9]\d{8}$/', $d)) return '88' . $d;
        return null;
    }
}
