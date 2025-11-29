<?php
class Benana_Automation_Shortcodes {
    public function __construct() {
        add_shortcode( 'project_inbox', array( $this, 'inbox_shortcode' ) );
        add_shortcode( 'project_user_history', array( $this, 'history_shortcode' ) );
        add_shortcode( 'project_user_stats', array( $this, 'stats_shortcode' ) );
        add_shortcode( 'benana_user_availability', array( $this, 'availability_shortcode' ) );
        add_shortcode( 'project_detail', array( $this, 'project_detail_shortcode' ) );
        add_action( 'template_redirect', array( $this, 'handle_actions' ) );
    }

    public function handle_actions() {
        $action     = sanitize_text_field( wp_unslash( $_POST['benana_action'] ?? '' ) );
        $project_id = absint( $_POST['project_id'] ?? 0 );
        $user_id    = get_current_user_id();

        if ( empty( $action ) || empty( $project_id ) || ! is_user_logged_in() ) {
            return;
        }

        if ( ! isset( $_POST['benana_action_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['benana_action_nonce'] ) ), 'benana_action_' . $project_id ) ) {
            return;
        }

        if ( ! Benana_Automation_Project_Handler::user_is_assignee( $project_id, $user_id ) ) {
            return;
        }

        $status        = 'none';
        $message       = '';
        $project_status = get_post_meta( $project_id, 'project_status', true );

        if ( $action === 'accept' && 'new' === $project_status ) {
            Benana_Automation_Project_Handler::accept_project( $project_id, $user_id );
            $status = 'accepted';
        }
        if ( $action === 'reject' && 'new' === $project_status ) {
            Benana_Automation_Project_Handler::reject_project( $project_id, $user_id );
            $status = 'rejected';
        }
        if ( 'upload_file' === $action && 'accepted' === $project_status ) {
            $upload_result = $this->handle_file_upload( $project_id, $user_id );
            $status        = $upload_result['status'];
            $message       = $upload_result['message'];
        }

        $redirect = wp_get_referer();
        if ( ! $redirect ) {
            $redirect = add_query_arg( 'project_id', $project_id, home_url( '/projects/' ) );
        } else {
            $redirect = add_query_arg( 'project_id', $project_id, remove_query_arg( array( 'benana_action', '_wpnonce' ), $redirect ) );
        }

        $redirect = add_query_arg( 'benana_action_status', $status, $redirect );
        if ( ! empty( $message ) ) {
            $redirect = add_query_arg( 'benana_action_message', rawurlencode( $message ), $redirect );
        }

        wp_safe_redirect( $redirect );
        exit;
    }

    private function handle_file_upload( $project_id, $user_id ) {
        $result = array(
            'status'  => 'upload_failed',
            'message' => '',
        );

        $accepted = intval( get_post_meta( $project_id, 'accepted_by', true ) );
        if ( $accepted !== intval( $user_id ) ) {
            $result['message'] = 'فقط مهندس پذیرفته‌کننده می‌تواند فایل بارگذاری کند.';
            return $result;
        }

        if ( empty( $_FILES['benana_project_files'] ) || empty( $_FILES['benana_project_files']['name'] ) ) {
            $result['message'] = 'هیچ فایلی انتخاب نشده است.';
            return $result;
        }

        $form_id     = absint( get_post_meta( $project_id, 'gf_form_id', true ) );
        $entry_id    = absint( get_post_meta( $project_id, 'gf_entry_id', true ) );
        $settings    = Benana_Automation_Settings::get_settings();
        $form_config = $settings['gravity_forms'][ $form_id ] ?? array();
        $upload_field = $form_config['file_field'] ?? ( $form_config['upload_field'] ?? '' );

        if ( empty( $upload_field ) ) {
            $result['message'] = 'شناسه فیلد آپلود در تنظیمات فرم تعیین نشده است.';
            return $result;
        }

        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $files           = $_FILES['benana_project_files'];
        $uploaded_urls   = array();
        $upload_errors   = array();
        $upload_overrides = array( 'test_form' => false );

        $this->ensure_custom_upload_dir();
        add_filter( 'upload_dir', array( $this, 'custom_upload_dir' ) );

        if ( is_array( $files['name'] ) ) {
            foreach ( $files['name'] as $index => $name ) {
                if ( empty( $name ) ) {
                    continue;
                }
                $file_array = array(
                    'name'     => $name,
                    'type'     => $files['type'][ $index ] ?? '',
                    'tmp_name' => $files['tmp_name'][ $index ] ?? '',
                    'error'    => $files['error'][ $index ] ?? 0,
                    'size'     => $files['size'][ $index ] ?? 0,
                );
                $movefile = wp_handle_upload( $file_array, $upload_overrides );
                if ( isset( $movefile['url'] ) ) {
                    $uploaded_urls[] = $movefile['url'];
                } elseif ( isset( $movefile['error'] ) ) {
                    $upload_errors[] = $movefile['error'];
                }
            }
        } else {
            $movefile = wp_handle_upload( $files, $upload_overrides );
            if ( isset( $movefile['url'] ) ) {
                $uploaded_urls[] = $movefile['url'];
            } elseif ( isset( $movefile['error'] ) ) {
                $upload_errors[] = $movefile['error'];
            }
        }

        remove_filter( 'upload_dir', array( $this, 'custom_upload_dir' ) );

        if ( empty( $uploaded_urls ) ) {
            $result['message'] = ! empty( $upload_errors ) ? implode( ' / ', $upload_errors ) : 'بارگذاری فایل انجام نشد.';
            return $result;
        }

        $entry = array();
        if ( class_exists( 'GFAPI' ) && $entry_id ) {
            $entry = GFAPI::get_entry( $entry_id );
            if ( is_wp_error( $entry ) ) {
                $entry = array();
            }
        }

        $form = array();
        if ( class_exists( 'GFAPI' ) && $form_id ) {
            $form = GFAPI::get_form( $form_id );
            if ( is_wp_error( $form ) ) {
                $form = array();
            }
        }

        $value = count( $uploaded_urls ) > 1 ? wp_json_encode( $uploaded_urls ) : $uploaded_urls[0];

        $snapshot    = Benana_Automation_Project_Handler::get_entry_snapshot( $project_id );
        $entry_merge = array_merge( $snapshot['entry'], $entry );
        $entry_merge[ $upload_field ] = $value;

        if ( ! empty( $entry ) && class_exists( 'GFAPI' ) ) {
            $entry[ $upload_field ] = $value;
            $update_result          = GFAPI::update_entry( $entry );
            if ( is_wp_error( $update_result ) ) {
                $result['message'] = $update_result->get_error_message();
                return $result;
            }
        }

        Benana_Automation_Project_Handler::store_entry_snapshot( $project_id, $form, $entry_merge );
        Benana_Automation_Project_Handler::upload_file( $project_id, $user_id, $uploaded_urls );

        $result['status']  = 'uploaded';
        $result['message'] = 'فایل با موفقیت پیوست شد.';

        return $result;
    }

    public function inbox_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>برای مشاهده پروژه‌ها وارد شوید.</p>';
        }
        $user_id  = get_current_user_id();
        $search   = sanitize_text_field( wp_unslash( $_GET['benana_search'] ?? '' ) );
        $status   = sanitize_text_field( wp_unslash( $_GET['benana_status'] ?? '' ) );
        $meta_query = array(
            'relation' => 'AND',
            array(
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

        if ( ! empty( $status ) ) {
            $meta_query[] = array(
                'key'   => 'project_status',
                'value' => $status,
            );
        }

        $args = array(
            'post_type'  => 'project',
            's'          => $search,
            'meta_query' => $meta_query,
        );
        $projects       = get_posts( $args );
        $status_labels  = array(
            'new'           => 'در انتظار پذیرش',
            'accepted'      => 'در حال انجام',
            'file_uploaded' => 'فایل ثبت شده',
            'completed'     => 'تکمیل شده',
            'rejected'      => 'رد شده',
        );

        $entry_dates = array();
        foreach ( $projects as $project ) {
            $snapshot                 = Benana_Automation_Project_Handler::get_entry_snapshot( $project->ID );
            $entry_dates[ $project->ID ] = $this->format_entry_datetime( $snapshot['entry'], $project->ID );
        }
        ob_start();
        include BENANA_AUTOMATION_PATH . 'templates/inbox.php';
        return ob_get_clean();
    }

    public function project_detail_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>برای مشاهده پروژه ابتدا وارد شوید.</p>';
        }

        $project_id = absint( $_GET['project_id'] ?? 0 );
        $action_msg = sanitize_text_field( wp_unslash( $_GET['benana_action_status'] ?? '' ) );
        $action_txt = sanitize_text_field( wp_unslash( $_GET['benana_action_message'] ?? '' ) );
        if ( ! $project_id ) {
            return '<p>پروژه‌ای انتخاب نشده است.</p>';
        }

        $project = get_post( $project_id );
        if ( ! $project || 'project' !== $project->post_type ) {
            return '<p>پروژه پیدا نشد.</p>';
        }

        $user_id = get_current_user_id();
        if ( ! Benana_Automation_Project_Handler::user_is_assignee( $project_id, $user_id ) ) {
            return '<p>دسترسی به این پروژه ندارید.</p>';
        }

        $entry         = Benana_Automation_Project_Handler::get_entry_for_project( $project_id );
        $snapshot      = Benana_Automation_Project_Handler::get_entry_snapshot( $project_id );
        $form_id       = get_post_meta( $project_id, 'gf_form_id', true );
        $status        = get_post_meta( $project_id, 'project_status', true );

        if ( empty( $form_id ) && ! empty( $entry['form_id'] ) ) {
            $form_id = $entry['form_id'];
        }

        $form = ( function_exists( 'GFAPI' ) && ! empty( $form_id ) ) ? GFAPI::get_form( $form_id ) : array();
        $settings      = Benana_Automation_Settings::get_settings();
        $hidden_before = array();
        $upload_field  = '';
        if ( isset( $settings['gravity_forms'][ $form_id ] ) ) {
            $gf_settings   = $settings['gravity_forms'][ $form_id ];
            $hidden_before = $this->parse_field_list( $gf_settings['before_accept'] ?? '' );
            $upload_field  = $gf_settings['file_field'] ?? ( $gf_settings['upload_field'] ?? '' );
        }

        $accepted      = intval( get_post_meta( $project_id, 'accepted_by', true ) ) === $user_id;
        $merged_entry  = array_merge( $snapshot['entry'], $entry );
        $render_fields = $this->prepare_fields_for_display( $form, $merged_entry, $snapshot, $hidden_before, $status );
        $entry_date    = $this->format_entry_datetime( $snapshot['entry'], $project_id );

        $view = array(
            'project'       => $project,
            'status'        => $status,
            'status_label'  => $this->translate_status( $status ),
            'entry'         => $entry,
            'form'          => $form,
            'fields'        => $render_fields,
            'accepted'      => $accepted,
            'province'      => get_post_meta( $project_id, 'project_province_id', true ),
            'city'          => get_post_meta( $project_id, 'project_city_id', true ),
            'nonce'         => wp_create_nonce( 'benana_action_' . $project_id ),
            'snapshot'      => $snapshot,
            'action_msg'    => $action_msg,
            'action_text'   => $action_txt,
            'upload_field'  => $upload_field,
            'entry_id'      => get_post_meta( $project_id, 'gf_entry_id', true ),
            'entry_date'    => $entry_date,
        );

        ob_start();
        include BENANA_AUTOMATION_PATH . 'templates/project-detail.php';
        return ob_get_clean();
    }

