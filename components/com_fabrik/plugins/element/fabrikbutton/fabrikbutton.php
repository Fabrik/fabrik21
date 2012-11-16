<?php
/**
* Plugin element to render button
* @package fabrikar
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikButton  extends FabrikModelElement {

	var $_pluginName = 'button';

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * draws a field element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name 		= $this->getHTMLName($repeatCounter);
		$id 			= $this->getHTMLId($repeatCounter);
		$element 	=& $this->getElement();
		$str = '';
		if ($this->canUse() || (JRequest::getCmd('view') == 'details' && $this->canView())) {
			$str .= "<input type=\"button\" class=\"fabrikinput button\" id=\"$id\" name=\"$name\" value=\"$element->label\" />";
		}
		return $str;
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 * @return string database field description
	 */

	function getFieldDescription()
	{
		$p =& $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "VARCHAR(255)";
	}

	function renderAdminSettings()
	{
		?>
		<div id="page-<?php echo $this->_name;?>" class="elementSettings" style="display:none">
		<?php echo JText::_('No extra options available');?>
		</div><?php
	}

	function getLabel($repeatCounter)
	{
		return '';
	}

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts = json_encode($opts);
		return "new fbButton('$id', $opts)";
		return $str;
	}

		/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikbutton/', false);
	}

	/**
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 * @param int repeat group counter
	 * @return array html ids to watch for validation
	 */

	function getValidationWatchElements($repeatCounter)
	{
		$id 			= $this->getHTMLId($repeatCounter);
		$ar = array(
			'id' 			=> $id,
			'triggerEvent' => 'click'
		);
		return array($ar);
	}
}
?>