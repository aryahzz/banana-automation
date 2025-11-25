<?php
class Benana_Automation_CPT {
    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post_project', array( $this, 'save_meta' ) );
    }

    public function register_cpt() {
        $labels = array(
            'name'               => 'پروژه‌ها',
            'singular_name'      => 'پروژه',
            'menu_name'          => 'پروژه‌ها',
            'name_admin_bar'     => 'پروژه',
            'add_new'            => 'پروژه جدید',
            'add_new_item'       => 'افزودن پروژه جدید',
            'new_item'           => 'پروژه جدید',
            'edit_item'          => 'ویرایش پروژه',
            'view_item'          => 'نمایش پروژه',
            'all_items'          => 'همه پروژه‌ها',
            'search_items'       => 'جستجوی پروژه‌ها',
            'parent_item_colon'  => 'پروژه مادر:',
            'not_found'          => 'پروژه‌ای یافت نشد',
            'not_found_in_trash' => 'در زباله‌دان پروژه‌ای نیست',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'has_archive'        => true,
            'rewrite'            => array( 'slug' => 'projects' ),
            'supports'           => array( 'title', 'editor', 'author' ),
            'show_in_rest'       => true,
            'menu_icon'          => 'dashicons-clipboard',
        );

        register_post_type( 'project', $args );
    }

    public function register_meta_boxes() {
        add_meta_box( 'benana_project_meta', 'اطلاعات پروژه', array( $this, 'meta_box_html' ), 'project', 'normal', 'high' );
    }

    public function meta_box_html( $post ) {
        $meta_keys = array(
            'project_status',
            'gf_entry_id',
            'gf_form_id',
            'project_city_id',
            'project_province_id',
            'assigned_users',
            'accepted_by',
            'client_user_id',
            'client_mobile',
            'file_url',
            'timestamps',
        );
        $provinces = Benana_Automation_Address::get_provinces();
        $cities    = Benana_Automation_Address::get_cities();
        wp_nonce_field( 'benana_project_meta', 'benana_project_meta_nonce' );
        ?>
        <div class="benana-meta">
            <?php foreach ( $meta_keys as $key ) :
                $value = get_post_meta( $post->ID, $key, true ); ?>
                <p>
                    <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $key ); ?></label>
                    <input class="widefat" type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
                </p>
            <?php endforeach; ?>
        </div>
        <hr />
        <h4>اساین دستی</h4>
        <p>استان و شهر مقصد را انتخاب کنید تا لیست کاربران فعال نمایش داده شود.</p>
        <p>
            <label for="benana_province">استان</label>
            <select name="benana_province" id="benana_province">
                <option value="">انتخاب استان</option>
                <?php foreach ( $provinces as $pid => $pname ) : ?>
                    <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( get_post_meta( $post->ID, 'project_province_id', true ), $pid ); ?>><?php echo esc_html( $pname ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="benana_city">شهر</label>
            <select name="benana_city" id="benana_city">
                <option value="">انتخاب شهر</option>
                <?php
                $province_id = get_post_meta( $post->ID, 'project_province_id', true );
                if ( $province_id && isset( $cities[ $province_id ] ) ) {
                    foreach ( $cities[ $province_id ] as $cid => $cname ) {
                        echo '<option value="' . esc_attr( $cid ) . '"' . selected( get_post_meta( $post->ID, 'project_city_id', true ), $cid, false ) . '>' . esc_html( $cname ) . '</option>';
                    }
                }
                ?>
            </select>
        </p>
        <p>
            <label for="benana_assign_user">کاربر</label>
            <select name="benana_assign_user" id="benana_assign_user">
                <option value="">انتخاب کاربر فعال</option>
                <?php
                $users = Benana_Automation_Project_Handler::find_assignees( get_post_meta( $post->ID, 'project_province_id', true ), get_post_meta( $post->ID, 'project_city_id', true ) );
                foreach ( $users as $uid ) {
                    $user = get_user_by( 'id', $uid );
                    if ( $user ) {
                        echo '<option value="' . esc_attr( $uid ) . '">' . esc_html( $user->display_name ) . '</option>';
                    }
                }
                ?>
            </select>
        </p>
        <p class="description">کاربر انتخاب‌شده به لیست اساینی‌ها افزوده می‌شود.</p>
        <?php
    }

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['benana_project_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['benana_project_meta_nonce'] ) ), 'benana_project_meta' ) ) {
            return;
        }
        $meta_keys = array(
            'project_status',
            'gf_entry_id',
            'gf_form_id',
            'project_city_id',
            'project_province_id',
            'assigned_users',
            'accepted_by',
            'client_user_id',
            'client_mobile',
            'file_url',
            'timestamps',
        );
        foreach ( $meta_keys as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
            }
        }

        if ( ! empty( $_POST['benana_assign_user'] ) ) {
            $assigned = json_decode( get_post_meta( $post_id, 'assigned_users', true ), true );
            if ( ! is_array( $assigned ) ) {
                $assigned = array();
            }
            $assigned[] = absint( $_POST['benana_assign_user'] );
            update_post_meta( $post_id, 'assigned_users', wp_json_encode( array_unique( $assigned ) ) );
        }
    }
}
