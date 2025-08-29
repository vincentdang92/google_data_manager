<?php
if (!defined('ABSPATH')) exit;

final class GDM_DB {
    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'gdm_records';
    }

    public static function upsert(array $row): bool|\WP_Error {
        global $wpdb;
        $table = self::table();

        $data = [
            'sheet_id'   => $row['sheet_id'] ?? null,
            'ext_id'     => $row['ext_id'],
            'name'       => $row['name'],
            'record_date'=> $row['record_date'],
            'amount'     => $row['amount'],
            'user_email' => $row['user_email']
        ];
        $fmt = ['%s','%s','%s','%s','%f','%s'];

        // REPLACE INTO bằng wpdb->query (dùng on duplicate key)
        $sql = $wpdb->prepare(
            "INSERT INTO {$table} (sheet_id, ext_id, name, record_date, amount, user_email)
             VALUES (%s,%s,%s,%s,%f,%s)
             ON DUPLICATE KEY UPDATE name=VALUES(name), record_date=VALUES(record_date), amount=VALUES(amount)",
            $data['sheet_id'], $data['ext_id'], $data['name'], $data['record_date'], $data['amount'], $data['user_email']
        );
        $res = $wpdb->query($sql);
        if ($res === false) return new \WP_Error('db', 'Không thể lưu dữ liệu.');
        return true;
    }

    

    
    private static function build_where(array $args, array &$params): string {
        global $wpdb;
        $where = 'WHERE 1=1';
        if (!empty($args['email'])) {
            $where .= ' AND user_email = %s'; $params[] = $args['email'];
        }
        if (!empty($args['search'])) {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $where .= ' AND (ext_id LIKE %s OR name LIKE %s)'; $params[] = $like; $params[] = $like;
        }
        if (!empty($args['min_date'])) { $where .= ' AND record_date >= %s'; $params[] = $args['min_date']; }
        if (!empty($args['max_date'])) { $where .= ' AND record_date <= %s'; $params[] = $args['max_date']; }
        if (isset($args['min_amount']) && $args['min_amount'] !== null) { $where .= ' AND amount >= %f'; $params[] = (float)$args['min_amount']; }
        if (isset($args['max_amount']) && $args['max_amount'] !== null) { $where .= ' AND amount <= %f'; $params[] = (float)$args['max_amount']; }
        return $where;
    }

    public static function count(array $args): int {
        global $wpdb; $table = self::table(); $params = [];
        $where = self::build_where($args, $params);
        $sql = "SELECT COUNT(*) FROM {$table} {$where}";
        return (int)$wpdb->get_var($wpdb->prepare($sql, $params));
    }

    public static function list(array $args): array {
        global $wpdb; $table = self::table(); $params = [];
        $where = self::build_where($args, $params);
        $sql = "SELECT ext_id, name, record_date, amount, user_email
                FROM {$table} {$where}
                ORDER BY record_date DESC, id DESC
                LIMIT %d OFFSET %d";
        $params[] = (int)$args['length']; $params[] = (int)$args['start'];
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }

    public static function stats(array $args): array {
        global $wpdb; $table = self::table(); $params = [];
        $where = self::build_where($args, $params);
        $sql = "SELECT COUNT(*) AS total_rows,
                    COALESCE(SUM(amount),0) AS total_amount,
                    COALESCE(AVG(amount),0) AS avg_amount
                FROM {$table} {$where}";
        $row = $wpdb->get_row($wpdb->prepare($sql, $params));
        return [
            'total_rows'   => (int)($row->total_rows ?? 0),
            'total_amount' => (float)($row->total_amount ?? 0),
            'avg_amount'   => (float)($row->avg_amount ?? 0),
        ];
    }

}
