<?php

/**
* A cron task to email records to a give set of users
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');


class FabrikModelCronemail extends fabrikModelPlugin {

	var $_counter = null;

	var $logMsg = '';

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}


	function canUse()
	{
		return true;
	}

	function getLog()
	{
		return $this->logMsg;
	}
	/**
	 * do the plugin action
	 * @return number of records updated
	 */

	function process(&$data)
	{
		$app 				=& JFactory::getApplication();
		jimport('joomla.mail.helper');
		$params =& $this->getParams();
		$msg = $params->get('message');
		$to = $params->get('to');
		$w = new FabrikWorker();
		$MailFrom	= $app->getCfg('mailfrom');
		$FromName	= $app->getCfg('fromname');
		$subject = $params->get('subject', 'Fabrik cron job');
		$eval = $params->get('cronemail-eval');
		$condition = trim($params->get('cronemail_condition', ''));
		$merge_emails = (int)$params->get('cronemail_mergemessages', 0);
		$merged_msg = '';
		$updates = array();
		$first_row = array();
		foreach ($data as $group) {
			if (is_array($group)) {
				foreach ($group as $row) {
					$row = JArrayHelper::fromObject($row);
					if (empty($first_row)) {
						$first_row = $row; // used for placeholders in subject when merging mail
					}
					if (!empty($condition)) {
						$this_condition = $w->parseMessageForPlaceHolder($condition, $row);
						if (eval($this_condition) === false) {
							$this->logMsg .= "\n condition returned false: $this_condition\n";
							continue;
						}
					}
					if (!$merge_emails) {
						$thistos = explode(',', $w->parseMessageForPlaceHolder($to, $row));
						$thismsg = $w->parseMessageForPlaceHolder($msg, $row);
						if ($eval) {
							$thismsg = eval($thismsg);
						}
						$thissubject = $w->parseMessageForPlaceHolder($subject, $row);
						foreach ($thistos as $thisto) {
							if (JMailHelper::isEmailAddress($thisto)) {
								$res = JUTility::sendMail($MailFrom, $FromName, $thisto, $thissubject, $thismsg, true);
								$this->logMsg .= "\n $thisto: ";
								$this->logMsg .= $res ? ' sent ' : ' not sent \n';
							} else {
									$this->logMsg .= "\n $thisto not an email\n";
							}
						}
					}
					else {
						$thismsg = $w->parseMessageForPlaceHolder($msg, $row);
						if ($eval) {
							$thismsg = eval($thismsg);
						}
						$merged_msg .= $thismsg;						
					}
					$updates[] = $row['__pk_val'];

				}
			}
		}
		if ($merge_emails) {
			// arbitrarily use first row for placeholders
			$thistos = explode(',', $w->parseMessageForPlaceHolder($to, $first_row));
			$thissubject = $w->parseMessageForPlaceHolder($subject, $first_row);
			$merged_msg = $params->get('cronemail_message_preamble', '') . $merged_msg . $params->get('cronemail_message_postamble', '');
			foreach ($thistos as $thisto) {
				if (JMailHelper::isEmailAddress($thisto)) {
					$res = JUTility::sendMail($MailFrom, $FromName, $thisto, $thissubject, $merged_msg, true);
					$this->logMsg .= "\n $thisto: ";
					$this->logMsg .= $res ? ' sent ' : ' not sent \n';
				} else {
						$this->logMsg .= "\n $thisto not an email\n";
				}
			}
		}
		$field = $params->get('cronemail-updatefield');
		if (!empty($updates) && trim($field) != '') {
			//do any update found
			$tableModel =& JModel::getInstance('table', 'FabrikModel');
			$tableModel->setId($params->get('table'));
			$table =& $tableModel->getTable();

			$connection = $params->get('connection');
			$field = $params->get('cronemail-updatefield');
			$value = $params->get('cronemail-updatefield-value');
			$noquotes = $params->get('cronemail-updatefield-no-quotes');

			$field = str_replace("___", ".", $field);
			$fabrikDb =& $tableModel->getDb();
			$query = "UPDATE $table->db_table_name set $field = " .
			  ($noquotes ? $value : $fabrikDb->Quote($value)) .
			  " WHERE $table->db_primary_key IN (" . implode(',', $updates) . ")";
			$fabrikDb->setQuery($query);
			$fabrikDb->query();
		}
		return count($updates);
	}

	/**
	 * show a new for entering the form actions options
	 */

	function renderAdminSettings()
	{
		//JHTML::stylesheet('fabrikadmin.css', 'administrator/components/com_fabrik/views/');
		$this->getRow();
		$pluginParams =& $this->getParams();

		$document =& JFactory::getDocument();
		?>
		<div id="page-<?php echo $this->_name;?>" class="pluginSettings" style="display:none">
		<?php
			echo $pluginParams->render('params');
			echo $pluginParams->render('params', 'fields');
			?>
			<fieldset>
				<legend><?php echo JText::_('update') ?></legend>
				<?php echo $pluginParams->render('params', 'update') ?>
			</fieldset>
		</div>

		<?php
		return;
	}

}
?>