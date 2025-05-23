<?php
/*
Plugin Name: Shortcode Manager
Description: Create shortcodes via admin and use them with [sc name="your-slug"].
Version: 1.0
Author: shubham ralli
Author URI: https://github.com/shubham-ralli
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: shortcode-manager
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register Custom Post Type: Shortcode
add_action('init', function () {
    register_post_type('shortcode', [
        'labels' => [
            'name' => 'Shortcodes',
            'singular_name' => 'Shortcode',
            'menu_name' => 'Shortcodes',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Shortcode',
            'edit_item' => 'Edit Shortcode',
            'view_item' => 'View Shortcode',
            'all_items' => 'All Shortcodes',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-editor-code',
        'supports' => ['title'],
    ]);
});


// Remove the default editor
add_action('admin_init', function () {
    remove_post_type_support('shortcode', 'editor');
});

// Enqueue CodeMirror scripts/styles
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'shortcode') {
            wp_enqueue_code_editor(['type' => 'text/html']);
            wp_enqueue_script('wp-theme-plugin-editor');
            wp_enqueue_style('wp-codemirror');
        }
    }
});

// Add custom meta box editor
add_action('add_meta_boxes', function () {
    add_meta_box('sm_shortcode_custom_editor', 'Shortcode Content', 'sm_shortcode_editor_box', 'shortcode', 'normal', 'high');
});

add_action('add_meta_boxes', function () {
    add_meta_box('sm_shortcode_help', 'Shortcode Usage Help', 'sm_shortcode_help_box', 'shortcode', 'normal', 'default');
});

add_action('add_meta_boxes', function () {
    add_meta_box(
        'sm_shortcode_usage',
        'Shortcode Usage',
        'sm_shortcode_usage_box',
        'shortcode',
        'side',
        'default'
    );
});

function sm_shortcode_editor_box($post) {
    $content = get_post_meta($post->ID, '_sm_shortcode_content', true);
    wp_nonce_field('sm_shortcode_save', 'sm_shortcode_nonce');
    $slug = $post->post_name;

    echo '<p><strong>Use this shortcode:</strong> <code>[sc name="' . esc_html($slug) . '"]</code></p>';
    echo '<textarea id="sm_shortcode_content" name="sm_shortcode_content" style="width:100%; height:500px;" class="code-editor">' . esc_textarea($content) . '</textarea>';

    echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
        if (typeof wp !== "undefined" && wp.codeEditor) {
            wp.codeEditor.initialize("sm_shortcode_content", {
                codemirror: {
                    mode: "application/x-httpd-php",
                    lineNumbers: true,
                    indentUnit: 2,
                    tabSize: 2,
                    matchBrackets: true,
                    autoCloseBrackets: true,
                    continueComments: "Enter",
                    extraKeys: {"Ctrl-Space": "autocomplete"}
                }
            });
        }
    });
    </script>';    
}

// Save shortcode content
add_action('save_post', function ($post_id) {
    if (!isset($_POST['sm_shortcode_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['sm_shortcode_nonce'])), 'sm_shortcode_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['sm_shortcode_content'])) {
        update_post_meta($post_id, '_sm_shortcode_content', wp_unslash($_POST['sm_shortcode_content']));
    }
});


// Add shortcode column and usage column in admin
add_filter('manage_shortcode_posts_columns', function ($columns) {
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['shortcode'] = 'Shortcode';
        }
    }
    return $new_columns;
});




add_action('manage_shortcode_posts_custom_column', function ($column, $post_id) {
    if ($column === 'shortcode') {
        echo '<code>[sc name="' . esc_html(get_post_field('post_name', $post_id)) . '"]</code>';
    }
}, 10, 2);




// Shortcode handler with PHP/HTML/JS/CSS support
add_shortcode('sc', function ($atts = []) {
    $slug = sanitize_title($atts['name'] ?? '');

    // If empty on home/front, default to "home"
    if ((is_home() || is_front_page()) && empty($slug)) {
        $slug = 'home';
    }

    $post = get_page_by_path($slug, OBJECT, 'shortcode');
    if (!$post || $post->post_status !== 'publish') {
        return '[sc name="' . esc_html($slug) . '"]';
    }

    $code = get_post_meta($post->ID, '_sm_shortcode_content', true);

    ob_start();
    try {
        // Extract attributes to be used as variables
        extract($atts, EXTR_SKIP); // Optional: creates $title, $color, etc.
        eval('?>' . $code);
    } catch (Throwable $e) {
        echo '<div style="color:red;">Error in shortcode: ' . esc_html($e->getMessage()) . '</div>';
    }
    return ob_get_clean();
});


// how to used shortcode
function sm_shortcode_help_box($post) {
    echo '<div style="font-size:13px; line-height:1.6">';

    echo '<p><strong>Basic Usage:</strong><br>';
    echo '<code>[sc name="' . esc_html($post->post_name) . '"]</code></p>';

    echo '<p><strong>With Parameters:</strong><br>';
    echo '<code>[sc name="' . esc_html($post->post_name) . '" title="Hello" color="#ff0000"]</code></p>';

    echo '<p><strong>Accessing Parameters in Code:</strong></p>';
    echo '<pre style="font-size:12px; background:#f5f5f5; padding:10px; white-space:pre-wrap;">';
    echo '&lt;?php echo $title; ?&gt;  // OR  &lt;?= $title; ?&gt;' . "\n";
    echo '&lt;?php echo $color; ?&gt;  // OR  &lt;?= $color; ?&gt;';
    echo '</pre>';

    echo '<p><strong>Using Default Values (Optional):</strong></p>';
    echo '<pre style="font-size:12px; background:#f5f5f5; padding:10px; white-space:pre-wrap;">';
    echo '&lt;?= $title ?? \'World\' ?&gt;' . "\n";
    echo '</pre>';

    echo '</div>';
}
// how to used shortcode




function sm_shortcode_usage_box($post) {
    $slug = $post->post_name;
    echo '<p><strong>Use this shortcode anywhere:</strong></p>';
    echo '<code style="font-size:14px; display:block; background:#f0f0f0; padding:8px; border-radius:4px;">[sc name="' . esc_html($slug) . '"]</code>';
}


// Clear cache when posts are updated
add_action('save_post', function ($post_id) {
    // Clear all shortcode usage cache when any post is saved
    wp_cache_flush_group('shortcode_manager');
});

add_action('delete_post', function ($post_id) {
    // Clear all shortcode usage cache when any post is deleted
    wp_cache_flush_group('shortcode_manager');
});


// Auto-include all PHP files in the 'features' folder
$feature_dir = plugin_dir_path(__FILE__) . 'features/';
foreach (glob($feature_dir . '*.php') as $file) {
    include_once $file;
}


