<?php
if (!defined('ABSPATH')) exit;

require_once GDM_PATH.'includes/class-gdm-admin.php';
require_once GDM_PATH.'includes/class-gdm-auth.php';
require_once GDM_PATH.'includes/class-gdm-recaptcha.php';
require_once GDM_PATH.'includes/class-gdm-rest.php';
require_once GDM_PATH.'includes/class-gdm-db.php';
require_once GDM_PATH.'includes/class-gdm-sheets.php';

final class GDM {
    public function run(): void {
        (new GDM_Admin())->hooks();
        (new GDM_Auth())->hooks();
        (new GDM_RECAPTCHA())->hooks();
        (new GDM_REST())->hooks();

        // Shortcodes
        add_shortcode('gdm_login', [$this, 'shortcode_login']);
        add_shortcode('gdm_register', [$this, 'shortcode_register']);
        add_shortcode('gdm_dashboard', [$this, 'shortcode_dashboard']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
    }

    public function frontend_assets(): void {
        // CSS
        wp_enqueue_style('gdm-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css', [], '4.6.2');
        wp_enqueue_style('gdm-datatables', 'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css', [], '1.13.8');
        wp_enqueue_style('gdm-dt-responsive', 'https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css', [], '2.5.0');
        wp_enqueue_style('gdm-dt-buttons', 'https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css', [], '2.4.2');
        wp_enqueue_style('gdm-frontend', GDM_URL.'assets/css/frontend.css', [], GDM_VERSION);

        // JS core
        wp_enqueue_script('gdm-jq', 'https://code.jquery.com/jquery-3.7.1.min.js', [], '3.7.1', true);
        wp_enqueue_script('gdm-popper', 'https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js', ['gdm-jq'], '1.16.1', true);
        wp_enqueue_script('gdm-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js', ['gdm-jq'], '4.6.2', true);

        // DataTables + plugins
        wp_enqueue_script('gdm-datatables', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js', ['gdm-jq'], '1.13.8', true);
        wp_enqueue_script('gdm-datatables-bs4', 'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js', ['gdm-datatables'], '1.13.8', true);
        wp_enqueue_script('gdm-dt-responsive', 'https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js', ['gdm-datatables'], '2.5.0', true);
        wp_enqueue_script('gdm-dt-responsive-bs4', 'https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js', ['gdm-dt-responsive'], '2.5.0', true);
        wp_enqueue_script('gdm-dt-buttons', 'https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js', ['gdm-datatables'], '2.4.2', true);
        wp_enqueue_script('gdm-dt-buttons-bs4', 'https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap4.min.js', ['gdm-dt-buttons'], '2.4.2', true);
        wp_enqueue_script('gdm-dt-jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js', [], '3.10.1', true);
        wp_enqueue_script('gdm-dt-buttons-html5', 'https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js', ['gdm-dt-buttons','gdm-dt-jszip'], '2.4.2', true);
        wp_enqueue_script('gdm-dt-buttons-print', 'https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js', ['gdm-dt-buttons'], '2.4.2', true);

        // Daterange picker (nhẹ, không phụ thuộc moment)
        wp_enqueue_style('gdm-litepicker', 'https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css', [], '2.0.12');
        wp_enqueue_script('gdm-litepicker', 'https://cdn.jsdelivr.net/npm/litepicker/dist/bundle.js', [], '2.0.12', true);

        // App JS
        wp_enqueue_script('gdm-dashboard', GDM_URL.'assets/js/dashboard.js', ['gdm-datatables'], GDM_VERSION, true);
        wp_localize_script('gdm-dashboard', 'GDM_VARS', [
            'rest'  => esc_url_raw(rest_url('gdm/v1/')),
            'nonce' => wp_create_nonce('wp_rest')
        ]);
    }


    public function shortcode_login(): string {
        ob_start();
        include GDM_PATH.'includes/templates/login.php';
        return ob_get_clean();
    }
    public function shortcode_register(): string {
        ob_start();
        include GDM_PATH.'includes/templates/register.php';
        return ob_get_clean();
    }
    public function shortcode_dashboard(): string {
        if (!is_user_logged_in()) {
            return '<div class="alert alert-warning">Vui lòng đăng nhập để xem dashboard.</div>';
        }
        ob_start();
        include GDM_PATH.'includes/templates/dashboard.php';
        return ob_get_clean();
    }
}
