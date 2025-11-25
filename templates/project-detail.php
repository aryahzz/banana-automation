<div class="benana-project-detail">
    <h3><?php echo esc_html( $view['project']->post_title ); ?></h3>
    <div class="benana-meta">
        <span>وضعیت: <?php echo esc_html( $view['status'] ); ?></span>
        <span>موقعیت: <?php echo esc_html( Benana_Automation_Address::get_city_name( $view['province'], $view['city'] ) ); ?></span>
    </div>

    <div class="benana-actions">
        <?php if ( 'accepted' !== $view['status'] ) : ?>
            <form method="post" class="benana-inline-form">
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $view['project']->ID ); ?>" />
                <input type="hidden" name="benana_action" value="accept" />
                <input type="hidden" name="benana_action_nonce" value="<?php echo esc_attr( $view['nonce'] ); ?>" />
                <button type="submit" class="button button-primary">قبول</button>
            </form>
            <form method="post" class="benana-inline-form">
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $view['project']->ID ); ?>" />
                <input type="hidden" name="benana_action" value="reject" />
                <input type="hidden" name="benana_action_nonce" value="<?php echo esc_attr( $view['nonce'] ); ?>" />
                <button type="submit" class="button">رد</button>
            </form>
        <?php else : ?>
            <p>پروژه توسط شما پذیرفته شده است.</p>
            <form method="post" class="benana-inline-form">
                <input type="hidden" name="project_id" value="<?php echo esc_attr( $view['project']->ID ); ?>" />
                <input type="hidden" name="benana_action" value="complete" />
                <input type="hidden" name="benana_action_nonce" value="<?php echo esc_attr( $view['nonce'] ); ?>" />
                <button type="submit" class="button button-primary">اتمام پروژه</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ( ! empty( $view['fields'] ) ) : ?>
        <div class="benana-entry-fields">
            <h4>جزئیات فرم</h4>
            <?php
            foreach ( $view['fields'] as $field ) {
                $value = is_array( $field['value'] ) ? implode( '، ', (array) $field['value'] ) : $field['value'];
                ?>
                <div class="benana-field">
                    <strong><?php echo esc_html( $field['label'] ); ?></strong>
                    <div><?php echo esc_html( $value ); ?></div>
                </div>
                <?php
            }
            ?>
        </div>
    <?php endif; ?>
</div>
