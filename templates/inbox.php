<div class="benana-inbox">
    <h3>پروژه‌های من</h3>
    <form class="benana-filter" method="get">
        <input type="text" name="benana_search" value="<?php echo isset( $_GET['benana_search'] ) ? esc_attr( wp_unslash( $_GET['benana_search'] ) ) : ''; ?>" placeholder="جستجو در عنوان پروژه" />
        <select name="benana_status">
            <option value="">همه وضعیت‌ها</option>
            <option value="assigned" <?php selected( 'assigned', isset( $_GET['benana_status'] ) ? wp_unslash( $_GET['benana_status'] ) : '' ); ?>>در انتظار قبول</option>
            <option value="accepted" <?php selected( 'accepted', isset( $_GET['benana_status'] ) ? wp_unslash( $_GET['benana_status'] ) : '' ); ?>>در حال انجام</option>
            <option value="completed" <?php selected( 'completed', isset( $_GET['benana_status'] ) ? wp_unslash( $_GET['benana_status'] ) : '' ); ?>>تکمیل شده</option>
        </select>
        <button type="submit" class="button">اعمال فیلتر</button>
    </form>
    <?php if ( empty( $projects ) ) : ?>
        <p>پروژه‌ای یافت نشد.</p>
    <?php else : ?>
        <?php foreach ( $projects as $project ) :
            $status      = get_post_meta( $project->ID, 'project_status', true );
            $city        = Benana_Automation_Address::get_city_name( get_post_meta( $project->ID, 'project_province_id', true ), get_post_meta( $project->ID, 'project_city_id', true ) );
            $projects_pg = get_page_by_path( 'projects' );
            $detail_url  = $projects_pg ? add_query_arg( 'project_id', $project->ID, get_permalink( $projects_pg ) ) : add_query_arg( 'project_id', $project->ID, home_url( '/projects/' ) );
            ?>
            <div class="benana-card">
                <strong><?php echo esc_html( get_the_title( $project ) ); ?></strong>
                <div class="meta">وضعیت: <?php echo esc_html( $status ); ?></div>
                <?php if ( $city ) : ?>
                    <div class="meta">شهر: <?php echo esc_html( $city ); ?></div>
                <?php endif; ?>
                <div class="benana-actions"><a class="button button-primary" href="<?php echo esc_url( $detail_url ); ?>">مشاهده و اقدام</a></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
