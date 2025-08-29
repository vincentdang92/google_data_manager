<?php
if (!defined('ABSPATH')) exit;

final class GDM_Admin {
    private string $opt_key = 'gdm_settings';

    public function hooks(): void {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function menu(): void {
        add_options_page(
            'Google Data Manager',
            'Google Data Manager',
            'manage_options',
            'gdm-settings',
            [$this, 'render_settings']
        );
    }

    public function register_settings(): void {
        register_setting($this->opt_key, $this->opt_key, ['sanitize_callback' => [$this, 'sanitize']]);

        add_settings_section('gdm_google', 'Google OAuth', '__return_false', $this->opt_key);
        add_settings_field('google_client_id', 'Client ID', [$this, 'field_text'], $this->opt_key, 'gdm_google', ['key'=>'google_client_id']);
        add_settings_field('google_client_secret', 'Client Secret', [$this, 'field_text'], $this->opt_key, 'gdm_google', ['key'=>'google_client_secret']);
        add_settings_field('google_redirect', 'Redirect URI', [$this, 'field_readonly'], $this->opt_key, 'gdm_google', ['value'=>home_url('/?gdm=google_callback')]);

        add_settings_section('gdm_recaptcha', 'Google reCAPTCHA', '__return_false', $this->opt_key);
        add_settings_field('recaptcha_v2_site', 'reCAPTCHA v2 Site Key', [$this, 'field_text'], $this->opt_key, 'gdm_recaptcha', ['key'=>'recaptcha_v2_site']);
        add_settings_field('recaptcha_v2_secret', 'reCAPTCHA v2 Secret Key', [$this, 'field_text'], $this->opt_key, 'gdm_recaptcha', ['key'=>'recaptcha_v2_secret']);
        add_settings_field('recaptcha_v3_site', 'reCAPTCHA v3 Site Key', [$this, 'field_text'], $this->opt_key, 'gdm_recaptcha', ['key'=>'recaptcha_v3_site']);
        add_settings_field('recaptcha_v3_secret', 'reCAPTCHA v3 Secret Key', [$this, 'field_text'], $this->opt_key, 'gdm_recaptcha', ['key'=>'recaptcha_v3_secret']);
        add_settings_field('recaptcha_v3_threshold', 'v3 Score Threshold (0.1-0.9)', [$this, 'field_text'], $this->opt_key, 'gdm_recaptcha', ['key'=>'recaptcha_v3_threshold', 'placeholder'=>'0.5']);

        add_settings_section('gdm_sheet', 'Google Sheet', '__return_false', $this->opt_key);
        add_settings_field('sheet_csv_url', 'Sheet CSV URL (Publish to web)', [$this, 'field_text'], $this->opt_key, 'gdm_sheet', ['key'=>'sheet_csv_url', 'placeholder'=>'https://docs.google.com/spreadsheets/d/.../gviz/tq?tqx=out:csv&sheet=...']);
        add_settings_field('sheet_id_name_map', 'ID & Sheet ID Label', [$this, 'field_text'], $this->opt_key, 'gdm_sheet', ['key'=>'sheet_id_name_map', 'placeholder'=>'Tên sheet id logic hiển thị (tùy chọn)']);
    }

    public function sanitize($input) {
        foreach (['google_client_id','google_client_secret','recaptcha_v2_site','recaptcha_v2_secret','recaptcha_v3_site','recaptcha_v3_secret','sheet_csv_url','sheet_id_name_map','recaptcha_v3_threshold'] as $k) {
            if (isset($input[$k])) $input[$k] = sanitize_text_field($input[$k]);
        }
        return $input;
    }

    public function field_text($args): void {
        $opts = get_option($this->opt_key, []);
        $key = $args['key'];
        $val = $opts[$key] ?? '';
        $ph  = $args['placeholder'] ?? '';
        printf('<input type="text" name="%1$s[%2$s]" value="%3$s" class="regular-text" placeholder="%4$s" />', esc_attr($this->opt_key), esc_attr($key), esc_attr($val), esc_attr($ph));
    }

    public function field_readonly($args): void {
        printf('<code>%s</code>', esc_html($args['value']));
    }

    public function render_settings(): void {
        ?>
        <div class="wrap">
            <h1>Google Data Manager - Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->opt_key);
                do_settings_sections($this->opt_key);
                submit_button();
                ?>
            </form>
            <p><strong>Lưu ý:</strong> với Google Sheet, cách nhẹ nhất là dùng “Publish to the web” → CSV, điền URL CSV vào ô trên.</p>
        </div>
        <?php
    }
}
