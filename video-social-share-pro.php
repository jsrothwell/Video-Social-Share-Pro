<?php
/**
 * Plugin Name: Video Social Share Pro
 * Plugin URI: https://github.com/jsrothwell/Video-Social-Share-Pro
 * Description: Professional social sharing buttons optimized for video content sites with YouTube integration
 * Version: 1.0.0
 * Author: Jamieson Rothwell
 * Author URI: https://github.com/jsrothwell
 * License: GPL2
 * Text Domain: video-social-share
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VideoSocialSharePro {

    private $options;

    public function __construct() {
        // Initialize plugin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_filter('the_content', array($this, 'add_share_buttons'));
        add_action('wp_footer', array($this, 'add_floating_buttons'));
        add_shortcode('social_share', array($this, 'social_share_shortcode'));
    }

    // Enqueue frontend styles and scripts
    public function enqueue_frontend_assets() {
        wp_enqueue_style('video-social-share-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_enqueue_script('video-social-share-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    // Add admin menu
    public function add_admin_menu() {
        add_menu_page(
            'Video Social Share Pro',
            'Social Share',
            'manage_options',
            'video_social_share',
            array($this, 'options_page'),
            'dashicons-share',
            30
        );
    }

    // Register settings
    public function settings_init() {
        register_setting('videoSocialShare', 'vss_settings');

        // General Settings Section
        add_settings_section(
            'vss_general_section',
            __('General Settings', 'video-social-share'),
            array($this, 'settings_section_callback'),
            'videoSocialShare'
        );

        // Button Position
        add_settings_field(
            'vss_position',
            __('Button Position', 'video-social-share'),
            array($this, 'position_render'),
            'videoSocialShare',
            'vss_general_section'
        );

        // Button Style
        add_settings_field(
            'vss_style',
            __('Button Style', 'video-social-share'),
            array($this, 'style_render'),
            'videoSocialShare',
            'vss_general_section'
        );

        // Active Networks
        add_settings_field(
            'vss_networks',
            __('Active Networks', 'video-social-share'),
            array($this, 'networks_render'),
            'videoSocialShare',
            'vss_general_section'
        );

        // Show Share Counts
        add_settings_field(
            'vss_show_counts',
            __('Show Share Counts', 'video-social-share'),
            array($this, 'show_counts_render'),
            'videoSocialShare',
            'vss_general_section'
        );

        // Floating Sidebar
        add_settings_field(
            'vss_floating',
            __('Enable Floating Sidebar', 'video-social-share'),
            array($this, 'floating_render'),
            'videoSocialShare',
            'vss_general_section'
        );

        // YouTube Channel
        add_settings_field(
            'vss_youtube_channel',
            __('YouTube Channel URL', 'video-social-share'),
            array($this, 'youtube_channel_render'),
            'videoSocialShare',
            'vss_general_section'
        );

        // Custom Color
        add_settings_field(
            'vss_custom_color',
            __('Custom Button Color', 'video-social-share'),
            array($this, 'custom_color_render'),
            'videoSocialShare',
            'vss_general_section'
        );
    }

    // Field renders
    public function position_render() {
        $options = get_option('vss_settings');
        $position = isset($options['vss_position']) ? $options['vss_position'] : 'both';
        ?>
        <select name='vss_settings[vss_position]'>
            <option value='before' <?php selected($position, 'before'); ?>>Before Content</option>
            <option value='after' <?php selected($position, 'after'); ?>>After Content</option>
            <option value='both' <?php selected($position, 'both'); ?>>Before & After Content</option>
            <option value='manual' <?php selected($position, 'manual'); ?>>Manual (Use Shortcode)</option>
        </select>
        <?php
    }

    public function style_render() {
        $options = get_option('vss_settings');
        $style = isset($options['vss_style']) ? $options['vss_style'] : 'default';
        ?>
        <select name='vss_settings[vss_style]'>
            <option value='default' <?php selected($style, 'default'); ?>>Default</option>
            <option value='rounded' <?php selected($style, 'rounded'); ?>>Rounded</option>
            <option value='square' <?php selected($style, 'square'); ?>>Square</option>
            <option value='minimal' <?php selected($style, 'minimal'); ?>>Minimal</option>
            <option value='boxed' <?php selected($style, 'boxed'); ?>>Boxed</option>
        </select>
        <?php
    }

    public function networks_render() {
        $options = get_option('vss_settings');
        $networks = isset($options['vss_networks']) ? $options['vss_networks'] : array();

        $available_networks = array(
            'facebook' => 'Facebook',
            'twitter' => 'Twitter (X)',
            'linkedin' => 'LinkedIn',
            'pinterest' => 'Pinterest',
            'reddit' => 'Reddit',
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'email' => 'Email',
            'youtube' => 'YouTube Subscribe'
        );

        foreach ($available_networks as $key => $label) {
            $checked = in_array($key, (array)$networks) ? 'checked' : '';
            echo "<label style='display:inline-block; margin-right:15px;'>";
            echo "<input type='checkbox' name='vss_settings[vss_networks][]' value='$key' $checked> $label";
            echo "</label>";
        }
    }

    public function show_counts_render() {
        $options = get_option('vss_settings');
        $show_counts = isset($options['vss_show_counts']) ? $options['vss_show_counts'] : '0';
        ?>
        <input type='checkbox' name='vss_settings[vss_show_counts]' value='1' <?php checked($show_counts, '1'); ?>>
        <p class="description">Display share count numbers (Note: Some networks no longer provide public counts)</p>
        <?php
    }

    public function floating_render() {
        $options = get_option('vss_settings');
        $floating = isset($options['vss_floating']) ? $options['vss_floating'] : '0';
        ?>
        <input type='checkbox' name='vss_settings[vss_floating]' value='1' <?php checked($floating, '1'); ?>>
        <p class="description">Show floating share buttons on the left side of the page</p>
        <?php
    }

    public function youtube_channel_render() {
        $options = get_option('vss_settings');
        $youtube = isset($options['vss_youtube_channel']) ? $options['vss_youtube_channel'] : '';
        ?>
        <input type='text' name='vss_settings[vss_youtube_channel]' value='<?php echo esc_attr($youtube); ?>' style='width:400px;'>
        <p class="description">Enter your YouTube channel URL (e.g., https://youtube.com/@yourchannel)</p>
        <?php
    }

    public function custom_color_render() {
        $options = get_option('vss_settings');
        $color = isset($options['vss_custom_color']) ? $options['vss_custom_color'] : '#1877f2';
        ?>
        <input type='color' name='vss_settings[vss_custom_color]' value='<?php echo esc_attr($color); ?>'>
        <p class="description">Choose a custom color for the buttons (applies to some styles)</p>
        <?php
    }

    public function settings_section_callback() {
        echo __('Configure your social sharing buttons below:', 'video-social-share');
    }

    // Options page HTML
    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Video Social Share Pro Settings</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('videoSocialShare');
                do_settings_sections('videoSocialShare');
                submit_button();
                ?>
            </form>

            <div style="margin-top: 30px; padding: 20px; background: #fff; border-left: 4px solid #2271b1;">
                <h3>Shortcode Usage</h3>
                <p>Use the shortcode <code>[social_share]</code> to manually place share buttons anywhere in your content.</p>
                <p>You can also use it in your theme files: <code>&lt;?php echo do_shortcode('[social_share]'); ?&gt;</code></p>
            </div>
        </div>
        <?php
    }

    // Generate share buttons HTML
    public function generate_buttons($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        $options = get_option('vss_settings');
        $networks = isset($options['vss_networks']) ? $options['vss_networks'] : array('facebook', 'twitter', 'linkedin');
        $style = isset($options['vss_style']) ? $options['vss_style'] : 'default';
        $show_counts = isset($options['vss_show_counts']) ? $options['vss_show_counts'] : '0';
        $youtube_channel = isset($options['vss_youtube_channel']) ? $options['vss_youtube_channel'] : '';

        $post_url = get_permalink($post_id);
        $post_title = get_the_title($post_id);
        $post_thumbnail = get_the_post_thumbnail_url($post_id, 'full');

        $html = '<div class="vss-share-buttons vss-style-' . esc_attr($style) . '">';
        $html .= '<div class="vss-buttons-wrapper">';

        foreach ($networks as $network) {
            switch ($network) {
                case 'facebook':
                    $share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($post_url);
                    $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-facebook" target="_blank" rel="noopener">';
                    $html .= '<span class="vss-icon">📘</span>';
                    $html .= '<span class="vss-label">Facebook</span>';
                    $html .= '</a>';
                    break;

                case 'twitter':
                    $share_url = 'https://twitter.com/intent/tweet?url=' . urlencode($post_url) . '&text=' . urlencode($post_title);
                    $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-twitter" target="_blank" rel="noopener">';
                    $html .= '<span class="vss-icon">𝕏</span>';
                    $html .= '<span class="vss-label">Twitter</span>';
                    $html .= '</a>';
                    break;

                case 'linkedin':
                    $share_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($post_url);
                    $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-linkedin" target="_blank" rel="noopener">';
                    $html .= '<span class="vss-icon">💼</span>';
                    $html .= '<span class="vss-label">LinkedIn</span>';
                    $html .= '</a>';
                    break;

                case 'pinterest':
                    if ($post_thumbnail) {
                        $share_url = 'https://pinterest.com/pin/create/button/?url=' . urlencode($post_url) . '&media=' . urlencode($post_thumbnail) . '&description=' . urlencode($post_title);
                        $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-pinterest" target="_blank" rel="noopener">';
                        $html .= '<span class="vss-icon">📌</span>';
                        $html .= '<span class="vss-label">Pinterest</span>';
                        $html .= '</a>';
                    }
                    break;

                case 'reddit':
                    $share_url = 'https://reddit.com/submit?url=' . urlencode($post_url) . '&title=' . urlencode($post_title);
                    $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-reddit" target="_blank" rel="noopener">';
                    $html .= '<span class="vss-icon">🤖</span>';
                    $html .= '<span class="vss-label">Reddit</span>';
                    $html .= '</a>';
                    break;

                case 'whatsapp':
                    $share_url = 'https://wa.me/?text=' . urlencode($post_title . ' ' . $post_url);
                    $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-whatsapp" target="_blank" rel="noopener">';
                    $html .= '<span class="vss-icon">💬</span>';
                    $html .= '<span class="vss-label">WhatsApp</span>';
                    $html .= '</a>';
                    break;

                case 'telegram':
                    $share_url = 'https://t.me/share/url?url=' . urlencode($post_url) . '&text=' . urlencode($post_title);
                    $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-telegram" target="_blank" rel="noopener">';
                    $html .= '<span class="vss-icon">✈️</span>';
                    $html .= '<span class="vss-label">Telegram</span>';
                    $html .= '</a>';
                    break;

                case 'email':
                    $share_url = 'mailto:?subject=' . urlencode($post_title) . '&body=' . urlencode($post_url);
                    $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-email">';
                    $html .= '<span class="vss-icon">✉️</span>';
                    $html .= '<span class="vss-label">Email</span>';
                    $html .= '</a>';
                    break;

                case 'youtube':
                    if ($youtube_channel) {
                        $html .= '<a href="' . esc_url($youtube_channel) . '?sub_confirmation=1" class="vss-button vss-youtube" target="_blank" rel="noopener">';
                        $html .= '<span class="vss-icon">▶️</span>';
                        $html .= '<span class="vss-label">Subscribe</span>';
                        $html .= '</a>';
                    }
                    break;
            }
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    // Add buttons to content
    public function add_share_buttons($content) {
        if (!is_singular('post')) {
            return $content;
        }

        $options = get_option('vss_settings');
        $position = isset($options['vss_position']) ? $options['vss_position'] : 'both';

        if ($position === 'manual') {
            return $content;
        }

        $buttons = $this->generate_buttons();

        if ($position === 'before') {
            return $buttons . $content;
        } elseif ($position === 'after') {
            return $content . $buttons;
        } else { // both
            return $buttons . $content . $buttons;
        }
    }

    // Floating sidebar buttons
    public function add_floating_buttons() {
        $options = get_option('vss_settings');
        $floating = isset($options['vss_floating']) ? $options['vss_floating'] : '0';

        if ($floating === '1' && is_singular('post')) {
            echo '<div class="vss-floating-sidebar">';
            echo $this->generate_buttons();
            echo '</div>';
        }
    }

    // Shortcode
    public function social_share_shortcode($atts) {
        return $this->generate_buttons();
    }
}

// Initialize the plugin
new VideoSocialSharePro();

// Create assets directory structure on activation
register_activation_hook(__FILE__, 'vss_create_assets');
function vss_create_assets() {
    $upload_dir = wp_upload_dir();
    $plugin_dir = plugin_dir_path(__FILE__);

    // Create assets directory if it doesn't exist
    if (!file_exists($plugin_dir . 'assets')) {
        mkdir($plugin_dir . 'assets', 0755, true);
    }

    // Create CSS file
    $css_content = "/* Video Social Share Pro Styles */

