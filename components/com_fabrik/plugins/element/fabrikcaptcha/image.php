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
define( '_JEXEC', 1);

//define('JPATH_BASE', dirname(__FILE__) . '/../../../../../..');
$jpath = dirname(__FILE__);
$jpath = str_replace('/components/com_fabrik/plugins/element/fabrikcaptcha', '', $jpath);
$jpath = str_replace('\components\com_fabrik\plugins\element\fabrikcaptcha', '', $jpath);
define('JPATH_BASE', $jpath);

define( 'DS', DIRECTORY_SEPARATOR);

require_once ( JPATH_BASE .DS.'includes'.DS.'defines.php');
require_once ( JPATH_BASE .DS.'includes'.DS.'framework.php');
$app 				=& JFactory::getApplication('site');
$app->initialise();

if (empty($_SESSION['security_code'])) {
	exit;
}

$code = $_SESSION['security_code'];
$width = JRequest::getVar('width', 100);
$height = JRequest::getVar('height', 50);
$font = JRequest::getVar('font', 50);

$font_size = $height * 0.75;
$image = @imagecreate($width, $height) or die('Cannot initialize new GD image stream');
/* set the colours */
$background_color = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 20, 40, 100);
$noise_color = imagecolorallocate($image, 100, 120, 180);
/* generate random dots in background */
for ($i=0; $i<($width*$height)/3; $i++) {
	imagefilledellipse($image, mt_rand(0,$width), mt_rand(0,$height), 1, 1, $noise_color);
}
/* generate random lines in background */
for ($i=0; $i<($width*$height)/150; $i++) {
	imageline($image, mt_rand(0,$width), mt_rand(0,$height), mt_rand(0,$width), mt_rand(0,$height), $noise_color);
}
/* create textbox and add text */
$fontPath = JPATH_SITE . '/components/com_fabrik/plugins/element/fabrikcaptcha/' . $font;

$textbox = imagettfbbox($font_size, 0, $fontPath, $code) or die('Error in imagettfbbox function ' . $fontPath);
$x = ($width - $textbox[4])/2;
$y = ($height - $textbox[5])/2;
imagettftext($image, $font_size, 0, $x, $y, $text_color, $fontPath , $code) or die('Error in imagettftext function');
// $$$ hugh - @TODO - add some session identifier to the image name (maybe using the hash we use in the formsession stuff)
ob_start();
imagejpeg($image);
$img = ob_get_contents();
ob_end_clean();
imagedestroy($image);

if( !empty($img)) {
	// it exists, so grab the contents ...
	//$img = file_get_contents( './image.jpg');
	
	// ... set no-cache (and friends) headers ...
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Some time in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
	header("Cache-Control: no-store, no-cache, must-revalidate"); 
	header("Cache-Control: post-check=0, pre-check=0", false); 
	header("Pragma: no-cache"); 
	header('Accept-Ranges: bytes');
	header('Content-Length: '.strlen( $img)); 
	header('Content-Type: image/jpeg');
	
	// ... serve up the image ...
	echo $img;
	
	// ... and we're done.
	exit();
}
?>