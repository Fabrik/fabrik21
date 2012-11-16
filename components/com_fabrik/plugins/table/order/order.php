<?php

/**
 * Allows drag and drop reordering of rows
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


class FabrikModelOrder extends FabrikModelTablePlugin {

	var $_counter = null;

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelTablePlugin::getAclParam()
	 */

	function getAclParam()
	{
		return 'order_access';
	}

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bol
	 */

	function canSelectRows()
	{
		return false;
	}


	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		if (!$this->canUse()) {
			return;
		}
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/table/order/', false);
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @param object parameters
	 * @param object table model
	 * @param array [0] => string table's form id to contain plugin
	 * @return bool
	 */

	function loadJavascriptInstance($params, $model, $args)
	{
		if (!$this->canUse()) {
			return;
		}
		$form_id = $args[0];
		FabrikHelperHTML::script('element.js', 'media/com_fabrik/js/');
		$orderEl = $model->getForm()->getElement($params->get('order_element'), true);
		$opts = new stdClass();
		$opts->enabled = (count($model->orderEls) === 1 && FabrikString::safeColNameToArrayKey($model->orderEls[0]) == FabrikString::safeColNameToArrayKey($orderEl->getOrderByName())) ? true : false;
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts->tableid = $model->_id;
		$opts->orderElementId = $params->get('order_element');
		$opts->handle = $params->get('order_element_as_handle', 1) == 1 ? '.fabrik_row___'.$orderEl->getOrderByName() : false;
		$opts->direction = $opts->enabled ? $model->orderDirs[0] : '';
		$opts->transition = '';
		$opts->duration = '';
		$opts->constrain = '';
		$opts->clone = '';
		$opts->revert = '';

		$opts->container = 'table_'.$model->getTable()->id;

		$opts = json_encode($opts);
		$lang = $this->_getLang();
		$lang = json_encode($lang);
		$this->jsInstance = "new FbTableOrder('$form_id', $opts, $lang)";
		return true;
	}

	/**
	 * show a new for entering the form actions options
	 */

	function renderAdminSettings($elementId, &$row, &$params, $lists, $c)
	{
		$params->_counter_override = $this->_counter;
		$display =  ($this->_adminVisible) ? "display:block" : "display:none";
		$return = '<div class="page-' . $elementId . ' elementSettings" style="' . $display . '">
 		' . $params->render('params', '_default', false, $c) .
 		'</div>
 		';
		$return = str_replace("\r", "", $return);
		return $return;
	}

	/**
	 * called via ajax when dragged row is dropped. Reorders records
	 */

	public function ajaxReorder()
	{
		//get table model
		$model =& JModel::getInstance('table', 'FabrikModel');
		$model->setId(JRequest::getInt('tableid'));
		$db =& $model->getDb();
		$direction = JRequest::getVar('direction');

		$orderEl = $model->getForm()->getElement(JRequest::getInt('orderelid'), true);
		$table = $model->getTable();
		$origOrder = JRequest::getVar('origorder');
		$orderBy =$db->nameQuote($orderEl->getElement()->name);
		$order = JRequest::getVar('order');
		$dragged = JRequest::getVar('dragged');

		//are we dragging up or down?
		$origPos = array_search($dragged, $origOrder);
		$newPos = array_search($dragged, $order);
		$dragDirection = $newPos > $origPos ? 'down' : 'up';

		//get the rows whose order has been altered
		$result = array_diff_assoc($order, $origOrder);
		$result = array_flip($result);
		//remove the dragged row from the list of altered rows
		unset($result[$dragged]);

		$result = array_flip($result);

		if (empty($result)) {
			//no order change
			return;
		}
		//get the order for the last record in $result
		$splitId = $dragDirection == 'up' ? array_shift($result) : array_pop($result);
		$db->setQuery("SELECT ".$orderBy." FROM ".$table->db_table_name." WHERE ".$table->db_primary_key." = ".$splitId);
		$o = (int)$db->loadResult();


		if ($direction == 'desc') {
			$compare = $dragDirection == 'down' ? '<' : '<=';
		}else{
			$compare = $dragDirection == 'down' ? '<=' : '<';
		}
		//shift down the ordered records which have an order less than or equal the newly moved record
		$query = "UPDATE ".$table->db_table_name." SET ".$orderBy.' = COALESCE('.$orderBy.', 1) - 1 ';
		$query .= " WHERE ".$orderBy.' '.$compare.' '.$o.' AND '.$table->db_primary_key. ' <> '.$dragged;
		$db->setQuery($query);
		if(!$db->query()) {
			echo $db->getErrorMsg();
		} else {

			//shift up the ordered records which have an order greater than the newly moved record

			if ($direction == 'desc') {
				$compare = $dragDirection == 'down' ? '>=' : '>';
			}else{
				$compare = $dragDirection == 'down' ? '>' : '>=';
			}

			$query = "UPDATE ".$table->db_table_name." SET ".$orderBy.' = COALESCE('.$orderBy.', 0) + 1';
			$query .= " WHERE ".$orderBy.' '.$compare.' '.$o;

			$db->setQuery($query);

			if(!$db->query()) {
				echo $db->getErrorMsg();
			} else {
				//change the order of the moved record
				$query = "UPDATE ".$table->db_table_name." SET ".$orderBy.' = '.$o;
				$query .= " WHERE ".$table->db_primary_key.' = '.$dragged;
				$db->setQuery($query);
				$db->query();
			}
		}
		$model->reorder(JRequest::getInt('orderelid'));
	}

}
?>