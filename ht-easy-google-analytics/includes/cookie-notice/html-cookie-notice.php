<?php
$notice_text         = $this->config('cookie_notice_text');
$notice_privacy_url  = $this->config('cookie_notice_privacy_url');
$notice_privacy_text = $this->config('cookie_notice_privacy_text');
$notice_accept_text  = $this->config('cookie_notice_accept_text');
$notice_decline_text = $this->config('cookie_notice_decline_text');
$notice_layout       = $this->config('cookie_notice_layout');
?>
<div id="htga4-cookie-notice" class="htga4-cookie-notice htga4-cookie-notice--<?php echo esc_attr($notice_layout); ?>">
    <div class="htga4-cookie-notice__content">
        <div class="htga4-cookie-notice__text-section">
            <p class="htga4-cookie-notice__text">
                <span><?php echo wp_kses_post($notice_text); ?></span>

                <?php if( $notice_privacy_url ): ?>
                    <a href="<?php echo esc_url($notice_privacy_url); ?>" class="htga4-cookie-notice__link" target="_blank"><?php echo wp_kses_post($notice_privacy_text); ?></a>
                <?php endif; ?>
            </p>
        </div>

        <div class="htga4-cookie-notice__actions">
            <span 
                id="htga4-cookie-decline" 
                class="htga4-cookie-notice__button htga4-cookie-decline" 
                type="button">
                <?php echo wp_kses_post($notice_decline_text); ?>
            </span>

            <span 
                class="htga4-cookie-notice__button htga4-cookie-accept" 
                id="htga4-cookie-accept" 
                type="button">
                <?php echo wp_kses_post($notice_accept_text); ?>
            </span>
        </div>
    </div>
</div>