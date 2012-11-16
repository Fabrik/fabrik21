<?php

/**
 * Alter the table's SQL select query from "SELECT...." "SELECT SQL_CACHE....."
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

class FabrikModelSql_cache extends FabrikModelTablePlugin {

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

	public function onQueryBuilt(&$params, &$oRequest, &$args)
	{
		// ensure we only modify the query once
		if (!strstr($args[0]->query['select'], 'SELECT SQL_CACHE ')) {
			$args[0]->query['select'] = str_replace('SELECT', 'SELECT SQL_CACHE ', $args[0]->query['select']);
		}
	}

}
?>