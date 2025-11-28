<div class="benana-inbox">
    <h3>پروژه‌های من</h3>
    <form class="benana-filter" method="get">
        <input type="text" name="benana_search" value="<?php echo isset( $_GET['benana_search'] ) ? esc_attr( wp_unslash( $_GET['benana_search'] ) ) : ''; ?>" placeholder="جستجو در عنوان پروژه" />
        <select name="benana_status">
            <option value="">همه وضعیت‌ها</option>
            <option value="new" <?php selected( 'new', isset( $_GET['benana_status'] ) ? wp_unslash( $_GET['benana_status'] ) : '' ); ?>>در انتظار پذیرش</option>
            <option value="accepted" <?php selected( 'accepted', isset( $_GET['benana_status'] ) ? wp_unslash( $_GET['benana_status'] ) : '' ); ?>>در حال انجام</option>
            <option value="file_uploaded" <?php selected( 'file_uploaded', isset( $_GET['benana_status'] ) ? wp_unslash( $_GET['benana_status'] ) : '' ); ?>>فایل ثبت شده</option>
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
            $entry_date  = $entry_dates[ $project->ID ] ?? '';
            ?>
            <div class="benana-card">
                <a class="benana-eye" href="<?php echo esc_url( $detail_url ); ?>" aria-label="مشاهده پروژه">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c-5 0-9 5-9 7s4 7 9 7 9-5 9-7-4-7-9-7zm0 12c-2.8 0-5-2.2-5-5s2.2-5 5-5 5 2.2 5 5-2.2 5-5 5zm0-8.2c-1.7 0-3.2 1.5-3.2 3.2S10.3 15.2 12 15.2s3.2-1.5 3.2-3.2S13.7 8.8 12 8.8z"/></svg>
                </a>
                <strong><?php echo esc_html( get_the_title( $project ) ); ?></strong>
                <div class="meta">
                    <span class="benana-status-pill"><span class="benana-status-dot"></span><?php echo esc_html( $status_labels[ $status ] ?? $status ); ?></span>
                </div>
                <?php if ( $entry_date ) : ?>
                    <div class="meta">تاریخ ورودی: <?php echo esc_html( $entry_date ); ?></div>
                <?php endif; ?>
                <?php if ( $city ) : ?>
                    <div class="meta">شهر: <?php echo esc_html( $city ); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
