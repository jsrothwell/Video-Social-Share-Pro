<?php
/**
 * Plugin Name: Video Social Share Pro
 * Plugin URI: https://github.com/jsrothwell/Video-Social-Share-Pro
 * Description: Professional social sharing buttons optimized for video content sites with YouTube integration
 * Version: 1.0.1
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
         // Font Awesome for icons
         wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
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
         <p class="description">Choose where the share buttons appear on your posts</p>
         <?php
     }

     public function style_render() {
         $options = get_option('vss_settings');
         $style = isset($options['vss_style']) ? $options['vss_style'] : 'flat';
         ?>
         <select name='vss_settings[vss_style]'>
             <option value='flat' <?php selected($style, 'flat'); ?>>Flat (Modern)</option>
             <option value='glass' <?php selected($style, 'glass'); ?>>Glass (Translucent)</option>
             <option value='wire' <?php selected($style, 'wire'); ?>>Wire (Outline)</option>
             <option value='popup' <?php selected($style, 'popup'); ?>>Popup (Expandable)</option>
         </select>
         <p class="description">Select the visual style for your share buttons</p>
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
                 <h3>📌 Shortcode Usage</h3>
                 <p>Use the shortcode <code>[social_share]</code> to manually place share buttons anywhere in your content.</p>
                 <p>You can also use it in your theme files: <code>&lt;?php echo do_shortcode('[social_share]'); ?&gt;</code></p>
             </div>

             <div style="margin-top: 20px; padding: 20px; background: #fff; border-left: 4px solid #00a32a;">
                 <h3>🎨 Button Style Guide</h3>
                 <ul style="line-height: 2;">
                     <li><strong>Flat (Modern):</strong> Clean, solid color buttons with subtle shadows - perfect for modern sites</li>
                     <li><strong>Glass (Translucent):</strong> Glassmorphism effect with blur and transparency - trendy and eye-catching</li>
                     <li><strong>Wire (Outline):</strong> Minimal outlined buttons that fill on hover - clean and professional</li>
                     <li><strong>Popup (Expandable):</strong> Space-saving toggle button that expands to show all networks</li>
                 </ul>
             </div>

             <div style="margin-top: 20px; padding: 20px; background: #fff; border-left: 4px solid #f0b849;">
                 <h3>💡 Tips for Video Sites</h3>
                 <ul style="line-height: 2;">
                     <li>Enable the <strong>YouTube Subscribe</strong> button to grow your channel</li>
                     <li>Use <strong>Floating Sidebar</strong> for longer video descriptions</li>
                     <li>Select <strong>Popup style</strong> for a clean, unobtrusive sharing experience</li>
                     <li>Place buttons <strong>After Content</strong> so viewers share after watching</li>
                 </ul>
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
         $style = isset($options['vss_style']) ? $options['vss_style'] : 'flat';
         $show_counts = isset($options['vss_show_counts']) ? $options['vss_show_counts'] : '0';
         $youtube_channel = isset($options['vss_youtube_channel']) ? $options['vss_youtube_channel'] : '';

         $post_url = get_permalink($post_id);
         $post_title = get_the_title($post_id);
         $post_thumbnail = get_the_post_thumbnail_url($post_id, 'full');

         $html = '<div class="vss-share-buttons vss-style-' . esc_attr($style) . '">';

         // Add popup toggle button for popup style
         if ($style === 'popup') {
             $html .= '<input type="checkbox" class="vss-popup-checkbox" id="vss-popup-' . $post_id . '" />';
             $html .= '<label for="vss-popup-' . $post_id . '" class="vss-popup-toggle">';
             $html .= '<i class="fas fa-share-alt"></i> <span>Share</span>';
             $html .= '</label>';
         }

         $html .= '<div class="vss-buttons-wrapper">';

         foreach ($networks as $network) {
             switch ($network) {
                 case 'facebook':
                     $share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($post_url);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-facebook" target="_blank" rel="noopener" title="Share on Facebook">';
                     $html .= '<i class="fab fa-facebook-f"></i>';
                     $html .= '<span class="vss-label">Facebook</span>';
                     $html .= '</a>';
                     break;

                 case 'twitter':
                     $share_url = 'https://twitter.com/intent/tweet?url=' . urlencode($post_url) . '&text=' . urlencode($post_title);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-twitter" target="_blank" rel="noopener" title="Share on Twitter">';
                     $html .= '<i class="fab fa-x-twitter"></i>';
                     $html .= '<span class="vss-label">Twitter</span>';
                     $html .= '</a>';
                     break;

                 case 'linkedin':
                     $share_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($post_url);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-linkedin" target="_blank" rel="noopener" title="Share on LinkedIn">';
                     $html .= '<i class="fab fa-linkedin-in"></i>';
                     $html .= '<span class="vss-label">LinkedIn</span>';
                     $html .= '</a>';
                     break;

                 case 'pinterest':
                     if ($post_thumbnail) {
                         $share_url = 'https://pinterest.com/pin/create/button/?url=' . urlencode($post_url) . '&media=' . urlencode($post_thumbnail) . '&description=' . urlencode($post_title);
                         $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-pinterest" target="_blank" rel="noopener" title="Pin on Pinterest">';
                         $html .= '<i class="fab fa-pinterest-p"></i>';
                         $html .= '<span class="vss-label">Pinterest</span>';
                         $html .= '</a>';
                     }
                     break;

                 case 'reddit':
                     $share_url = 'https://reddit.com/submit?url=' . urlencode($post_url) . '&title=' . urlencode($post_title);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-reddit" target="_blank" rel="noopener" title="Share on Reddit">';
                     $html .= '<i class="fab fa-reddit-alien"></i>';
                     $html .= '<span class="vss-label">Reddit</span>';
                     $html .= '</a>';
                     break;

                 case 'whatsapp':
                     $share_url = 'https://wa.me/?text=' . urlencode($post_title . ' ' . $post_url);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-whatsapp" target="_blank" rel="noopener" title="Share on WhatsApp">';
                     $html .= '<i class="fab fa-whatsapp"></i>';
                     $html .= '<span class="vss-label">WhatsApp</span>';
                     $html .= '</a>';
                     break;

                 case 'telegram':
                     $share_url = 'https://t.me/share/url?url=' . urlencode($post_url) . '&text=' . urlencode($post_title);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-telegram" target="_blank" rel="noopener" title="Share on Telegram">';
                     $html .= '<i class="fab fa-telegram-plane"></i>';
                     $html .= '<span class="vss-label">Telegram</span>';
                     $html .= '</a>';
                     break;

                 case 'email':
                     $share_url = 'mailto:?subject=' . urlencode($post_title) . '&body=' . urlencode($post_url);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-email" title="Share via Email">';
                     $html .= '<i class="fas fa-envelope"></i>';
                     $html .= '<span class="vss-label">Email</span>';
                     $html .= '</a>';
                     break;

                 case 'youtube':
                     if ($youtube_channel) {
                         $html .= '<a href="' . esc_url($youtube_channel) . '?sub_confirmation=1" class="vss-button vss-youtube" target="_blank" rel="noopener" title="Subscribe on YouTube">';
                         $html .= '<i class="fab fa-youtube"></i>';
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
     $css_content = "/* Video Social Share Pro - Modern Bootstrap Style */

 /* Base Container */
 .vss-share-buttons {
     margin: 25px 0;
     padding: 0;
     clear: both;
     text-align: center;
 }

 .vss-buttons-wrapper {
     display: inline-flex;
     flex-wrap: wrap;
     gap: 10px;
     justify-content: center;
     align-items: center;
 }

 /* Base Button Styles */
 .vss-button {
     display: inline-flex;
     align-items: center;
     justify-content: center;
     gap: 7px;
     padding: 9px 16px;
     border: none;
     border-radius: 4px;
     text-decoration: none !important;
     font-size: 14px;
     font-weight: 500;
     font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
     transition: all 0.15s ease-in-out;
     cursor: pointer;
     position: relative;
     line-height: 1.5;
     white-space: nowrap;
     min-height: 38px;
 }

 .vss-button:hover {
     text-decoration: none !important;
     transform: translateY(-1px);
 }

 .vss-button:active {
     transform: translateY(0);
 }

 .vss-button:focus {
     outline: none;
     text-decoration: none !important;
 }

 .vss-button i {
     font-size: 16px;
     line-height: 1;
     width: 16px;
     text-align: center;
 }

 .vss-label {
     font-size: 14px;
     font-weight: 500;
     line-height: 1;
     text-decoration: none !important;
 }

 /* ========================================
    FLAT STYLE (Bootstrap Default)
    ======================================== */

 .vss-style-flat {
     background: transparent;
     padding: 0;
 }

 .vss-style-flat .vss-button {
     color: #fff;
     box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
 }

 .vss-style-flat .vss-button:hover {
     box-shadow: 0 2px 6px rgba(0, 0, 0, 0.16);
     filter: brightness(1.05);
 }

 .vss-style-flat .vss-button:active {
     box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
     filter: brightness(0.95);
 }

 /* Flat Colors */
 .vss-style-flat .vss-facebook { background-color: #1877f2; }
 .vss-style-flat .vss-facebook:hover { background-color: #166fe5; }

 .vss-style-flat .vss-twitter { background-color: #000000; }
 .vss-style-flat .vss-twitter:hover { background-color: #1a1a1a; }

 .vss-style-flat .vss-linkedin { background-color: #0a66c2; }
 .vss-style-flat .vss-linkedin:hover { background-color: #004182; }

 .vss-style-flat .vss-pinterest { background-color: #e60023; }
 .vss-style-flat .vss-pinterest:hover { background-color: #bd081c; }

 .vss-style-flat .vss-reddit { background-color: #ff4500; }
 .vss-style-flat .vss-reddit:hover { background-color: #e03d00; }

 .vss-style-flat .vss-whatsapp { background-color: #25d366; }
 .vss-style-flat .vss-whatsapp:hover { background-color: #20ba5a; }

 .vss-style-flat .vss-telegram { background-color: #0088cc; }
 .vss-style-flat .vss-telegram:hover { background-color: #0077b3; }

 .vss-style-flat .vss-email { background-color: #6c757d; }
 .vss-style-flat .vss-email:hover { background-color: #5a6268; }

 .vss-style-flat .vss-youtube { background-color: #ff0000; }
 .vss-style-flat .vss-youtube:hover { background-color: #cc0000; }

 /* ========================================
    GLASS STYLE (Glassmorphism)
    ======================================== */

 .vss-style-glass {
     background: transparent;
     padding: 0;
 }

 .vss-style-glass .vss-button {
     background: rgba(255, 255, 255, 0.15);
     backdrop-filter: blur(10px);
     -webkit-backdrop-filter: blur(10px);
     border: 1px solid rgba(255, 255, 255, 0.25);
     color: #fff;
     box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
     text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
 }

 .vss-style-glass .vss-button:hover {
     background: rgba(255, 255, 255, 0.25);
     border-color: rgba(255, 255, 255, 0.35);
     box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
 }

 /* ========================================
    WIRE STYLE (Outline)
    ======================================== */

 .vss-style-wire {
     background: transparent;
     padding: 0;
 }

 .vss-style-wire .vss-button {
     background: transparent;
     border: 2px solid;
     font-weight: 500;
     box-shadow: none;
 }

 .vss-style-wire .vss-button:hover {
     color: #fff !important;
     box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
 }

 /* Wire Colors */
 .vss-style-wire .vss-facebook {
     color: #1877f2;
     border-color: #1877f2;
 }
 .vss-style-wire .vss-facebook:hover {
     background-color: #1877f2;
 }

 .vss-style-wire .vss-twitter {
     color: #000000;
     border-color: #000000;
 }
 .vss-style-wire .vss-twitter:hover {
     background-color: #000000;
 }

 .vss-style-wire .vss-linkedin {
     color: #0a66c2;
     border-color: #0a66c2;
 }
 .vss-style-wire .vss-linkedin:hover {
     background-color: #0a66c2;
 }

 .vss-style-wire .vss-pinterest {
     color: #e60023;
     border-color: #e60023;
 }
 .vss-style-wire .vss-pinterest:hover {
     background-color: #e60023;
 }

 .vss-style-wire .vss-reddit {
     color: #ff4500;
     border-color: #ff4500;
 }
 .vss-style-wire .vss-reddit:hover {
     background-color: #ff4500;
 }

 .vss-style-wire .vss-whatsapp {
     color: #25d366;
     border-color: #25d366;
 }
 .vss-style-wire .vss-whatsapp:hover {
     background-color: #25d366;
 }

 .vss-style-wire .vss-telegram {
     color: #0088cc;
     border-color: #0088cc;
 }
 .vss-style-wire .vss-telegram:hover {
     background-color: #0088cc;
 }

 .vss-style-wire .vss-email {
     color: #6c757d;
     border-color: #6c757d;
 }
 .vss-style-wire .vss-email:hover {
     background-color: #6c757d;
 }

 .vss-style-wire .vss-youtube {
     color: #ff0000;
     border-color: #ff0000;
 }
 .vss-style-wire .vss-youtube:hover {
     background-color: #ff0000;
 }

 /* ========================================
    FLOATING SIDEBAR
    ======================================== */

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
     width: 48px;
     height: 48px;
     justify-content: center;
     padding: 0;
     border-radius: 50%;
     box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
 }

 .vss-floating-sidebar .vss-button:hover {
     box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
 }

 .vss-floating-sidebar .vss-label {
     display: none;
 }

 .vss-floating-sidebar .vss-button i {
     font-size: 18px;
 }

 /* ========================================
    POPUP STYLE
    ======================================== */

 .vss-style-popup {
     background: transparent;
     padding: 0;
     position: relative;
 }

 .vss-popup-checkbox {
     display: none;
 }

 .vss-popup-toggle {
     background-color: #007bff;
     font-size: 14px;
     font-weight: 500;
     cursor: pointer;
     margin: 0 auto;
     padding: 9px 18px;
     border-radius: 4px;
     color: #fff;
     display: inline-flex;
     align-items: center;
     gap: 7px;
     transition: all 0.15s ease-in-out;
     box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
     text-decoration: none !important;
     border: none;
     min-height: 38px;
 }

 .vss-popup-toggle:hover {
     background-color: #0056b3;
     box-shadow: 0 2px 6px rgba(0, 0, 0, 0.16);
     transform: translateY(-1px);
     text-decoration: none !important;
 }

 .vss-popup-toggle i {
     font-size: 14px;
 }

 .vss-popup-checkbox:checked + .vss-popup-toggle {
     background-color: #6c757d;
 }

 .vss-popup-checkbox:checked + .vss-popup-toggle:hover {
     background-color: #5a6268;
 }

 .vss-style-popup .vss-buttons-wrapper {
     opacity: 0;
     transform: scale(0.9) translateY(-10px);
     transform-origin: 50% 0%;
     transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
     position: relative;
     margin-top: 15px;
     display: flex;
     flex-wrap: wrap;
     justify-content: center;
     gap: 10px;
     pointer-events: none;
 }

 .vss-popup-checkbox:checked ~ .vss-buttons-wrapper {
     opacity: 1;
     transform: scale(1) translateY(0);
     pointer-events: auto;
 }

 .vss-style-popup .vss-button {
     box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
 }

 .vss-style-popup .vss-button:hover {
     box-shadow: 0 2px 6px rgba(0, 0, 0, 0.16);
 }

 /* ========================================
    RESPONSIVE DESIGN
    ======================================== */

 @media (max-width: 768px) {
     .vss-buttons-wrapper {
         gap: 8px;
     }

     .vss-button {
         font-size: 13px;
         padding: 8px 14px;
         min-height: 36px;
     }

     .vss-button i {
         font-size: 15px;
     }

     .vss-label {
         font-size: 13px;
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
         width: 42px;
         height: 42px;
         min-height: 42px;
         justify-content: center;
         padding: 0;
         border-radius: 50%;
     }

     .vss-button i {
         font-size: 18px;
         width: auto;
     }

     .vss-style-wire .vss-button {
         width: 42px;
         height: 42px;
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

         // Popup style: Close when clicking outside
         $(document).on('click', function(e) {
             if (!$(e.target).closest('.vss-style-popup').length) {
                 $('.vss-popup-checkbox').prop('checked', false);
             }
         });

         // Popup style: Toggle on label click
         $('.vss-popup-toggle').on('click', function(e) {
             e.stopPropagation();
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
