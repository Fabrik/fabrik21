<?php
/**
 * @version
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.filesystem.file');
$defines = JFile::exists(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php') ? JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php' : JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'defines.php';
require_once($defines);
jimport('joomla.application.component.model');
jimport('joomla.application.component.helper');
JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'models');

$app =& JFactory::getApplication();
//load front end language file as well
$lang =& JFactory::getLanguage();
$lang->load('com_fabrik');

require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'parent.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php');

require_once(COM_FABRIK_FRONTEND.DS.'controller.php');
require_once(COM_FABRIK_FRONTEND.DS.'controllers'.DS.'table.php');

//$$$rob looks like including the view does something to the layout variable
$origLayout = JRequest::getVar('layout');
require_once(COM_FABRIK_FRONTEND.DS.'views'.DS.'table'.DS.'view.html.php');
JRequest::setVar('layout', $origLayout);

require_once(COM_FABRIK_FRONTEND.DS.'views'.DS.'package'.DS.'view.html.php');


JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'models');
JTable::addIncludePath( COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'tables');
$document =& JFactory::getDocument();

require_once(COM_FABRIK_FRONTEND.DS.'controllers'.DS.'package.php');
require_once(COM_FABRIK_FRONTEND.DS.'views'.DS.'form'.DS.'view.html.php');

$tableId			= intval($params->get('table_id', 1));
$useajax			= intval($params->get('useajax', 0));
$random 			= intval($params->get('radomizerecords', 0));
$limit				= intval($params->get('limit', 0));
$layout				=  $params->get('fabriklayout', 'default');

$incfilters = $params->get('incfilters', 1);
$origIncFilters = JRequest::getVar('incfilters');
JRequest::setVar('incfilters', $incfilters);

JRequest::setVar('layout', $layout);

if ($limit !== 0) {
	$app->setUserState('com_fabrik.table'.$tableId.'.list.limitlength'.$tableId, $limit);
	JRequest::setVar('limit', $limit);
}

/*this all works fine for a table
 * going to try to load a package so u can access the form and table
 */
$moduleclass_sfx = $params->get('moduleclass_sfx', '');
if (!$useajax) {
	$tableId = intval($params->get('table_id', 1));

	$viewName = 'table';
	$viewType	= $document->getType();
	$controller = new FabrikControllerTable();

	// Set the default view name from the Request
	$view = clone($controller->getView($viewName, $viewType));

	// Push a model into the view
	$model	= &$controller->getModel($viewName);
	$model->setId($tableId);
	$model->_randomRecords = $random;
	if ($limit !== 0) {
		$model->getTable()->rows_per_page = $limit;
	}

	// $$$ hugh - added this (copied from the plugin code) as I don't see how else we'd be
	// handling row deletion in the table module??
	// Also added case for 'filter' task.
	// Haven't tested to see what happens if we've got multiple tables!!
	// Hey Rob - I don't know if I'm on the wrong track here, but it seemed like we simply
	// weren't handling 'task' anywhere for the table module?
	$task = JRequest::getVar('task');
	if ($task == 'delete') {
		if (method_exists($controller, $task)) {
			//enable delete() of rows
			$controller->$task();
		}
	}
	else if ($task == 'filter') {
		$request = $model->getRequestData();
		$model->storeRequestData($request);
	}

	//$model->_postMethod = 'ajax';
	if (!JError::isError($model)) {
		$view->setModel($model, true);
	}
	$view->_isMambot = true;
	// Display the view
	$view->assign('error', $controller->getError());
	$view->setId($tableId);

	$post = JRequest::get('post');
	//build unique cache id on url, post and user id
	$user =& JFactory::getUser();
	$cacheid = serialize(array(JRequest::getURI(), $post, $user->get('id'), get_class($view), 'display'));
	$cache =& JFactory::getCache('com_fabrik', 'view');

	ob_start();
	$cache->get($view, 'display', $cacheid);
	$contents = ob_get_contents();
	ob_end_clean();
	echo $contents;

} else {

	$document =& JFactory::getDocument();

	$viewName	= 'Package';

	$viewType	= $document->getType();

	$controller = new FabrikControllerPackage();

	// Set the default view name from the Request
	$view = &$controller->getView($viewName, $viewType);

	// $$$ rob used so we can test if form is in package when determining its action url
	$view->_id = -1;

	//if the view is a package create and assign the table and form views
	$tableView = &$controller->getView('Table', $viewType);
	$tableModel =& $controller->getModel('Table');

	$tableModel->_randomRecords = $random;
	$tableView->setModel($tableModel, true);
	$view->_tableView =& $tableView;

	$view->_formView = &$controller->getView('Form', $viewType);
	$formModel =& $controller->getModel('Form');

	$view->_formView->setModel($formModel, true);

	// Push a model into the view
	$model	= &$controller->getModel($viewName);
	$package =& $model->getPackage();
	$package->tables = $tableId;
	$package->template = 'module';

	if (!JError::isError($model)) {
		$view->setModel($model, true);
	}
	$view->_isMambot = true;
	// Display the view
	$view->assign('error', $this->getError());

	//force the module layout for the package

	//push some data into the model
	$divid = "fabrikModule_table_$tableId";
	echo "<div id=\"$divid\">";
	echo $view->display();
	echo "</div>";

	FabrikHelperHTML::script('tablemodule.js', 'modules/mod_fabrik_table/', true);
	$fbConfig =& JComponentHelper::getParams( 'com_fabrik');
	$opts = new stdClass();
	$opts->mooversion = ($fbConfig->get('usefabrik_mootools', false )) ? 1.2 : 1.1;
	$opts->tableid = $tableId;
	$opts = FastJSON::encode($opts);
	$script  = "var oFabrikTableModule = new fabrikTableModule('$divid', $opts);\n";
	$script .= "oPackage.addBlock('$divid', oFabrikTableModule);\n";
	$document->addScriptDeclaration($script);
}
JRequest::setVar('layout', $origLayout);
JRequest::setVar('incfilters', $origIncFilters);
?>