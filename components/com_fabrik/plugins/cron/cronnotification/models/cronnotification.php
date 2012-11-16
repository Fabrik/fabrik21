<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin.php');

class fabrikModelCronnotification extends fabrikModelPlugin {


	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
		$lang =& JFactory::getLanguage();
		$langfile = 'com_fabrik.plg.cron.cronnotification';
		$lang->load($langfile);
	}

	function process(&$data, &$tableModel)
	{
		$db =& JFactory::getDBO();

		$sql = "SELECT n.*, e.event AS event, e.id AS event_id,
		n.user_id AS observer_id, observer_user.name AS observer_name, observer_user.email AS observer_email,
		e.user_id AS creator_id, creator_user.name AS creator_name, creator_user.email AS creator_email
		 FROM #__fabrik_notification AS n".
		"\n LEFT JOIN #__fabrik_notification_event AS e ON e.reference = n.reference".
		"\n LEFT JOIN #__fabrik_notification_event_sent AS s ON s.notification_event_id = e.id".
		"\n INNER JOIN #__users AS observer_user ON observer_user.id = n.user_id".
		"\n INNER JOIN #__users AS creator_user ON creator_user.id = e.user_id".
		"\n WHERE (s.sent <> 1 OR s.sent IS NULL)".
"\n AND  n.user_id <> e.user_id".
		"\n ORDER BY n.reference"; //don't bother informing users about events that they've created themselves

		$db->setQuery($sql);
		echo $db->getQuery();
		$rows = $db->loadObjectList();
		$config =& JFactory::getConfig();
		$email_from = $config->getValue('mailfrom');
		$sitename = $config->getValue('sitename');
		$sent = array();
		$usermsgs = array();
		foreach ($rows as $row) {
			/*
			 * {observer_name, creator_name, event, record url
			 * dear %s, %s has %s on %s
			 */
			$event = JText::_($row->event);
			list($tableid, $formid, $rowid) = explode('.', $row->reference);

			$url = COM_FABRIK_LIVESITE.JRoute::_('index.php?option=com_fabrik&view=details&tableid='.$tableid.'&fabrik='.$formid.'&rowid='.$rowid);
			$msg = JText::sprintf('FABRIK_NOTIFICATION_EMAIL_PART', $row->creator_name, $url, $event . ': ' . @$row->label);
			if (!array_key_exists($row->observer_id, $usermsgs)) {
				$usermsgs[$row->observer_email] = array();
			}
			$usermsgs[$row->observer_email][] = $msg;

			$sent[] = 'INSERT INTO #__fabrik_notification_event_sent (`notification_event_id`, `user_id`, `sent`) VALUES ('.$row->event_id.', '.$row->observer_id.', 1)';
		}
		$subject = $sitename.": " .JText::_('FABRIK_NOTIFICATION_EMAIL_SUBJECT');
		foreach ($usermsgs as $email => $messages) {
			$msg = implode(' ', $messages);
			$msg .= "<p><a href=\"".COM_FABRIK_LIVESITE."index.php?option=com_fabrik&controller=cron.cronnotification\">" . JText::_('FABRIK_NOTIFICATION_MANAGE_NOTIFICATIONS') . "</a></p>";
			$res = JUtility::sendMail($email_from, $email_from, $email, $subject, $msg, true);
		}
		if (!empty($sent)) {
			foreach ($sent as $s) {
				$db->setQuery($s);
				$db->query();
			}
		}
		$this->usermsgs = $usermsgs;
		return count($sent);
	}

	/**
	 * get a list of notifications that the user has signed up to
	 * accessed via the url http://site.com/index.php?option=com_fabrik&controller=cron.cronnotification
	 */

	function getUserNotifications()
	{
		$user =& JFactory::getUser();
		$db =& JFactory::getDBO();


		$sql = "SELECT * FROM #__fabrik_notification WHERE user_id = " . $user->get('id');
		$db->setQuery($sql);
		$rows = $db->loadObjectList();
		$tableModel =& JModel::getInstance('Table', 'FabrikModel');
		foreach ($rows as &$row) {
			/*
			 * {observer_name, creator_name, event, record url
			 * dear %s, %s has %s on %s
			 */
			$event = JText::_($row->event);
			list($tableid, $formid, $rowid) = explode('.', $row->reference);
			$tableModel->setId($tableid);
			$data = $tableModel->getRow($rowid);
			$row->url = JRoute::_('index.php?option=com_fabrik&view=details&tableid='.$tableid.'&fabrik='.$formid.'&rowid='.$rowid);
			$row->title = $row->url;
			if (is_object($data)) {
				foreach ($data as $key => $value) {
					$k = strtolower(array_pop(explode('___', $key)));
					if (strstr($k, 'title')) {
						$row->title = $value;
					}
				}
			}
		}
		return $rows;
	}

	function delete()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$ids = JRequest::getVar('cid', array());
		JArrayHelper::toInteger($ids);
		$db =& JFactory::getDBO();
		$db->setQuery("DELETE FROM #__fabrik_notification WHERE id IN (".implode(',', $ids).")");
		$db->query();
	}

	function getLog()
	{
		return json_encode($this->usermsgs);
	}

}

?>