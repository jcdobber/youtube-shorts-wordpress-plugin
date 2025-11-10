<?php
/**
 * Plugin Name: YouTube Shorts Gallery
 * Description: Manage a list of YouTube Shorts in the admin and display them on the front-end via shortcode with a modal player.
 * Version: 1.0.0
 * Author: OpenAI Assistant
 * License: GPL2
 */

if (! defined('ABSPATH')) {
    exit;
}

class YouTube_Shorts_Gallery
{
    const OPTION_KEY = 'ysg_shorts';
    const NONCE_ACTION = 'ysg_manage_shorts';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_shortcode('youtube_shorts_gallery', [$this, 'render_shortcode']);
    }

    public function register_admin_page(): void
    {
        add_menu_page(
            __('YouTube Shorts', 'youtube-shorts-gallery'),
            __('YouTube Shorts', 'youtube-shorts-gallery'),
            'manage_options',
            'youtube-shorts-gallery',
            [$this, 'render_admin_page'],
            'dashicons-video-alt3'
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'ysg_settings_group',
            self::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_shorts'],
                'default' => [],
            ]
        );
    }

    /**
     * @param array<string, mixed> $input
     * @return array<int, array<string, string>>
     */
    public function sanitize_shorts($input): array
    {
        if (! is_array($input)) {
            return [];
        }

        $sanitized = [];

        foreach ($input as $item) {
            if (! is_array($item)) {
                continue;
            }

            $url = isset($item['url']) ? esc_url_raw(trim((string) $item['url'])) : '';
            $title = isset($item['title']) ? sanitize_text_field($item['title']) : '';
            $description = isset($item['description']) ? sanitize_textarea_field($item['description']) : '';
            $duration = isset($item['duration']) ? sanitize_text_field($item['duration']) : '';

            if (empty($url)) {
                continue;
            }

            $sanitized[] = [
                'url' => $url,
                'title' => $title,
                'description' => $description,
                'duration' => $duration,
            ];
        }

        return $sanitized;
    }

    public function enqueue_admin_assets(string $hook): void
    {
        if ('toplevel_page_youtube-shorts-gallery' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'ysg-admin',
            plugins_url('assets/css/admin.css', __FILE__),
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/admin.css')
        );

        wp_enqueue_script(
            'ysg-admin',
            plugins_url('assets/js/admin.js', __FILE__),
            ['wp-i18n'],
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/admin.js'),
            true
        );

        wp_set_script_translations('ysg-admin', 'youtube-shorts-gallery');
    }

    public function render_admin_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $shorts = get_option(self::OPTION_KEY, []);

        ?>
        <div class="wrap ysg-admin">
            <h1><?php esc_html_e('YouTube Shorts Gallery', 'youtube-shorts-gallery'); ?></h1>
            <p class="description">
                <?php esc_html_e('Add your YouTube Shorts to display them on the front-end via the [youtube_shorts_gallery] shortcode.', 'youtube-shorts-gallery'); ?>
            </p>

            <form method="post" action="options.php">
                <?php
                settings_fields('ysg_settings_group');
                wp_nonce_field(self::NONCE_ACTION, '_ysg_nonce');
                ?>

                <table class="widefat ysg-table" id="ysg-shorts-table">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Video URL', 'youtube-shorts-gallery'); ?></th>
                        <th><?php esc_html_e('Title', 'youtube-shorts-gallery'); ?></th>
                        <th><?php esc_html_e('Description', 'youtube-shorts-gallery'); ?></th>
                        <th><?php esc_html_e('Duration', 'youtube-shorts-gallery'); ?></th>
                        <th class="column-actions">&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (! empty($shorts)) : ?>
                        <?php foreach ($shorts as $index => $short) : ?>
                            <tr class="ysg-row">
                                <td>
                                    <input type="url" name="<?php echo esc_attr(self::OPTION_KEY); ?>[<?php echo esc_attr((string) $index); ?>][url]" value="<?php echo esc_attr($short['url'] ?? ''); ?>" placeholder="https://youtube.com/shorts/..." required>
                                </td>
                                <td>
                                    <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[<?php echo esc_attr((string) $index); ?>][title]" value="<?php echo esc_attr($short['title'] ?? ''); ?>" placeholder="<?php esc_attr_e('Title', 'youtube-shorts-gallery'); ?>">
                                </td>
                                <td>
                                    <textarea name="<?php echo esc_attr(self::OPTION_KEY); ?>[<?php echo esc_attr((string) $index); ?>][description]" rows="2" placeholder="<?php esc_attr_e('Short description', 'youtube-shorts-gallery'); ?>"><?php echo esc_textarea($short['description'] ?? ''); ?></textarea>
                                </td>
                                <td>
                                    <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[<?php echo esc_attr((string) $index); ?>][duration]" value="<?php echo esc_attr($short['duration'] ?? ''); ?>" placeholder="0:30">
                                </td>
                                <td class="column-actions">
                                    <button type="button" class="button button-secondary ysg-remove-row">&times;</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>

                <p>
                    <button type="button" class="button button-primary" id="ysg-add-row">
                        <?php esc_html_e('Add video', 'youtube-shorts-gallery'); ?>
                    </button>
                </p>

                <?php submit_button(__('Save Shorts', 'youtube-shorts-gallery')); ?>
            </form>

            <script type="text/html" id="tmpl-ysg-row-template">
                <tr class="ysg-row">
                    <td>
                        <input type="url" name="<?php echo esc_attr(self::OPTION_KEY); ?>[{{index}}][url]" placeholder="https://youtube.com/shorts/..." required>
                    </td>
                    <td>
                        <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[{{index}}][title]" placeholder="<?php esc_attr_e('Title', 'youtube-shorts-gallery'); ?>">
                    </td>
                    <td>
                        <textarea name="<?php echo esc_attr(self::OPTION_KEY); ?>[{{index}}][description]" rows="2" placeholder="<?php esc_attr_e('Short description', 'youtube-shorts-gallery'); ?>"></textarea>
                    </td>
                    <td>
                        <input type="text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[{{index}}][duration]" placeholder="0:30">
                    </td>
                    <td class="column-actions">
                        <button type="button" class="button button-secondary ysg-remove-row">&times;</button>
                    </td>
                </tr>
            </script>
        </div>
        <?php
    }

    /**
     * @param array<string, mixed> $atts
     * @return string
     */
    public function render_shortcode($atts): string
    {
        $shorts = get_option(self::OPTION_KEY, []);

        if (empty($shorts) || ! is_array($shorts)) {
            return '';
        }

        wp_enqueue_style(
            'ysg-frontend',
            plugins_url('assets/css/frontend.css', __FILE__),
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/css/frontend.css')
        );

        wp_enqueue_script(
            'ysg-frontend',
            plugins_url('assets/js/frontend.js', __FILE__),
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/js/frontend.js'),
            true
        );

        ob_start();
        ?>
        <div class="ysg-gallery" data-ysg-gallery>
            <?php foreach ($shorts as $short) :
                $video_id = $this->extract_video_id($short['url'] ?? '');
                if (! $video_id) {
                    continue;
                }
                $title = $short['title'] ?? '';
                $description = $short['description'] ?? '';
                $duration = $short['duration'] ?? '';
                $thumbnail = sprintf('https://img.youtube.com/vi/%s/hqdefault.jpg', $video_id);
                ?>
                <article class="ysg-item" data-video-id="<?php echo esc_attr($video_id); ?>" data-video-title="<?php echo esc_attr($title); ?>" data-video-description="<?php echo esc_attr($description); ?>">
                    <button type="button" class="ysg-card" aria-label="<?php echo esc_attr(sprintf(__('Play %s', 'youtube-shorts-gallery'), $title ?: $video_id)); ?>">
                        <span class="ysg-thumbnail" style="background-image: url('<?php echo esc_url($thumbnail); ?>');"></span>
                        <span class="ysg-overlay">
                            <span class="ysg-play-icon" aria-hidden="true"></span>
                            <?php if (! empty($duration)) : ?>
                                <span class="ysg-duration"><?php echo esc_html($duration); ?></span>
                            <?php endif; ?>
                        </span>
                        <?php if (! empty($title)) : ?>
                            <span class="ysg-title"><?php echo esc_html($title); ?></span>
                        <?php endif; ?>
                    </button>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="ysg-modal" data-ysg-modal aria-hidden="true" role="dialog" aria-modal="true">
            <div class="ysg-modal__backdrop" data-ysg-close></div>
            <div class="ysg-modal__dialog" role="document">
                <button type="button" class="ysg-modal__close" data-ysg-close aria-label="<?php esc_attr_e('Close video', 'youtube-shorts-gallery'); ?>">&times;</button>
                <div class="ysg-modal__content">
                    <div class="ysg-modal__video">
                        <iframe src="" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen data-ysg-iframe></iframe>
                    </div>
                    <div class="ysg-modal__meta">
                        <h3 class="ysg-modal__title" data-ysg-title></h3>
                        <p class="ysg-modal__description" data-ysg-description></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private function extract_video_id(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $patterns = [
            '/youtu\.be\/([\w-]{11})/i',
            '/youtube\.com\/(?:shorts\/|watch\?v=)([\w-]{11})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        $parts = wp_parse_url($url);
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (! empty($query['v'])) {
                return sanitize_text_field($query['v']);
            }
        }

        return '';
    }
}

new YouTube_Shorts_Gallery();
