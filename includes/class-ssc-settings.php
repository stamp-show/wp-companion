<?php
defined('ABSPATH') || exit;

class SSC_Settings
{
    const GROUP = 'ssc_settings';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'register']);
        add_action('admin_post_ssc_test', [self::class, 'test']);
        add_action('admin_post_ssc_flush', [self::class, 'flush']);
        add_action('admin_post_ssc_decide', [self::class, 'decide']);
    }

    public static function menu(): void
    {
        add_options_page('Stamp.Show', 'Stamp.Show', 'manage_options', 'stamp-show', [self::class, 'page']);
    }

    public static function register(): void
    {
        register_setting(self::GROUP, 'ssc_base_url', ['type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => 'https://api.stamp.show']);
        register_setting(self::GROUP, 'ssc_api_key', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
        register_setting(self::GROUP, 'ssc_webhook_secret', ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field']);
    }

    public static function test(): void
    {
        check_admin_referer('ssc_test');
        current_user_can('manage_options') || wp_die('Forbidden');
        $result = SSC_Client::request('GET', '/applications');
        $notice = is_wp_error($result) ? 'error:' . $result->get_error_message() : 'ok';
        wp_safe_redirect(add_query_arg('ssc_notice', rawurlencode($notice), admin_url('options-general.php?page=stamp-show')));
        exit;
    }

    /** Accepts or rejects a pending application with the expo's API key. */
    public static function decide(): void
    {
        check_admin_referer('ssc_decide');
        current_user_can('manage_options') || wp_die('Forbidden');
        $id = isset($_POST['application']) ? sanitize_text_field(wp_unslash($_POST['application'])) : '';
        $decision = isset($_POST['decision']) && $_POST['decision'] === 'reject' ? 'reject' : 'accept';
        $result = SSC_Client::request('POST', '/applications/' . rawurlencode($id) . '/' . $decision);
        $notice = is_wp_error($result) ? 'error:' . $result->get_error_message() : ($decision === 'accept' ? 'accepted' : 'rejected');
        wp_safe_redirect(add_query_arg('ssc_notice', rawurlencode($notice), admin_url('options-general.php?page=stamp-show')));
        exit;
    }

    public static function flush(): void
    {
        check_admin_referer('ssc_flush');
        current_user_can('manage_options') || wp_die('Forbidden');
        SSC_Client::flush();
        wp_safe_redirect(add_query_arg('ssc_notice', 'flushed', admin_url('options-general.php?page=stamp-show')));
        exit;
    }

    public static function page(): void
    {
        $notice = isset($_GET['ssc_notice']) ? sanitize_text_field(wp_unslash($_GET['ssc_notice'])) : '';
        $webhook = rest_url('stamp-show/v1/webhook');
        ?>
        <div class="wrap">
            <h1>Stamp.Show</h1>
            <?php if ($notice === 'ok') : ?>
                <div class="notice notice-success"><p>Connection works.</p></div>
            <?php elseif (strpos($notice, 'error:') === 0) : ?>
                <div class="notice notice-error"><p><?php echo esc_html(substr($notice, 6)); ?></p></div>
            <?php elseif ($notice === 'accepted' || $notice === 'rejected') : ?>
                <div class="notice notice-success"><p>Application <?php echo esc_html($notice); ?>.</p></div>
            <?php elseif ($notice === 'flushed') : ?>
                <div class="notice notice-success"><p>Cache cleared.</p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields(self::GROUP); ?>
                <table class="form-table" role="presentation">
                    <tr><th><label for="ssc_base_url">API base URL</label></th>
                        <td><input class="regular-text" id="ssc_base_url" name="ssc_base_url" value="<?php echo esc_attr(SSC_Client::base_url()); ?>"></td></tr>
                    <tr><th><label for="ssc_api_key">API key</label></th>
                        <td><input class="regular-text" type="password" id="ssc_api_key" name="ssc_api_key" autocomplete="off" value="<?php echo esc_attr((string) get_option('ssc_api_key', '')); ?>">
                            <p class="description">From your expo's API Keys tab in Stamp.Show (jpk_...).</p></td></tr>
                    <tr><th><label for="ssc_webhook_secret">Webhook secret</label></th>
                        <td><input class="regular-text" type="password" id="ssc_webhook_secret" name="ssc_webhook_secret" autocomplete="off" value="<?php echo esc_attr((string) get_option('ssc_webhook_secret', '')); ?>">
                            <p class="description">In the API Keys tab, set the webhook URL to <code><?php echo esc_html($webhook); ?></code> and tick the events you want.</p></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <h2>Tools</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                <input type="hidden" name="action" value="ssc_test">
                <?php wp_nonce_field('ssc_test'); submit_button('Test connection', 'secondary', 'submit', false); ?>
            </form>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                <input type="hidden" name="action" value="ssc_flush">
                <?php wp_nonce_field('ssc_flush'); submit_button('Clear cache', 'secondary', 'submit', false); ?>
            </form>

            <?php self::applications(); ?>

            <h2>Shortcodes</h2>
            <p><code>[stampshow_palmares]</code> <code>[stampshow_sponsors category="Platinum"]</code> <code>[stampshow_special_prizes]</code>
               <code>[stampshow_judges]</code> <code>[stampshow_commissioners]</code> <code>[stampshow_catalog]</code>
               <code>[stampshow_apply classes="Traditional,Thematic"]</code></p>
        </div>
        <?php
    }

    /** Pending applications received through the API, with accept / reject buttons. */
    private static function applications(): void
    {
        if (!SSC_Client::is_configured()) {
            return;
        }
        $list = SSC_Client::request('GET', '/applications');
        echo '<h2>Applications</h2>';
        if (is_wp_error($list)) {
            echo '<p>' . esc_html($list->get_error_message()) . '</p>';
            return;
        }
        if (!$list) {
            echo '<p>No applications yet.</p>';
            return;
        }
        echo '<table class="widefat striped"><thead><tr><th>Exhibit</th><th>Class</th><th>Frames</th><th>Exhibitor</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($list as $a) {
            $status = (string) ($a['status'] ?? '');
            echo '<tr><td>' . esc_html((string) ($a['title'] ?? '')) . '</td><td>' . esc_html((string) ($a['exhibit_class'] ?? ''))
                . '</td><td>' . esc_html((string) ($a['frame_count'] ?? '')) . '</td><td>' . esc_html((string) ($a['exhibitor_email'] ?? ''))
                . '</td><td>' . esc_html($status) . '</td><td>';
            if ($status === 'pending') {
                foreach (['accept' => 'Accept', 'reject' => 'Reject'] as $decision => $label) {
                    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline">'
                        . '<input type="hidden" name="action" value="ssc_decide">'
                        . '<input type="hidden" name="application" value="' . esc_attr((string) $a['id']) . '">'
                        . '<input type="hidden" name="decision" value="' . esc_attr($decision) . '">';
                    wp_nonce_field('ssc_decide');
                    submit_button($label, $decision === 'accept' ? 'primary small' : 'secondary small', 'submit', false);
                    echo '</form> ';
                }
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }
}