.vss-share-buttons {
    margin: 30px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    clear: both;
}

.vss-buttons-wrapper {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}

.vss-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
    color: #fff;
}

.vss-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    text-decoration: none;
    color: #fff;
}

.vss-icon {
    font-size: 18px;
}

/* Network Colors */
.vss-facebook { background: #1877f2; }
.vss-twitter { background: #000; }
.vss-linkedin { background: #0077b5; }
.vss-pinterest { background: #e60023; }
.vss-reddit { background: #ff4500; }
.vss-whatsapp { background: #25d366; }
.vss-telegram { background: #0088cc; }
.vss-email { background: #666; }
.vss-youtube { background: #ff0000; }

/* Style Variations */
.vss-style-rounded .vss-button {
    border-radius: 50px;
}

.vss-style-square .vss-button {
    border-radius: 0;
}

.vss-style-minimal .vss-button {
    background: transparent;
    border: 2px solid currentColor;
    padding: 10px 18px;
}

.vss-style-minimal .vss-facebook { color: #1877f2; }
.vss-style-minimal .vss-twitter { color: #000; }
.vss-style-minimal .vss-linkedin { color: #0077b5; }
.vss-style-minimal .vss-pinterest { color: #e60023; }
.vss-style-minimal .vss-reddit { color: #ff4500; }
.vss-style-minimal .vss-whatsapp { color: #25d366; }
.vss-style-minimal .vss-telegram { color: #0088cc; }
.vss-style-minimal .vss-email { color: #666; }
.vss-style-minimal .vss-youtube { color: #ff0000; }

.vss-style-minimal .vss-button:hover {
    color: #fff !important;
}

.vss-style-minimal .vss-facebook:hover { background: #1877f2; }
.vss-style-minimal .vss-twitter:hover { background: #000; }
.vss-style-minimal .vss-linkedin:hover { background: #0077b5; }
.vss-style-minimal .vss-pinterest:hover { background: #e60023; }
.vss-style-minimal .vss-reddit:hover { background: #ff4500; }
.vss-style-minimal .vss-whatsapp:hover { background: #25d366; }
.vss-style-minimal .vss-telegram:hover { background: #0088cc; }
.vss-style-minimal .vss-email:hover { background: #666; }
.vss-style-minimal .vss-youtube:hover { background: #ff0000; }

.vss-style-boxed {
    background: transparent;
    padding: 0;
}

.vss-style-boxed .vss-buttons-wrapper {
    background: #fff;
    padding: 20px;
    border: 2px solid #e1e8ed;
    border-radius: 8px;
}

/* Floating Sidebar */
.vss-floating-sidebar {
    position: fixed;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1000;
}

.vss-floating-sidebar .vss-share-buttons {
    background: transparent;
    padding: 0;
    margin: 0;
}

.vss-floating-sidebar .vss-buttons-wrapper {
    flex-direction: column;
    gap: 10px;
}

.vss-floating-sidebar .vss-button {
    width: 50px;
    height: 50px;
    justify-content: center;
    padding: 0;
    border-radius: 50%;
}

.vss-floating-sidebar .vss-label {
    display: none;
}

.vss-floating-sidebar .vss-icon {
    font-size: 20px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .vss-buttons-wrapper {
        justify-content: flex-start;
    }

    .vss-button {
        font-size: 12px;
        padding: 10px 16px;
    }

    .vss-floating-sidebar {
        display: none;
    }
}

@media (max-width: 480px) {
    .vss-button .vss-label {
        display: none;
    }

    .vss-button {
        width: 45px;
        height: 45px;
        justify-content: center;
        padding: 0;
        border-radius: 50%;
    }
}";

    file_put_contents($plugin_dir . 'assets/style.css', $css_content);

    // Create JS file
    $js_content = "// Video Social Share Pro JavaScript
(function($) {
    'use strict';

    $(document).ready(function() {
        // Track share clicks
        $('.vss-button').on('click', function(e) {
            var network = $(this).attr('class').match(/vss-([a-z]+)/)[1];

            // Open in popup for social networks (except email)
            if (network !== 'email' && network !== 'youtube') {
                e.preventDefault();
                var url = $(this).attr('href');
                var width = 600;
                var height = 400;
                var left = (screen.width - width) / 2;
                var top = (screen.height - height) / 2;

                window.open(
                    url,
                    'share',
                    'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top
                );
            }

            // Optional: Track analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'share', {
                    'method': network,
                    'content_type': 'post',
                    'item_id': window.location.href
                });
            }
        });

        // Smooth scroll for floating sidebar
        if ($('.vss-floating-sidebar').length) {
            var sidebar = $('.vss-floating-sidebar');
            var startPos = 300;

            $(window).on('scroll', function() {
                if ($(window).scrollTop() > startPos) {
                    sidebar.fadeIn();
                } else {
                    sidebar.fadeOut();
                }
            });

            // Hide initially if at top
            if ($(window).scrollTop() <= startPos) {
                sidebar.hide();
            }
        }
    });

})(jQuery);";

    file_put_contents($plugin_dir . 'assets/script.js', $js_content);
}
?>
