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
		add_shortcode( 'benana_dashboard', array( $this, 'dashboard_shortcode' ) ); // این خط رو اضافه کن

        add_action( 'template_redirect', array( $this, 'handle_actions' ) );

}
    
public function dashboard_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'show_stats'        => 'yes',
        'show_chart'        => 'yes',
        'show_progress'     => 'yes',
        'show_last_entries' => 'yes',
        'show_main_table'   => 'yes',
        'limit_last'        => 10,
        'per_page'          => 20,
    ), $atts );

    $status_map     = $this->dashboard_get_status_labels();
    $status_counts  = $this->dashboard_get_status_counts( $status_map );
    $recent_entries = $this->dashboard_get_recent_projects( absint( $atts['limit_last'] ) );
    $top_acceptors  = $this->dashboard_get_top_acceptors();
    
    $status_filter = sanitize_text_field( wp_unslash( $_GET['project_status'] ?? '' ) );
    $paged         = max( 1, absint( $_GET['paged'] ?? 1 ) );

    $query_args = array(
        'post_type'      => 'project',
        'posts_per_page' => absint( $atts['per_page'] ),
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( $status_filter ) {
        $query_args['meta_query'] = array(
            array(
                'key'   => 'project_status',
                'value' => $status_filter,
            ),
        );
    }

    $projects  = new WP_Query( $query_args );
    $provinces = Benana_Automation_Address::get_provinces();

    ob_start();
    ?>
    <div class="wrap benana-admin benana-shortcode-dashboard">
        
        <?php if ( $atts['show_stats'] === 'yes' ) : ?>
        <div class="benana-report-grid">
            <div class="report-card">
                <h3>مجموع پروژه‌ها</h3>
                <strong><?php echo esc_html( $status_counts['total'] ?? 0 ); ?></strong>
            </div>
            <?php foreach ( $status_map as $status_key => $status_label ) : ?>
                <div class="report-card">
                    <h3><?php echo esc_html( $status_label ); ?></h3>
                    <strong><?php echo esc_html( $status_counts['statuses'][ $status_key ] ?? 0 ); ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( $atts['show_chart'] === 'yes' || $atts['show_progress'] === 'yes' ) : ?>
        <div class="benana-chart-wrapper">
            <?php if ( $atts['show_chart'] === 'yes' ) : ?>
            <div class="benana-chart-card">
                <h3>نمودار وضعیت‌ها</h3>
                <?php $total_for_chart = $status_counts['total'] ?: 1; ?>
                <div class="benana-bar-chart">
                    <?php foreach ( $status_map as $status_key => $status_label ) :
                        $count     = $status_counts['statuses'][ $status_key ] ?? 0;
                        $percent   = round( ( $count / $total_for_chart ) * 100 );
                        $bar_class = 'status-' . $status_key;
                        ?>
                        <div class="benana-bar-row">
                            <div class="benana-bar-label"><?php echo esc_html( $status_label ); ?></div>
                            <div class="benana-bar-track">
                                <span class="benana-bar-fill <?php echo esc_attr( $bar_class ); ?>" style="width: <?php echo esc_attr( $percent ); ?>%"></span>
                                <span class="benana-bar-count"><?php echo esc_html( $count ); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ( $atts['show_progress'] === 'yes' ) : ?>
            <div class="benana-chart-card">
                <h3>پیشرفت تکمیل</h3>
                <?php
                $completed_count = $status_counts['statuses']['completed'] ?? 0;
                $total_projects  = $status_counts['total'] ?: 0;
                $completion_pct  = $total_projects ? round( ( $completed_count / $total_projects ) * 100 ) : 0;
                ?>
                <div class="benana-progress">
                    <div class="benana-progress-track">
                        <span class="benana-progress-fill" style="width: <?php echo esc_attr( $completion_pct ); ?>%"></span>
                    </div>
                    <p class="benana-progress-label"><?php echo esc_html( $completion_pct ); ?>٪ از پروژه‌ها تکمیل شده است.</p>
                </div>

                <?php if ( ! empty( $top_acceptors ) ) : ?>
                    <h4>فعال‌ترین پذیرنده‌ها</h4>
                    <table class="table-banana widefat fixed benana-top-acceptors">
                        <thead>
                            <tr>
                                <th>نام کاربر</th>
                                <th>تعداد پذیرش</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $top_acceptors as $acceptor ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $acceptor['user']->display_name ); ?></td>
                                    <td><?php echo esc_html( $acceptor['count'] ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ( $atts['show_last_entries'] === 'yes' ) : ?>
        <h2>آخرین ورودی‌ها</h2>
        <table class="table-banana widefat fixed striped last-entries">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>عنوان</th>
                    <th>وضعیت</th>
                    <th>استان/شهر</th>
                    <th>آخرین بروزرسانی</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( $recent_entries->have_posts() ) :
                    while ( $recent_entries->have_posts() ) :
                        $recent_entries->the_post();
                        $project_id   = get_the_ID();
                        $province_id  = get_post_meta( $project_id, 'project_province_id', true );
                        $city_id      = get_post_meta( $project_id, 'project_city_id', true );
                        $province     = isset( $provinces[ $province_id ] ) ? $provinces[ $province_id ] : '—';
                        $city         = Benana_Automation_Address::get_city_name( $province_id, $city_id );
                        $status       = get_post_meta( $project_id, 'project_status', true );
                        $status_label = $status_map[ $status ] ?? 'نامشخص';
                        ?>
                        <tr>
                            <td>#<?php echo esc_html( $project_id ); ?></td>
                            <td>
                                <?php 
                                $gf_entry_id = get_post_meta( $project_id, 'gf_entry_id', true );
                                $gf_form_id  = get_post_meta( $project_id, 'gf_form_id', true );
                                
                                if ( $gf_entry_id && $gf_form_id ) {
                                    $gf_link = add_query_arg(
                                        array(
                                            'page' => 'gf_entries',
                                            'view' => 'entry',
                                            'id'   => $gf_form_id,
                                            'lid'  => $gf_entry_id,
                                        ),
                                        admin_url( 'admin.php' )
                                    );
                                    echo '<a href="' . esc_url( $gf_link ) . '">مشاهده پروژه</a>';
                                } else {
                                    echo '—';
                                }
                                ?>
                            </td>
                            <td class="status status-<?php echo esc_attr( $status ?: 'none' ); ?>"><?php echo esc_html( $status_label ); ?></td>
                            <td><?php echo esc_html( $province . ' / ' . $city ); ?></td>
                            <td><?php echo esc_html( get_the_modified_date( 'Y/m/d H:i' ) ); ?></td>
                        </tr>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <tr><td colspan="5">موردی یافت نشد.</td></tr>
                    <?php
                endif;
                ?>
            </tbody>
        </table>
        <?php endif; ?>

        <?php if ( $atts['show_main_table'] === 'yes' ) : ?>
        <h2 class="ascjnaksjcans">لیست پروژه‌ها</h2>
        
        <form method="get" class="benana-filters">
            <?php if ( is_page() ) : ?>
                <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>" />
            <?php endif; ?>
            <label for="project_status">فیلتر وضعیت:</label>
            <select name="project_status" id="project_status">
                <option value="">همه وضعیت‌ها</option>
                <?php foreach ( $status_map as $status_key => $status_label ) : ?>
                    <option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status_filter, $status_key ); ?>><?php echo esc_html( $status_label ); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button">اعمال فیلتر</button>
        </form>

        <table class="table-banana widefat fixed striped main-entries">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>عنوان</th>
                    <th>ورودی GF</th>
                    <th>وضعیت</th>
                    <th>استان/شهر</th>
                    <th>کارگزاران</th>
                    <th>پذیرفته‌شده توسط</th>
                    <th>فایل</th>
                    <th>تاریخ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( $projects->have_posts() ) :
                    while ( $projects->have_posts() ) :
                        $projects->the_post();
                        $project_id      = get_the_ID();
                        $province_id     = get_post_meta( $project_id, 'project_province_id', true );
                        $city_id         = get_post_meta( $project_id, 'project_city_id', true );
                        $province_label  = isset( $provinces[ $province_id ] ) ? $provinces[ $province_id ] : '—';
                        $city_label      = Benana_Automation_Address::get_city_name( $province_id, $city_id );
                        if ( '' === $city_label && ! empty( $city_id ) ) {
                            $city_label = $city_id;
                        }
                        $status          = get_post_meta( $project_id, 'project_status', true );
                        $status_label    = $status_map[ $status ] ?? 'نامشخص';
                        $assigned_raw    = get_post_meta( $project_id, 'assigned_users', true );
                        $assigned_users  = $assigned_raw ? json_decode( $assigned_raw, true ) : array();
                        $accepted_by     = get_post_meta( $project_id, 'accepted_by', true );
                        $accepted_user   = $accepted_by ? get_user_by( 'id', $accepted_by ) : false;
                        $file_url        = get_post_meta( $project_id, 'file_url', true );
                        $gf_entry_id     = get_post_meta( $project_id, 'gf_entry_id', true );
                        $gf_form_id      = get_post_meta( $project_id, 'gf_form_id', true );
                        $gf_link         = ( $gf_entry_id && $gf_form_id ) ? add_query_arg(
                            array(
                                'page' => 'gf_entries',
                                'view' => 'entry',
                                'id'   => $gf_form_id,
                                'lid'  => $gf_entry_id,
                            ),
                            admin_url( 'admin.php' )
                        ) : '';
                        ?>
                        <tr>
                            <td>#<?php echo esc_html( $project_id ); ?></td>
                            <td><a href="<?php echo esc_url( get_edit_post_link( $project_id ) ); ?>"><?php the_title(); ?></a></td>
                            <td><?php echo $gf_link ? '<a href="' . esc_url( $gf_link ) . '">مشاهده ورودی</a>' : '—'; ?></td>
                            <td class="status status-<?php echo esc_attr( $status ?: 'none' ); ?>"><?php echo esc_html( $status_label ); ?></td>
                            <td><?php echo esc_html( $province_label . ' / ' . $city_label ); ?></td>
                            <td><?php echo esc_html( is_array( $assigned_users ) ? count( $assigned_users ) : 0 ); ?></td>
                            <td><?php echo $accepted_user ? esc_html( $accepted_user->display_name ) : '—'; ?></td>
                            <td><?php echo $file_url ? '<a href="' . esc_url( $file_url ) . '" target="_blank">مشاهده</a>' : '—'; ?></td>
                            <td><?php echo esc_html( get_the_date( 'Y/m/d H:i' ) ); ?></td>
                        </tr>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <tr><td colspan="9">موردی یافت نشد.</td></tr>
                    <?php
                endif;
                ?>
            </tbody>
        </table>

        <?php
        $pagination = paginate_links(
            array(
                'total'    => $projects->max_num_pages,
                'current'  => $paged,
                'type'     => 'array',
                'add_args' => array(
                    'project_status' => $status_filter,
                ),
            )
        );

        if ( $pagination ) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( implode( ' ', $pagination ) ) . '</div></div>';
        }
        ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

