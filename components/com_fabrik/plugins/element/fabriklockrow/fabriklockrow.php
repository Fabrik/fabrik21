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

class FabrikModelFabrikLockrow extends FabrikModelElement {

	var $_pluginName = 'lockrow';

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}

	public function isLocked($value)
	{
		if (!empty($value)) {
			list($time,$user) = explode(';', $value);
			$this_user = JFactory::getUser();
			// $$$ decide what to do about guests
			$userid = $this_user->get('id');
			if ((int)$userid === (int)$user)
			{
				return false;
			}
			$params = $this->getParams();
			$ttl = (int) $params->get('lockrow_ttl', '24');
			$ttl_time = (int) $time + ($ttl * 60 * 60);
			if (time() < $ttl_time)
			{
				return true;
			}
		}
		return false;
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

		$element->hidden = true;

		if (!$this->_editable || !$this->canUse() || (JRequest::getCmd('view') == 'details')) {
			return '';
		}

		$rowid = (int) $this->getFormModel()->getRowId();

		if (empty($rowid)) {
			return "";
		}

		$ttl_unlock = false;
		if ($value != 0) {
			list($time,$user) = explode(';', $value);
			$user = JFactory::getUser();
			// $$$ decide what to do about guests
			$userid = $user->get('id');
			if ((int)$userid === (int)$user)
			{
				return "";
			}
			$ttl = (int) $params->get('lockrow_ttl', '24');
			$ttl_time = (int) $time + ($ttl * 60 * 60);
			if (time() < $ttl_time)
			{
				$app = JFactory::getApplication();
				$app->enqueueMessage('ROW IS LOCKED!');
				return "";
			}
			else
			{
				$app = JFactory::getApplication();
				$app->enqueueMessage('ROW UNLOCKED!');
				$ttl_unlock = true;
			}
		}

		$db_table_name = $this->getTableName();
		$field_name = FabrikString::safeColName($this->getFullName(false, false, false));
		$pk = $this->getTableModel()->getTable()->db_primary_key;
		$db = $this->getDBO();
		$user = JFactory::getUser();
		$user_id = $user->get('id');
		$lockstr = time() . ";" . $user_id;

		$query = "UPDATE $db_table_name SET $field_name = " . $db->quote($lockstr) . " WHERE $pk = " . $db->quote($rowid);
		$db->setQuery($query);
		$db->query();

		return "";
	}

	/**
	* shows the data formatted for the table view
	* @param string data
	* @param object all the data in the tables current row
	* @return string formatted value
	*/
	function renderTableData($data, $oAllRowsData)
	{
		$data = explode(GROUPSPLITTER, $data);
		for ($i=0; $i <count($data); $i++) {
			$data[$i] = $this->_renderTableData($data[$i], $oAllRowsData);
		}
		$data = implode(GROUPSPLITTER, $data);
		return parent::renderTableData($data, $oAllRowsData);
	}

	function _renderTableData($data, $oAllRowsData)
	{
		$icon = '';
		$alt = '';
		$class = '';
		$imagepath = COM_FABRIK_LIVESITE.'components/com_fabrik/plugins/element/fabriklockrow/images/';
		if ($this->isLocked($data))
		{
			$icon = 'locked.png';
			$alt = 'Locked';
			$class = 'fabrikElement_lockrow_locked';
		}
		else
		{
			$icon = 'unlocked.png';
			$alt = 'Not Locked';
			$class = 'fabrikElement_lockrow_unlocked';
		}

		$str = "<img src='" . $imagepath . $icon . "' alt='" . $alt . "' class='fabrikElement_lockrow " . $class . "' />";
		return $str;
	}

	function storeDatabaseFormat($val, $data)
	{
		return '0';
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */

	function getFieldDescription()
	{
		return "VARCHAR(32)";
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
		return "new fbLockrow('$id', $opts)";
	}


	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabriklockrow/', false);
	}

	function isHidden()
	{
		return true;
	}
}
?>