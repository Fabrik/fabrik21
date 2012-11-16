<?php
/**
 * Plugin element to render mootools slider
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikslider extends FabrikModelElement {

	var $_pluginName = 'slider';

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		$params =& $this->getParams();

		return parent::renderTableData($data, $oAllRowsData);
	}

	/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		FabrikHelperHTML::stylesheet('fabrikslider.css', 'media/com_fabrik/css/');
		$name 			= $this->getHTMLName($repeatCounter);
		$id 				= $this->getHTMLId($repeatCounter);
		$params 		=& $this->getParams();
		$element 		=& $this->getElement();
		$val 				= $this->getValue($data, $repeatCounter);
		if (!$this->_editable) {
			return $val;
		}
		$imagepath = COM_FABRIK_LIVESITE.'components/com_fabrik/plugins/element/fabrikslider/images/';

		$labels = array_filter(explode(',', $params->get('slider-labels')));


		$str 			= "<div class=\"fabrikSubElementContainer\" id=\"$id\">";
		if ($params->get('slider-shownone')) {
			$str .= "<div class=\"clearslider_cont\"><img src=\"$imagepath"."clear_rating_out.png\" style=\"cursor:pointer;padding:3px;\" alt=\"clear\" class=\"clearslider\" /></div>";
		}
		$str .="<div class=\"slider_cont\" style=\"width:{$element->width}px;\">\n";
		if (count($labels) > 0) {
			$spanwidth = floor(($element->width - (2 * count($labels))) /count($labels));
			$str .= "<ul class=\"slider-labels\" style=\"width:{$element->width}px;\">\n";
			for ($i=0; $i < count($labels); $i++) {
				if ($i == ceil(floor($labels)/2)) {
					$align = 'center';
				}
				switch($i) {
					case 0:
						$align = 'left';
						break;
					case 1:
					default:
						$align = 'center';
						break;
					case count($labels) -1:
						$align = 'right';
						break;
				}
				$str .= "<li style=\"width:{$spanwidth}px;text-align:$align;\">".$labels[$i]."</li>\n";
			}
			$str .= "</ul>\n";
		}
		$str .= "<div class=\"fabrikslider-line\" style=\"width:{$element->width}px\">\n<div class=\"knob\"></div>\n</div>\n";
		$str .= "<input type=\"hidden\" class=\"fabrikinput\"  name=\"$name\" value=\"$val\"/>\n";
		$str .= "<div class=\"slider_output\">$val</div>\n";
		$str .= "</div>";
		return $str;
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$params =& $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts->steps = (int)$params->get('slider-steps', 100);
		$data 		=& $this->_form->_data;
		$opts->value = $this->getValue($data, $repeatCounter);
		$opts = json_encode($opts);
		return "new fbSlider('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikslider/', false);
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */
	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		$group =& $this->getGroup();
    if ($group->isJoin()== 0 && $group->canRepeat()) {
      return "TEXT";
    }
		return "INT(6)";
	}

}
?>