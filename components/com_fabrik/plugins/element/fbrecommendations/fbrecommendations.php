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

class FabrikModelFbrecommendations extends FabrikModelElement {

	var $_pluginName = 'fbrecommendations';

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
		$str = FabrikHelperHTML::facebookGraphAPI($params->get('opengraph_applicationid'), $params->get('fbrecommendations_locale', 'en_US'));
		$domain = $params->get('fbrecommendations_domain');
		$width = $params->get('fbrecommendations_width', 300);
		$height = $params->get('fbrecommendations_height', 300);
		$header = $params->get('fbrecommendations_header', 1) == 1 ? 'true' : 'false';
		$border = $params->get('fbrecommendations_border', '');
		$font = $params->get('fbrecommendations_font', 'arial');
		$colorscheme = $params->get('fbrecommendations_colorscheme', 'light');
		$str .= "<fb:recommendations site=\"$domain\" width=\"$width\" height=\"$height\" header=\"$header\" colorscheme=\"$colorscheme\" font=\"$font\" border_color=\"$border\" />";
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
		return "new fbRecommendations('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fbrecommendations/', false);
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