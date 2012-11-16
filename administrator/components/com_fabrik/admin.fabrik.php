<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');
$version = new JVersion();
$sversion = $version->getShortVersion();
if (version_compare( phpversion(), '5.0.0', '<')) {
    echo 'Sorry you are using ' .  phpversion() . ". You need to have PHP5 installed to run Fabrik\n";
    return;
}

if (version_compare( phpversion(), '5.3', '>=') && ($version->RELEASE <= 1.5 && $version->DEV_LEVEL <= 14)) {
	JError::raiseNotice(500, 'You are using PHP ' .  phpversion() . ". but Joomla $sversion does not fully suport this!");
}

if (ini_get('magic_quotes_sybase') == 1) {
	echo "You have the PHP directive magic_quotes_sybase turned ON Fabrik requires you to turn this directive off, either by editing your php.ini file or adding:<p> php_value magic_quotes_sybase             0</p> to your .htaccess file";
	return;
}

if (in_array('suhosin', get_loaded_extensions())) {
	JError::raiseWarning(500, JText::_('Looks like your server has suhosin installed - this may cause issues when submitting large forms, or forms with long element names'));
}

// Set the table directory
JTable::addIncludePath( JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables');

$controllerName = JRequest::getCmd('c', 'home');

jimport('joomla.filesystem.file');
$defines = JFile::exists(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php') ? JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php' : JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'defines.php';
require_once($defines);


//just until joomla uses mootools 1.2
jimport('joomla.html.editor');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'editor.php');
//end mootools 1.2

//add the helpers directory

require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php');

jimport('joomla.application.component.model');
jimport('joomla.application.component.controller');
JModel::addIncludePath( COM_FABRIK_FRONTEND.DS.'models');
//$$$ rob if you want to you can override any fabrik model by copying it from
// models/ to models/adaptors the copied file will overwrite (NOT extend) the original
JModel::addIncludePath( JPATH_COMPONENT.DS.'models'.DS.'adaptors');

//load front end language file as well
$lang =& JFactory::getLanguage();
$lang->load('com_fabrik', COM_FABRIK_BASE);


$task = JRequest::getCmd('task');
//echo $task;

require_once(JPATH_COMPONENT.DS.'controllers'.DS.$controllerName.'.php');
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'parent.php');


$config = array();
if ($controllerName == 'table' || $controllerName == 'form') {
	$config['view_path'] =  COM_FABRIK_FRONTEND . DS . 'views';
}

$controllerName = 'FabrikController'.$controllerName;

$fbConfig =& JComponentHelper::getParams('com_fabrik');
if (!$fbConfig->get('use_wip') && $controllerName === 'FabrikControllerpackage') {
  $app =& JFactory::getApplication();
  $app->enqueueMessage( JText::_('PACKAGE_WIP'), 'notice');
  return;
}

//set big selects see http://fabrikar.com/forums/showthread.php?t=21779
$db = JFactory::getDBO();
$db->setQuery("SET OPTION SQL_BIG_SELECTS=1");
$db->query();

JHTML::stylesheet('headings.css', 'administrator/components/com_fabrik/');
// Create the controller
$controller = new $controllerName($config);

// Perform the Request task
$controller->execute($task);

// Redirect if set by the controller
$controller->redirect();

?>