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


//load front end language file as well
$lang =& JFactory::getLanguage();
$lang->load('com_fabrik');

require_once( COM_FABRIK_FRONTEND.DS.'models'.DS.'parent.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php' );

require_once(COM_FABRIK_FRONTEND.DS.'controllers'.DS.'form.php');

//$$$rob looks like including the view does something to the layout variable
$origLayout = JRequest::getVar('layout');
require_once(COM_FABRIK_FRONTEND.DS.'views'.DS.'form'.DS.'view.html.php');
JRequest::setVar('layout', $origLayout);

require_once(COM_FABRIK_FRONTEND.DS.'views'.DS.'package'.DS.'view.html.php');
require_once(COM_FABRIK_FRONTEND.DS.'views'.DS.'table'.DS.'view.html.php');

JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables');
JModel::addIncludePath(COM_FABRIK_FRONTEND.DS.'models');

$formId				= intval($params->get('form_id', 1));
$rowid				= intval($params->get('row_id', 0));
$layout			= $params->get('template', 'default');
$usersConfig 	=& JComponentHelper::getParams('com_fabrik');
$usersConfig->set('rowid', $rowid);

$usekey = $params->get('usekey', '');
if (!empty($usekey)) {
	JRequest::setVar('usekey', $usekey);
}

$moduleclass_sfx 	= $params->get('moduleclass_sfx', '');

$document =& JFactory::getDocument();

$model->_isMambot = true;

$model->_postMethod = $params->get('formmodule_useajax', true) ? 'ajax' : 'post';

$origView = JRequest::getVar('view');

if ($model->_postMethod == 'post') {

	JRequest::setVar('fabrik', $formId);
	JRequest::setVar('view', 'form');
	$controller = new FabrikControllerForm();

	// $$$ hugh - fix for issue with multiple form modules, so the view code doesn't re-use
	// the first _id and render the same form over.
 	$view = $controller->getView( 'form', $document->getType() );
 	unset($view->_id);

	//$$$rob for table views in category blog layouts when no layout specified in {} the blog layout
	// was being used to render the table - which was not found which gave a 500 error
	JRequest::setVar('layout', $layout);

	// Display the view
	$controller->_isMambot = true;
	echo $controller->display();
} else {

	require_once('components'.DS.'com_fabrik'.DS.'controllers'.DS.'package.php');
	JRequest::setVar('flayout', $layout);

	$document =& JFactory::getDocument();

	$viewName	= 'Package';

	$viewType	= $document->getType();

	$controller = new FabrikControllerPackage();

	// Set the default view name from the Request
	$view = clone($controller->getView($viewName, $viewType));

	// $$$ rob used so we can test if form is in package when determining its action url
	$view->_id = -1;

	//if the view is a package create and assign the table and form views
	$view->_formView = &$controller->getView('Form', $viewType);
	$view->_formView->setId($formId);
	$formModel =& $controller->getModel('Form');
	$formModel->setId($formId);
	$view->_formView->setModel($formModel, true);
	$tableView = &$controller->getView('Table', $viewType);
	$tableModel =& $formModel->getTableModel();
	$tableView->setModel($tableModel, true);
	$view->_tableView =& $tableView;

	// Push a model into the view
	$model	= &$controller->getModel($viewName);
	$package =& $model->getPackage();
	$package->forms = $formId;
	$package->template = 'module';

	if (!JError::isError($model)) {
		$view->setModel($model, true);
	}
	$view->_isMambot = true;
	// Display the view
	$view->assign('error', $this->getError());

	//force the module layout for the package

	//push some data into the model
	$divid = "fabrikModule_form_{$formId}";
	echo "<div id=\"$divid\">";
	echo $view->display();
	echo "</div>";

}
//reset the layout and view for when the component needs them
JRequest::setVar('layout', $origLayout);
JRequest::setVar('flayout', $origLayout);
JRequest::setVar('view', $origView);
JRequest::setVar('usekey', '');
?>