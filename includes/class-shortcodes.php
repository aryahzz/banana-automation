<?php
class Benana_Automation_Shortcodes {
    private $field_label_overrides = array(
        '6'    => 'متراژ حدودی زمین',
        '7'    => 'مورد استفاده برای؟',
        '9'    => 'زمین مرز دارد؟',
        '10'   => 'انتخاب مهندس',
        '16'   => 'شخص مهندسی مد نظر دارید؟',
        '17'   => 'تلفن',
        '20'   => 'خدمت مورد نیاز (تفکیک اراضی)',
        '21'   => 'خدمت مورد نیاز (پروژه عمرانی)',
        '22'   => 'خدمت مورد نیاز (راهسازی)',
        '23'   => 'خدمت مورد نیاز (UTM)',
        '24'   => 'خدمت مورد نیاز (نقشه ثبتی)',
        '27'   => 'خدمت مورد نیاز (نقشه هوایی)',
        '28'   => 'آدرس محل پروژه',
        '28.1' => 'آدرس',
        '28.3' => 'شهر',
        '28.4' => 'استان',
        '28.6' => 'کشور',
        '37'   => 'مساحت سطح اشغال',
        '42'   => 'تعداد طبقات',
        '43'   => 'طول مسیر',
        '45'   => 'مساحت سطح اشغال',
        '48'   => 'نوع فونداسیون',
        '50'   => 'تعداد ستون',
        '51'   => 'زمان شاغولی',
        '53'   => 'قوس',
        '54'   => 'وید',
        '56'   => 'تعداد سقف',
        '57'   => 'تعداد راه پله',
        '58'   => 'مساحت سقف',
        '59'   => 'نوع ستون',
        '62'   => 'تعداد ستون',
        '64'   => 'طول مسیر',
        '66'   => 'نوع پروژه',
        '67'   => 'متراژ',
        '69'   => 'مساحت زمین',
        '70'   => 'تعداد قطعات',
        '72'   => 'پرداخت',
        '81'   => 'پیش پرداخت  تفکیک اراضی',
        '82'   => 'پیش پرداخت  حجم عملیات خاکی',
        '84'   => 'پیش پرداخت  تهیه پروفیل طولی و عرضی',
        '85'   => 'پیش پرداخت  طراحی مسیر',
        '86'   => 'پیش پرداخت  آکس ستون',
        '87'   => 'پیش پرداخت  پیاده سازی فونداسیون',
        '88'   => 'پیش پرداخت  هزینه شاقولی ستون',
        '90'   => 'تعداد قوس',
        '91'   => 'هزینه پیاده سازی دامنه',
        '92'   => 'دسته',
        '94'   => 'نام و نام خانوادگی',
        '96'   => 'پیش پرداخت',
        '98'   => 'مجموع',
        '101'  => 'لوکیشن',
        '115'  => 'تاریخ',
        '117'  => 'نقشه برداری',
    );
    public function __construct() {
        add_shortcode( 'project_inbox', array( $this, 'inbox_shortcode' ) );
        add_shortcode( 'project_user_history', array( $this, 'history_shortcode' ) );
        add_shortcode( 'project_user_stats', array( $this, 'stats_shortcode' ) );
        add_shortcode( 'benana_stats', array( $this, 'stats_shortcode' ) );
        add_shortcode( 'benana_pending_stats', array( $this, 'pending_stats_shortcode' ) );
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
            $result['message'] = 'فقط مهندس پذیرنده می‌تواند فایل بارگذاری کند.';
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

        if ( ! empty( $entry ) && class_exists( 'GFAPI' ) ) {
            $entry[ $upload_field ] = $value;
            $update_result          = GFAPI::update_entry( $entry );
            if ( is_wp_error( $update_result ) ) {
                $result['message'] = $update_result->get_error_message();
                return $result;
            }
        }
        Benana_Automation_Project_Handler::upload_file( $project_id, $user_id, $uploaded_urls );

        $result['status']  = 'uploaded';
        $result['message'] = '';

        return $result;
    }

