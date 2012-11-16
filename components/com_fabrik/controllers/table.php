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
 * Fabrik Table Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */

class FabrikControllerTable extends JController
{

	/* @var int  id used from content plugin when caching turned on to ensure correct element rendered)*/
	var $cacheId = 0;

	/**
	 * Display the view
	 */

	function display($model = null)
	{

		//menu links use fabriklayout parameters rather than layout
		$flayout = JRequest::getVar('fabriklayout');
		if ($flayout != '') {
			JRequest::setVar('layout', $flayout);
		}

		$document =& JFactory::getDocument();

		$viewName	= JRequest::getVar('view', 'table', 'default', 'cmd');
		$modelName = $viewName;
		$layout		= JRequest::getWord('layout', 'default');

		$viewType	= $document->getType();

		// Set the default view name from the Request
		$view = &$this->getView($viewName, $viewType);
		$view->setLayout($layout);
		// Push a model into the view
		if (is_null($model)) {
			$model = &$this->getModel($modelName);
		}
		if (!JError::isError($model) && is_object($model)) {
			$view->setModel($model, true);
		}

		// Display the view
		$view->assign('error', $this->getError());

		$post = JRequest::get('post');
		if ($model->getParams()->get('list_disable_caching', '0') !== '1') {
			//build unique cache id on url, post and user id
			$user =& JFactory::getUser();
			$cacheid = serialize(array(JRequest::getURI(), $post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache =& JFactory::getCache('com_fabrik', 'view');
			$cache->get($view, 'display', $cacheid);
		}
		else {
			$view->display();
		}
	}

	/**
	 * reorder the data in the table
	 * @return null
	 */

	function order()
	{
		$modelName = JRequest::getVar('view', 'table', 'default', 'cmd');
		$model = &$this->getModel($modelName);
		$model->setId(JRequest::getInt('tableid'));
		$model->setOrderByAndDir();
		// $$$ hugh - unset 'resetfilters' in case it was set on QS of original table load.
		JRequest::setVar('resetfilters', 0);
		JRequest::setVar('clearfilters', 0);
		// @TODO do we run this through the cache?
		$this->display();
	}

	/**
	 * filter the table data
	 * @return null
	 */

	function filter()
	{
		$modelName	= JRequest::getVar('view', 'table', 'default', 'cmd');
		$model	= &$this->getModel($modelName);
		$model->setId(JRequest::getInt('tableid'));
		FabrikHelperHTML::debug('', 'table model: getRequestData');
		$request =& $model->getRequestData();
		$model->storeRequestData($request);
		// $$$ rob pass in the model otherwise display() rebuilds it and the request data is rebuilt
		return $this->display($model);
	}

	/**
	 * delete rows from table
	 */

	function delete()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$app =& JFactory::getApplication();
		$model =& $this->getModel('table');
		$ids = JRequest::getVar('ids', array(), 'request', 'array');

		$tableid = JRequest::getInt('tableid');
		$limitstart = JRequest::getInt('limitstart'. $tableid);
		$length = JRequest::getInt('limit' . $tableid);

		$oldtotal = $model->getTotalRecords();
		$model->deleteRows($ids);

		$total = $oldtotal - count($ids);

		$ref = JRequest::getVar('fabrik_referrer', "index.php?option=com_fabrik&view=table&tableid=$tableid", 'post');
		// $$$ hugh - for some reason fabrik_referrer is sometimes empty, so a little defensive coding ...
		if (empty($ref)) {
			$ref = JRequest::getVar('HTTP_REFERER', "index.php?option=com_fabrik&view=table&tableid=$tableid", 'server');
		}
		if ($total <= $limitstart) {
			$newlimitstart = $limitstart - $length;
			if ($newlimitstart < 0) {
				$newlimitstart = 0;
			}
			$ref = str_replace("limitstart$tableid=$limitstart", "limitstart$tableid=$newlimitstart", $ref);
			$context = 'com_fabrik.list'.$tableid.'.';
			$app->setUserState($context.'limitstart', $newlimitstart);
		}
		if (JRequest::getVar('format') == 'raw') {
			JRequest::setVar('view', 'table');
			$this->display();
		} else {
			//@TODO: test this
			$app->redirect($ref, count($ids) . " " . JText::_('RECORDS DELETED'));
		}
	}

	/**
	 * empty a table of records and reset its key to 0
	 */

	function doempty()
	{
		$model = &$this->getModel('table');
		$model->truncate();
		$this->display();
	}

	/**
	 * run a table plugin
	 */

	function doPlugin()
	{
		$cid = JRequest::getVar('cid', array(0), 'method', 'array');
		if (is_array($cid)) {$cid = $cid[0];}
		$model = &$this->getModel('table');
		$model->setId(JRequest::getInt('tableid', $cid));
		// $$$ rob need to ask the model to get its data here as if the plugin calls $model->getData
		// then the other plugins are recalled which makes the current plugins params incorrect.
		$model->setLimits();
		$model->getData();
		//if showing n tables in article page then ensure that only activated table runs its plugin
		if (JRequest::getInt('id') == $model->_id || JRequest::getVar('origid', '') == '') {
			$msgs = $model->processPlugin();
			if (JRequest::getVar('format') == 'raw') {
				JRequest::setVar('view', 'table');
			} else {
				$app =& JFactory::getApplication();
				foreach ($msgs as $msg) {
					$app->enqueueMessage($msg);
				}
			}
		}
		return $this->display();
	}

	/**
	 * called via ajax when element selected in advanced search popup window
	 */

	function elementFilter()
	{
		$id = JRequest::getInt('id');
		$model = &$this->getModel('table');
		$model->setId($id);
		echo $model->getAdvancedElementFilter();
	}

}
?>