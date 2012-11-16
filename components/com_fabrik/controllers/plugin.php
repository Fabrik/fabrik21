<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');
//just until joomla uses mootools 1.2
jimport('joomla.html.editor');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'editor.php');
//end mootools 1.2

/**
 * Fabrik Plugin Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */
class FabrikControllerPlugin extends JController
{

	/* @var int  id used from content plugin when caching turned on to ensure correct element rendered)*/
	var $cacheId = 0;

	/**
	 * ajax action called from element
	 */

	function pluginAjax()
	{
		$plugin = JRequest::getVar('plugin', '');
		$method = JRequest::getVar('method', '');
		$id = JRequest::getInt('element_id', 0);
		$group = JRequest::getVar('g', 'element');
		$pluginManager =& $this->getModel('pluginmanager');
		$model =& $pluginManager->getPlugIn($plugin, $group);
		if (!is_object($model)) {
			// $model is an error message string alert();
			$this->pluginAjaxComplete($model);
			return;
		}
		$model->setId($id);

		if (method_exists($model, $method)) {
			if ($group == 'cron') {
				JRequest::setVar('cid', array($id)); //doesnt seem to work if you are calling this from a proper cron job
				$msg = $this->doCron( $pluginManager);
			} else {
				$msg = $model->$method();
			}
			// $$$ rob plugins prob echo their results inside - at some point get them
			// to return so we can make use of pluginAjaxComplete for non ajax calls
			$this->pluginAjaxComplete($msg);
		} else {
			$this->pluginAjaxComplete("alert('method doesnt exist');\n");
		}
	}

	protected function pluginAjaxComplete($msg)
	{
		// $$$ rob for usability ajax calls triggered via links should have the same url assigned to their href
		// property along with nonajax=1 this tells fabrik that its not an ajax call and to redirect
		// the browser to the http referrer.
		if (JRequest::getInt('nonajax') == 1)
		{
			$app =& JFactory::getApplication();
			$reffer = JRequest::getVar('HTTP_REFERER', 'index.php', 'server');
			$app->redirect($reffer, $msg);
		} else {
			echo $msg;
		}
	}

	/**
	 * custom user ajax class handling as per F1.0.x
	 * @return unknown_type
	 */
	function userAjax()
	{
		$db =& JFactory::getDBO();
		require_once(COM_FABRIK_FRONTEND . DS. "user_ajax.php");
		$method = JRequest::getVar('method', '');
		$userAjax = new userAjax ( $db);
		if (method_exists($userAjax, $method)) {
			$userAjax->$method();
		}
	}

	function doCron(&$pluginManager)
	{
		$db =& JFactory::getDBO();
		$cid = JRequest::getVar('element_id', array(), 'method', 'array');
		JArrayHelper::toInteger($cid);
		if (empty($cid)) {
			return;
		}
		$query = "SELECT id, plugin FROM #__fabrik_cron";
		if (!empty($cid)) {
			$query .= " WHERE id IN (" . implode(',', $cid).")";
		}
		$db->setQuery($query);
		$rows = $db->loadObjectList();
		$tableModel =& JModel::getInstance('table', 'FabrikModel');
		$c = 0;
		foreach ($rows as $row) {
			//load in the plugin
			$plugin =& $pluginManager->getPlugIn($row->plugin, 'cron');
			$plugin->setId($row->id);
			$params =& $plugin->getParams();

			$thisTableModel = clone($tableModel);
			$thisTableModel->setId($params->get('table'));
			$table =& $tableModel->getTable();
			// $$$ hugh @TODO - really think we need to add two more options to the cron plugins
			// 1) "Load rows?" because it really may not be practical to load ALL rows into $data
			// on large tables, and the plugin itself may not need all data.
			// 2) "Bypass prefilters" - I think we need a way of bypassing pre-filters for cron
			// jobs, as they are run with access of whoever happened to hit the page at the time
			// the cron was due to run, so it's pot luck as to what pre-filters get applied.
			$total 						= $thisTableModel->getTotalRecords();
			$nav =& $thisTableModel->getPagination($total, 0, $total);
			$data  = $thisTableModel->getData();
			// $$$ hugh - added table model param, in case plugin wants to do further table processing
			$c = $c + $plugin->process($data, $thisTableModel);
		}
		$query = "UPDATE #__fabrik_cron set lastrun = NOW() where id IN (" . implode(',', $cid).")";
		$db->setQuery($query);
		$db->query();
	}

}
?>