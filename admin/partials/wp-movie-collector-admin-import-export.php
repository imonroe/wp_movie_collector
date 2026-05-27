<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php
    // Show success message if there is one
    if (isset($_GET['message'])) {
        $message_type = sanitize_text_field(wp_unslash($_GET['message']));
        $message = '';
        
        switch ($message_type) {
            case 'import_success':
                $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
                if ($count === 1) {
                    $message = __('1 item was successfully imported.', 'wp-movie-collector');
                } else {
                    $message = sprintf(__('%d items were successfully imported.', 'wp-movie-collector'), $count);
                }
                break;
            case 'synced':
                $synced_movies   = isset($_GET['synced_movies']) ? intval($_GET['synced_movies']) : 0;
                $synced_box_sets = isset($_GET['synced_box_sets']) ? intval($_GET['synced_box_sets']) : 0;
                $message = sprintf(
                    /* translators: 1: number of movies, 2: number of box sets */
                    __('Sync complete: %1$d movies and %2$d box sets are now mirrored as posts.', 'wp-movie-collector'),
                    $synced_movies,
                    $synced_box_sets
                );
                break;
            default:
                $message = __('Operation completed successfully.', 'wp-movie-collector');
                break;
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
    
    // Show error message if there is one
    if (isset($_GET['error'])) {
        $error_type = sanitize_text_field(wp_unslash($_GET['error']));
        $error_message = '';
        
        switch ($error_type) {
            case 'file_upload':
                $error_message = __('There was an error uploading the file. Please try again.', 'wp-movie-collector');
                break;
            case 'invalid_format':
                $error_message = __('Invalid file format. Please upload a CSV or JSON file.', 'wp-movie-collector');
                break;
            case 'import_failed':
                if (isset($_GET['message'])) {
                    $error_message = urldecode($_GET['message']);
                } else {
                    $error_message = __('Import failed. Please check your file and try again.', 'wp-movie-collector');
                }
                break;
            default:
                $error_message = __('An unknown error occurred. Please try again.', 'wp-movie-collector');
                break;
        }
        
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
    }
    ?>
    
    <div class="wp-movie-collector-import-export">
        <div class="wp-movie-collector-card">
            <h2><?php esc_html_e('Export Movies', 'wp-movie-collector'); ?></h2>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wp_movie_collector_export_movies">
                <?php wp_nonce_field('wp_movie_collector_export', 'wp_movie_collector_nonce'); ?>
                
                <div class="form-group">
                    <label for="export-type"><?php esc_html_e('Export Type', 'wp-movie-collector'); ?></label>
                    <select id="export-type" name="export_type">
                        <option value="all"><?php esc_html_e('All Movies', 'wp-movie-collector'); ?></option>
                        <option value="movies_only"><?php esc_html_e('Movies Only (exclude box sets)', 'wp-movie-collector'); ?></option>
                        <option value="box_sets"><?php esc_html_e('Box Sets Only', 'wp-movie-collector'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="export-format"><?php esc_html_e('Export Format', 'wp-movie-collector'); ?></label>
                    <select id="export-format" name="export_format">
                        <option value="csv"><?php esc_html_e('CSV (Comma Separated Values)', 'wp-movie-collector'); ?></option>
                        <option value="json"><?php esc_html_e('JSON', 'wp-movie-collector'); ?></option>
                    </select>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Export', 'wp-movie-collector'); ?>
                    </button>
                </p>
            </form>
        </div>
        
        <div class="wp-movie-collector-card">
            <h2><?php esc_html_e('Import Movies', 'wp-movie-collector'); ?></h2>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" id="wp-movie-collector-import-form">
                <input type="hidden" name="action" value="wp_movie_collector_import_movies">
                <?php wp_nonce_field('wp_movie_collector_import', 'wp_movie_collector_nonce'); ?>
                
                <div class="form-group">
                    <label for="import-file"><?php esc_html_e('Import File', 'wp-movie-collector'); ?></label>
                    <input type="file" id="import-file" name="import_file" accept=".csv,.json" required>
                    <p class="description"><?php esc_html_e('Upload a CSV or JSON file containing movie data.', 'wp-movie-collector'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="import-type"><?php esc_html_e('Import Type', 'wp-movie-collector'); ?></label>
                    <select id="import-type" name="import_type">
                        <option value="append"><?php esc_html_e('Append to Collection', 'wp-movie-collector'); ?></option>
                        <option value="replace"><?php esc_html_e('Replace Collection (Warning: This will delete all existing movies!)', 'wp-movie-collector'); ?></option>
                    </select>
                </div>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Import', 'wp-movie-collector'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
    
    <div class="wp-movie-collector-import-template">
        <h2><?php esc_html_e('CSV Template', 'wp-movie-collector'); ?></h2>
        <p><?php esc_html_e('You can download a CSV template to help you format your movie data for import.', 'wp-movie-collector'); ?></p>
        
        <a href="<?php echo esc_url(admin_url('admin-post.php?action=wp_movie_collector_download_csv_template&wp_movie_collector_nonce=' . wp_create_nonce('wp_movie_collector_template'))); ?>" class="button">
            <?php esc_html_e('Download CSV Template', 'wp-movie-collector'); ?>
        </a>
    </div>

    <div class="wp-movie-collector-import-template">
        <h2><?php esc_html_e('Sync to Posts', 'wp-movie-collector'); ?></h2>
        <p><?php esc_html_e('Create or refresh the WordPress posts (movie / box set) that mirror your collection so single-item pages, WordPress search, taxonomies, and SEO plugins work. New and edited items sync automatically; use this to backfill existing data.', 'wp-movie-collector'); ?></p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="wp_movie_collector_sync_posts">
            <?php wp_nonce_field('wp_movie_collector_sync_posts', 'wp_movie_collector_nonce'); ?>
            <button type="submit" class="button">
                <?php esc_html_e('Sync collection to posts', 'wp-movie-collector'); ?>
            </button>
        </form>
    </div>
</div>

<style>
.wp-movie-collector-import-export {
    display: flex;
    gap: 30px;
    margin-bottom: 30px;
}

.wp-movie-collector-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    flex: 1;
    max-width: 500px;
}

.wp-movie-collector-card h2 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.wp-movie-collector-import-template {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

@media (max-width: 768px) {
    .wp-movie-collector-import-export {
        flex-direction: column;
    }
    
    .wp-movie-collector-card {
        max-width: none;
    }
}
</style>