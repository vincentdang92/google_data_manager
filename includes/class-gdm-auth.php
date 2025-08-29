<?php
if (!defined('ABSPATH')) exit;

final class GDM_Auth {
    private string $opt_key = 'gdm_settings';

    public function hooks(): void {
        add_action('init', [$this, 'maybe_google_callback']);
        add_action('wp_ajax_nopriv_gdm_request_otp', [$this, 'ajax_request_otp']);
        add_action('wp_ajax_nopriv_gdm_verify_otp',  [$this, 'ajax_verify_otp']);
        add_action('rest_api_init', function(){
            register_rest_route('gdm/v1', '/login', [
                'methods' => 'POST',
                'permission_callback' => '__return_true',
                'callback' => [$this, 'rest_login_username_password']
            ]);
        });
    }

    // === Google OAuth ===
    public function maybe_google_callback(): void {
        if (!isset($_GET['gdm']) || $_GET['gdm'] !== 'google_callback') return;

        $opts = get_option($this->opt_key, []);
        $client_id = $opts['google_client_id'] ?? '';
        $client_secret = $opts['google_client_secret'] ?? '';
        $redirect = home_url('/?gdm=google_callback');

        if (isset($_GET['code']) && $client_id && $client_secret) {
            $code = sanitize_text_field($_GET['code']);
            $resp = wp_remote_post('https://oauth2.googleapis.com/token', [
                'timeout' => 15,
                'body' => [
                    'code' => $code,
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'redirect_uri' => $redirect,
                    'grant_type' => 'authorization_code',
                ]
            ]);

            if (!is_wp_error($resp)) {
                $data = json_decode(wp_remote_retrieve_body($resp), true);
                $id_token = $data['id_token'] ?? '';
                if ($id_token) {
                    $payload = explode('.', $id_token);
                    $json = json_decode(base64_decode(strtr($payload[1] ?? '', '-_', '+/')), true);
                    $email = sanitize_email($json['email'] ?? '');
                    $verified = (bool)($json['email_verified'] ?? false);
                    $name = sanitize_text_field($json['name'] ?? '');

                    if ($email && $verified) {
                        $user = get_user_by('email', $email);
                        if (!$user) {
                            $user_id = wp_insert_user([
                                'user_login' => $email,
                                'user_email' => $email,
                                'display_name' => $name ?: $email,
                                'user_pass' => wp_generate_password(20),
                                'role' => 'gdm_user'
                            ]);
                            if (is_wp_error($user_id)) wp_die('Không thể tạo user.');
                            $user = get_user_by('id', $user_id);
                        }
                        wp_set_current_user($user->ID);
                        wp_set_auth_cookie($user->ID, true);
                        wp_redirect(home_url('/gdm-dashboard')); exit;
                    }
                }
            }
            wp_die('Đăng nhập Google thất bại.');
        }
    }

    public static function google_login_url(): string {
        $opts = get_option('gdm_settings', []);
        $client_id = $opts['google_client_id'] ?? '';
        if (!$client_id) return '#';
        $redirect = urlencode(home_url('/?gdm=google_callback'));
        $state = wp_create_nonce('gdm_google_state');
        $scope = urlencode('openid email profile');
        return "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id={$client_id}&redirect_uri={$redirect}&scope={$scope}&access_type=online&prompt=select_account&state={$state}";
    }

    // === Login bằng username/password + reCAPTCHA v3 (mặc định) ===
    public function rest_login_username_password(\WP_REST_Request $req) {
        $params = $req->get_json_params();
        $user_login = sanitize_text_field($params['username'] ?? '');
        $password   = $params['password'] ?? '';
        $token      = sanitize_text_field($params['recaptcha_token'] ?? '');
        $action     = sanitize_text_field($params['recaptcha_action'] ?? 'login');

        if (!GDM_RECAPTCHA::verify($token, 'v3', $action)) {
            return new \WP_REST_Response(['message'=>'reCAPTCHA không hợp lệ'], 400);
        }

        $user = wp_authenticate($user_login, $password);
        if (is_wp_error($user)) {
            return new \WP_REST_Response(['message'=>'Sai tài khoản hoặc mật khẩu'], 401);
        }
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        return ['message' => 'Đăng nhập thành công', 'redirect' => home_url('/gdm-dashboard')];
    }

    // === OTP Register ===
    public function ajax_request_otp(): void {
        check_ajax_referer('gdm_register', 'nonce');
        $email = sanitize_email($_POST['email'] ?? '');
        if (!$email) wp_send_json_error(['message'=>'Email không hợp lệ']);

        $otp = wp_rand(100000, 999999);
        set_transient('gdm_otp_'.md5($email), $otp, 10 * MINUTE_IN_SECONDS);

        wp_mail($email, 'Mã OTP đăng ký', "Mã xác thực của bạn: {$otp}");

        wp_send_json_success(['message'=>'Đã gửi OTP. Vui lòng kiểm tra email.']);
    }

    public function ajax_verify_otp(): void {
        check_ajax_referer('gdm_register', 'nonce');
        $email = sanitize_email($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');
        $otp   = (int)($_POST['otp'] ?? 0);

        $saved = (int)get_transient('gdm_otp_'.md5($email));
        if (!$saved || $saved !== $otp) {
            wp_send_json_error(['message'=>'OTP không đúng hoặc đã hết hạn.']);
        }
        delete_transient('gdm_otp_'.md5($email));

        if (email_exists($email)) {
            wp_send_json_error(['message'=>'Email đã tồn tại.']);
        }
        $user_id = wp_insert_user([
            'user_login' => $email,
            'user_email' => $email,
            'user_pass'  => $pass ?: wp_generate_password(16),
            'role'       => 'gdm_user'
        ]);
        if (is_wp_error($user_id)) {
            wp_send_json_error(['message'=>'Không thể tạo tài khoản.']);
        }
        wp_send_json_success(['message'=>'Đăng ký thành công! Hãy đăng nhập.']);
    }
}
