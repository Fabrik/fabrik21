<?php

/**
 * Add an action button to the table to update selected columns to a given value
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-table.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php');

class FabrikModelUpdate_col extends FabrikModelTablePlugin
{

	var $_counter = null;

	var $_buttonPrefix = 'update_col';

	var $_sent = 0;

	var $_notsent = 0;

	var $_row_count = 0;

	var $msg = null;

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function button()
	{
		return "update records";
	}

	/**
	 * renders button at bottom of the page
	 * @param int plugin render order
	 */

	function button_result($c)
	{
		$params =& $this->getParams();
		$loc = $params->get('updatecol_button_location', 'bottom');
		if ($loc == 'bottom' || $loc == 'both') {
			return $this->getButton();
		} else {
			return '';
		}
	}

	protected function getButton()
	{
		$params =& $this->getParams();
		$access = $params->get('updatecol_access');
		$name = $this->_getButtonName();
		$canuse = FabrikWorker::getACL($access, $name);
		if ($canuse) {
			return "<input type=\"button\" name=\"$name\" value=\"". $params->get( 'button_label', 'update') . "\" class=\"button tableplugin\"/>";
		}
		return '';
	}

	function canUse()
	{
		return true;
	}


	/**
	 * (non-PHPdoc)
	 * @see FabrikModelTablePlugin::getAclParam()
	 */

	function getAclParam()
	{
		return 'updatecol_access';
	}

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bol
	 */

	function canSelectRows()
	{
		$params =& $this->getParams();
		$access = $params->get('updatecol_access');
		$name = $this->_getButtonName();
		$canuse = FabrikWorker::getACL($access, $name);
		return $canuse;
	}

	/**
	 * do the plugin action
	 * @param object parameters
	 * @param object table model
	 */

	function process(&$params, &$model, $opts = array())
	{
		$db =& $model->getDb();
		$user =& JFactory::getUser();
		$updateTo = $params->get('update_value');
		$updateCol = $params->get('coltoupdate');
		$updateTo_2 = $params->get('update_value_2');
		$updateCol_2 = $params->get('coltoupdate_2');
		// $$$ rob moved here from bottom of func see http://fabrikar.com/forums/showthread.php?t=15920&page=7
		$tbl = array_shift(explode('.', $updateCol));
		$dateCol = $params->get('update_date_element');
		$userCol = $params->get('update_user_element');

		$table =& $model->getTable();
		// array_unique for left joined table data
		$ids = array_unique(JRequest::getVar('ids', array(), 'method', 'array'));
		JArrayHelper::toInteger($ids);
		$this->_row_count = count($ids);
		$ids	= implode(',', $ids);
		$model->_pluginQueryWhere[] = $table->db_primary_key . ' IN ( '.$ids.')';
		$data =& $model->getData();

		//$$$servantek reordered the update process in case the email routine wants to kill the updates
		$emailColID = $params->get('update_email_element', '');
		if (!empty($emailColID)) {
			$w = new FabrikWorker();
			jimport('joomla.mail.helper');
			$message = $params->get('update_email_msg');
			$subject = $params->get('update_email_subject');
			$eval = $params->get('eval', 0);
			$config =& JFactory::getConfig();
			$from = $config->getValue('mailfrom');
			$fromname = $config->getValue('fromname');
			$elementModel =& JModel::getInstance('element', 'FabrikModel');
			$elementModel->setId($emailColID);
			$emailElement =& $elementModel->getElement(true);
			$emailField = $elementModel->getFullName(false, true, false);
			$emailColumn = $elementModel->getFullName(false, false, false);
			$emailFieldRaw = $emailField . '_raw';
			$emailWhich = $emailElement->plugin == 'fabrikuser' ? 'user' : 'field';
			$db =& JFactory::getDBO();
			$aids = explode(',', $ids);
			// if using a user element, build a lookup list of emails from jos_users,
			// so we're only doing one query to grab all involved emails.
			if ($emailWhich == 'user') {
				$userids_emails = array();
				$query = 'SELECT #__users.id AS id, #__users.email AS email FROM #__users LEFT JOIN ' . $tbl . ' ON #__users.id = ' . $emailColumn . ' WHERE ' . $table->db_primary_key . ' IN ('.$ids.')';
				$db->setQuery($query);
				$results = $db->loadObjectList();
				foreach ($results as $result) {
					$userids_emails[(int)$result->id] = $result->email;
				}
			}
			foreach ($aids as $id) {
				$row = $model->getRow($id);
				if ($emailWhich == 'user') {
					$userid = (int)$row->$emailFieldRaw;
					$to = $userids_emails[$userid];
				}
				else {
					$to = $row->$emailField;
				}

				if (JMailHelper::cleanAddress($to) && JMailHelper::isEmailAddress($to)) {
					//$tofull = '"' . JMailHelper::cleanLine($toname) . '" <' . $to . '>';
					//$$$servantek added an eval option and rearranged placeholder call
					$thissubject = $w->parseMessageForPlaceholder($subject, $row);
					$thismessage = $w->parseMessageForPlaceholder($message, $row);
					if ($eval) {
						$thismessage = @eval($thismessage);
						FabrikWorker::logEval($thismessage, 'Caught exception on eval in updatecol::process() : %s');
					}
					$res = JUtility::sendMail($from, $fromname, $to, $thissubject, $thismessage, true);
					if ($res) {
						$this->_sent ++;
					} else {
						$$this->_notsent ++;
					}
				} else {
					$this->_notsent ++;
				}
			}
		}
		//$$$servantek reordered the update process in case the email routine wants to kill the updates
		if (!empty($dateCol)) {
			$date =& JFactory::getDate();
			$this->_process($model, $dateCol, $date->toMySQL());
		}

		if (!empty($userCol)) {
			$this->_process($model, $userCol, (int)$user->get('id'));
		}
		$this->_process($model, $updateCol, $updateTo);
		if (!empty($updateCol_2)) {
			$this->_process($model, $updateCol_2, $updateTo_2);
		}
		// $$$ hugh - this stuff has to go in process_result()
		//$msg = $params->get( 'update_message' );
		//return JText::sprintf( $msg, count($ids));
			$this->msg = $params->get('update_message', '');

		if (empty($this->msg)) {
			$this->msg = JText::sprintf('%d ROWS UPDATED, %d EMAILS SENT', $this->_row_count, $this->_sent);
		} else {
			$this->msg = JText::sprintf($this->msg, $this->_row_count, $this->_sent);
		}

		return true;
	}

	function process_result($c)
	{
		// $$$ rob moved msg processing to process() as for some reason we
		//have incorrect plugin object here (where as php table plugin's process_result()
		//has correct params object - not sure why that is :(
		// $$$ hugh - I think we can move it back now, didn't you decide it was something to do with building the table mode in process()
		// and fix that?
		return $this->msg;
	}

	/**
	 *
	 * @param string table name to update
	 * @param object $model table
	 * @param array $joins objects
	 * @param string $update column
	 * @param string update val
	 */

	private function _process(&$model, $col, $val)
	{
		$ids	= JRequest::getVar('ids', array(), 'method', 'array');
		$model->updateRows($ids, $col, $val);
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/table/update_col/', false);
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @param object parameters
	 * @param object table model
	 * @param array [0] => string table's form id to contain plugin
	 * @return bool
	 */

	function loadJavascriptInstance($params, $model, $args)
	{
		$form_id = $args[0];
		$opts = new stdClass();
		$opts->name = $this->_getButtonName();
		$opts = json_encode($opts);
		$lang = $this->_getLang();
		$lang = json_encode($lang);
		$this->jsInstance = "new fbTableUpdateCol('$form_id', $opts, $lang)";
		return true;
	}


	function _getColName()
	{
		$params = $this->getParams();
		$col = $params->get('coltoupdate');
		return $col.'-'.$this->renderOrder;
	}

	function _getButtonName()
	{
		//$$$ hugh - for some reason, _getColName() screws up because getParams() returns the wrong params.
		// and as we don't seem to use the col name from the button name, lets skip it ...
		//return $this->_buttonPrefix ."-" . $this->_getColName();
		return parent::_getButtonName();
	}

	/**
	 * show a new for entering the form actions options
	 */

	function renderAdminSettings($elementId, &$row, &$params, $lists, $c)
	{
		$params->_counter_override = $this->_counter;
		$display =  ($this->_adminVisible) ? "display:block" : "display:none";
		$return = '<div class="page-' . $elementId . ' elementSettings" style="' . $display . '">
 		' . $params->render('params', '_default', false, $c) .
 		'</div>
 		';
		$return = str_replace("\r", "", $return);
		return $return;
	}

	/**
	 * get the position for the button
	 */

	protected function getRenderLocation()
	{
		return $this->getParams()->get('updatecol_button_location', 'bottom');
	}

}
?>