    private function parse_field_list( $raw ) {
        $items = array();
        foreach ( explode( ',', $raw ) as $item ) {
            $item = trim( $item );
            if ( empty( $item ) ) {
                continue;
            }

            $item = trim( $item, '{}' );

            if ( preg_match( '/^gf_(.+)$/i', $item, $m ) ) {
                $item = $m[1];
            }

            if ( preg_match( '/(.+):(\d+(?:\.\d+)?)/', $item, $m ) ) {
                $item = $m[2];
            }

            $items[] = $item;
        }

        return $items;
    }

    private function is_empty_value( $value ) {
        if ( is_array( $value ) ) {
            $filled = array();
            foreach ( $value as $val ) {
                if ( ! $this->is_empty_value( $val ) ) {
                    $filled[] = $val;
                }
            }

            return empty( $filled );
        }

        return '' === trim( wp_strip_all_tags( (string) $value ) );
    }

    private function resolve_field_value( $token, $entry, $form, $display_map = array() ) {
        $clean = trim( $token, '{}' );

        // Direct entry value first (supports 1.3 style sub-inputs).
        if ( isset( $entry[ $clean ] ) ) {
            return $this->decode_unicode_literals( $entry[ $clean ] );
        }

        if ( class_exists( 'GFFormsModel' ) && ! empty( $form ) ) {
            $field = GFFormsModel::get_field( $form, $clean );
            if ( $field ) {
                $raw = rgar( $entry, $clean );
                $val = GFCommon::get_lead_field_display( $field, $raw, $entry['currency'] ?? '', true, 'html' );
                if ( '' !== trim( wp_strip_all_tags( (string) $val ) ) ) {
                    return $this->decode_unicode_literals( $val );
                }
            }
        }

        if ( class_exists( 'GFCommon' ) && ! empty( $form ) ) {
            $merge_tag = '{' . $clean . '}';
            $val       = GFCommon::replace_variables( $merge_tag, $form, $entry, false, false, false, 'html' );
            if ( $val !== $merge_tag ) {
                return $this->decode_unicode_literals( $val );
            }
        }

        if ( isset( $display_map[ $clean ] ) ) {
            return $this->decode_unicode_literals( $display_map[ $clean ] );
        }

        return '';
    }

