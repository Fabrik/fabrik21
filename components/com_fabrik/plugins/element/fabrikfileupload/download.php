<?php
/**
 * Trivial image serving script, to work round IE caching static CAPTCHA IMG's
 * @package fabrikar
 * @author Hugh Messenger
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

//$id = session_id();
/*
$name = session_name($_GET['session_name']);
$id = session_id($_GET['session_id']);
$path = session_save_path($_GET['session_save_path']);
$name = session_name();
$path = session_save_path();
$id = session_id();
$result = session_start();
*/
define( '_JEXEC', 1 );

//define('JPATH_BASE', dirname(__FILE__) . '/../../../../../..' );
$jpath = dirname(__FILE__);
$jpath = str_replace('/components/com_fabrik/plugins/element/fabrikfileupload', '', $jpath);
$jpath = str_replace('\components\com_fabrik\plugins\element\fabrikfileupload', '', $jpath);
define('JPATH_BASE', $jpath );

define( 'DS', DIRECTORY_SEPARATOR );

require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php' );
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php' );
$app 				=& JFactory::getApplication('site');
$app->initialise();

if( !empty($file_content) ) {
	// ... set no-cache (and friends) headers ...
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Some time in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	header('Accept-Ranges: bytes');
	header('Content-Length: '.strlen( $img ));
	header('Content-Type: image/jpeg');

	// ... serve up the image ...
	echo $file_content;

	// ... and we're done.
	exit();
}

?>