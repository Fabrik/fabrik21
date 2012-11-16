<?php
/**
 * Plugin element to render facebook open graph activity feed widget
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFblikebox extends FabrikModelElement {

	var $_pluginName = 'fblikebox';

	var $hasLabel = false;
	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * draws the form element
	 * @param array data to pre-populate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$params =& $this->getParams();
		$str = FabrikHelperHTML::facebookGraphAPI($params->get('opengraph_applicationid'), $params->get('fblikebox_locale', 'en_US'));
		$pageid = $params->get('fblikebox_pageid', '');
		$stream = $params->get('fblikebox_stream', 1) == 1 ? 'true' : 'false';
		$width = $params->get('fblikebox_width', 300);
		$header = $params->get('fblikebox_header', 1) == 1 ? 'true' : 'false';
		$connections = $params->get('fblikebox_connections', 10);
		$str .= "<fb:like-box href=\"$pageid\" width=\"$width\" connections=\"$connections\" stream=\"$stream\" header=\"$header\" />";

		//<fb:like-box href="https://www.facebook.com/badmintonrochelais" width="292" show_faces="true" stream="true" header="true"></fb:like-box>
		//$str .= "<fb:like-box id=\"185550966885\" width=\"292\" height=\"440\" connections=\"4\" stream=\"true\" header=\"true\" />";
		return $str;
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts = json_encode($opts);
		return "new fbLikebox('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fblikebox/', false);
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */
	function getFieldDescription()
	{
		$p =& $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "INT(1)";
	}

}
?>