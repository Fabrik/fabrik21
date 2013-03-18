<?php

/**
* Determines if a row is editable
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-table.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');

class FabrikModelLockrow extends FabrikModelTablePlugin {

	var $_counter = null;

	function canSelectRows()
	{
		return false;
	}

	function onCanEdit($params, $tableModel, $row)
	{
	// If $row is null, we were called from the table's canEdit() in a per-table rather than per-row context,
		// and we don't have an opinion on per-table edit permissions, so just return true.
		if (is_null($row) || is_null($row[0]))
		{
			return true;
		}
		$data = array();
		if (!is_array($row[0]))
		{
			$data = JArrayHelper::fromObject($row[0]);
		}
		else
		{
			$data = $row[0];
		}

		// @TODO - should probably cache this in a static so we don't need to search pn every row
		$groupModels =& $tableModel->getFormGroupElementData();
		static $lockElementModel = null;
		static $lockElementName = null;
		static $hasLock = null;
		if ($hasLock === null) {
			foreach ($groupModels as $groupModel) {
				// not going to mess with having lockrow elements in joins for now
				if ($groupModel->isJoin())
				{
					continue;
				}
				$elementModels =& $groupModel->getPublishedElements();
				foreach ($elementModels as $elementModel) {
					if ($elementModel->_pluginName === 'lockrow')
					{
						// found one, only support one per table, so stash it and bail
						$lockElementModel = $elementModel;
						$lockElementName = $elementModel->getFullName(false, true, false, true);
						$hasLock = true;
						break 2;
					}
				}
			}
			if ($hasLock !== true)
			{
				$hasLock = false;
			}
		}
		if ($hasLock)
		{
			$value = JArrayHelper::getValue($data, $lockElementName . '_raw', '0');
			return $lockElementModel->isLocked($value) === false;
		}
		return true;
	}
}