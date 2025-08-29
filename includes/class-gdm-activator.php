<?php
if (!defined('ABSPATH')) exit;

final class GDM_Activator {
    public static function activate(): void {
        global $wpdb;

        // Tạo role user riêng (đơn giản, có thể mở rộng capability sau)
        add_role('gdm_user', 'GDM User', ['read' => true]);

        // Tạo bảng lưu data từ Google Sheet
        $table = $wpdb->prefix . 'gdm_records';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sheet_id VARCHAR(191) NULL,
            ext_id VARCHAR(191) NOT NULL,
            name VARCHAR(191) NOT NULL,
            record_date DATE NULL,
            amount DECIMAL(18,2) DEFAULT 0,
            user_email VARCHAR(191) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_row (user_email, ext_id, sheet_id),
            KEY idx_email (user_email),
            KEY idx_date (record_date)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
