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

// Add submenu page for shortcode usage
add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=shortcode',
        'Shortcode Usage',
        'Usage Details',
        'manage_options',
        'shortcode-usage',
        'sm_shortcode_usage_page'
    );
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

// Function to find shortcode usage across all content with caching
function sm_find_shortcode_usage($shortcode_slug) {
    // Create cache key
    $cache_key = 'sm_shortcode_usage_' . md5($shortcode_slug);
    
    // Try to get from cache first
    $cached_result = wp_cache_get($cache_key, 'shortcode_manager');
    if ($cached_result !== false) {
        return $cached_result;
    }
    
    global $wpdb;
    
    $search_pattern = '[sc name="' . $shortcode_slug . '"';
    
    // Search in posts, pages, and custom post types
    $query = $wpdb->prepare("
        SELECT ID, post_title, post_type, post_status, post_date 
        FROM {$wpdb->posts} 
        WHERE post_content LIKE %s 
        AND post_status IN ('publish', 'draft', 'private')
        AND post_type NOT IN ('revision', 'shortcode')
        ORDER BY post_type, post_title
    ", '%' . $wpdb->esc_like($search_pattern) . '%');
    
    $results = $wpdb->get_results($query);
    
    // Also search in widgets and customizer
    $widget_results = [];
    $widgets = get_option('widget_text', []);
    if (is_array($widgets)) {
        foreach ($widgets as $key => $widget) {
            if (isset($widget['text']) && strpos($widget['text'], $search_pattern) !== false) {
                $widget_results[] = (object)[
                    'ID' => 'widget_' . $key,
                    'post_title' => 'Text Widget #' . $key,
                    'post_type' => 'widget',
                    'post_status' => 'active',
                    'post_date' => ''
                ];
            }
        }
    }
    
    $final_results = array_merge($results, $widget_results);
    
    // Cache the results for 5 minutes
    wp_cache_set($cache_key, $final_results, 'shortcode_manager', 300);
    
    return $final_results;
}

// Add shortcode column and usage column in admin
add_filter('manage_shortcode_posts_columns', function ($columns) {
    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'title') {
            $new_columns['shortcode'] = 'Shortcode';
            $new_columns['usage_count'] = 'Used In';
        }
    }
    return $new_columns;
});

add_action('manage_shortcode_posts_custom_column', function ($column, $post_id) {
    if ($column === 'shortcode') {
        echo '<code>[sc name="' . esc_html(get_post_field('post_name', $post_id)) . '"]</code>';
    }
    
    if ($column === 'usage_count') {
        $slug = get_post_field('post_name', $post_id);
        $usage = sm_find_shortcode_usage($slug);
        $count = count($usage);
        
        if ($count > 0) {
            $url = admin_url('edit.php?post_type=shortcode&page=shortcode-usage&shortcode=' . urlencode($slug));
            echo '<a href="' . esc_url($url) . '">';
            echo '<strong>' . esc_html($count) . ' page' . ($count !== 1 ? 's' : '') . '</strong>';
            echo '</a>';
        } else {
            echo '<span style="color: #999;">Not used</span>';
        }
    }
}, 10, 2);

// Make usage column sortable
add_filter('manage_edit-shortcode_sortable_columns', function ($columns) {
    $columns['usage_count'] = 'usage_count';
    return $columns;
});





