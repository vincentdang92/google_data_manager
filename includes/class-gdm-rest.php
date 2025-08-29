<?php
if (!defined('ABSPATH')) exit;

final class GDM_REST {
    public function hooks(): void {
        add_action('rest_api_init', function(){
            register_rest_route('gdm/v1', '/data', [
                'methods' => 'GET',
                'permission_callback' => function(){ return is_user_logged_in(); },
                'callback' => [$this, 'datatable']
            ]);
            register_rest_route('gdm/v1', '/sync', [
                'methods' => 'POST',
                'permission_callback' => function(){ return is_user_logged_in(); },
                'callback' => [$this, 'sync_my_data']
            ]);
            register_rest_route('gdm/v1', '/stats', [
                'methods' => 'GET',
                'permission_callback' => function(){ return is_user_logged_in(); },
                'callback' => [$this, 'stats']
            ]);
        });
    }

    public function datatable(\WP_REST_Request $req) {
        $draw   = intval($req['draw'] ?? 1);
        $start  = intval($req['start'] ?? 0);
        $length = max(10, intval($req['length'] ?? 10));
        $search = sanitize_text_field($req->get_param('search')['value'] ?? '');

        // filters
        $min_date = sanitize_text_field($req->get_param('min_date') ?? '');
        $max_date = sanitize_text_field($req->get_param('max_date') ?? '');
        $min_amt  = floatval($req->get_param('min_amount') ?? 0);
        $max_amt  = ($req->get_param('max_amount') !== null && $req->get_param('max_amount') !== '') ? floatval($req->get_param('max_amount')) : null;

        $current_user = wp_get_current_user();
        $email = $current_user->user_email;

        $args = [
            'start'  => $start,
            'length' => $length,
            'search' => $search,
            'email'  => current_user_can('manage_options') ? null : $email,
            'min_date' => $min_date ?: null,
            'max_date' => $max_date ?: null,
            'min_amount' => $min_amt ?: null,
            'max_amount' => $max_amt
        ];

        $total    = GDM_DB::count($args);
        $records  = GDM_DB::list($args);

        return [
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => array_map(function($r){
                return [
                    esc_html($r->ext_id),
                    esc_html($r->name),
                    esc_html($r->record_date),
                    esc_html(number_format((float)$r->amount, 2)),
                    esc_html($r->user_email)
                ];
            }, $records)
        ];
    }

    public function sync_my_data(\WP_REST_Request $req) {
        $user = wp_get_current_user();
        $email = $user->user_email;

        $inserted = GDM_Sheets::sync_for_email($email);
        if (is_wp_error($inserted)) {
            return new \WP_REST_Response(['message'=>$inserted->get_error_message()], 400);
        }
        return ['message' => "Đồng bộ xong. Thêm mới {$inserted} dòng cho {$email}."];
    }
    public function stats(\WP_REST_Request $req) {
        $user = wp_get_current_user();
        $email = current_user_can('manage_options') ? null : $user->user_email;
        $res = GDM_DB::stats([
            'email' => $email
        ]);
        return $res;
    }
}
