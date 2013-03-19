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
			list($time,$locking_user_id) = explode(';', $value);
			$this_user = JFactory::getUser();
			// $$$ decide what to do about guests
			$this_user_id = $this_user->get('id');
			if ((int)$this_user_id === (int)$locking_user_id)
			{
				return false;
			}
			$params = $this->getParams();
			$ttl = (int) $params->get('lockrow_ttl', '24');
			$ttl_time = (int) $time + ($ttl * 60 * 60);
			$time_now = time();
			if ($time_now < $ttl_time)
			{
				return true;
			}
		}
		return false;
	}

	public function showLocked($value, $this_user_id = null)
	{
		if (!empty($value)) {
			if (!isset($this_user_id))
			{
				$this_user = JFactory::getUser();
				$this_user_id = (int)$this_user->get('id');
			}
			else
			{
				$this_user_id = (int)$this_user_id;
			}
			list($time,$locking_user_id) = explode(';', $value);
			/*
			$this_user = JFactory::getUser();
			// $$$ decide what to do about guests
			$this_user_id = $this_user->get('id');
			if ((int)$this_user_id === (int)$locking_user_id)
			{
				return false;
			}
			*/
			$params = $this->getParams();
			$ttl = (int) $params->get('lockrow_ttl', '24');
			$ttl_time = (int) $time + ($ttl * 60 * 60);
			$time_now = time();
			if ($time_now < $ttl_time)
			{
				return true;
			}
		}
		return false;
	}

	private function canUnlock($value, $this_user_id = null)
	{
		$can_unlock = false;
		if (!empty($value))
		{
			if (!isset($this_user_id))
			{
				$this_user = JFactory::getUser();
				$this_user_id = (int)$this_user->get('id');
			}
			else
			{
				$this_user_id = (int)$this_user_id;
			}
			list($time,$locking_user_id) = explode(';', $value);
			$locking_user_id = (int)$locking_user_id;
			if ($this_user_id === $locking_user_id)
			{
				$can_unlock = true;
			}
		}
		return $can_unlock;
	}

	private function canLock($value, $this_user_id = null)
	{
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
			list($time,$locking_user_id) = explode(';', $value);
			$this_user = JFactory::getUser();
			// $$$ decide what to do about guests
			$this_user_id = $this_user->get('id');
			if ((int)$this_user_id === (int)$locking_user_id)
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

		// $$$ @TODO - may need to clean com_content cache as well
		$cache = & JFactory::getCache('com_fabrik');
		$cache->clean();

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
		if ($this->showLocked($data))
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

	function tableJavascriptClass()
	{

		FabrikHelperHTML::script('table-fabriklockrow.js', 'components/com_fabrik/plugins/element/fabriklockrow/', false);
	}

	function elementTableJavascript()
	{
		$user =& JFactory::getUser();

		$params =& $this->getParams();
		$user =& JFactory::getUser();
		$userid = (int) $user->get('id');
		$id = $this->getHTMLId();
		$tableModel =& $this->getTableModel();
		$table =& $tableModel->getTable();
		$formid = $table->form_id;
		$data =& $tableModel->getData();
		$gKeys = array_keys($data);
		$el_name = $this->getFullName(false, true, false, true);
		$el_name_raw = $el_name . '_raw';
		$row_locks = array();
		$can_unlocks = array();
		$can_locks = array();
		foreach ($gKeys as $gKey) {
			$groupedData = $data[$gKey];
			foreach ($groupedData as $rowkey) {
				$row_locks[$rowkey->__pk_val] = isset($rowkey->$el_name_raw) ? $this->showLocked($rowkey->$el_name_raw, $userid) : false;
				$can_unlocks[$rowkey->__pk_val] =  isset($rowkey->$el_name_raw) ? $this->canUnlock($rowkey->$el_name_raw, $userid) : false;
				$can_locks[$rowkey->__pk_val] = isset($rowkey->$el_name_raw) ? $this->canLock($rowkey->$el_name_raw, $userid) : false;
			}
		}
		$opts = new stdClass();

		jimport('joomla.utilities.simplecrypt');
		jimport('joomla.utilities.utility');
		//Create the encryption key, apply extra hardening using the user agent string
		$key = JUtility::getHash(@$_SERVER['HTTP_USER_AGENT']);
		$crypt = new JSimpleCrypt($key);
		$crypt_userid = $crypt->encrypt($userid);

		$opts->tableid      = $table->id;
		$opts->livesite     = COM_FABRIK_LIVESITE;
		$opts->imagepath    = COM_FABRIK_LIVESITE.'components/com_fabrik/plugins/element/fabriklockrow/images/';
		$opts->elid         = $this->getElement()->id;
		$opts->userid       = urlencode($crypt_userid);
		$opts->row_locks		= $row_locks;
		$opts->can_unlocks	= $can_unlocks;
		$opts->can_locks	= $can_locks;
		$opts               = json_encode($opts);
		return "new FbLockrowTable('$id', $opts);\n";
	}

	function ajax_unlock()
	{
		jimport('joomla.utilities.simplecrypt');
		jimport('joomla.utilities.utility');
		//Create the encryption key, apply extra hardening using the user agent string
		$key = JUtility::getHash(@$_SERVER['HTTP_USER_AGENT']);
		$crypt = new JSimpleCrypt($key);

		$table =& $this->getTableModel()->getTable();
		$tableid = $table->id;
		$formid = $table->form_id;
		$rowid = JRequest::getVar('row_id');
		$userid = JRequest::getVar('userid');

		$db_table_name = $this->getTableName();
		$field_name = FabrikString::safeColName($this->getFullName(false, false, false));
		$pk = $this->getTableModel()->getTable()->db_primary_key;
		$db = $this->getDBO();

		//$this_user = JFactory::getUser();
		//$this_user_id = $this_user->get('id');
		$this_user_id = $crypt->decrypt(urldecode($userid));

		$query = "SELECT $field_name FROM $db_table_name WHERE $pk = " . $db->quote($rowid);
		$db->setQuery($query);
		$value = $db->loadResult();

		$ret['status'] = 'unlocked';
		$ret['msg'] = 'Row unlocked';
		if (!empty($value))
		{
			if ($this->canUnlock($value, $this_user_id))
			{
				$query = "UPDATE $db_table_name SET $field_name = 0 WHERE $pk = " . $db->quote($rowid);
				$db->setQuery($query);
				$db->query();

				// $$$ @TODO - may need to clean com_content cache as well
				$cache = & JFactory::getCache('com_fabrik');
				$cache->clean();
			}
			else
			{
				$ret['status'] = 'locked';
				$ret['msg'] = 'Row was not unlocked!';
			}
		}
		echo json_encode($ret);
	}

}
?>