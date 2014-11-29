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
# Include the Dropbox SDK libraries
include( 'dropbox-sdk/Dropbox/autoload.php' );
use \Dropbox as dbx;
global $file_urls;
global $post_ids;
$file_urls = array();
$post_ids  = array();

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
	$get_api_token = get_option( 'dropbox_api_token' );
	if ( 'post.php' == $hook_suffix || 'post-new.php' == $hook_suffix ) {
		if ( isset( $get_api_token ) && ! empty( $get_api_token ) ) {
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
		else {
			echo '<div id="message" class="error">Please Update Dropbox API access token in order to use WP Voice Recorder!!</div>';
		}
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
		# Include the Dropbox SDK libraries
		$accessToken 		= get_option( 'dropbox_api_token' );
		$dbxClient   		= new dbx\Client( $accessToken, 'PHP-Example/1.0' );
		$search_old_record	= $dbxClient->searchFileNames( '/', $filename.'.wav', null, false );
		if ( ! empty ( $search_old_record ) )
			$delete_old_record	= $dbxClient->delete( '/'.$filename.'.wav' );
		$f 		= fopen( $upload_path.'/'.$filename.'.wav', 'rb' );
		$result = $dbxClient->uploadFile( '/'.$filename.'.wav', dbx\WriteMode::add(), $f );
		fclose( $f );
		unlink( $upload_path.'/'.$filename.'.wav' );
}

/**
 * Add the jplayer to each post having recorded audio
 * @param string content of recorded file
 * @return string content of recorded file
 */
function wpvr_content_filter( $content ) {
	global $file_urls;
	global $post_ids;
	$post_id  		= $GLOBALS['post']->ID;
	$accessToken 	= get_option( 'dropbox_api_token' );
	$dbxClient 		= new dbx\Client( $accessToken, 'PHP-Example/1.0' );
	$file_url_array = $dbxClient->createTemporaryDirectLink( '/recorded_file'.$post_id.'.wav' );
	$file_url 		= isset( $file_url_array ) ? $file_url_array[0] : '';
	if ( ! empty( $file_url ) ) {
		$post_ids[] 	= $post_id;
		$file_urls[] 	= $file_url;
		require( 'lib/wpvr-jplayer-interface.php' );		
	}
	// Add play and record buttons to each post
	// Returns the content.
    return $content;
}
add_filter( 'the_content', 'wpvr_content_filter', 20 );

function wpvr_run_script() {
	global $file_urls;
	global $post_ids;
	$site_parameters = array(
				'file_url' => $file_urls,
				'plugin_url' => plugins_url(),
				'theme_directory' => get_template_directory_uri(),
				'post_id' => $post_ids,
			); 
			//echo $file_url;
	wp_localize_script( 'jplayerjs', 'wpvr_var', $site_parameters );
}
add_action( 'wp_footer' , 'wpvr_run_script' );
/*
* Add a submenu in the settings for wpvr_recorder to add options 
*
*/
function wpvr_recorder_submenu() {
	add_submenu_page( 'options-general.php', 'WP Voice Recoder Settings', 'WP Voice Recoder Settings', 'manage_options', 'wpvr-recorder-submenu-page', 'wpvr_recorder_submenu_page_callback' );
}
add_action( 'admin_menu', 'wpvr_recorder_submenu' );
/*
* function to add the Submenu for WP Voice Recorder settings page under Settings and add the fields in it.
* @param none
* @return none
*/
function wpvr_recorder_submenu_page_callback() {
	?>
	  <div class="wrap">
        <?php screen_icon( 'themes' ); ?> <h2>WP Voice Recorder Settings</h2>
		<?php if ( isset( $_POST['update_settings'] ) && ! empty ( $_POST['update_settings'] ) ) {
				// Do the saving
				$dropbox_api_token = esc_attr( $_POST['dropbox_api_token'] );   
				update_option( 'dropbox_api_token', $dropbox_api_token );
				echo '<div id="message" class="updated">Settings saved</div>';
				if ( empty( $_POST['dropbox_api_token'] ) )
					echo '<div id="message" class="error">Please fill in your Dropbox credential</div>';
			}
			$dropbox_api_token = get_option( 'dropbox_api_token' );   
		?>
        <form method="POST" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="dropbox_api_token">
                            Enter the API token for the Dropbox account
                        </label> 
                    </th>
                    <td>
                        <input type="text" name="dropbox_api_token" value="<?php echo ( isset( $dropbox_api_token ) ) ? $dropbox_api_token : '' ?>" size="25" />				
						<input type="hidden" name="update_settings" value="Y" />
                    </td>
                </tr>
				<tr>
					<td>
						<div class="button-holder"><input class="button-primary" name="submit" type="submit" value="Save Changes" /></div>
					</td>
				</tr>
            </table>
        </form>
    </div>
	<?php
} 