private function dashboard_get_status_labels() {
    return array(
        'new'       => 'جدید',
        'accepted'  => 'پذیرفته‌شده',
        'completed' => 'تکمیل‌شده',
    );
}

private function dashboard_get_status_counts( $status_map ) {
    global $wpdb;
    
    $counts = array();
    
    foreach ( $status_map as $status_key => $status_label ) {
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'project'
             AND p.post_status = 'publish'
             AND pm.meta_key = 'project_status'
             AND pm.meta_value = %s",
            $status_key
        ) );
        
        $counts[ $status_key ] = (int) $count;
    }

    $total = $wpdb->get_var(
        "SELECT COUNT(*) 
         FROM {$wpdb->posts} 
         WHERE post_type = 'project' 
         AND post_status = 'publish'"
    );

    return array(
        'total'    => (int) $total,
        'statuses' => $counts,
    );
}

private function dashboard_get_recent_projects( $limit = 10 ) {
    return new WP_Query(
        array(
            'post_type'      => 'project',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        )
    );
}

private function dashboard_get_top_acceptors( $limit = 3 ) {
    $projects = get_posts(
        array(
            'post_type'      => 'project',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        )
    );

    $accept_counts = array();
    foreach ( $projects as $project_id ) {
        $accepted_by = get_post_meta( $project_id, 'accepted_by', true );
        if ( empty( $accepted_by ) ) {
            continue;
        }

        if ( ! isset( $accept_counts[ $accepted_by ] ) ) {
            $accept_counts[ $accepted_by ] = 0;
        }
        $accept_counts[ $accepted_by ]++;
    }

    arsort( $accept_counts );

    $top = array();
    foreach ( $accept_counts as $user_id => $count ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            continue;
        }

        $top[] = array(
            'user'  => $user,
            'count' => $count,
        );

        if ( count( $top ) >= $limit ) {
            break;
        }
    }

    return $top;
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
            foreach ( $form['fields'] as $field ) {
                if ( is_array( $field ) && class_exists( 'GF_Fields' ) ) {
                    $field = GF_Fields::create( $field );
                }

                if ( ! is_object( $field ) ) {
                    continue;
                }

                $fields[] = $field;
            }
        }

        if ( class_exists( 'GFCommon' ) ) {
            foreach ( $fields as $field ) {
                $field_id = (string) $field->id;
                $currency = $entry['currency'] ?? '';

                $input_type = is_callable( array( $field, 'get_input_type' ) ) ? $field->get_input_type() : '';
                if ( in_array( $input_type, array( 'section', 'captcha', 'html', 'password', 'page' ), true ) ) {
                    continue;
                }

                if ( ! $is_accepted && $this->should_hide_before_acceptance( $field_id, $field, $protected_fields ) ) {
                    continue;
                }

                if ( is_array( $field->fields ) ) {
                    $field->nestingLevel = 0;
                }

                $value = class_exists( 'RGFormsModel' ) ? RGFormsModel::get_lead_field_value( $entry, $field ) : rgar( $entry, $field_id );

                $display = $this->get_field_display_html( $field, $value, $entry, $form );

                if ( $this->is_empty_value( $display ) ) {
                    if ( isset( $snapshot_display[ $field_id ] ) ) {
                        $display = $snapshot_display[ $field_id ];
                    } elseif ( isset( $snapshot_entry[ $field_id ] ) ) {
                        $display = $snapshot_entry[ $field_id ];
                    } elseif ( isset( $entry[ $field_id ] ) ) {
                        $display = $entry[ $field_id ];
                    }

                    $display = $this->get_field_display_html( $field, $display, $entry, $form );
                }

                if ( $this->is_empty_value( $display ) && ! $display_empty ) {
                    continue;
                }

                $label = $this->get_field_display_label( $field, $field_id, $form, $label_map );

                if ( isset( $label_map[ $field_id ] ) && ( '' === trim( (string) $label ) || (string) $field_id === trim( (string) $label ) ) ) {
                    $label = $label_map[ $field_id ];
                }

                if ( $this->is_empty_value( $display ) ) {
                    $display = '&nbsp;';
                }

                $render[]  = array(
                    'key'   => $field_id,
                    'label' => $label,
                    'value' => $this->ensure_html_value( $this->decode_unicode_literals( $display ) ),
                );
                $handled[] = $field_id;

                if ( is_object( $field ) && isset( $field->inputs ) && is_array( $field->inputs ) ) {
                    foreach ( $field->inputs as $input ) {
                        if ( isset( $input['id'] ) ) {
                            $handled[] = (string) $input['id'];
                        }
                    }
                }

                if ( $this->is_empty_value( $display ) && ! $display_empty ) {
                    continue;
                }

                $label = $this->get_field_display_label( $field, $field_id, $form, $label_map );

                if ( isset( $label_map[ $field_id ] ) && ( '' === trim( (string) $label ) || (string) $field_id === trim( (string) $label ) ) ) {
                    $label = $label_map[ $field_id ];
                }

                if ( $this->is_empty_value( $display ) ) {
                    $display = '&nbsp;';
                }

                $render[]  = array(
                    'key'   => $field_id,
                    'label' => $label,
                    'value' => $this->decode_unicode_literals( $display ),
                );
                $handled[] = $field_id;
            }
        }

        if ( empty( $render ) && ! empty( $entry ) ) {
            foreach ( $entry as $field_id => $display_value ) {
                $field_key = (string) $field_id;
                if ( in_array( $field_key, $handled, true ) ) {
                    continue;
                }

                if ( ! preg_match( '/^\d+(?:\.\d+)?$/', $field_key ) ) {
                    continue;
                }

                if ( ! $is_accepted && $this->should_hide_before_acceptance( $field_key, null, $protected_fields ) ) {
                    continue;
                }

                if ( $this->is_empty_value( $display_value ) ) {
                    if ( isset( $snapshot_display[ $field_key ] ) ) {
                        $display_value = $snapshot_display[ $field_key ];
                    } elseif ( isset( $snapshot_entry[ $field_key ] ) ) {
                        $display_value = $snapshot_entry[ $field_key ];
                    }
                }

                if ( $this->is_empty_value( $display_value ) && ! $display_empty ) {
                    continue;
                }

                $label = $label_map[ $field_key ] ?? $this->resolve_field_label( $field_key, $form, $label_map );
                if ( '' === trim( (string) $label ) ) {
                    continue;
                }

                if ( class_exists( 'GFFormsModel' ) && ! empty( $form ) ) {
                    $field_obj = GFFormsModel::get_field( $form, $field_key );
                    if ( $field_obj ) {
                        $display_value = $this->get_field_display_html( $field_obj, $display_value, $entry, $form );
                    }
                }

                if ( $this->is_empty_value( $display_value ) ) {
                    $display_value = '&nbsp;';
                }

                $render[] = array(
                    'key'   => $field_key,
                    'label' => $label,
                    'value' => $this->ensure_html_value( $this->decode_unicode_literals( $display_value ) ),
                );
            }
        }

        if ( empty( $render ) && ! empty( $snapshot_display ) ) {
            foreach ( $snapshot_display as $field_id => $display_value ) {
                $field_key = (string) $field_id;
                if ( in_array( $field_key, $handled, true ) ) {
                    continue;
                }

                if ( ! preg_match( '/^\d+(?:\.\d+)?$/', $field_key ) ) {
                    continue;
                }

                if ( ! $is_accepted && $this->should_hide_before_acceptance( $field_key, null, $protected_fields ) ) {
                    continue;
                }

                if ( $this->is_empty_value( $display_value ) && ! $display_empty ) {
                    continue;
                }

                $label = $label_map[ $field_key ] ?? $this->resolve_field_label( $field_key, $form, $label_map );
                if ( '' === trim( (string) $label ) ) {
                    continue;
                }

                if ( class_exists( 'GFFormsModel' ) && ! empty( $form ) ) {
                    $field_obj = GFFormsModel::get_field( $form, $field_key );
                    if ( $field_obj ) {
                        $display_value = $this->get_field_display_html( $field_obj, $display_value, $entry, $form );
                    }
                }

                if ( $this->is_empty_value( $display_value ) ) {
                    $display_value = '&nbsp;';
                }

                $render[] = array(
                    'key'   => $field_key,
                    'label' => $label,
                    'value' => $this->ensure_html_value( $this->decode_unicode_literals( $display_value ) ),
                );
            }
        }

        return $render;
    }

    private function ensure_html_value( $value ) {
        if ( is_array( $value ) ) {
            return array_map( array( $this, 'ensure_html_value' ), $value );
        }

        if ( ! is_string( $value ) ) {
            return $value;
        }

        if ( false !== strpos( $value, '<' ) && false !== strpos( $value, '>' ) ) {
            return $value;
        }

        $trimmed = trim( $value );

        if ( filter_var( $trimmed, FILTER_VALIDATE_URL ) ) {
            return '<a href="' . esc_url( $trimmed ) . '" target="_blank" rel="noreferrer noopener">' . esc_html( $trimmed ) . '</a>';
        }

        return $value;
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
        if ( $atts['type'] === 'pending_count' ) {
            $args = array(
                'post_type'      => 'project',
                'posts_per_page' => -1,
                'meta_query'     => array(
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
                    array(
                        'key'   => 'project_status',
                        'value' => 'new',
                    ),
                ),
                'no_found_rows'  => true,
                'fields'         => 'ids',
            );
            $query = new WP_Query( $args );
            $count = $query->post_count;
        }
        if ( $atts['type'] === 'completed_count' ) {
            $args = array(
                'post_type'      => 'project',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'   => 'accepted_by',
                        'value' => $user_id,
                    ),
                    array(
                        'key'   => 'project_status',
                        'value' => 'completed',
                    ),
                ),
                'no_found_rows'  => true,
                'fields'         => 'ids',
            );
            $query = new WP_Query( $args );
            $count = $query->post_count;
        }
        return (string) intval( $count );
    }



    public function pending_stats_shortcode( $atts ) {
        $atts         = (array) $atts;
        $atts['type'] = 'pending_count';
        return $this->stats_shortcode( $atts );
    }

