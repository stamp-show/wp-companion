<?php
defined('ABSPATH') || exit;

/** Shortcodes. Each one renders from the cached API read and escapes everything it prints. */
class SSC_Modules
{
    public static function init(): void
    {
        $map = [
            'stampshow_palmares' => 'palmares',
            'stampshow_sponsors' => 'sponsors',
            'stampshow_special_prizes' => 'special_prizes',
            'stampshow_judges' => 'judges',
            'stampshow_commissioners' => 'commissioners',
            'stampshow_catalog' => 'catalog',
            'stampshow_apply' => 'apply',
        ];
        foreach ($map as $tag => $method) {
            add_shortcode($tag, [self::class, $method]);
        }
        add_action('admin_post_nopriv_ssc_apply', [self::class, 'submit_application']);
        add_action('admin_post_ssc_apply', [self::class, 'submit_application']);
    }

    /** @return array|string the items, or the HTML to print instead */
    private static function load(string $resource)
    {
        $data = SSC_Client::resource($resource);
        if (is_wp_error($data)) {
            return current_user_can('manage_options')
                ? '<p class="ssc-error">Stamp.Show: ' . esc_html($data->get_error_message()) . '</p>'
                : '';
        }
        return $data;
    }

    private static function list_items(string $resource, callable $row, string $class): string
    {
        $data = self::load($resource);
        if (is_string($data)) {
            return $data;
        }
        $html = '';
        foreach ((array) ($data['items'] ?? []) as $item) {
            $cell = $row($item);
            if ($cell !== null) {
                $html .= '<li>' . $cell . '</li>';
            }
        }
        return $html === '' ? '' : '<ul class="ssc-list ' . esc_attr($class) . '">' . $html . '</ul>';
    }

    private static function img(?string $url, string $alt): string
    {
        return $url ? '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" loading="lazy"> ' : '';
    }

    public static function sponsors($atts = []): string
    {
        $atts = shortcode_atts(['category' => ''], $atts);
        $only = mb_strtolower(trim($atts['category']));
        return self::list_items('sponsors', static function ($s) use ($only) {
            if ($only !== '' && mb_strtolower(trim((string) $s['category'])) !== $only) {
                return null;
            }
            return self::img($s['logo_url'] ?? null, (string) $s['name']) . '<span>' . esc_html($s['name']) . '</span>';
        }, 'ssc-sponsors');
    }

    public static function special_prizes(): string
    {
        return self::list_items('special-prizes', static function ($p) {
            $out = self::img($p['logo_url'] ?? null, (string) ($p['donor'] ?? $p['name'])) . '<strong>' . esc_html($p['name']) . '</strong>';
            if (!empty($p['donor'])) {
                $out .= ' <span>' . esc_html($p['donor']) . '</span>';
            }
            if (!empty($p['conditions'])) {
                $out .= '<br><small>' . esc_html($p['conditions']) . '</small>';
            }
            return $out;
        }, 'ssc-prizes');
    }

    public static function judges(): string
    {
        return self::list_items('judges', static function ($j) {
            $out = self::img($j['photo_url'] ?? null, (string) $j['name']) . '<strong>' . esc_html($j['name']) . '</strong>';
            if (!empty($j['country'])) {
                $out .= ' <span>' . esc_html($j['country']) . '</span>';
            }
            return $out;
        }, 'ssc-judges');
    }

    public static function commissioners(): string
    {
        return self::list_items('commissioners', static function ($c) {
            $out = '<strong>' . esc_html($c['name']) . '</strong>';
            if (!empty($c['countries'])) {
                $out .= ' <span>' . esc_html(implode(', ', $c['countries'])) . '</span>';
            }
            if (!empty($c['languages'])) {
                $out .= ' <small>' . esc_html(implode(', ', $c['languages'])) . '</small>';
            }
            return $out;
        }, 'ssc-commissioners');
    }

    public static function catalog(): string
    {
        $data = self::load('catalog');
        if (is_string($data)) {
            return $data;
        }
        $rows = '';
        foreach ((array) ($data['items'] ?? []) as $e) {
            $rows .= '<tr><td>' . esc_html((string) ($e['catalogue_number'] ?? '')) . '</td><td>' . esc_html($e['title'])
                . '</td><td>' . esc_html((string) ($e['exhibitor'] ?? '')) . '</td><td>' . esc_html((string) ($e['country'] ?? ''))
                . '</td><td>' . esc_html((string) ($e['exhibit_class'] ?? '')) . '</td><td>' . esc_html((string) ($e['medal'] ?? '')) . '</td></tr>';
        }
        return $rows === '' ? '' : '<table class="ssc-catalog"><thead><tr><th>#</th><th>Title</th><th>Exhibitor</th><th>Country</th><th>Class</th><th>Medal</th></tr></thead><tbody>' . $rows . '</tbody></table>';
    }

