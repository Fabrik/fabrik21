<?php
/**
 * Plugin element to render fields
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikDisplaytext extends FabrikModelElement {

	var $_pluginName = 'displaytext';

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function setIsRecordedInDatabase() {
		$this->_recordInDatabase = false;
	}

	/**
	 * write out the label for the form element
	 * @param object form
	 * @param bol encase label in <label> tag
	 * @param string id of element related to the label
	 */

	function getLabel($repeatCounter)
	{
		return "";
	}

	/**
	 * draws the form element
	 * @param array data
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0) {
		$str = "<div class=\"fabrikSubElementContainer\">";
		$value = $this->getValue($data, $repeatCounter);
		$str .= $this->_replaceWithIcons($value);
		$str .= "</div>";
		return $str;
	}

  /**
   * this really does get just the default value (as defined in the element's settings)
   * @return unknown_type
   */

  function getDefaultValue($data = array())
  {
    if (!isset($this->_default)) {
	    $w = new FabrikWorker();
			$element =& $this->getElement();
			if ($element->eval) {
				//strip html tags
				$element->label = preg_replace(  '/<[^>]+>/i', '', $element->label);
				//change htmlencoded chars back
				$element->label = htmlspecialchars_decode($element->label);
				$this->_default = @eval($this->_default);
				FabrikWorker::logEval($this->_default, 'Caught exception on eval in '.$element->name.'::getDefaultValue() : %s');
			} else {
				$this->_default = $element->label;
			}
			$this->_default = $w->parseMessageForPlaceHolder($this->_default, $data);
    }
    return $this->_default;
  }

	/**
	 * draws the form element
	 * @param array data
	 * @param int repeat group counter
	 * @param array options
	 * @return string default value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array() )
	{
		// $$$rob - if no search form data submitted for the search element then the default
		// selection was being applied instead
		if (array_key_exists('use_default', $opts) && $opts['use_default'] == false) {
			$value = '';
		} else {
			$value   = $this->getDefaultValue($data);
		}
		// $$$ rob - $name not defined so this isnt going to do anything!
		/*if ($value === '') { //query string for joined data
			$value = JArrayHelper::getValue($data, $name);
		}*/
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
	 * render this elements admin section
	 *
	 */

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php echo $pluginParams->render('details');?></div>
		<?php
	}
}
?>