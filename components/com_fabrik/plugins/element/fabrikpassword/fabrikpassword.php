<?php
/**
 * Plugin element to render 2 fields to capture and confirm a password
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikPassword extends FabrikModelElement {

	var $_pluginName = 'password';

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function recordInDatabase($data)
	{
		$element =& $this->getElement();
		//if storing from inline edit then key may not exist
		if (!array_key_exists($element->name, $data)) {
			return false;
		}
		if (trim($data[$element->name]) === '') {
			return false;
		}else{
			return true;
		}
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		//$val = md5(trim($val));
		jimport('joomla.user.helper');
		$salt  = JUserHelper::genRandomPassword(32);
		$crypt = JUserHelper::getCryptedPassword($val, $salt);
		$val = $crypt.':'.$salt;
		return $val;
	}

	/**
	 * determines if the element can contain data used in sending receipts, e.g. fabrikfield returns true
	 */

	function isReceiptElement() {
		return true;
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name				= $this->getHTMLName($repeatCounter);
		$id 				= $this->getHTMLId($repeatCounter);
		$params 		=& $this->getParams();
		$element 		=& $this->getElement();
		$size 	    = $params->get('sizefield', 20);
		$maxlength  = $params->get('maxlength');
		if ((int)$maxlength === 0) {
			$maxlength = $size;
		}
		$value 	= "";
		$type = "password";
		$class = '';
		if (isset($this->_elementError) && $this->_elementError != '') {
			$class = " elementErrorHighlight";
		}
		if ($element->hidden == '1') {
			$type = "hidden";
		}
		$sizeInfo =  " size=\"$size\" maxlength=\"$maxlength\"";
		if (!$this->_editable) {
			if ($element->hidden == '1') {
				return "<!--" . stripslashes($value) . "-->";
			} else {
				return stripslashes($value);
			}
		}

		$value = stripslashes($value);
		$str = "<input class=\"fabrikinput inputbox $class\" type=\"$type\" name=\"$name\" $sizeInfo id=\"$id\" value=\"$value\" />\n";
		$str .= "<span class='strength'></span>";

		$origname = $element->name;
		$element->name = $element->name . "_check";
		$name				= $this->getHTMLName($repeatCounter);
		$str .= "<div class=\"fabrikSubLabel\"><label for=\"" . $id . "_check\">" . JText::_('CONFIRM_PASSWORD'). "</label>
		</div><input class=\"inputbox $class fabrikSubElement\" type=\"$type\" name=\"$name\" $sizeInfo id=\"" . $id . "_check\" value=\"$value\" />\n";
		$element->name = $origname;
		return $str;
	}

	/**
	 * validate the passwords
	 * @param string elements data
	 * @param int repeat group counter
	 * @return bol true if passes / false if falise validation
	 */

	function validate($data, $repeatCounter = 0)
	{
		$k = $this->getTableModel()->getTable()->db_primary_key;
		$k = FabrikString::safeColNameToArrayKey($k);
		$post	=& JRequest::get('post');
		$this->defaults = null;
		$element =& $this->getElement();
		$origname = $element->name;
		$element->name = $element->name . "_check";
		$checkvalue = $this->getValue($post, $repeatCounter);
		if (JArrayHelper::getValue($post, 'fabrik_postMethod', '') == 'ajax') {
			$checkvalue = urldecode($checkvalue);
		}
		$element->name = $origname;

		if ($checkvalue != $data) {
			$this->_validationErr = JText::_('PASSWORD_CONFIRMATION_DOES_NOT_MATCH');
			return false;
		} else {
			// $$$ hugh - added this option, mostly because of a corner case where the password
			// is on a table join, to a row that already exists, but the main table row is new.
			// Got lazy, and added this option, rather than try and work out in this code if the corner case
			// is true or not!
			$params = $this->getParams();
			if ($params->get('password_allow_empty', '0') == '0') {
				//$$$ rob add rowid test as well as if using row=-1 and usekey=field $k may have a value
				if (JRequest::getInt('rowid') === 0 && JRequest::getInt($k, 0, 'post') === 0 && $data === '') {
					$this->_validationErr .= JText::_('PASSWORD_CONFIRMATION_EMPTY_NOT_ALLOWED');
					return false;
				}
			}
			return true;
		}
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @param object element
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$formparams =& $this->getForm()->getParams();
		$opts->ajax_validation =  $formparams->get('ajax_validations');
		$opts = json_encode($opts);
		$lang = new stdClass();
		$lang->strong = JText::_('STRONG');
		$lang->medium = JText::_('MEDIUM');
		$lang->weak = JText::_('WEAK');

		$lang->type_password = JText::_('TYPE_PASSWORD');
		$lang->more_characters = JText::_('MORE_CHARACTERS');
		$lang = json_encode($lang);
		return "new fbPassword('$id', $opts, $lang)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikpassword/', false);
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
		$objtype = "VARCHAR(255)";
		return $objtype;
	}

	/**
	 * render the element admin settings
	 * @param object element
	 */

	function renderAdminSettings()
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	?> <?php
	echo $pluginParams->render();
	?></div>
	<?php
	}

	/**
	 *
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 * @param int repeat group counter
	 * @return array html ids to watch for validation
	 */

	function getValidationWatchElements($repeatCounter)
	{
		$id 			= $this->getHTMLId($repeatCounter) . "_check";
		$ar = array(
			'id' 			=> $id,
			'triggerEvent' => 'blur'
			);
			return array($ar);
	}
}
?>