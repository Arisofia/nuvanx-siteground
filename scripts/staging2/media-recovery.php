<?php
/**
 * Media Recovery Script for Staging2.
 * This script finds broken image attachments and downloads them from production.
 */
if ( 'cli' !== php_sapi_name() ) {
    die( 'This script can only be run via WP-CLI.' );
}

$production_url = 'https://nuvanx.com';
$upload_dir = wp_upload_dir();
$basedir = $upload_dir['basedir'];

echo "Starting media recovery...\n";

// Get all attachments
$attachments = get_posts( array(
    'post_type'      => 'attachment',
    'post_mime_type' => 'image',
    'post_status'    => 'inherit',
    'posts_per_page' => -1,
) );

$recovered = 0;
$failed = 0;

foreach ( $attachments as $attachment ) {
    $file = get_attached_file( $attachment->ID );
    if ( ! $file || ! file_exists( $file ) ) {
        // Physical file is missing
        $relative_path = _wp_relative_upload_path( $file );
        $prod_url = $production_url . '/wp-content/uploads/' . $relative_path;
        
        echo "Missing file: " . $relative_path . "\n";
        echo "Fetching from: " . $prod_url . "\n";
        
        $response = wp_remote_get( $prod_url, array( 'timeout' => 15 ) );
        
        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $body = wp_remote_retrieve_body( $response );
            
            // Ensure directory exists
            $dir = dirname( $file );
            if ( ! wp_mkdir_p( $dir ) ) {
                echo "Failed to create directory: $dir\n";
                $failed++;
                continue;
            }
            
            if ( file_put_contents( $file, $body ) !== false ) {
                echo "Successfully recovered: $file\n";
                
                // Regenerate metadata
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                $attach_data = wp_generate_attachment_metadata( $attachment->ID, $file );
                wp_update_attachment_metadata( $attachment->ID, $attach_data );
                
                $recovered++;
            } else {
                echo "Failed to write file: $file\n";
                $failed++;
            }
        } else {
            echo "Failed to download from production. Code: " . wp_remote_retrieve_response_code( $response ) . "\n";
            $failed++;
        }
    }
}

echo "Recovery complete. Recovered: $recovered, Failed: $failed\n";
