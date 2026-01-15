<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// elements in admin-tab-default.php;
add_action( 'wp_ajax_csi_run_search_action', 'csi_run_search_action' );
add_action( 'wp_ajax_csi_run_delete_action', 'csi_run_delete_action' );


/**
 * Perform search function
 * 
 */

function csi_run_search_action() {
  check_ajax_referer( 'csi-admin', 'nonce' );

  // ajax variables
  $html    = '';
  $message = '';
  
  // local variables
  $saved_space = 0;
  $counter     = 0;
  $table_open  = false;
  $paged       = 1;
  $per_page    = 200;

  ob_start();

  do {
    $query_images = csi_get_query_images( $paged, $per_page );

    foreach ( $query_images->posts as $id ) {
      $data = wp_get_attachment_metadata( $id );

      // skip not an image attachment
      if ( ! isset( $data['file'] ) || ! isset( $data['image_meta'] ) ) {
        continue;
      }

      // target scaled images
      if ( preg_match( '/-scaled/', $data['file'] ) && isset( $data['original_image'] ) ) {
        $original_image_url  = wp_get_original_image_url( $id );
        $original_image_path = wp_get_original_image_path( $id );
        if ( ! $original_image_path || ! file_exists( $original_image_path ) ) {
          continue;
        }
        $original_thumb_url  = wp_get_attachment_thumb_url( $id );
        $saved_space += filesize( $original_image_path );
        $counter++;

        if ( ! $table_open ) {
          echo '<table class="wp-list-table widefat striped table-view-list media">';
          $table_open = true;
        }

        ?>
          <tr>
            <th width="50">
                <a href="<?php echo esc_url( $original_image_url ); ?>" target="_blank" rel="noopener">
                  <img src="<?php echo esc_url( $original_thumb_url ); ?>" alt="thumb" width="50">
                </a>
            </th>
            <td>
            <span>
            <?php echo esc_html( $original_image_url . ' (' . csi_filesize_formatted( null, filesize( $original_image_path ) ) . ')' ); ?>
            </span>
            </td>
          </tr>
        <?php

      };
    }
    $paged++;
  } while ( $paged <= $query_images->max_num_pages );

  if ( $table_open ) {
    echo '</table>';
    echo '<style>.scaledImages table td {vertical-align: middle;}</style>';
  }

  // create result
  if( $counter > 0 ) {

    $message = csi_message( 'Found ' . $counter . ' files ( ' . csi_filesize_formatted( null, $saved_space ) . ' )' );
    $html = ob_get_clean();

  } else {
    
    $message = csi_message( 'Nothing found, all files are sorted.' );
    ob_end_clean();

  }
  
  // ajax return multiple elements;
  wp_send_json(
    array(
      'message' => $message,
      'html'    => $html,
    )
  );
  
}




/**
 * Perform delete function
 * 
 */

function csi_run_delete_action() {
  check_ajax_referer( 'csi-admin', 'nonce' );

  // ajax variables
  $html    = '';
  $buffer  = '';
  $message = '';
  
  // local variables
  $paged       = 1;
  $per_page    = 200;
  $saved_space  = 0;
  $counter      = 0;

  ob_start();

  do {
    $query_images = csi_get_query_images( $paged, $per_page );

    foreach ( $query_images->posts as $id ) {
      $data = wp_get_attachment_metadata( $id );

      // check if not an image attachment
      if ( ! isset( $data['file'] ) || ! isset( $data['image_meta'] ) ) {
        continue;
      }

      // target scaled images
      if ( preg_match( '/-scaled/', $data['file'] ) && isset( $data['original_image'] ) ) {

        $original_image_path = wp_get_original_image_path( $id );
        if ( ! $original_image_path || ! file_exists( $original_image_path ) ) {
          continue;
        }
        $saved_space += filesize( $original_image_path );
        $counter++;

        // Print data for delete
        echo '<pre>';
        echo esc_html( $data['original_image'] . ' (' . csi_filesize_formatted( $original_image_path ) . ')' );
        echo '</pre>';

        // Delete data
        wp_delete_file( $original_image_path ); // delete file here
        unset( $data['original_image'] ); // unset original_image from attachment_metadata
        wp_update_attachment_metadata( $id, $data ); // update changes to attachment;

      };

    }
    $paged++;
  } while ( $paged <= $query_images->max_num_pages );

  $buffer = ob_get_clean();

  if( $saved_space > 0 ) {

    $message = csi_message( 'Deleted ' . $counter . ' files ( ' . csi_filesize_formatted( null, $saved_space ) . ' )', 'success' );
    
    $html = '<h3>Log</h3>' . $buffer;
  
  } else {

    $message = csi_message( 'Nothing to delete, all files are sorted.', 'info' );

  }

  // ajax return multiple elements;
  wp_send_json(
    array(
      'message'  => $message,
      'html'     => $html,
    )
  );
}



