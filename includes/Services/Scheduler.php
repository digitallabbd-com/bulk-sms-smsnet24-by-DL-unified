<?php
namespace BulkSMS\SMSNET24\Unified\Services;

final class Scheduler {
    public function register(): void {
        (new Sender())->register();
    }
}
