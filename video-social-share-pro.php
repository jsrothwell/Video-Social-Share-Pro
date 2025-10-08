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
             $html .= '<i class="vss-share-icon">⤴</i> <span>Share</span>';
             $html .= '</label>';
         }

         $html .= '<div class="vss-buttons-wrapper">';

         foreach ($networks as $network) {
             switch ($network) {
                 case 'facebook':
                     $share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($post_url);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-facebook" target="_blank" rel="noopener" title="Share on Facebook">';
                     $html .= '<span class="vss-icon">f</span>';
                     $html .= '<span class="vss-label">Facebook</span>';
                     $html .= '</a>';
                     break;

                 case 'twitter':
                     $share_url = 'https://twitter.com/intent/tweet?url=' . urlencode($post_url) . '&text=' . urlencode($post_title);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-twitter" target="_blank" rel="noopener" title="Share on Twitter">';
                     $html .= '<span class="vss-icon">𝕏</span>';
                     $html .= '<span class="vss-label">Twitter</span>';
                     $html .= '</a>';
                     break;

                 case 'linkedin':
                     $share_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($post_url);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-linkedin" target="_blank" rel="noopener" title="Share on LinkedIn">';
                     $html .= '<span class="vss-icon">in</span>';
                     $html .= '<span class="vss-label">LinkedIn</span>';
                     $html .= '</a>';
                     break;

                 case 'pinterest':
                     if ($post_thumbnail) {
                         $share_url = 'https://pinterest.com/pin/create/button/?url=' . urlencode($post_url) . '&media=' . urlencode($post_thumbnail) . '&description=' . urlencode($post_title);
                         $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-pinterest" target="_blank" rel="noopener" title="Pin on Pinterest">';
                         $html .= '<span class="vss-icon">℗</span>';
                         $html .= '<span class="vss-label">Pinterest</span>';
                         $html .= '</a>';
                     }
                     break;

                 case 'reddit':
                     $share_url = 'https://reddit.com/submit?url=' . urlencode($post_url) . '&title=' . urlencode($post_title);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-reddit" target="_blank" rel="noopener" title="Share on Reddit">';
                     $html .= '<span class="vss-icon">⊚</span>';
                     $html .= '<span class="vss-label">Reddit</span>';
                     $html .= '</a>';
                     break;

                 case 'whatsapp':
                     $share_url = 'https://wa.me/?text=' . urlencode($post_title . ' ' . $post_url);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-whatsapp" target="_blank" rel="noopener" title="Share on WhatsApp">';
                     $html .= '<span class="vss-icon">⌘</span>';
                     $html .= '<span class="vss-label">WhatsApp</span>';
                     $html .= '</a>';
                     break;

                 case 'telegram':
                     $share_url = 'https://t.me/share/url?url=' . urlencode($post_url) . '&text=' . urlencode($post_title);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-telegram" target="_blank" rel="noopener" title="Share on Telegram">';
                     $html .= '<span class="vss-icon">✈</span>';
                     $html .= '<span class="vss-label">Telegram</span>';
                     $html .= '</a>';
                     break;

                 case 'email':
                     $share_url = 'mailto:?subject=' . urlencode($post_title) . '&body=' . urlencode($post_url);
                     $html .= '<a href="' . esc_url($share_url) . '" class="vss-button vss-email" title="Share via Email">';
                     $html .= '<span class="vss-icon">✉</span>';
                     $html .= '<span class="vss-label">Email</span>';
                     $html .= '</a>';
                     break;

                 case 'youtube':
                     if ($youtube_channel) {
                         $html .= '<a href="' . esc_url($youtube_channel) . '?sub_confirmation=1" class="vss-button vss-youtube" target="_blank" rel="noopener" title="Subscribe on YouTube">';
                         $html .= '<span class="vss-icon">▶</span>';
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
     $css_content = "/* Video Social Share Pro Styles - Bootstrap Inspired */

 /* Base Container Styles */
 .vss-share-buttons {
     margin: 20px 0;
     padding: 15px;
     clear: both;
     text-align: center;
 }

 .vss-buttons-wrapper {
     display: inline-flex;
     flex-wrap: wrap;
     gap: 8px;
     justify-content: center;
     align-items: center;
 }

 /* Base Button Styles */
 .vss-button {
     display: inline-flex;
     align-items: center;
     justify-content: center;
     gap: 6px;
     padding: 8px 14px;
     border: none;
     border-radius: 5px;
     text-decoration: none !important;
     font-size: 15px;
     font-weight: 600;
     font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
     transition: all 0.2s ease;
     cursor: pointer;
     position: relative;
     overflow: hidden;
     line-height: 1;
     white-space: nowrap;
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

 .vss-icon {
     font-size: 18px;
     font-style: normal;
     font-weight: bold;
     line-height: 1;
 }

 .vss-label {
     font-size: 15px;
     font-weight: 600;
     line-height: 1;
     text-decoration: none !important;
 }

 /* ========================================
    FLAT STYLE (Modern Bootstrap)
    ======================================== */

 .vss-style-flat {
     background: transparent;
     padding: 0;
 }

 .vss-style-flat .vss-button {
     box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
     color: #fff;
 }

 .vss-style-flat .vss-button:hover {
     box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
     filter: brightness(1.08);
 }

 .vss-style-flat .vss-button:active {
     box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
 }

 /* Flat Network Colors */
 .vss-style-flat .vss-facebook { background: #1877f2; }
 .vss-style-flat .vss-facebook:hover { background: #166fe5; }

 .vss-style-flat .vss-twitter { background: #000000; }
 .vss-style-flat .vss-twitter:hover { background: #1a1a1a; }

 .vss-style-flat .vss-linkedin { background: #0a66c2; }
 .vss-style-flat .vss-linkedin:hover { background: #095196; }

 .vss-style-flat .vss-pinterest { background: #e60023; }
 .vss-style-flat .vss-pinterest:hover { background: #cc001f; }

 .vss-style-flat .vss-reddit { background: #ff4500; }
 .vss-style-flat .vss-reddit:hover { background: #e63e00; }

 .vss-style-flat .vss-whatsapp { background: #25d366; }
 .vss-style-flat .vss-whatsapp:hover { background: #20bd5a; }

 .vss-style-flat .vss-telegram { background: #0088cc; }
 .vss-style-flat .vss-telegram:hover { background: #0077b3; }

 .vss-style-flat .vss-email { background: #6c757d; }
 .vss-style-flat .vss-email:hover { background: #5a6268; }

 .vss-style-flat .vss-youtube { background: #ff0000; }
 .vss-style-flat .vss-youtube:hover { background: #e60000; }

 /* ========================================
    GLASS STYLE (Glassmorphism)
    ======================================== */

 .vss-style-glass {
     background: transparent;
     padding: 0;
 }

 .vss-style-glass .vss-button {
     background: rgba(255, 255, 255, 0.1);
     backdrop-filter: blur(10px);
     -webkit-backdrop-filter: blur(10px);
     border: 1px solid rgba(255, 255, 255, 0.2);
     box-shadow: 0 4px 16px 0 rgba(0, 0, 0, 0.1);
     color: #fff;
     position: relative;
 }

 .vss-style-glass .vss-button::before {
     content: '';
     position: absolute;
     top: 0;
     left: 0;
     right: 0;
     bottom: 0;
     border-radius: 5px;
     opacity: 0;
     transition: opacity 0.2s ease;
 }

 .vss-style-glass .vss-button:hover {
     border-color: rgba(255, 255, 255, 0.3);
     box-shadow: 0 6px 20px 0 rgba(0, 0, 0, 0.2);
 }

 .vss-style-glass .vss-button:hover::before {
     opacity: 1;
 }

 /* Glass Network Colors (Applied as gradient overlays) */
 .vss-style-glass .vss-facebook::before { background: linear-gradient(135deg, rgba(24, 119, 242, 0.3), rgba(24, 119, 242, 0.6)); }
 .vss-style-glass .vss-twitter::before { background: linear-gradient(135deg, rgba(0, 0, 0, 0.3), rgba(0, 0, 0, 0.6)); }
 .vss-style-glass .vss-linkedin::before { background: linear-gradient(135deg, rgba(10, 102, 194, 0.3), rgba(10, 102, 194, 0.6)); }
 .vss-style-glass .vss-pinterest::before { background: linear-gradient(135deg, rgba(230, 0, 35, 0.3), rgba(230, 0, 35, 0.6)); }
 .vss-style-glass .vss-reddit::before { background: linear-gradient(135deg, rgba(255, 69, 0, 0.3), rgba(255, 69, 0, 0.6)); }
 .vss-style-glass .vss-whatsapp::before { background: linear-gradient(135deg, rgba(37, 211, 102, 0.3), rgba(37, 211, 102, 0.6)); }
 .vss-style-glass .vss-telegram::before { background: linear-gradient(135deg, rgba(0, 136, 204, 0.3), rgba(0, 136, 204, 0.6)); }
 .vss-style-glass .vss-email::before { background: linear-gradient(135deg, rgba(108, 117, 125, 0.3), rgba(108, 117, 125, 0.6)); }
 .vss-style-glass .vss-youtube::before { background: linear-gradient(135deg, rgba(255, 0, 0, 0.3), rgba(255, 0, 0, 0.6)); }

 /* ========================================
    WIRE STYLE (Outline/Ghost)
    ======================================== */

 .vss-style-wire {
     background: transparent;
     padding: 0;
 }

 .vss-style-wire .vss-button {
     background: transparent;
     border: 2px solid;
     box-shadow: none;
     font-weight: 600;
     padding: 6px 12px;
 }

 .vss-style-wire .vss-button:hover {
     color: #fff !important;
     box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
 }

 /* Wire Network Colors */
 .vss-style-wire .vss-facebook {
     color: #1877f2;
     border-color: #1877f2;
 }
 .vss-style-wire .vss-facebook:hover {
     background: #1877f2;
 }

 .vss-style-wire .vss-twitter {
     color: #000000;
     border-color: #000000;
 }
 .vss-style-wire .vss-twitter:hover {
     background: #000000;
 }

 .vss-style-wire .vss-linkedin {
     color: #0a66c2;
     border-color: #0a66c2;
 }
 .vss-style-wire .vss-linkedin:hover {
     background: #0a66c2;
 }

 .vss-style-wire .vss-pinterest {
     color: #e60023;
     border-color: #e60023;
 }
 .vss-style-wire .vss-pinterest:hover {
     background: #e60023;
 }

 .vss-style-wire .vss-reddit {
     color: #ff4500;
     border-color: #ff4500;
 }
 .vss-style-wire .vss-reddit:hover {
     background: #ff4500;
 }

 .vss-style-wire .vss-whatsapp {
     color: #25d366;
     border-color: #25d366;
 }
 .vss-style-wire .vss-whatsapp:hover {
     background: #25d366;
 }

 .vss-style-wire .vss-telegram {
     color: #0088cc;
     border-color: #0088cc;
 }
 .vss-style-wire .vss-telegram:hover {
     background: #0088cc;
 }

 .vss-style-wire .vss-email {
     color: #6c757d;
     border-color: #6c757d;
 }
 .vss-style-wire .vss-email:hover {
     background: #6c757d;
 }

 .vss-style-wire .vss-youtube {
     color: #ff0000;
     border-color: #ff0000;
 }
 .vss-style-wire .vss-youtube:hover {
     background: #ff0000;
 }

 /* ========================================
    LEGACY STYLES (Kept for compatibility)
    ======================================== */

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
     width: 45px;
     height: 45px;
     justify-content: center;
     padding: 0;
     border-radius: 50%;
 }

 .vss-floating-sidebar .vss-label {
     display: none;
 }

 .vss-floating-sidebar .vss-icon {
     font-size: 18px;
 }

 /* ========================================
    POPUP STYLE
    ======================================== */

 .vss-style-popup {
     background: transparent;
     padding: 0;
     position: relative;
     min-height: 100px;
 }

 .vss-popup-checkbox {
     display: none;
 }

 .vss-popup-toggle {
     background: #2c3e50;
     font-size: 15px;
     font-weight: 600;
     cursor: pointer;
     margin: 0 auto;
     padding: 8px 16px;
     border-radius: 5px;
     color: #ecf0f1;
     display: inline-flex;
     align-items: center;
     gap: 6px;
     transition: all 0.2s ease;
     box-shadow: 0 2px 6px rgba(0,0,0,0.1);
     text-decoration: none !important;
 }

 .vss-popup-toggle:hover {
     background: #34495e;
     transform: translateY(-1px);
     box-shadow: 0 3px 10px rgba(0,0,0,0.15);
     text-decoration: none !important;
 }

 .vss-share-icon {
     font-size: 16px;
     font-style: normal;
     line-height: 1;
 }

 .vss-popup-checkbox:checked + .vss-popup-toggle {
     background: #95a5a6;
     color: #2c3e50;
 }

 .vss-style-popup .vss-buttons-wrapper {
     opacity: 0;
     transform: scale(0) translateY(-20px);
     transform-origin: 50% 0%;
     transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
     position: relative;
     margin-top: 15px;
     display: flex;
     flex-wrap: nowrap;
     justify-content: center;
     background: #3b5998;
     padding: 0;
     border-radius: 6px;
     overflow: hidden;
     box-shadow: 0 3px 15px rgba(0,0,0,0.15);
 }

 .vss-popup-checkbox:checked ~ .vss-buttons-wrapper {
     opacity: 1;
     transform: scale(1) translateY(0);
 }

 .vss-style-popup .vss-buttons-wrapper::after {
     content: '';
     display: block;
     position: absolute;
     left: 50%;
     bottom: 100%;
     margin-left: -10px;
     width: 0;
     height: 0;
     border-left: 10px solid transparent;
     border-right: 10px solid transparent;
     border-bottom: 10px solid #3b5998;
 }

 .vss-style-popup .vss-button {
     flex: 1;
     min-width: 55px;
     height: 55px;
     display: flex;
     align-items: center;
     justify-content: center;
     color: #fff;
     font-size: 22px;
     border-radius: 0;
     padding: 0;
     margin: 0;
     transition: all 0.2s ease;
     border-right: 1px solid rgba(255,255,255,0.1);
 }

 .vss-style-popup .vss-button:last-child {
     border-right: none;
 }

 .vss-style-popup .vss-button:hover {
     background: rgba(0,0,0,0.2);
     transform: translateY(-2px) scale(1.05);
     z-index: 10;
 }

 .vss-style-popup .vss-label {
     display: none;
 }

 .vss-style-popup .vss-icon {
     font-size: 22px;
 }

 /* Popup Style Network Colors */
 .vss-style-popup .vss-facebook { background: #3b5998; }
 .vss-style-popup .vss-twitter { background: #6cdfea; }
 .vss-style-popup .vss-linkedin { background: #0077b5; }
 .vss-style-popup .vss-pinterest { background: #e60023; }
 .vss-style-popup .vss-reddit { background: #ff4500; }
 .vss-style-popup .vss-whatsapp { background: #25d366; }
 .vss-style-popup .vss-telegram { background: #0088cc; }
 .vss-style-popup .vss-email { background: #7f8c8d; }
 .vss-style-popup .vss-youtube { background: #cc181e; }

 /* ========================================
    RESPONSIVE DESIGN
    ======================================== */

 @media (max-width: 768px) {
     .vss-buttons-wrapper {
         gap: 8px;
     }

     .vss-button {
         font-size: 14px;
         padding: 7px 12px;
     }

     .vss-label {
         font-size: 14px;
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
         width: 40px;
         height: 40px;
         justify-content: center;
         padding: 0;
         border-radius: 50%;
     }

     .vss-icon {
         font-size: 18px;
     }

     .vss-style-wire .vss-button,
     .vss-style-glass .vss-button {
         width: 40px;
         height: 40px;
     }

     .vss-style-popup .vss-buttons-wrapper {
         flex-wrap: wrap;
     }

     .vss-style-popup .vss-button {
         min-width: 50px;
         height: 50px;
         font-size: 20px;
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