/**
 * Auto Delete oroginal after upload;
 * 
 */
add_filter( 'wp_generate_attachment_metadata', 'csi_auto_delete_original', 10, 2 );
function csi_auto_delete_original( $data, $attachment_id ) {
  
  // get plugin admin settings
  $options = get_option( 'csi_options', csi_options_default() );
  // check values
  $auto_delete = isset( $options['auto_delete']  ) ? $options['auto_delete'] : '';

  if ( $auto_delete ) {

    // check if an image attachment
    if ( isset( $data['file'] ) && isset( $data['image_meta'] ) ) {

      // check if image is scaled
      if ( preg_match( '/-scaled/', $data['file'] ) && isset( $data['original_image'] ) ) {

        $original_image_path  = wp_get_original_image_path( $attachment_id );

        // Perform Delete
        if ( $original_image_path && file_exists( $original_image_path ) ) {
          wp_delete_file( $original_image_path ); // delete file here
          unset( $data['original_image'] ); // unset original_image from attachment_metadata
        }

      }

    }

  } // endif auto_delete.

  return $data;
}




/**
 * Limit upload size
 * 
 */
add_filter( 'wp_handle_upload_prefilter', 'csi_max_image_size' );
function csi_max_image_size( $file ) {

  // get plugin admin settings
  $options = get_option( 'csi_options', csi_options_default() );

  // check values
  $limit_size  = isset( $options['limit_size'] ) ? $options['limit_size'] : '';
  $file_size   = isset( $options['file_size']  ) ? $options['file_size'] : '';

  // // exclude users from limitation
  // $exclude_users  = array('adminusername');
  // $current_user   = wp_get_current_user()->user_login;
  // $not_exclude    = ! in_array( $user, $exclude_users );

  // execute if limit is enabled
  if ( $limit_size && $file_size ) {
      
      $size     = $file['size'];
      $size     = $size / (1024 * 1024) ; // MB
      $limit    = (float) $file_size; // set your limit

      $type     = $file['type'];
      $is_image = strpos( $type, 'image' ) !== false;
      
      if ( $is_image && $size > $limit ) {
        
        $limit_output = $limit . 'MB';
        $size_output  = round( $size, 0 ) . 'MB';

        $file['error'] = "Nope! Image file size is $size_output, must be smaller than $limit_output";
      }

  }

  return $file;

}


// UTILITY FUNCTIONS

function csi_get_query_images( $paged = 1, $per_page = 200 ) {
  $query_images_args = array(
    'post_type' => 'attachment',
    'post_mime_type' =>'image',
    'post_status' => 'inherit',
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'fields' => 'ids',
    'no_found_rows' => false,
  );

  $query_images = new WP_Query( $query_images_args );

  return $query_images;
}

/**
 * Message format
 * 
 * @param string $alert - info,success,warning,error
 * @param string $text  - text content
 */
function csi_message($text, $alert = 'info') {
    
    $message  = '<div class="notice notice-'.$alert.' settings-success">';
    $message .= '<p><strong>'.$text.'</strong></p>';
    $message .= '</div>';

    return $message;

}

/**
 * Filesize format
 * 
 * @param string $path  - path to file
 * @param string $bites - set $path NULL for bites format without file
 */
function csi_filesize_formatted($path, $bites = null) {

    $size  = $bites ? $bites : filesize($path);
    $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];

}