    private function decode_unicode_literals( $value ) {
        if ( is_array( $value ) ) {
            return array_map( array( $this, 'decode_unicode_literals' ), $value );
        }

        if ( is_string( $value ) && preg_match( '/\\\u[0-9a-fA-F]{4}/', $value ) ) {
            $prepared = '"' . str_replace( array( '\\', '"' ), array( '\\\\', '\"' ), $value ) . '"';
            $decoded  = json_decode( $prepared );
            if ( is_string( $decoded ) ) {
                return $decoded;
            }
        }

        return $value;
    }

    private function resolve_field_label( $token, $form, $label_map = array() ) {
        $field_id = trim( $token, '{}' );

        // Prefer live form definitions to prevent stale snapshots from swapping labels/values.
        if ( ! empty( $form['fields'] ) ) {
            foreach ( $form['fields'] as $field ) {
                $fid = is_object( $field ) ? $field->id : ( $field['id'] ?? '' );
                if ( (string) $fid === (string) $field_id ) {
                    return is_object( $field ) ? $field->label : ( $field['label'] ?? $field_id );
                }

                if ( is_object( $field ) && is_array( $field->get_entry_inputs() ) ) {
                    foreach ( $field->get_entry_inputs() as $input ) {
                        $iid = (string) ( $input['id'] ?? '' );
                        if ( (string) $iid === (string) $field_id ) {
                            return $input['label'] ?? $field_id;
                        }
                    }
                }
            }
        }

        if ( isset( $label_map[ $field_id ] ) ) {
            return $label_map[ $field_id ];
        }

        return $field_id;
    }

