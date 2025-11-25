<?php
class Benana_Automation_Shortcodes {
    public function __construct() {
        add_shortcode( 'project_inbox', array( $this, 'inbox_shortcode' ) );
        add_shortcode( 'project_user_history', array( $this, 'history_shortcode' ) );
        add_shortcode( 'project_user_stats', array( $this, 'stats_shortcode' ) );
        add_action( 'init', array( $this, 'handle_actions' ) );
    }

    public function handle_actions() {
        if ( isset( $_GET['benana_action'], $_GET['project_id'] ) && is_user_logged_in() ) {
            $action     = sanitize_text_field( wp_unslash( $_GET['benana_action'] ) );
            $project_id = absint( $_GET['project_id'] );
            $user_id    = get_current_user_id();
            if ( $action === 'accept' ) {
                Benana_Automation_Project_Handler::accept_project( $project_id, $user_id );
            }
            if ( $action === 'reject' ) {
                Benana_Automation_Project_Handler::reject_project( $project_id, $user_id );
            }
            if ( $action === 'complete' ) {
                Benana_Automation_Project_Handler::complete_project( $project_id );
            }
        }
    }

    public function inbox_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>برای مشاهده پروژه‌ها وارد شوید.</p>';
        }
        $user_id  = get_current_user_id();
        $args     = array(
            'post_type'  => 'project',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key'     => 'assigned_users',
                    'value'   => $user_id,
                    'compare' => 'LIKE',
                ),
                array(
                    'key'   => 'accepted_by',
                    'value' => $user_id,
                ),
            ),
        );
        $projects = get_posts( $args );
        ob_start();
        include BENANA_AUTOMATION_PATH . 'templates/inbox.php';
        return ob_get_clean();
    }

    public function history_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>برای مشاهده تاریخچه وارد شوید.</p>';
        }
        $user_id = get_current_user_id();
        $args    = array(
            'post_type'  => 'project',
            'meta_query' => array(
                array(
                    'key'   => 'client_user_id',
                    'value' => $user_id,
                ),
            ),
        );
        $projects = get_posts( $args );
        ob_start();
        include BENANA_AUTOMATION_PATH . 'templates/history.php';
        return ob_get_clean();
    }

    public function stats_shortcode( $atts ) {
        $atts    = shortcode_atts( array( 'type' => 'completed_count' ), $atts );
        $user_id = get_current_user_id();
        $count   = 0;
        if ( $atts['type'] === 'completed_count' ) {
            $args  = array(
                'post_type'  => 'project',
                'meta_query' => array(
                    array(
                        'key'   => 'accepted_by',
                        'value' => $user_id,
                    ),
                    array(
                        'key'   => 'project_status',
                        'value' => 'completed',
                    ),
                ),
            );
            $query = new WP_Query( $args );
            $count = $query->found_posts;
        }
        return '<div class="benana-stats">پروژه‌های تکمیل‌شده: ' . intval( $count ) . '</div>';
    }
}