public function availability_shortcode() {
    if ( ! is_user_logged_in() ) {
        return '<p class="login-error-benana">برای مدیریت وضعیت خود ابتدا وارد شوید.</p>';
    }

    $user_id = get_current_user_id();
    $message = '';

    // فقط وضعیت فعالیت از فرم خوانده می‌شود
    if (
        isset( $_POST['benana_availability_nonce'] )
        && wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['benana_availability_nonce'] ) ),
            'benana_availability'
        )
    ) {
        // اگر چک‌باکس وجود داشته باشد یعنی فعاله، اگر نباشد یعنی غیرفعال
        $is_active = isset( $_POST['user_is_active'] ) ? '1' : '0';
        $inactive_until = ( '1' === $is_active ) ? '' : -1;

        // استان و شهر دیگر از فرم نمی‌آیند و اینجا آپدیت نمی‌شوند
        update_user_meta( $user_id, 'user_is_active', $is_active );
        update_user_meta( $user_id, 'user_inactive_until', $inactive_until );

        $message = '<div class="benana-alert success">وضعیت شما به‌روزرسانی شد.</div>';
    }

    // فقط برای نمایش
    $province_id    = get_user_meta( $user_id, 'user_province_id', true );
    $city_ids       = (array) get_user_meta( $user_id, 'user_city_ids', true );
    $is_active      = get_user_meta( $user_id, 'user_is_active', true );
    $inactive_until = get_user_meta( $user_id, 'user_inactive_until', true );

    if ( '' === $is_active ) {
        $is_active = '1';
    }

    $provinces = Benana_Automation_Address::get_provinces();
    $cities    = Benana_Automation_Address::get_cities();

    // متن خوانا برای استان و شهرها
    $province_name = '';
    $city_names    = array();

    if ( $province_id && isset( $provinces[ $province_id ] ) ) {
        $province_name = $provinces[ $province_id ];
    }

    if ( $province_id && isset( $cities[ $province_id ] ) ) {
        foreach ( $city_ids as $cid ) {
            if ( isset( $cities[ $province_id ][ $cid ] ) ) {
                $city_names[] = $cities[ $province_id ][ $cid ];
            }
        }
    }

    ob_start();
    ?>
    <div class="benana-availability-form">
        <?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <form method="post">
            <?php wp_nonce_field( 'benana_availability', 'benana_availability_nonce' ); ?>

            <!-- ۱. اطلاعات: کاربر کجا می‌تونه فعالیت کنه -->
            <div class="field">
                <label>محدوده فعالیت شما</label>
                <p class="benana-coverage">
                    <?php
                    if ( $province_name ) {
                        echo 'استان ' . esc_html( $province_name );

                        if ( ! empty( $city_names ) ) {
                            echo ' – شهرهای: ' . esc_html( implode( '، ', $city_names ) );
                        } else {
                            echo ' (شهر ثبت‌شده‌ای وجود ندارد)';
                        }
                    } else {
                        echo 'استان و شهرهای فعالیت شما هنوز ثبت نشده است.';
                    }
                    ?>
                </p>
            </div>

            <!-- ۲. فقط وضعیت فعالیت قابل تغییر است -->
            <div class="field benana-availability">
                <label>وضعیت فعالیت</label>
                <label class="benana-toggle">
                    <input
                        type="checkbox"
                        name="user_is_active"
                        value="1"
                        <?php checked( $is_active, '1' ); ?>
                        onchange="this.form.submit();"  <!-- این خط باعث اتو‌سیو می‌شود -->
                    <span><?php echo $is_active === '1' ? 'فعال هستم' : 'غیرفعال هستم'; ?></span>
                </label>
            </div>

            <!-- ۳. دکمه‌ی ذخیره مخفی، فقط برای fallback اگر JS غیرفعال بود -->
            <button type="submit" class="button button-primary benana-availability-submit" style="display:none;">
                ثبت تغییرات
            </button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

	}