    private function prepare_fields_for_display( $form, $entry, $snapshot, $hidden_before, $status ) {
        $render_fields = array();
        $field_map     = $this->map_form_fields( $form );
        $display_map   = $snapshot['display'] ?? array();
        $label_map     = $snapshot['labels'] ?? array();

        $field_ids = array_keys( $display_map );
        if ( ! empty( $form['fields'] ) ) {
            foreach ( $form['fields'] as $field ) {
                $fid = is_object( $field ) ? $field->id : ( $field['id'] ?? '' );
                if ( '' === $fid ) {
                    continue;
                }
                $field_ids[] = (string) $fid;

                if ( is_object( $field ) && is_array( $field->get_entry_inputs() ) ) {
                    foreach ( $field->get_entry_inputs() as $input ) {
                        $iid = (string) ( $input['id'] ?? '' );
                        if ( '' !== $iid ) {
                            $field_ids[] = $iid;
                        }
                    }
                }
            }
        }

        $field_ids = array_unique( $field_ids );

        if ( empty( $field_ids ) && ! empty( $snapshot['entry'] ) ) {
            $field_ids = array_keys( $snapshot['entry'] );
        }

        foreach ( $field_ids as $fid ) {
            $field_id     = (string) $fid;
            $value        = array_key_exists( $field_id, $display_map ) ? $display_map[ $field_id ] : $this->resolve_field_value( $field_id, $entry, $form, $display_map );
            $raw          = $this->resolve_raw_value( $field_id, $entry, $snapshot );
            $resolved_label = $label_map[ $field_id ] ?? $this->resolve_field_label( $field_id, $form, $label_map );

            if ( $this->is_empty_value( $value ) ) {
                continue; // فیلد خالی نمایش داده نشود.
            }

            $render_fields[] = array(
                'key'   => $field_id,
                'label' => $resolved_label,
                'value' => $value,
            );
        }

        return $render_fields;
    }

