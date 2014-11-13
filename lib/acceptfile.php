<?php
require_once("../../../../wp-load.php");
$upload_dir = wp_upload_dir();		
//save the path in the uploads folder
$upload_path = $upload_dir['basedir'] . "/recorded_files";
$filename    = $_REQUEST['filename'];
$fp 	  	 = fopen( $upload_path."/".$filename.".wav", "wb" );
fwrite( $fp, file_get_contents( 'php://input' ) );
fclose( $fp ); 
# Include the Dropbox SDK libraries
require_once "../dropbox-sdk/Dropbox/autoload.php";
use \Dropbox as dbx;
$accessToken 		= get_option( 'dropbox_api_token' );
$dbxClient   		= new dbx\Client( $accessToken, "PHP-Example/1.0" );
$search_old_record	= $dbxClient->searchFileNames( '/', $filename.'.wav', null, false );
if( ! empty ( $search_old_record ) )
	$delete_old_record	= $dbxClient->delete( "/".$filename.".wav" );

$f 			 		= fopen( $upload_path."/".$filename.".wav", "rb" );
$result 			= $dbxClient->uploadFile( "/".$filename.".wav", dbx\WriteMode::add(), $f );
fclose( $f );
unlink( $upload_path."/".$filename.".wav" );
?>