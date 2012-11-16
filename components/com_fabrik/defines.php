<?php

// any of these defines can be overwritten by copying this file to
// components/com_fabrik/user_defines.php

// no direct access
defined('_JEXEC') or die('Restricted access');

define("COM_FABRIK_BASE",  str_replace(DS.'administrator', '', JPATH_BASE));
define("COM_FABRIK_FRONTEND",  COM_FABRIK_BASE.DS.'components'.DS.'com_fabrik');
// Note: as we are using JURI::base() the url will end in a '/' so don't add a '/' after when using COM_FABRIK_LIVESITE
define("COM_FABRIK_LIVESITE",  str_replace('/administrator', '', JURI::base()));

define("FABRIKFILTER_TEXT", 0);
define("FABRIKFILTER_EVAL", 1);
define("FABRIKFILTER_QUERY", 2);
define("FABRKFILTER_NOQUOTES", 3);

/** @var string separator used in repeat groups */
define ("GROUPSPLITTER", "//..*..//");
/** @var same as GROUPSPLITTER, but for use in MySQL REGEXP, so escape any 'RE significant' chars */
define ("RE_GROUPSPLITTER", "//\.\.\*\.\.//");
/** @var string separator used to delimit multiple values eg for check boxes */
define ("GROUPSPLITTER2", "|-|");

/** @var delimiter used to define seperator in csv export */
define("COM_FABRIK_CSV_DELIMITER", ",");
define("COM_FABRIK_EXCEL_CSV_DELIMITER", ";");

//Register the element class with the loader
JLoader::register('JElement', JPATH_SITE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'element.php');

//Provide any PHP version specific function wrappers

// These two only exist in >=5.2.0
// We can assume FastJSON (via our json helper) will be included in fabrik.php, so use that instead
if (!function_exists('json_decode')) {
	function json_decode($json){
		if (empty($json)) {
			$json = array();
		}
		return FastJSON::decode($json);
	}
}
if (!function_exists('json_encode')) {
	function json_encode($json){
		return FastJSON::encode($json);
	}
}
?>