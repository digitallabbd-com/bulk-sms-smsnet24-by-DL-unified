<?php
namespace BulkSMS\SMSNET24\Unified\Support;

final class Validation {
    public static function sanitizeSettings(array $input): array {
        return [
            'api_base'  => esc_url_raw($input['api_base'] ?? ''),
            'api_key'   => sanitize_text_field($input['api_key'] ?? ''),
            'sender'    => sanitize_text_field($input['sender'] ?? ''),
            'debug'     => !empty($input['debug']),

            'enable_wc' => !empty($input['enable_wc']),
            'enable_dokan' => !empty($input['enable_dokan']),

            'role_notice_maxlen' => max(120, (int) ($input['role_notice_maxlen'] ?? 480)),

            'enable_learndash' => !empty($input['enable_learndash']),
            'learndash_enroll_tpl'   => sanitize_textarea_field($input['learndash_enroll_tpl'] ?? ''),
            'learndash_complete_tpl' => sanitize_textarea_field($input['learndash_complete_tpl'] ?? ''),

            'enable_learnpress' => !empty($input['enable_learnpress']),
            'learnpress_enroll_tpl'   => sanitize_textarea_field($input['learnpress_enroll_tpl'] ?? ''),
            'learnpress_complete_tpl' => sanitize_textarea_field($input['learnpress_complete_tpl'] ?? ''),

            'enable_tutor' => !empty($input['enable_tutor']),
            'tutor_enroll_tpl'   => sanitize_textarea_field($input['tutor_enroll_tpl'] ?? ''),
            'tutor_complete_tpl' => sanitize_textarea_field($input['tutor_complete_tpl'] ?? ''),

            'enable_memberpress' => !empty($input['enable_memberpress']),
            'memberpress_txn_tpl' => sanitize_textarea_field($input['memberpress_txn_tpl'] ?? ''),

            'vendor_new_order_tpl' => sanitize_textarea_field($input['vendor_new_order_tpl'] ?? ''),
            'vendor_status_tpl'    => sanitize_textarea_field($input['vendor_status_tpl'] ?? ''),
            'vendor_onboard_tpl'   => sanitize_textarea_field($input['vendor_onboard_tpl'] ?? ''),

            'enable_bookly' => !empty($input['enable_bookly']),
            'bookly_new_booking_tpl' => sanitize_textarea_field($input['bookly_new_booking_tpl'] ?? ''),

            'enable_student_result' => !empty($input['enable_student_result']),
            'student_result_tpl' => sanitize_textarea_field($input['student_result_tpl'] ?? ''),

            'enable_dues_reminder' => !empty($input['enable_dues_reminder']),
            'dues_days' => max(1, (int) ($input['dues_days'] ?? 7)),
            'dues_reminder_tpl' => sanitize_textarea_field($input['dues_reminder_tpl'] ?? ''),

            'enable_ready_delivery' => !empty($input['enable_ready_delivery']),
            'ready_delivery_tpl' => sanitize_textarea_field($input['ready_delivery_tpl'] ?? ''),

            'enable_attendance_notice' => !empty($input['enable_attendance_notice']),
            'attendance_absent_tpl' => sanitize_textarea_field($input['attendance_absent_tpl'] ?? ''),
        ];
    }
}
