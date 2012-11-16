<?php

/**
 * Allows users to report the record
 *
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelReport_record extends FabrikModelElement {

	/**
	 * draws the form element
	 * @param array data to pre-populate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name 			= $this->getHTMLName($repeatCounter);
		$id 				= $this->getHTMLId($repeatCounter);
		$str = '';
		// check if this is a new record, in which case pointless providing a button or link
		if (!array_key_exists('__pk_val', $data) || empty($data['__pk_val'])) {
			$str = "[" . JText::_('AVAILABLE_ON_SAVE') . "]";
			$str .= "<input class='fabrikinput inputbox' type='hidden' name='$name' value='' id='$id' />\n";
			return $str;
		}
		$user =& JFactory::getUser();
		$value 	= $this->getValue($data, $repeatCounter);
		if ($user->gid == 25) {
			$str .= JText::sprintf('REPORTED_X_TIMES', $value) . "<br />";
		}
		if (!$this->show()) {
			$str .= JText::_('YOUVE_ALREADY_REPORTED_THIS_RECORD');
			return $str;
		}

		$params =& $this->getParams();
		$url = COM_FABRIK_LIVESITE . 'index.php?option=com_fabrik&format=raw&controller=plugin&g=element&task=pluginAjax&plugin=report_record&method=report&nonajax=1&elid='.$this->getElement()->id;

		$url .='&rowid='.$data['__pk_val'];
		$url .= '&tableid='.$this->getTableModel()->getTable()->id;
		$url .='formid='.$this->getForm()->getForm()->id;

		if ($params->get('report_record_style') == 'button') {
			$str .= '<input type="button" name="'.$name.'" id="'.$id.'" class="report_record_button" value="'.JText::_('REPORT_RECORD').'" />';
		} else {
			$str .= '<a href="'.$url.'" name="'.$name.'" id="'.$id.'" class="report_record_button" rel="nofollow">'.JText::_('REPORT_RECORD').'</a>';
		}
		return $str;
	}

	protected function show($rowid = null)
	{
		$params =& $this->getParams();
		$formModel		=& $this->getForm();
		if (is_null($rowid)) {
			$rowid = $formModel->_rowId;
		}
		$tableid = $formModel->getTableModel()->getTable()->id;
		return $this->getCookieValue($tableid, $rowid) != '' ? false : true;
	}

	public function formJavascriptClass()
	{
		if (!$this->show()) {
			return;
		}
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/report_record/');
	}

	public function elementJavascript($repeatCounter)
	{
		if (!$this->show()) {
			return;
		}
		$id = $this->getHTMLId($repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts->elid = $this->getElement()->id;
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts = json_encode($opts);
		return "new FbReportRecord('$id', $opts)";
	}

	function tableJavascriptClass()
	{
		if ($this->getElement()->show_in_table_summary) {
			FabrikHelperHTML::script('table-report_record.js', 'components/com_fabrik/plugins/element/report_record/', false);
		}
	}

	function elementTableJavascript()
	{
		if (!$this->getElement()->show_in_table_summary) {
			return;
		}
		$params =& $this->getParams();
		$id = $this->getHTMLId();
		$opts = new stdClass();
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts->elid = $this->getElement()->id;

		$opts->style = $params->get('report_record_style', 'button');
		$formModel =& $this->getForm();
		$opts->formid = $formModel->getForm()->id;
		$opts->tableid = $formModel->getTableModel()->getTable()->id;
		$opts->view = 'table';
		$opts = json_encode($opts);
		return "new FbReportRecordTable('$id', $opts);\n";
	}

	/**
	 * called from plugins ajax call
	 */

	function report()
	{
		$this->_id = JRequest::getInt('elid');
		$config =& JFactory::getConfig();
		$tableid = JRequest::getInt('tableid');
		$rowid = JRequest::getVar('rowid');
		$cookie = $this->getCookieValue($tableid, $rowid);
		if ($cookie != '') {
		 return JText::_('YOUVE_ALREADY_REPORTED_THIS_RECORD');
		}
		jimport('joomla.mail.helper');
		$this->setCookie($tableid, $rowid);
		$this->setReportCount();
		$params =& $this->getParams();
		$email_to = explode(',', $params->get('report_record_email'));
		$email_from = $config->getValue('mailfrom');
		$thisSubject = $params->get('report_record_subject');
		$thisMessage = $params->get('report_record_message');
		$email_from_name= $config->getValue('fromname');
		$formModel =& $this->getForm();
		// Add link to record
		$viewURL = COM_FABRIK_LIVESITE . "index.php?option=com_fabrik&view=details&fabrik=".$formModel->_id;
		$viewURL .= "&rowid=".JRequest::getVar('rowid');

		$thisMessage = str_ireplace('{LINK}', $viewURL, $thisMessage);
		$thisSubject = str_ireplace('{LINK}', $viewURL, $thisSubject);
		foreach ($email_to as $email) {
			$email = trim($email);
			if (empty($email)) {
				continue;
			}
			if (JMailHelper::isEmailAddress($email)) {
				$res = JUtility::sendMail( $email_from, $email_from_name, $email, $thisSubject, $thisMessage, true);
			} else {
				JError::raiseNotice(500, JText::sprintf( 'DID_NOT_SEND_EMAIL_INVALID_ADDRESS', $email));
			}
		}
		return JText::_('THIS_RECORD_HAS_BEEN_REPORTED');
	}

	private function setReportCount()
	{
		$tableModel =& $this->getTableModel();
		$rowid = JRequest::getVar('rowid');
		$key = $this->getElement()->name;
		$tableModel->incrementCell($rowid, $key, 1);
	}

	/**
	 * save the cookie so users can't re-report the same record
	 * @param int $tableid
	 * @param int $rowid
	 */

	private function setCookie($tableid, $rowid)
	{
		$hash = $this->getCookieName($tableid, $rowid);
		//if voted and cookie doesnt exist
		//set cookie
		$cookie = 'rand'.rand();
		$lifetime = time() + 365*24*60*60;
		setcookie( $hash, $cookie, $lifetime, '/');
	}

	/**
	 * get the hashed cookie name
	 * @param int $tableid
	 * @param int $rowid
	 */
	private function getCookieName($tableid, $rowid)
	{
		$cookieName =  "report_record-table_{$tableid}_row_{$rowid}";
		jimport('joomla.utilities.utility');
		return JUtility::getHash($cookieName);
	}

	/**
	 * get the hashed cookie name's value
	 * @param int $tableid
	 * @param int $rowid
	 */
	private function getCookieValue($tableid, $rowid)
	{
		$hash = $this->getCookieName($tableid, $rowid);
		$cookie = JRequest::getString($hash, '', 'cookie', JREQUEST_ALLOWRAW | JREQUEST_NOTRIM);
		return $cookie;
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php echo $pluginParams->render();?></div>
		<?php
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
		$str = '';
		$user =& JFactory::getUser();
		$value 	= (int)$data;
		if ($user->gid == 25) {
			$str .= '<small class="report_record_num_reported">'.JText::sprintf('REPORTED_X_TIMES', $value) . "</small><br />";
		}
		if (!$this->show($oAllRowsData->__pk_val)) {
			$str .= '<span class="report_record_already_reported">'.JText::_('YOUVE_ALREADY_REPORTED_THIS_RECORD').'</span>';
			return $str;
		}

		$name 			= $this->getHTMLName();
		$id 				= $this->getHTMLId();
		$params =& $this->getParams();
		$url =  COM_FABRIK_LIVESITE.'index.php?option=com_fabrik&controller=plugin&view=plugin&format=raw&g=element&task=pluginAjax&plugin=report_record&method=report&nonajax=1&elid='.$this->getElement()->id;

		$url .='&rowid='.$oAllRowsData->__pk_val;
		$url .= '&tableid='.$this->getTableModel()->getTable()->id;
		$url .='&formid='.$this->getForm()->getForm()->id;
		$url = str_replace('&', '&amp;', $url);
		if ($params->get('report_record_style') == 'button') {
			$str .= '<input type="button" name="'.$name.'" class="report_record_button" value="'.JText::_('REPORT_RECORD').'" />';
		} else {
			$str .= '<a href="'.$url.'" class="report_record_button" rel="nofollow">'.JText::_('REPORT_RECORD').'</a>';
		}
		return $str;
	}
}
?>