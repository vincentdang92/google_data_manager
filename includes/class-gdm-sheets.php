<?php
if (!defined('ABSPATH')) exit;

final class GDM_Sheets {
    /**
     * Yêu cầu: trong Sheet có các cột header: id, name, date, amount, user_email
     * Bạn Publish to web (CSV) và dán URL vào settings.
     */
    public static function sync_for_email(string $email): int|\WP_Error {
        $opts = get_option('gdm_settings', []);
        $csv_url = $opts['sheet_csv_url'] ?? '';
        if (!$csv_url) return new \WP_Error('sheet', 'Chưa cấu hình Sheet CSV URL.');

        $resp = wp_remote_get($csv_url, ['timeout' => 20]);
        if (is_wp_error($resp)) return $resp;
        $csv = wp_remote_retrieve_body($resp);

        $rows = self::parse_csv($csv);
        if (empty($rows)) return 0;

        $inserted = 0;
        foreach ($rows as $r) {
            // map + lọc theo email
            $row_email = sanitize_email($r['user_email'] ?? '');
            if (!$row_email || strtolower($row_email) !== strtolower($email)) continue;

            $ext_id = sanitize_text_field($r['id'] ?? '');
            if (!$ext_id) continue;

            $ok = GDM_DB::upsert([
                'sheet_id'    => $opts['sheet_id_name_map'] ?? null,
                'ext_id'      => $ext_id,
                'name'        => sanitize_text_field($r['name'] ?? ''),
                'record_date' => self::normalize_date($r['date'] ?? ''),
                'amount'      => (float)($r['amount'] ?? 0),
                'user_email'  => $row_email
            ]);
            if (!is_wp_error($ok)) $inserted++;
        }
        return $inserted;
    }

    private static function parse_csv(string $csv): array {
        $rows = [];
        $fh = fopen('php://memory', 'r+');
        fwrite($fh, $csv); rewind($fh);

        $header = null;
        while (($data = fgetcsv($fh)) !== false) {
            if ($header === null) { $header = array_map('trim', $data); continue; }
            $row = [];
            foreach ($header as $i => $h) {
                $row[strtolower($h)] = $data[$i] ?? '';
            }
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }

    private static function normalize_date(string $d): ?string {
        $d = trim($d);
        if (!$d) return null;
        // chấp nhận dd/mm/yyyy hay yyyy-mm-dd
        if (preg_match('~^\d{2}/\d{2}/\d{4}$~', $d)) {
            [$dd,$mm,$yy] = explode('/',$d);
            return "$yy-$mm-$dd";
        }
        if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $d)) return $d;
        return null;
    }
}
