<?php
/*
Plugin Name: Shortcode Manager
Description: Create shortcodes via admin and use them with [sc name="your-slug"].
Version: 2.0
Author: Your Name
*/

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
        if ($screen->post_type === 'shortcode') {
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

function sm_shortcode_editor_box($post) {
    $content = get_post_meta($post->ID, '_sm_shortcode_content', true);
    wp_nonce_field('sm_shortcode_save', 'sm_shortcode_nonce');
    $slug = $post->post_name;

    echo '<p><strong>Use this shortcode:</strong> <code>[sc name="' . esc_html($slug) . '"]</code></p>';
    echo '<textarea id="sm_shortcode_content" name="sm_shortcode_content" style="width:100%; height:300px;" class="code-editor">' . esc_textarea($content) . '</textarea>';

    echo '<p><strong>Live Preview (HTML/CSS/JS only):</strong></p>';
    echo '<iframe id="sm_preview_iframe" style="width:100%; height:300px; border:1px solid #ccc;"></iframe>';

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
    
        const textarea = document.getElementById("sm_shortcode_content");
        const iframe = document.getElementById("sm_preview_iframe");
    
        function updatePreview() {
            const content = textarea.value;
            let sanitizedContent = content.replace(/<\\?(php)?[^]*?\\?>/gi, "<pre style=\'color:red;\'>PHP not previewable</pre>");
            const doc = iframe.contentDocument || iframe.contentWindow.document;
            doc.open();
            doc.write(sanitizedContent);
            doc.close();
        }
    
        textarea.addEventListener("input", updatePreview);
        updatePreview();
    });
    </script>';    
}

// Save shortcode content
add_action('save_post', function ($post_id) {
    if (!isset($_POST['sm_shortcode_nonce']) || !wp_verify_nonce($_POST['sm_shortcode_nonce'], 'sm_shortcode_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['sm_shortcode_content'])) {
        update_post_meta($post_id, '_sm_shortcode_content', $_POST['sm_shortcode_content']);
    }
});

// Add shortcode column in admin
add_filter('manage_shortcode_posts_columns', function ($columns) {
    $columns['shortcode'] = 'Shortcode';
    return $columns;
});
add_action('manage_shortcode_posts_custom_column', function ($column, $post_id) {
    if ($column === 'shortcode') {
        echo '<code>[sc name="' . esc_html(get_post_field('post_name', $post_id)) . '"]</code>';
    }
}, 10, 2);

// Shortcode handler with PHP/HTML/JS/CSS support
add_shortcode('sc', function ($atts) {
    $atts = shortcode_atts(['name' => ''], $atts);
    $slug = sanitize_title($atts['name']);

    // Default to 'home' if name is empty and on home/front page
    if ((is_home() || is_front_page()) && empty($slug)) {
        $slug = 'home';
    }

    $post = get_page_by_path($slug, OBJECT, 'shortcode');

    // If not found or not published, return shortcode placeholder
    if (!$post || $post->post_status !== 'publish') {
        return '[sc name="' . esc_html($slug) . '"]';
    }

    $code = get_post_meta($post->ID, '_sm_shortcode_content', true);

    ob_start();
    try {
        eval('?>' . $code);
    } catch (Throwable $e) {
        echo '<div style="color:red;">Error in shortcode: ' . esc_html($e->getMessage()) . '</div>';
    }

    return ob_get_clean();
});
