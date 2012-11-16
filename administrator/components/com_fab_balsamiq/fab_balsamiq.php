<?php
/**
* @package Joomla
* @subpackage Balsamiq 2 Fabrik
* @copyright Copyright (C) 2011 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die;

// Include dependancies
jimport('joomla.application.component.controller');
jimport('joomla.filesystem.file');

// Require the base controller
require_once JPATH_COMPONENT.DS.'controller.php';

// Create the controller
$controller = new Fab_balsamiqController();

// we need Fabrik installed to get this baby going
if (!JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'admin.fabrik.php')) {
	JRequest::setVar('task', 'getfabrik');
}

// Execute the task.
$controller->execute(JRequest::getCmd('task'));

$controller->redirect();

?>