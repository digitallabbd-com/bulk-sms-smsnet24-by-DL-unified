<?php
namespace BulkSMS\SMSNET24\Unified;
 
use BulkSMS\SMSNET24\Unified\Admin\SettingsPage;
use BulkSMS\SMSNET24\Unified\Admin\RoleNoticePage;
use BulkSMS\SMSNET24\Unified\Admin\AttendanceNoticePage;
use BulkSMS\SMSNET24\Unified\Admin\UserGuidePage;
use BulkSMS\SMSNET24\Unified\Admin\AdminGuidePage;
use BulkSMS\SMSNET24\Unified\Admin\QueueReportPage;
 
use BulkSMS\SMSNET24\Unified\Services\Sender;
 
use BulkSMS\SMSNET24\Unified\Integration\WooCommerceHooks;
use BulkSMS\SMSNET24\Unified\Integration\ReadyDeliveryHooks;
use BulkSMS\SMSNET24\Unified\Integration\DuesReminderScheduler;
 
use BulkSMS\SMSNET24\Unified\Integration\DokanHooks;
use BulkSMS\SMSNET24\Unified\Integration\LearnDashHooks;
use BulkSMS\SMSNET24\Unified\Integration\LearnPressHooks;
use BulkSMS\SMSNET24\Unified\Integration\TutorLMSHooks;
use BulkSMS\SMSNET24\Unified\Integration\MemberPressHooks;
use BulkSMS\SMSNET24\Unified\Integration\BooklyHooks;
 
use BulkSMS\SMSNET24\Unified\Integration\StudentResultHooks;
use BulkSMS\SMSNET24\Unified\Integration\AttendanceHooks;
 
final class Plugin {
    public function init(): void {
        // Admin UI (menus/pages)
        (new SettingsPage())->register();
        (new RoleNoticePage())->register();
        (new AttendanceNoticePage())->register();
        (new UserGuidePage())->register();
        (new AdminGuidePage())->register();
        (new QueueReportPage())->register();
 
        // Core sender (async queue consumer)
        (new Sender())->register();
 
        // Defer 3rd-party integrations until all plugins are loaded
        add_action('plugins_loaded', function () {
            // Commerce + operational
            (new WooCommerceHooks())->register();
            (new ReadyDeliveryHooks())->register();
            (new DuesReminderScheduler())->register();
 
            // Marketplaces, LMS, memberships, bookings
            (new DokanHooks())->register();
            (new LearnDashHooks())->register();
            (new LearnPressHooks())->register();
            (new TutorLMSHooks())->register();
            (new MemberPressHooks())->register();
            (new BooklyHooks())->register();
 
            // School/operational generic hooks
            (new StudentResultHooks())->register();
            (new AttendanceHooks())->register();
        }, 11);
    }
}
 