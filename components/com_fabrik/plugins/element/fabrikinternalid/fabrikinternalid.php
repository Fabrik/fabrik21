<?php
/**
* Plugin element to render internal id
* @package fabrikar
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikInternalid extends FabrikModelElement {

	var $_pluginName = 'internalid';

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name 		= $this->getHTMLName($repeatCounter);
		$id				= $this->getHTMLId($repeatCounter);
		$params 	=& $this->getParams();
		$element 	=& $this->getElement();
		$value 		= $this->getValue($data, $repeatCounter);
		$type = "hidden";
		if (isset($this->_elementError) && $this->_elementError != '') {
			$type .= " elementErrorHighlight";
		}
		if (!$this->_editable) {
			//as per http://fabrikar.com/forums/showthread.php?t=12867
			//return "<!--" . stripslashes($value) . "-->";
			return($element->hidden == '1') ? "<!-- " . stripslashes($value) . " -->" : stripslashes($value);
		}
		$hidden = 'hidden';
		/* no need to eval here as its done before hand i think ! */
		if ($element->eval == "1" and !isset($data[$name])) {
			$str = "<input class=\"inputbox $type\" type=\"$hidden\" name=\"$name\" id=\"$id\" value=\"$value\" />\n";
		} else {
			$value = stripslashes($value);
			$str = "<input class=\"inputbox fabrikinput $type\" type=\"$hidden\" name=\"$name\" id=\"$id\" value=\"$value\" />\n";
		}
		return $str;
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */

	function getFieldDescription()
	{
		return "INT(6) NOT NULL AUTO_INCREMENT";
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings()
	{
		?>
		<div id="page-<?php echo $this->_name;?>" class="elementSettings" style="display:none">
		<?php echo JText::_('No extra options available');?>
		</div><?php
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts = json_encode($opts);
		return "new fbInternalId('$id', $opts)";
	}


	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikinternalid/', false);
	}

	function isHidden()
	{
		return true;
	}
}
?>