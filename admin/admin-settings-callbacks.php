<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * admin tab settings html
 */


// validate plugin settings
function csi_callback_validate_options($input) {
		
	// checkbox 
	if ( ! isset( $input['auto_delete'] ) ) {
		$input['auto_delete'] = null;
	}
	$input['auto_delete'] = ( $input['auto_delete'] == 1 ? 1 : 0 );

	if ( ! isset( $input['limit_size'] ) ) {
		$input['limit_size'] = null;
	}
	$input['limit_size'] = ( $input['limit_size'] == 1 ? 1 : 0 );


	// file_size
	if ( isset( $input['file_size'] ) ) {
		$input['file_size'] = sanitize_text_field( $input['file_size'] );
	}

	return $input;
	
}


// callback: admin section
function csi_callback_section_admin() {
  echo '<p>Manage big-image uploads to WP media. ( Applies only to image file type ).</p>';
}


// callback: checkbox field
function csi_callback_field_checkbox( $args ) {
	
	$options = get_option( 'csi_options', csi_options_default() );
	
	$id    = isset( $args['id'] )    ? $args['id']    : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';
	$supp  = isset( $args['supplemental'] ) ? $args['supplemental'] : '';
	
	$checked = isset( $options[$id] ) ? checked( $options[$id], 1, false ) : '';
	
	echo '<input id="csi_options_'. $id .'" name="csi_options['. $id .']" type="checkbox" value="1"'. $checked .'> ';
	echo '<label for="csi_options_'. $id .'">'. $label .'</label>';

	// If there is supplemental text
  if( $supp ){
      echo '<p class="description">'. $supp .'</p>';
  }
	
}


// callback: number field
function csi_callback_field_number( $args ) {
	
	$options = get_option( 'csi_options', csi_options_default() );
	
	$id    = isset( $args['id'] )    ? $args['id']    : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';
	$supp  = isset( $args['supplemental'] ) ? $args['supplemental'] : '';
	$max	 = wp_max_upload_size() / (1024 * 1024); // bytes to MB
	
	$value = isset( $options[$id] ) ? sanitize_text_field( $options[$id] ) : '';
	
	echo '<input id="csi_options_'. $id .'" name="csi_options['. $id .']" type="number" min="1" max="'. $max .'" value="'. $value .'"> ';
	echo '<label for="csi_options_'. $id .'">'. $label .'</label>';
	
	// If there is supplemental text
  if( $supp ){
      echo '<p class="description">'. $supp .'</p>';
  }

	
}
