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

    // echo '<p><strong>Live Preview (HTML/CSS/JS only):</strong></p>';
    // echo '<iframe id="sm_preview_iframe" style="width:100%; height:300px; border:1px solid #ccc;"></iframe>';

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

}

function sm_shortcode_usage_box($post) {
    $slug = $post->post_name;
    echo '<p><strong>Use this shortcode anywhere:</strong></p>';
    echo '<code style="font-size:14px; display:block; background:#f0f0f0; padding:8px; border-radius:4px;">[sc name="' . esc_html($slug) . '"]</code>';
}


add_filter('post_row_actions', function ($actions, $post) {
    if ($post->post_type === 'shortcode') {
        $url = wp_nonce_url(admin_url('admin.php?action=clone_shortcode&post=' . $post->ID), 'clone_shortcode_' . $post->ID);
        $actions['clone'] = '<a href="' . esc_url($url) . '">Clone</a>';
    }
    return $actions;
}, 10, 2);

add_action('admin_action_clone_shortcode', function () {
    if (!isset($_GET['post']) || !isset($_GET['_wpnonce'])) return;
    $post_id = (int) $_GET['post'];
    if (!wp_verify_nonce($_GET['_wpnonce'], 'clone_shortcode_' . $post_id)) return;

    $post = get_post($post_id);
    $new_post = [
        'post_title' => $post->post_title . ' (Copy)',
        'post_status' => 'draft',
        'post_type' => 'shortcode',
    ];
    $new_post_id = wp_insert_post($new_post);
    $meta = get_post_meta($post_id, '_sm_shortcode_content', true);
    update_post_meta($new_post_id, '_sm_shortcode_content', $meta);

    wp_redirect(admin_url('post.php?post=' . $new_post_id . '&action=edit'));
    exit;
});


