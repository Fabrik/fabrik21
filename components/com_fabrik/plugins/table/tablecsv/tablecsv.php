<?php

/**
 * Allow processing of CSV import / export on a per row basis
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

class FabrikModelTablecsv extends FabrikModelTablePlugin {

	var $_counter = null;

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bol
	 */

	function canSelectRows()
	{
		return false;
	}

	public function onImportCSVRow(&$params, &$tableModel)
	{
		$file = JFilterInput::clean($params->get('tablecsv_import_php_file'), 'CMD');
		if ($file == -1 || $file == '') {
			$code = @eval($params->get('tablecsv_import_php_code'));
			FabrikWorker::logEval($code, 'Caught exception on eval in onImportCSVRow : %s');
		} else {
			@require(COM_FABRIK_FRONTEND.DS.'plugins'.DS.'table'.DS.'tablecsv'.DS.'scripts'.DS.$file);
		}
		return true;
	}

}
?>