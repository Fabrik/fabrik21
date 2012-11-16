<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.helper');
jimport('joomla.filesystem.file');

$defines = JFile::exists(JPATH_COMPONENT.DS.'user_defines.php') ? JPATH_COMPONENT.DS.'user_defines.php' : JPATH_COMPONENT.DS.'defines.php';
require_once($defines);

/** php 4.? compat */

if (version_compare( phpversion(), '5.0.0', '<')) {
    echo 'Sorry you are using ' .  phpversion() . ". You need to have PHP5 installed to run Fabrik\n";
    return;
}

//test for YQL & XML document type
// use the format request value to check for document type
// $$$ hugh - comment this out for now, as we're not shipping the class files in the ZIP
/*
$docs = array("yql", "xml");
foreach ($docs as $d) {
	if (JRequest::getCmd("type") == $d) {
	  // get the class
	  require_once(JPATH_SITE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'classes'.DS.$d.'document.php');
	  // replace the document
	  $document =& JFactory::getDocument();
	  $docClass = 'JDocument'.strtoupper($d);
	  $document = new $docClass();
	}
}
*/

require_once(JPATH_COMPONENT.DS.'controller.php');
require_once(JPATH_COMPONENT.DS.'controllers'.DS.'visualization.php');
require_once(JPATH_COMPONENT.DS.'models'.DS.'parent.php');
require_once(JPATH_COMPONENT.DS.'helpers'.DS.'parent.php');
require_once(JPATH_COMPONENT.DS.'helpers'.DS.'json.php');

JModel::addIncludePath(JPATH_COMPONENT.DS.'models');
//$$$ rob if you want to you can override any fabrik model by copying it from
// models/ to models/adaptors the copied file will overwrite (NOT extend) the original
JModel::addIncludePath(JPATH_COMPONENT.DS.'models'.DS.'adaptors');

$controllerName = JRequest::getCmd('controller');
//check for a plugin controller

//call a plugin controller via the url :
// &c=visualization.calendar

$isplugin = false;

if (JString::strpos($controllerName, '.') != false)
{
	list($type, $name) = explode('.', $controllerName);
	$path = JPATH_COMPONENT.DS.'plugins'.DS.$type.DS.$name.DS.'controllers'.DS.$name.'.php';
	if (file_exists($path)) {
		require_once $path;
		$isplugin = true;
		$controller = $type.$name;
	} else {
		$controller = '';
	}

} else {
	// its not a plugin
	// map controller to view - load if exists

	//$$$ROB was a simple $controller = view, which was giving an error when trying to save a popup
	//form to the calendar viz
	//May simply be the best idea to remove main contoller and have different controllers for each view

	//hack for package
	if (JRequest::getWord( 'view') == 'package' || JRequest::getWord( 'view') == 'table') {
		$controller = JRequest::getWord('view');
	}
	else if (JRequest::getWord( 'view') == 'form' || JRequest::getWord( 'view') == 'details') {
		$controller = 'form';
	} else {
		$controller = $controllerName;
	}

	//if($controller != JRequest::getWord('view')) {
		$path = JPATH_COMPONENT.DS.'controllers'.DS.$controller.'.php';
		if (file_exists($path)) {
			require_once $path;
		} else {
			$controller = '';
		}
	//}
}
// Create the controller
$classname	= 'FabrikController'.ucfirst($controller);
$task = JRequest::getVar('task', null, 'default', 'cmd');

$controller = new $classname();

if ($isplugin) {
	//ack for some reason the table view <param>'s aren't inside <params> so they get appended to the url
	//whilst the viz view xml file has to have <params><param> so this kludge takes the viz resetfilter menu item option
	// and adds it to the request.
	$menus	= &JSite::getMenu();
	// $$$ hugh - this is breaking when popping up add event form in calendar viz, no menu id is set?  At least on my
	// test setup it isn't, accessing cal viz thru 'main' menu item.
	//JRequest::setVar('resetfilters', JRequest::getVar('resetfilters', $menus->getParams($menus->getActive()->id)->get('resetfilters')));
	$active_menu = $menus->getActive();
	if (isset($active_menu)) {
		$menu_id = $active_menu->id;
		JRequest::setVar('resetfilters', JRequest::getVar('resetfilters', $menus->getParams($menu_id)->get('resetfilters')));
	}
	//add in plugin view
	$controller->addViewPath(JPATH_COMPONENT.DS.'plugins'.DS.$type.DS.$name.DS.'views');
	//add the model path
	$modelpaths = JModel::addIncludePath(JPATH_COMPONENT.DS.'plugins'.DS.$type.DS.$name.DS.'models');
}
//echo "$classname $task";exit;
// Perform the Request task
$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();

?>