// Shortcode Usage Details Page
function sm_shortcode_usage_page() {
    echo '<div class="wrap">';
    echo '<h1>Shortcode Usage Details</h1>';

    if (isset($_GET['shortcode'])) {
        $shortcode_slug = sanitize_text_field(wp_unslash($_GET['shortcode']));
        $usage = sm_find_shortcode_usage($shortcode_slug);

        echo '<div class="notice notice-info">';
        echo '<p><strong>Shortcode:</strong> <code>[sc name="' . esc_html($shortcode_slug) . '"]</code></p>';
        echo '</div>';

        if (empty($usage)) {
            echo '<div class="notice notice-warning">';
            echo '<p>This shortcode is not currently used anywhere.</p>';
            echo '</div>';
        } else {
            echo '<p>This shortcode is used in <strong>' . esc_html(count($usage)) . '</strong> location' . (count($usage) !== 1 ? 's' : '') . ':</p>';

            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th scope="col" style="width: 40%;">Title</th>';
            echo '<th scope="col" style="width: 15%;">Type</th>';
            echo '<th scope="col" style="width: 15%;">Status</th>';
            echo '<th scope="col" style="width: 15%;">Date</th>';
            echo '<th scope="col" style="width: 15%;">Actions</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ($usage as $item) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($item->post_title) . '</strong></td>';
                echo '<td>' . esc_html(ucfirst(str_replace('_', ' ', $item->post_type))) . '</td>';

                if ($item->post_type === 'widget') {
                    // Use WP's success and warning text classes
                    echo '<td><span class="dashicons dashicons-yes" style="color: #008000;"></span> <span class="text-success">Active</span></td>';
                    echo '<td>-</td>';
                    echo '<td>-</td>';
                } else {
                    $status_class = $item->post_status === 'publish' ? 'text-success' : 'text-error';
                    echo '<td><span class="' . esc_attr($status_class) . '">' . esc_html(ucfirst($item->post_status)) . '</span></td>';
                    echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($item->post_date))) . '</td>';

                    echo '<td>';
                    $edit_link = get_edit_post_link($item->ID);
                    if ($edit_link) {
                        echo '<a href="' . esc_url($edit_link) . '" class="button button-small" aria-label="Edit ' . esc_attr($item->post_title) . '">Edit</a> ';
                    }
                    if ($item->post_status === 'publish') {
                        $view_link = get_permalink($item->ID);
                        if ($view_link) {
                            echo '<a href="' . esc_url($view_link) . '" class="button button-small" target="_blank" rel="noopener noreferrer" aria-label="View ' . esc_attr($item->post_title) . '">View</a>';
                        }
                    }
                    echo '</td>';
                }

                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        }

        echo '<p><a href="' . esc_url(admin_url('edit.php?post_type=shortcode')) . '" class="button">&larr; Back to All Shortcodes</a></p>';

    } else {
        // Show all shortcodes with their usage counts
        echo '<p>Select a shortcode from the main <a href="' . esc_url(admin_url('edit.php?post_type=shortcode')) . '">Shortcodes</a> page to view its usage details.</p>';

        echo '<h2>All Shortcodes Usage Summary</h2>';

        $shortcodes = get_posts([
            'post_type' => 'shortcode',
            'post_status' => 'any',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        if (empty($shortcodes)) {
            echo '<p>No shortcodes found.</p>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th scope="col" style="width: 30%;">Shortcode Name</th>';
            echo '<th scope="col" style="width: 40%;">Shortcode</th>';
            echo '<th scope="col" style="width: 15%;">Usage Count</th>';
            echo '<th scope="col" style="width: 15%;">Actions</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ($shortcodes as $shortcode) {
                $slug = $shortcode->post_name;
                $usage = sm_find_shortcode_usage($slug);
                $count = count($usage);

                echo '<tr>';
                echo '<td><strong>' . esc_html($shortcode->post_title) . '</strong></td>';
                echo '<td><code>[sc name="' . esc_html($slug) . '"]</code></td>';
                echo '<td>';
                if ($count > 0) {
                    echo '<strong class="text-success">' . esc_html($count) . ' page' . ($count !== 1 ? 's' : '') . '</strong>';
                } else {
                    echo '<span class="text-muted">Not used</span>';
                }
                echo '</td>';
                echo '<td>';
                echo '<a href="' . esc_url(get_edit_post_link($shortcode->ID)) . '" class="button button-small" aria-label="Edit ' . esc_attr($shortcode->post_title) . '">Edit</a> ';
                if ($count > 0) {
                    $usage_url = admin_url('edit.php?post_type=shortcode&page=shortcode-usage&shortcode=' . urlencode($slug));
                    echo '<a href="' . esc_url($usage_url) . '" class="button button-small" aria-label="View usage of ' . esc_attr($shortcode->post_title) . '">View Usage</a>';
                }
                echo '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        }
    }

    echo '</div>';
}


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

    echo '</div>';
}

function sm_shortcode_usage_box($post) {
    $slug = $post->post_name;
    echo '<p><strong>Use this shortcode anywhere:</strong></p>';
    echo '<code style="font-size:14px; display:block; background:#f0f0f0; padding:8px; border-radius:4px;">[sc name="' . esc_html($slug) . '"]</code>';
    
    // Show only usage count
    $usage = sm_find_shortcode_usage($slug);
    $count = count($usage);
    
    echo '<p style="margin-top: 15px;"><strong>Total usage:</strong></p>';
    if ($count > 0) {
        $usage_url = admin_url('edit.php?post_type=shortcode&page=shortcode-usage&shortcode=' . urlencode($slug));
        echo '<p><a href="' . esc_url($usage_url) . '" style="color: #008000; font-weight: bold; text-decoration: none;">' . esc_html($count) . ' page' . ($count !== 1 ? 's' : '') . '</a></p>';
    } else {
        echo '<p style="color: #d63638;">0 pages</p>';
    }
}

add_filter('post_row_actions', function ($actions, $post) {
    if ($post->post_type === 'shortcode') {
        $url = wp_nonce_url(admin_url('admin.php?action=clone_shortcode&post=' . $post->ID), 'clone_shortcode_' . $post->ID);
        $actions['clone'] = '<a href="' . esc_url($url) . '">Clone</a>';
    }
    return $actions;
}, 10, 2);

add_action('admin_action_clone_shortcode', function () {
    if (!isset($_GET['post']) || !isset($_GET['_wpnonce'])) {
        return;
    }
    
    $post_id = (int) $_GET['post'];
    $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
    
    if (!wp_verify_nonce($nonce, 'clone_shortcode_' . $post_id)) {
        return;
    }

    $post = get_post($post_id);
    if (!$post) {
        return;
    }
    
    $new_post = [
        'post_title' => $post->post_title . ' (Copy)',
        'post_status' => 'draft',
        'post_type' => 'shortcode',
    ];
    $new_post_id = wp_insert_post($new_post);
    
    if ($new_post_id && !is_wp_error($new_post_id)) {
        $meta = get_post_meta($post_id, '_sm_shortcode_content', true);
        update_post_meta($new_post_id, '_sm_shortcode_content', $meta);

        wp_redirect(admin_url('post.php?post=' . $new_post_id . '&action=edit'));
        exit;
    }
});

// Clear cache when posts are updated
add_action('save_post', function ($post_id) {
    // Clear all shortcode usage cache when any post is saved
    wp_cache_flush_group('shortcode_manager');
});

add_action('delete_post', function ($post_id) {
    // Clear all shortcode usage cache when any post is deleted
    wp_cache_flush_group('shortcode_manager');
});