    public static function palmares(): string
    {
        $data = self::load('palmares');
        if (is_string($data)) {
            return $data;
        }
        if (empty($data['visible'])) {
            $date = $data['available_on'] ?? null;
            return '<p class="ssc-palmares-hidden">' . esc_html($date ? 'The palmares will be published on ' . $date . '.' : 'The palmares has not been published yet.') . '</p>';
        }
        $rows = '';
        foreach ((array) ($data['awards'] ?? []) as $a) {
            $rows .= '<tr><td>' . esc_html((string) $a['medal']) . '</td><td>' . esc_html((string) ($a['score'] ?? '')) . '</td><td>' . esc_html($a['title'])
                . '</td><td>' . esc_html((string) $a['exhibitor']) . '</td><td>' . esc_html((string) $a['country']) . '</td></tr>';
        }
        $html = '<table class="ssc-palmares"><thead><tr><th>Medal</th><th>Points</th><th>Title</th><th>Exhibitor</th><th>Country</th></tr></thead><tbody>' . $rows . '</tbody></table>';
        $prizes = '';
        foreach ((array) ($data['special_prizes'] ?? []) as $p) {
            $prizes .= '<li><strong>' . esc_html($p['name']) . '</strong>: ' . esc_html($p['title']) . ' (' . esc_html((string) $p['exhibitor']) . ')</li>';
        }
        return $html . ($prizes !== '' ? '<ul class="ssc-special-prizes">' . $prizes . '</ul>' : '');
    }

    public static function apply($atts = []): string
    {
        if (!SSC_Client::is_configured()) {
            return '';
        }
        $atts = shortcode_atts(['classes' => '', 'max_frames' => 16], $atts);
        $classes = array_filter(array_map('trim', explode(',', $atts['classes'])));

        $notice = '';
        $result = isset($_GET['ssc_apply']) ? sanitize_text_field(wp_unslash($_GET['ssc_apply'])) : '';
        if ($result === 'ok') {
            $notice = '<p class="ssc-notice">Thanks! Your application was received. Watch your e-mail for the decision.</p>';
        } elseif ($result !== '') {
            $notice = '<p class="ssc-error">' . esc_html(get_transient('ssc_apply_err_' . $result) ?: 'The application could not be sent.') . '</p>';
        }

        $field = static fn ($label, $input) => '<p><label>' . esc_html($label) . '<br>' . $input . '</label></p>';
        $class_input = $classes
            ? '<select name="exhibit_class" required>' . implode('', array_map(static fn ($c) => '<option>' . esc_html($c) . '</option>', $classes)) . '</select>'
            : '<input name="exhibit_class" required>';

        return $notice . '<form class="ssc-apply" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
            . '<input type="hidden" name="action" value="ssc_apply">'
            . '<input type="hidden" name="redirect" value="' . esc_url(get_permalink() ?: home_url('/')) . '">'
            . wp_nonce_field('ssc_apply', '_wpnonce', true, false)
            . '<p style="position:absolute;left:-9999px"><input name="website" tabindex="-1" autocomplete="off"></p>'
            . $field('Name', '<input name="name" required>')
            . $field('E-mail', '<input type="email" name="email" required>')
            . $field('Country (2-letter code)', '<input name="country" maxlength="2" pattern="[A-Za-z]{2}">')
            . $field('Exhibit title', '<input name="title" required maxlength="200">')
            . $field('Class', $class_input)
            . $field('Frames', '<input type="number" name="frame_count" min="1" max="' . (int) $atts['max_frames'] . '" value="1" required>')
            . '<p><button type="submit">Apply</button></p></form>';
    }

    public static function submit_application(): void
    {
        $back = isset($_POST['redirect']) ? esc_url_raw(wp_unslash($_POST['redirect'])) : home_url('/');
        $back = wp_validate_redirect($back, home_url('/'));
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'ssc_apply') || !empty($_POST['website'])) {
            wp_safe_redirect(add_query_arg('ssc_apply', 'ok', $back)); // honeypot: pretend success
            exit;
        }
        $post = static fn ($k) => isset($_POST[$k]) ? sanitize_text_field(wp_unslash($_POST[$k])) : '';

        $result = SSC_Client::request('POST', '/applications', [
            'exhibitor' => array_filter([
                'email' => sanitize_email($post('email')),
                'name' => $post('name'),
                'country' => strtoupper($post('country')),
            ]),
            'title' => $post('title'),
            'exhibit_class' => $post('exhibit_class'),
            'frame_count' => (int) $post('frame_count'),
        ]);

        if (is_wp_error($result)) {
            $token = wp_generate_password(8, false);
            set_transient('ssc_apply_err_' . $token, $result->get_error_message(), 5 * MINUTE_IN_SECONDS);
            wp_safe_redirect(add_query_arg('ssc_apply', $token, $back));
        } else {
            wp_safe_redirect(add_query_arg('ssc_apply', 'ok', $back));
        }
        exit;
    }
}
