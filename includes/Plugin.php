<?php
namespace BulkSMS\SMSNET24\Unified;

use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Admin\RoleNoticePage;
use BulkSMS\SMSNET24\Unified\Admin\AttendanceNoticePage;
use BulkSMS\SMSNET24\Unified\Services\Sender;

final class Plugin {
    public function init(): void {
        (new SettingsPage())->register();
        (new RoleNoticePage())->register();
        (new AttendanceNoticePage())->register();
        (new Sender())->register();

        add_action('plugins_loaded', function () {
            (new \BulkSMS\SMSNET24\Unified\Integration\WooCommerceHooks())->register();
            (new \BulkSMS\SMSNET24\Unified\Integration\ReadyDeliveryHooks())->register();
            (new \BulkSMS\SMSNET24\Unified\Integration\DuesReminderScheduler())->register();

            (new \BulkSMS\SMSNET24\Unified\Integration\DokanHooks())->register();
            (new \BulkSMS\SMSNET24\Unified\Integration\LearnDashHooks())->register();
            (new \BulkSMS\SMSNET24\Unified\Integration\LearnPressHooks())->register();
            (new \BulkSMS\SMSNET24\Unified\Integration\TutorLMSHooks())->register();
            (new \BulkSMS\SMSNET24\Unified\Integration\MemberPressHooks())->register();
            (new \BulkSMS\SMSNET24\Unified\Integration\BooklyHooks())->register();

            (new \BulkSMS\SMSNET24\Unified\Integration\StudentResultHooks())->register();
            (new \BulkSMS\SMSNET24\Unified\Integration\AttendanceHooks())->register();
        }, 11);
    }
}
