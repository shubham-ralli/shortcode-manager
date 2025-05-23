<?php
// Name: IMPORT / EXPORT SHORTCODES

// Add Import/Export submenu under Shortcode post type
add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=shortcode',
        'Import / Export',
        'Import / Export',
        'manage_options',
        'shortcode-import-export',
        'bxcode_render_shortcode_import_export_page'
    );
});

// Render the Import/Export Page
function bxcode_render_shortcode_import_export_page() {
    ?>
    <div class="wrap">
        <h1>Import / Export Shortcodes</h1>

        <?php if (isset($_GET['imported']) && $_GET['imported'] == '1') : ?>
            <div class="notice notice-success is-dismissible"><p>Shortcodes imported successfully.</p></div>
        <?php endif; ?>

        <h2>Export</h2>
        <p>Download all shortcodes as a JSON file.</p>
        <form method="post">
            <?php wp_nonce_field('bxcode_shortcode_export_nonce', 'bxcode_shortcode_export_nonce'); ?>
            <input type="submit" name="bxcode_export_shortcodes" class="button button-primary" value="Export Shortcodes">
        </form>

        <hr>

        <h2>Import</h2>
        <p>Upload a JSON file containing shortcodes to import.</p>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('bxcode_shortcode_import_nonce', 'bxcode_shortcode_import_nonce'); ?>
            <input type="file" name="bxcode_import_file" accept=".json" required>
            <br><br>
            <input type="submit" name="bxcode_import_shortcodes" class="button button-primary" value="Import Shortcodes">
        </form>
    </div>
    <?php
}

// Handle Import and Export
add_action('admin_init', function () {

    // Export Shortcodes
    if (isset($_POST['bxcode_export_shortcodes']) && check_admin_referer('bxcode_shortcode_export_nonce', 'bxcode_shortcode_export_nonce')) {
        $shortcodes = get_posts([
            'post_type' => 'shortcode',
            'numberposts' => -1,
        ]);

        $export_data = [];

        foreach ($shortcodes as $post) {
            $export_data[] = [
                'title'   => $post->post_title,
                'slug'    => $post->post_name,
                'content' => get_post_meta($post->ID, '_sm_shortcode_content', true),
            ];
        }

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=shortcodes-export-' . date('Y-m-d') . '.json');
        echo json_encode($export_data);
        exit;
    }

    // Import Shortcodes
    if (isset($_POST['bxcode_import_shortcodes']) && check_admin_referer('bxcode_shortcode_import_nonce', 'bxcode_shortcode_import_nonce')) {
        if (!empty($_FILES['bxcode_import_file']['tmp_name'])) {
            $file_contents = file_get_contents($_FILES['bxcode_import_file']['tmp_name']);
            $import_data = json_decode($file_contents, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($import_data)) {
                foreach ($import_data as $item) {
                    if (!empty($item['slug']) && !empty($item['content'])) {
                        $existing = get_page_by_path(sanitize_title($item['slug']), OBJECT, 'shortcode');

                        if (!$existing) {
                            $post_id = wp_insert_post([
                                'post_title'  => wp_slash($item['title']),
                                'post_name'   => sanitize_title($item['slug']),
                                'post_type'   => 'shortcode',
                                'post_status' => 'publish',
                            ]);

                            if ($post_id) {
                                update_post_meta($post_id, '_sm_shortcode_content', wp_slash($item['content']));
                            }
                        }
                    }
                }

                wp_redirect(admin_url('edit.php?post_type=shortcode&page=shortcode-import-export&imported=1'));
                exit;
            } else {
                wp_die('Invalid JSON file.');
            }
        }
    }
});