    private function resolve_raw_value( $token, $entry, $snapshot ) {
        $clean = trim( $token, '{}' );

        if ( isset( $entry[ $clean ] ) ) {
            return $entry[ $clean ];
        }

        if ( isset( $snapshot['entry'][ $clean ] ) ) {
            return $snapshot['entry'][ $clean ];
        }

        return '';
    }

    private function map_form_fields( $form ) {
        $map = array();

        if ( empty( $form['fields'] ) ) {
            return $map;
        }

        foreach ( $form['fields'] as $field ) {
            $fid = is_object( $field ) ? $field->id : ( $field['id'] ?? '' );
            if ( '' === $fid ) {
                continue;
            }

            $map[ (string) $fid ] = $field;
        }

        return $map;
    }

    private function is_default_value( $field_id, $raw_value, $field_map ) {
        if ( $this->is_empty_value( $raw_value ) ) {
            return false;
        }

        $parent_id = ( strpos( (string) $field_id, '.' ) !== false ) ? explode( '.', (string) $field_id )[0] : $field_id;
        $field     = $field_map[ (string) $field_id ] ?? $field_map[ (string) $parent_id ] ?? null;

        if ( ! $field ) {
            return false;
        }

        $defaults = array();
        if ( isset( $field->defaultValue ) && '' !== trim( (string) $field->defaultValue ) ) {
            $defaults[] = $field->defaultValue;
        }

        if ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
            foreach ( $field->inputs as $input ) {
                if ( (string) ( $input['id'] ?? '' ) === (string) $field_id && isset( $input['defaultValue'] ) ) {
                    $defaults[] = $input['defaultValue'];
                }
            }
        }

        if ( empty( $defaults ) ) {
            return false;
        }

        if ( is_array( $raw_value ) ) {
            $raw_value = implode( ' ', array_map( 'wp_strip_all_tags', $raw_value ) );
        }

        $raw_clean = wp_strip_all_tags( (string) $raw_value );

        foreach ( $defaults as $default ) {
            if ( trim( (string) $default ) === trim( $raw_clean ) ) {
                return true;
            }
        }

