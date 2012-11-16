<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Pollen 8 Design Ltd. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewImport extends JView
{

	function display($tpl = null)
	{
		$this->tableid = JRequest::getVar('tableid', 0);
		$tableModel =& JModel::getInstance('Table', 'FabrikModel');
		$tableModel->setId($this->tableid);
		$this->table = $tableModel->getTable();
		if (!$tableModel->canCSVImport()) {
			JError::raiseError(400, 'Naughty naughty!');
			jexit;
		}
		parent::display();
	}
}
?>