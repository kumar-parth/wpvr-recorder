<?php
# Include the Dropbox SDK libraries
require_once "dropbox-sdk/Dropbox/autoload.php";
use \Dropbox as dbx;
$accessToken = '4Dl0Uewu1fQAAAAAAAAAB7a5Dby4U7-eNRhDOz4ILbkFLiagAZhBvBOqn-r1PP4z';
$dbxClient = new dbx\Client($accessToken, "PHP-Example/1.0");
$f = fopen( $upload_path."/".$filename.".wav", "rb" );
$result = $dbxClient->uploadFile("/".$filename.".wav", dbx\WriteMode::add(), $f);
fclose($f);
unlink( $upload_path."/".$filename.".wav" );
?>