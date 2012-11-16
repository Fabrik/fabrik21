<?php
/**
 * Plugin element to render plain text
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikDisplay extends FabrikModelElement {

	var $_pluginName = 'display';

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function setIsRecordedInDatabase()
	{
		$this->_recordInDatabase = false;
	}

	/**
	 * write out the label for the form element
	 * @param object form
	 * @param bol encase label in <label> tag
	 * @param string id of element related to the label
	 */

	function getLabel($repeatCounter = 0)
	{
		$params =& $this->getParams();
		if ($params->get('display_showlabel', true)) {
			return parent::getLabel($repeatCounter);
		}
		$bLabel = false;

		$element =& $this->getElement();
		$element->label = $this->getValue(array());
		$elementHTMLId = $this->getHTMLId();
		if ($element->hidden) {
		  return '';
		}
		$task = JRequest::getVar('task', '', 'default');
		$view = JRequest::getVar('view', '', 'form');
		if ($view == 'form' && ! ( $this->canUse() || $this->canView())) {
		  return '';
		}
		$params =& $this->getParams();
		$elementid = "fb_el_" . $elementHTMLId;
		$this->_form->loadValidationRuleClasses();
		$str = '';

		$rollOver = JText::_($params->get('hover_text_title')) . "::" . JText::_($params->get('rollover'));
		$rollOver = htmlspecialchars($rollOver, ENT_QUOTES);

		if ($this->canView()) {
		  $str .= "<div class=\"fabrikLabel fabrikPluginElementDisplayLabel";
		  $validations =& $this->getValidations();
		  if ($this->_editable) {
			foreach ($validations as $validation) {
			  $vid = $validation->_pluginName;
			  if (array_key_exists($vid, $this->_form->_validationRuleClasses)) {
				if ($this->_form->_validationRuleClasses[$vid] != '') {
				  $str .= " " . $this->_form->_validationRuleClasses[$vid];
				}
			  }
			}
		  }
		  if ($rollOver != '::') {
			$str .= " fabrikHover";
		  }
		  $str .= "\" id=\"$elementid" . "_text\">";
		  if ($bLabel) {
			$str .= "<label for=\"$elementHTMLId\">";
		  }


		  $str .= ($rollOver != '::') ? "<span class='hasTip' title='$rollOver'>{$element->label}</span>" : $element->label;
		  if ($bLabel) {
			$str .= "</label>";
		  }
		  $str .= "</div>\n";
		}
		return $str;
	}

	/**
	 * draws the form element
	 * @param array data
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0) {
		$params =& $this->getParams();
		$id 	= $this->getHTMLId($repeatCounter);
		if (!$params->get('display_showlabel', true)) {
			// $$$ hugh - still need to include empty div with $id so JS FX will work
			return "<div class=\"fabrikSubElementContainer\" id=\"$id\"></div>";
		}
		$value =  $this->getValue($data, $repeatCounter);
		$value = $this->_replaceWithIcons($value);
		return "<div class=\"fabrikSubElementContainer\" id=\"$id\">$value</div>";
	}



	/**
	 * draws the form element
	 * @param array data
	 * @param int repeat group counter
	 * @param array options
	 * @return string default value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		$element =& $this->getElement();
		$params =& $this->getParams();
		// $$$rob - if no search form data submitted for the search element then the default
		// selection was being applied instead
		if (array_key_exists('use_default', $opts) && $opts['use_default'] == false) {
			$value = '';
		} else {
			$value    = $this->getDefaultValue($data);
		}
		if ($value === '') { //query string for joined data
			$value = JArrayHelper::getValue($data, $value);
		}
		$formModel =& $this->getForm();
		//stops this getting called from form validation code as it messes up repeated/join group validations
		if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1) {
			$formModel->getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
		}
		return $value;
	}

	/**
	 * get the db field description
	 *
	 * @return string
	 */

	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "TEXT";
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
		return "new fbDisplay('$id', $opts)";
	}
	
	/**
	* load the javascript class that manages interaction with the form element
	* should only be called once
	* @return string javascript class file
	*/
	
	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikdisplay/', false);
	}

}
?>