<?php
class Benana_Automation_Updater {
    private $plugin_file;

    public function __construct() {
        $this->plugin_file = plugin_basename( BENANA_AUTOMATION_PATH . 'benana-automation-projects.php' );

        add_filter( 'site_transient_update_plugins', array( $this, 'check_for_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugins_api' ), 10, 3 );
    }

    public function check_for_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            return $transient;
        }

        $remote = $this->get_remote_data();

        if ( ! $remote || empty( $remote['version'] ) ) {
            return $transient;
        }

        if ( version_compare( BENANA_AUTOMATION_VERSION, $remote['version'], '>=' ) ) {
            return $transient;
        }

        $update              = new stdClass();
        $update->slug        = 'benana-automation-projects';
        $update->plugin      = $this->plugin_file;
        $update->new_version = $remote['version'];
        $update->package     = isset( $remote['download_url'] ) ? esc_url_raw( $remote['download_url'] ) : '';
        $update->tested      = isset( $remote['tested'] ) ? sanitize_text_field( $remote['tested'] ) : '';
        $update->requires    = isset( $remote['requires'] ) ? sanitize_text_field( $remote['requires'] ) : '';
        $update->sections    = array(
            'changelog' => isset( $remote['changelog'] ) ? wp_kses_post( $remote['changelog'] ) : __( 'لیست تغییرات در دسترس نیست.', 'benana-automation-projects' ),
        );

        $transient->response[ $this->plugin_file ] = $update;

        return $transient;
    }

    public function plugins_api( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( empty( $args->slug ) || 'benana-automation-projects' !== $args->slug ) {
            return $result;
        }

        $remote = $this->get_remote_data();

        if ( ! $remote || empty( $remote['version'] ) ) {
            return $result;
        }

        $info              = new stdClass();
        $info->name        = 'بنانا اتوماسیون پروژه‌ها';
        $info->slug        = 'benana-automation-projects';
        $info->version     = $remote['version'];
        $info->author      = '<a href="https://example.com">Banana Automation</a>';
        $info->download_link = isset( $remote['download_url'] ) ? esc_url_raw( $remote['download_url'] ) : '';
        $info->requires    = isset( $remote['requires'] ) ? sanitize_text_field( $remote['requires'] ) : '';
        $info->tested      = isset( $remote['tested'] ) ? sanitize_text_field( $remote['tested'] ) : '';
        $info->sections    = array(
            'description' => __( 'افزونه اتوماسیون پروژه با پشتیبانی Gravity Forms و WP-SMS.', 'benana-automation-projects' ),
            'changelog'   => isset( $remote['changelog'] ) ? wp_kses_post( $remote['changelog'] ) : __( 'لیست تغییرات در دسترس نیست.', 'benana-automation-projects' ),
        );

        return $info;
    }

    private function get_remote_data() {
        $settings = Benana_Automation_Settings::get_settings();
        $url      = isset( $settings['update_source'] ) ? esc_url_raw( $settings['update_source'] ) : '';

        if ( empty( $url ) ) {
            return false;
        }

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 8,
            )
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data ) || ! is_array( $data ) ) {
            return false;
        }

        return $data;
    }
}
