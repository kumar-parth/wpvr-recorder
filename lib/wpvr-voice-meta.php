<?php
/*
*File to add the audio recorder to the post pages
*
*/


/*add a meta box to record the voice post 
*@param  none
*@return none
*/
function add_audio_recorder() {
	add_meta_box(                                                  
			'wpvr_post_recorder',
			__( 'WP Post Recorder', 'myplugin_textdomain' ),
			'wpvr_record_box',
			'post','side'
	);
}
add_action( 'add_meta_boxes', 'add_audio_recorder' );
/* Function to add metabox for the recorder
*	@param none
* 	@return none
*/
function wpvr_record_box() {
	global $post;
	// Add an nonce field so we can check for it later.	
	wp_nonce_field( 'save_audio', 'save_audio_nonce' );
	$html = '<div style="background-color: #eeeeee;border:1px solid #cccccc">
			Time: <span id="time">00:00</span>
			</div>';
	$html .= '<label for="record">';
	$html .= _e( 'record your voice', 'myplugin_textdomain' );
	$html .= '</label> ';
	$html .= '<div>
			 Level: <span id="level"></span>
			</div> ';
	$html .= '<div id="levelbase" style="width:200px;height:20px;background-color:#ffff00">
			  <div id="levelbar" style="height:19px; width:2px;background-color:red"></div>
			  </div>';
	$html .= '<div> Status: <span id="status"></span></div>'; 
	$html .= '<input type="button" id="record" name="record" value="record" />';
	$html .= '<input type="button" id="stop" name="stop" value="Save/Stop"/>';
	$html .= '<input type="hidden" name="record_file" id="record_file" value="recorded_file'.get_the_ID().'"/>';
	echo $html;
}
/* Function to save the meta data 
* @param integer post id
* @return none
*/
function wpvr_save_audio( $post_id ) {
	$rec_upload_file = wp_upload_dir();
	$rec_path 		 = $rec_upload_file['baseurl'];
	$record_file 	 = 'recorded_file'.$post_id;
	update_post_meta( $post_id,'record_file', $rec_path . '/recorded_files/'. $record_file . '.wav' );
}
add_action( 'save_post', 'wpvr_save_audio' );