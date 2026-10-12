<?php
defined('ABSPATH') || exit;

/**
 * Gutenberg blocks. All dynamic: the editor shows a server-side preview and the front end renders
 * through the same SSC_Modules methods as the shortcodes.
 */
class SSC_Blocks
{
    const BLOCKS = [
        'palmares' => ['title' => 'Palmares', 'attributes' => []],
        'sponsors' => ['title' => 'Sponsors', 'attributes' => ['category' => ['type' => 'string', 'default' => '']]],
        'special-prizes' => ['title' => 'Special prizes', 'attributes' => []],
        'judges' => ['title' => 'Judges', 'attributes' => []],
        'commissioners' => ['title' => 'Commissioners', 'attributes' => []],
        'catalog' => ['title' => 'Catalog', 'attributes' => []],
        'apply' => [
            'title' => 'Application form',
            'attributes' => [
                'classes' => ['type' => 'string', 'default' => ''],
                'max_frames' => ['type' => 'number', 'default' => 10],
            ],
        ],
    ];

    public static function init(): void
    {
        add_action('init', [self::class, 'register']);
    }

    public static function register(): void
    {
        wp_register_script(
            'ssc-blocks',
            plugins_url('assets/blocks.js', SSC_DIR . 'stamp-show-companion.php'),
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render'],
            SSC_VERSION,
            true
        );
        wp_localize_script('ssc-blocks', 'SSC_BLOCKS', [
            'blocks' => array_map(static fn ($b) => ['title' => $b['title'], 'attributes' => $b['attributes']], self::BLOCKS),
        ]);

        foreach (self::BLOCKS as $slug => $block) {
            register_block_type('stamp-show/' . $slug, [
                'api_version' => 2,
                'title' => 'Stamp.Show: ' . $block['title'],
                'category' => 'widgets',
                'attributes' => $block['attributes'],
                'editor_script' => 'ssc-blocks',
                'render_callback' => static function (array $attrs) use ($slug): string {
                    $method = str_replace('-', '_', $slug);
                    $html = SSC_Modules::$method($attrs);
                    return '<div class="wp-block-stamp-show-' . esc_attr($slug) . '">' . $html . '</div>';
                },
            ]);
        }
    }
}
