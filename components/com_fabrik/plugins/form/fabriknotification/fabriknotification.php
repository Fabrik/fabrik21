<?php

/**
 * Allows users to subscribe to updates to a given row and receive emails
 * of those updates. Used in conjunction with the cron notification plug-in
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();


//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-form.php');

class FabrikModelfabriknotification extends FabrikModelFormPlugin {

	var $_counter = null;


	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * inject custom html into the bottom of the form
	 * @param int plugin counter
	 * @return string html
	 */

	function getBottomContent_result($c)
	{
		return $this->html;
	}

	/**
	 * set up the html to be injected into the bottom of the form
	 *
	 * @param object $params (no repeat counter stuff needed here as the plugin manager
	 * which calls this function has already done the work for you
	 */

	function getBottomContent(&$params, &$formModel)
	{
		$user =& JFactory::getUser();
		if ($user->get('id') == 0) {
			$this->html = JText::_('PLG_CRON_NOTIFICATION_SIGN_IN_TO_RECEIVE_NOTIFICATIONS');
			return;
		}
		$opts = new stdClass();
		$opts->tableid = $formModel->getTableModel()->_id;
		$opts->fabrik = $formModel->_id;
		$opts->rowid = $formModel->_rowId;
		$opts->senderBlock = JRequest::getCmd('view') == 'form' ? 'form_' : 'details_';
		$opts->senderBlock .= $formModel->_id;
		$opts = json_encode($opts);
		$id = uniqid('fabrik_notification');
		if ($params->get('notification_ajax', 0) == 1) {
			FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/form/fabriknotification/', true);;
			$script = "window.addEvent('domready', function() {
				var notify = new Notify('$id', $opts);
 			});";
			FabrikHelperHTML::addScriptDeclaration($script);
		}
		//see if the checkbox should be checked
		$db =& JFactory::getDBO();
		$ref =$this->getRef($formModel->getTableModel()->_id);
		$db->setQuery("SELECT COUNT(id) FROM #__fabrik_notification WHERE user_id = ".(int)$user->get('id') . " AND reference = $ref");
		$found = $db->loadResult();
		$checked = $found ? "checked=\"checked\"" : "";
		$this->html = "
		<label><input id=\"$id\" $checked type=\"checkbox\" name=\"fabrik_notification\" class=\"input\" value=\"1\"  />
		 ".JText::_('NOTIFY_ME') . "</label>";
	}

	public function toggleNotification()
	{
		//$$$ rob yes this looks odd but its right - as the js mouseup event is fired before the checkbox checked value changes
		$notify = JRequest::getVar('notify') == 'true' ? false : true;
		$this->process($notify, 'observer');
	}

	protected function getRef($tableid = 0)
	{
		$db =& Jfactory::getDBO();
		return $db->Quote(JRequest::getInt('tableid', $tableid).'.'.JRequest::getInt('fabrik', 0 ).'.'.JRequest::getInt('rowid', 0));;
	}

	protected function process($add = true, $why = 'author', $label)
	{

		$db =& Jfactory::getDBO();
		$user =& JFactory::getUser();
		$userid = (int)$user->get('id');

		$ref = $this->getRef();

		if ($add) {
			echo JText::_('PLG_CRON_NOTIFICATION_ADDED');
			$why = $db->Quote($why);
			$label = $db->Quote($label);
			$db->setQuery("INSERT INTO #__fabrik_notification (`reference`, `user_id`, `reason`, `label`) VALUES ( $ref, $userid, $why, $label) ON DUPLICATE KEY update reason = $why");
			$db->query();

		} else {
			echo JText::_('PLG_CRON_NOTIFICATION_REMOVED');
			$db->setQuery("DELETE FROM #__fabrik_notification WHERE reference = $ref AND user_id = $userid");
			$db->query();

		}
	}

	function onAfterProcess($params, &$formModel)
	{
		if ($params->get('notification_ajax', 0) == 1) {
			return;
		}
		$user =& JFactory::getUser();
		$userid = $user->get('id');
		$notify = JRequest::getInt('fabrik_notification', 0);
		if ($userid == 0) {
			return;
		}
		$label= '';
		foreach ($formModel->_data as $k => $v) {
			if (strstr($k, 'title')) {
				$label = $v;
				break;
			}
		}
		$why = JRequest::getInt('rowid') == 0 ? 'author' : 'editor';
		$this->process($notify, $why, $label);

		// add entry indicating the form has been updated this record will then be used by the cron plugin to
		// see which new events have been generated and notify subscribers of said events.
		$db =& Jfactory::getDBO();
		$event = JRequest::getInt('rowid') == 0 ? $db->Quote(JText::_('RECORD_ADDED')) : $db->Quote(JText::_('RECORD_UPDATED'));
		$date = JFactory::getDate();
		$date = $db->Quote($date->toMySQL());
		$ref = $this->getRef();
		$msg = $notify ? JText::_('PLG_CRON_NOTIFICATION_ADDED') : JText::_('PLG_CRON_NOTIFICATION_REMOVED');
		$app =& JFactory::getApplication();
		$app->enqueueMessage($msg);
		$db->setQuery("INSERT INTO #__fabrik_notification_event (`reference`, `event`, `user_id`, `date_time`) VALUES ($ref, $event, $userid, $date)");
		$db->query();
	}

}
?>