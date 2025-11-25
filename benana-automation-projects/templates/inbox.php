<div class="benana-inbox">
    <h3>پروژه‌های من</h3>
    <?php if ( empty( $projects ) ) : ?>
        <p>پروژه‌ای یافت نشد.</p>
    <?php else : ?>
        <?php foreach ( $projects as $project ) :
            $status  = get_post_meta( $project->ID, 'project_status', true );
            $actions = array();
            $base    = add_query_arg( array( 'project_id' => $project->ID ) );
            if ( 'accepted' !== $status ) {
                $actions[] = '<a class="button" href="' . esc_url( add_query_arg( 'benana_action', 'accept', $base ) ) . '">قبول</a>';
                $actions[] = '<a class="button" href="' . esc_url( add_query_arg( 'benana_action', 'reject', $base ) ) . '">رد</a>';
            } else {
                $actions[] = '<a class="button" href="' . esc_url( add_query_arg( 'benana_action', 'complete', $base ) ) . '">اتمام</a>';
            }
            ?>
            <div class="benana-card">
                <strong><?php echo esc_html( get_the_title( $project ) ); ?></strong>
                <div>وضعیت: <?php echo esc_html( $status ); ?></div>
                <div class="benana-actions"><?php echo implode( ' ', $actions ); ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
