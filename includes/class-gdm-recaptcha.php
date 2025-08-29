<?php
if (!defined('ABSPATH')) exit;

final class GDM_RECAPTCHA {
    public function hooks(): void {
        // Có thể gắn thêm script v2/v3 ở frontend tùy trang (login/register)
        add_action('wp_head', [$this, 'maybe_enqueue_v3']);
    }

    public function maybe_enqueue_v3(): void {
        $opt = get_option('gdm_settings', []);
        if (!empty($opt['recaptcha_v3_site'])) {
            $site = esc_js($opt['recaptcha_v3_site']);
            echo "<script src='https://www.google.com/recaptcha/api.js?render={$site}'></script>";
        }
    }

    public static function verify(string $token, string $version = 'v3', string $action = 'login'): bool {
        $opt = get_option('gdm_settings', []);
        $secret = $version === 'v2' ? ($opt['recaptcha_v2_secret'] ?? '') : ($opt['recaptcha_v3_secret'] ?? '');
        if (!$secret || !$token) return false;

        $resp = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 10,
            'body' => [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]
        ]);

        if (is_wp_error($resp)) return false;

        $body = json_decode(wp_remote_retrieve_body($resp), true);
        if (!is_array($body) || empty($body['success'])) return false;

        if ($version === 'v3') {
            $score = floatval($body['score'] ?? 0);
            $threshold = floatval($opt['recaptcha_v3_threshold'] ?? 0.5);
            if (($body['action'] ?? '') !== $action) return false;
            return $score >= $threshold;
        }
        return true;
    }
}