        return false;
    }

    private function format_entry_datetime( $entry, $project_id ) {
        $raw = $entry['date_created'] ?? '';

        if ( empty( $raw ) ) {
            $raw = get_post_time( 'Y-m-d H:i:s', true, $project_id );
        }

        $timestamp = $raw ? ( function_exists( 'GFCommon' ) ? GFCommon::get_local_timestamp( $raw ) : strtotime( $raw ) ) : false;
        if ( ! $timestamp ) {
            $timestamp = get_post_timestamp( $project_id );
        }

        return $timestamp ? wp_date( 'Y/m/d H:i', $timestamp ) : '';
    }

    private function translate_status( $status ) {
        $map = array(
            'new'           => 'در انتظار پذیرش',
            'accepted'      => 'در حال انجام',
            'file_uploaded' => 'فایل ثبت شده',
            'completed'     => 'تکمیل شده',
            'rejected'      => 'رد شده',
            'approved'      => 'تأیید شده',
        );

        return $map[ $status ] ?? $status;
    }

    private function ensure_custom_upload_dir() {
        $path = trailingslashit( WP_CONTENT_DIR ) . 'project_files';
        if ( ! file_exists( $path ) ) {
            wp_mkdir_p( $path );
        }
    }

    public function custom_upload_dir( $dirs ) {
        $base_dir = trailingslashit( WP_CONTENT_DIR ) . 'project_files';
        $base_url = 'https://naghshehbardar.com/project_files';
        $subdir   = ltrim( (string) ( $dirs['subdir'] ?? '' ), '/' );

        wp_mkdir_p( trailingslashit( $base_dir ) . $subdir );

        $dirs['path']    = trailingslashit( $base_dir ) . $subdir;
        $dirs['basedir'] = $base_dir;
        $dirs['url']     = trailingslashit( $base_url ) . $subdir;
        $dirs['baseurl'] = $base_url;

        return $dirs;
    }

    public function history_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>برای مشاهده تاریخچه وارد شوید.</p>';
        }
        $user_id = get_current_user_id();
        $search  = sanitize_text_field( wp_unslash( $_GET['benana_history_search'] ?? '' ) );
        $args    = array(
            'post_type'  => 'project',
            's'          => $search,
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

    public function availability_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p>برای مدیریت وضعیت خود ابتدا وارد شوید.</p>';
        }

        $user_id = get_current_user_id();
        $message = '';

        if ( isset( $_POST['benana_availability_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['benana_availability_nonce'] ) ), 'benana_availability' ) ) {
            $province_id    = sanitize_text_field( wp_unslash( $_POST['user_province_id'] ?? '' ) );
            $city_ids       = array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['user_city_ids'] ?? array() ) ) );
            $is_active      = isset( $_POST['user_is_active'] ) ? sanitize_text_field( wp_unslash( $_POST['user_is_active'] ) ) : '0';
            $inactive_until = ( '1' === $is_active ) ? '' : -1;

            update_user_meta( $user_id, 'user_province_id', $province_id );
            update_user_meta( $user_id, 'user_city_ids', $city_ids );
            update_user_meta( $user_id, 'user_is_active', $is_active === '1' ? '1' : '0' );
            update_user_meta( $user_id, 'user_inactive_until', $inactive_until );

            $message = '<div class="benana-alert success">اطلاعات با موفقیت ذخیره شد.</div>';
        }

        $province_id     = get_user_meta( $user_id, 'user_province_id', true );
        $city_ids        = (array) get_user_meta( $user_id, 'user_city_ids', true );
        $is_active       = get_user_meta( $user_id, 'user_is_active', true );
        $inactive_until  = get_user_meta( $user_id, 'user_inactive_until', true );

        if ( '' === $is_active ) {
            $is_active = '1';
        }

        $provinces = Benana_Automation_Address::get_provinces();
        $cities    = Benana_Automation_Address::get_cities();

        ob_start();
        ?>
        <div class="benana-availability-form">
            <?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <form method="post">
                <?php wp_nonce_field( 'benana_availability', 'benana_availability_nonce' ); ?>
                <div class="field">
                    <label for="benana-province">استان</label>
                    <select id="benana-province" name="user_province_id">
                        <option value="">انتخاب استان</option>
                        <?php foreach ( $provinces as $pid => $pname ) : ?>
                            <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $province_id, $pid ); ?>><?php echo esc_html( $pname ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>شهرها</label>
                    <div class="benana-city-select" data-field="user_city_ids" data-selected="<?php echo esc_attr( implode( ',', $city_ids ) ); ?>">
                        <div class="benana-city-grid">
                            <?php
                            if ( $province_id && isset( $cities[ $province_id ] ) ) {
                                foreach ( $cities[ $province_id ] as $cid => $cname ) {
                                    ?>
                                    <label class="benana-city-item">
                                        <input type="checkbox" name="user_city_ids[]" value="<?php echo esc_attr( $cid ); ?>" <?php checked( in_array( $cid, $city_ids, true ), true ); ?> />
                                        <span><?php echo esc_html( $cname ); ?></span>
                                    </label>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="field benana-availability">
                    <label>وضعیت فعالیت</label>
                    <label class="benana-toggle">
                        <input type="hidden" name="user_is_active" value="0" />
                        <input type="checkbox" name="user_is_active" value="1" <?php checked( $is_active, '1' ); ?> />
                        <span>فعال هستم</span>
                    </label>
                </div>
                <button type="submit" class="button button-primary">ثبت تغییرات</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}