    public function inbox_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p class="login-error-benana">برای مشاهده پروژه‌ها وارد شوید.</p>';
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
            'posts_per_page' => -1,
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
            $entry_dates[ $project->ID ] = $this->format_entry_datetime( Benana_Automation_Project_Handler::get_entry_for_project( $project->ID ), $project->ID );
        }
        ob_start();
        include BENANA_AUTOMATION_PATH . 'templates/inbox.php';
        return ob_get_clean();
    }

    public function project_detail_shortcode() {
        if ( ! is_user_logged_in() ) {
            return '<p class="login-error-benana">برای مشاهده پروژه‌ها وارد شوید.</p>';
        }

        $project_id = absint( $_GET['project_id'] ?? 0 );
        $action_msg = sanitize_text_field( wp_unslash( $_GET['benana_action_status'] ?? '' ) );
        $action_txt = sanitize_text_field( wp_unslash( $_GET['benana_action_message'] ?? '' ) );
        if ( ! $project_id ) {
            return '<p class="not-selected-benana">پروژه‌ای انتخاب نشده است.</p>';
        }

        $project = get_post( $project_id );
        if ( ! $project || 'project' !== $project->post_type ) {
            return '<p class="notfound-error-benana">پروژه پیدا نشد.</p>';
        }

        $user_id = get_current_user_id();
        if ( ! Benana_Automation_Project_Handler::user_is_assignee( $project_id, $user_id ) ) {
            return '<p class="noaccess-error-benana" >دسترسی به این پروژه ندارید.</p>';
        }

        $entry    = Benana_Automation_Project_Handler::get_entry_for_project( $project_id );
        $snapshot = Benana_Automation_Project_Handler::get_entry_snapshot( $project_id );
        $form_id  = get_post_meta( $project_id, 'gf_form_id', true );
        $status   = get_post_meta( $project_id, 'project_status', true );

        if ( empty( $form_id ) && ! empty( $entry['form_id'] ) ) {
            $form_id = $entry['form_id'];
        }

        $form = ( function_exists( 'GFAPI' ) && ! empty( $form_id ) ) ? GFAPI::get_form( $form_id ) : array();
        $settings     = Benana_Automation_Settings::get_settings();
        $upload_field = '';
        if ( isset( $settings['gravity_forms'][ $form_id ] ) ) {
            $gf_settings  = $settings['gravity_forms'][ $form_id ];
            $upload_field = $gf_settings['file_field'] ?? ( $gf_settings['upload_field'] ?? '' );
        }

        $accepted      = intval( get_post_meta( $project_id, 'accepted_by', true ) ) === $user_id;
        $render_fields = $this->prepare_fields_for_display( $form, $entry, $snapshot, $accepted );
        $entry_date    = $this->format_entry_datetime( empty( $entry ) ? $snapshot['entry'] : $entry, $project_id );

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
                    return $this->get_field_display_label( $field, $field_id, $form, $label_map );
                }

                $inputs = array();
                if ( is_object( $field ) ) {
                    if ( is_callable( array( $field, 'get_entry_inputs' ) ) && is_array( $field->get_entry_inputs() ) ) {
                        $inputs = $field->get_entry_inputs();
                    } elseif ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
                        $inputs = $field->inputs;
                    }
                } elseif ( is_array( $field ) && isset( $field['inputs'] ) && is_array( $field['inputs'] ) ) {
                    $inputs = $field['inputs'];
                }

                foreach ( $inputs as $input ) {
                    $iid = (string) ( $input['id'] ?? '' );
                    if ( (string) $iid === (string) $field_id ) {
                        if ( '' !== trim( (string) ( $input['name'] ?? '' ) ) ) {
                            return $input['name'];
                        }

                        return $input['label'] ?? $field_id;
                    }
                }
            }
        }

        if ( isset( $label_map[ $field_id ] ) ) {
            return $label_map[ $field_id ];
        }

        return $field_id;
    }

    private function resolve_field_name_property( $field ) {
        if ( is_object( $field ) ) {
            if ( isset( $field->inputName ) && '' !== trim( (string) $field->inputName ) ) {
                return $field->inputName;
            }

            if ( isset( $field->name ) && '' !== trim( (string) $field->name ) ) {
                return $field->name;
            }

            if ( is_callable( array( $field, 'get_field_input_name' ) ) ) {
                $name = $field->get_field_input_name();
                if ( '' !== trim( (string) $name ) ) {
                    return $name;
                }
            }
        }

        if ( is_array( $field ) ) {
            if ( isset( $field['inputName'] ) && '' !== trim( (string) $field['inputName'] ) ) {
                return $field['inputName'];
            }

            if ( isset( $field['name'] ) && '' !== trim( (string) $field['name'] ) ) {
                return $field['name'];
            }
        }

        return '';
    }

    private function get_field_display_label( $field, $field_id, $form = array(), $label_map = array() ) {
        if ( isset( $label_map[ $field_id ] ) ) {
            return $label_map[ $field_id ];
        }

        $input = ( class_exists( 'GFFormsModel' ) && ! empty( $form ) ) ? GFFormsModel::get_input( $form, $field_id ) : false;
        if ( is_array( $input ) ) {
            if ( '' !== trim( (string) ( $input['name'] ?? '' ) ) ) {
                return $input['name'];
            }

            if ( '' !== trim( (string) ( $input['label'] ?? '' ) ) ) {
                return $input['label'];
            }
        }

        if ( class_exists( 'GFFormsModel' ) && is_object( $field ) ) {
            $label = GFFormsModel::get_label( $field, $field_id, false, true );
            if ( '' !== trim( (string) $label ) && (string) $label !== (string) $field_id ) {
                return $label;
            }
        }

        $name = $this->resolve_field_name_property( $field );
        if ( '' !== trim( (string) $name ) ) {
            return $name;
        }

        if ( class_exists( 'GFCommon' ) && is_object( $field ) ) {
            $label = GFCommon::get_label( $field, $field_id, false, true );
            if ( '' !== trim( (string) $label ) ) {
                return $label;
            }
        }

        if ( isset( $label_map[ $field_id ] ) ) {
            return $label_map[ $field_id ];
        }

        return is_object( $field ) ? ( $field->label ?? $field_id ) : ( $field['label'] ?? $field_id );
    }

    private function get_field_display_html( $field, $value, $entry, $form ) {
        if ( ! class_exists( 'GFCommon' ) || ! is_object( $field ) ) {
            return $value;
        }

        $currency = is_array( $entry ) ? ( $entry['currency'] ?? '' ) : '';

        $display = GFCommon::get_lead_field_display( $field, $value, $currency, true, 'html' );
        $display = apply_filters( 'gform_entry_field_value', $display, $field, $entry, $form );

        return $display;
    }

    private function should_hide_before_acceptance( $field_id, $field, $protected_fields ) {
        if ( in_array( (string) $field_id, $protected_fields, true ) ) {
            return true;
        }

        $inputs = array();

        if ( is_object( $field ) ) {
            if ( is_callable( array( $field, 'get_entry_inputs' ) ) && is_array( $field->get_entry_inputs() ) ) {
                $inputs = $field->get_entry_inputs();
            } elseif ( isset( $field->inputs ) && is_array( $field->inputs ) ) {
                $inputs = $field->inputs;
            }
        } elseif ( is_array( $field ) && isset( $field['inputs'] ) ) {
            $inputs = $field['inputs'];
        }

        foreach ( $inputs as $input ) {
            if ( in_array( (string) ( $input['id'] ?? '' ), $protected_fields, true ) ) {
                return true;
            }
        }

        return false;
    }

    private function prepare_fields_for_display( $form, $entry, $snapshot = array(), $is_accepted = false ) {
        $entry            = is_array( $entry ) ? $entry : (array) $entry;
        $form             = is_array( $form ) ? $form : array();
        $snapshot         = is_array( $snapshot ) ? $snapshot : array();
        $snapshot_entry   = isset( $snapshot['entry'] ) && is_array( $snapshot['entry'] ) ? $snapshot['entry'] : array();
        $snapshot_display = isset( $snapshot['display'] ) && is_array( $snapshot['display'] ) ? $snapshot['display'] : array();
        $snapshot_labels  = isset( $snapshot['labels'] ) && is_array( $snapshot['labels'] ) ? $snapshot['labels'] : array();
        $label_map        = $snapshot_labels;
        $fields           = array();
        $render           = array();
        $handled          = array();
        $lead_for_filter  = ! empty( $entry ) ? $entry : $snapshot_entry;
        $display_empty    = apply_filters( 'gform_entry_detail_grid_display_empty_fields', false, $form, $lead_for_filter );

        $protected_fields = array( '94', '17', '28.1', '101' );

        if ( empty( $entry ) && ! empty( $snapshot_entry ) ) {
            $entry = $snapshot_entry;
        }

        if ( ! empty( $form['fields'] ) ) {
            foreach ( $form['fields'] as $fiel