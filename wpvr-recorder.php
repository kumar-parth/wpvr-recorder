<?php
/*
Plugin Name: WP Voice Recorder
Plugin URI:
Description: WP Voice Recorder will help record your voice and play your posts content in front end.
Version: 1.0
Author: Kumar Parth
Author URI: 
*/
include( 'lib/wpvr-voice-meta.php' );


/* function to enqueue scripts for the plugin
* @param none
* @return none
*/
function wpvr_add_scripts() {
	wp_enqueue_style( 'jplayercss', plugins_url( 'skin/jplayer.blue.monday.css',__FILE__ ) );
	wp_enqueue_script( 'jplayer', plugins_url( 'js/jquery.jplayer.min.js' , __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'jplayerjs', plugins_url( 'js/mfsjplayer.js',__FILE__ ), array( 'jquery' ), '', true ); 
}
add_action( 'wp_enqueue_scripts', 'wpvr_add_scripts' );

/* function to enqueue style for the plugin
* @param none
* @return none
*/
function wpvr_add_style() {
	wp_enqueue_style( 'jplayercss', plugins_url( 'skin/jplayer.blue.monday.css',__FILE__ ) );
}
add_action( 'wp_enqueue_style', 'wpvr_add_style' );

/*  Function for adding subdirectory for post recorded files into the uploads folder on plugin activation
* @param none
* @return none
*/
function wpvr_voice_activation() {   
	$upload_dir = wp_upload_dir();
	$upload_loc = $upload_dir['basedir'] . '/recorded_files';
	if ( ! is_dir( $upload_loc ) ) {
		wp_mkdir_p( $upload_loc ); 
	}
}
register_activation_hook( __FILE__, 'wpvr_voice_activation' );

/*
* Function to add scripts in the admin side for jrecorder plugin
* @param none
* @return none
*/
function wpvr_admin_scripts( $hook_suffix ) {		
	if ( 'post.php' == $hook_suffix || 'post-new.php' == $hook_suffix ) {
		wp_enqueue_script( 'jrecorder', plugins_url( 'js/jRecorder.js' , __FILE__ ), array('jquery') );   
		wp_enqueue_script( 'audiorec', plugins_url( 'js/wpvr-audio-recorder.js' , __FILE__ ), array( 'jrecorder' ) );
		$site_parameters = array(
			'plugins_url' => plugins_url(), 
			'post_id' => get_the_ID(),
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		);
		wp_localize_script( 'audiorec', 'wpvr_audio', $site_parameters );  
		wp_localize_script( 'jrecorder', 'wpvr_variables', $site_parameters );
	}
}
add_action( 'admin_enqueue_scripts', 'wpvr_admin_scripts' );

add_action( 'wp_ajax_wpvr_upload_file', 'wpvr_upload_file' );
add_action( 'wp_ajax_nopriv_wpvr_upload_file', 'wpvr_upload_file' );
function wpvr_upload_file() {
	$upload_dir = wp_upload_dir();		
	//save the path in the uploads folder
	$upload_path = $upload_dir['basedir'] . '/recorded_files';
	$url = wp_get_referer();
	$host = parse_url($url, PHP_URL_QUERY);
	$split_url = explode( 'filename=',$host );
	$url_filename = $split_url[1];
	$filename = rtrim( $url_filename, '?action=wpvr_upload_file' );
	$fp 	  	 = fopen( $upload_path.'/'.$filename.'.wav', 'wb' );
	fwrite( $fp, file_get_contents( 'php://input' ) );
	fclose( $fp ); 
}

/**
 * Add the jplayer to each post having recorded audio
 * @param string content of recorded file
 * @return string content of recorded file
 */
function wpvr_content_filter( $content ) {
	$post_id  		= $GLOBALS['post']->ID;
	$upload_dir = wp_upload_dir();		
	//save the path in the uploads folder
	$upload_path = $upload_dir['basedir'] . '/recorded_files';
	$filename = 'recorded_file' . $post_id;
	if ( file_exists( $upload_path.'/'.$filename.'.wav' ) ) 
		$file_url = fopen( $upload_path.'/'.$filename.'.wav', 'rb' );
	else
		$file_url = '';
	if ( ! empty ( $file_url ) && is_single() )
		require( 'lib/wpvr-jplayer-interface.php' );		
    return $content;
}
add_filter( 'the_content', 'wpvr_content_filter', 20 );

function wpvr_run_script() {
	$post_id = get_the_id();
	$file_url = get_post_meta($post_id, 'record_file', true );
	$site_parameters = array(
				'file_url' => $file_url,
				'plugin_url' => plugins_url(),
				'theme_directory' => get_template_directory_uri(),
				'post_id' => $post_id,
			); 
	wp_localize_script( 'jplayerjs', 'wpvr_var', $site_parameters );
}
add_action( 'wp_footer' , 'wpvr_run_script' );


