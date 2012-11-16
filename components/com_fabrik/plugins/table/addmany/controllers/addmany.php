<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php');

/**
 * Contact Component Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Contact
 * @since 1.5
 */
class FabrikControllerTableaddmany extends JController
{
	/*
	 * Fix up broken layout search path.
	 */

	function &getView($name = '', $type = '', $prefix = '', $config = array())
	{
		$config['base_path'] = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'table'.DS.'addmany';
		return parent::getView($name,$type,$prefix,$config);
	}

	/*
	 * Fix up view and model names to normal values.
	 */
	function display()
	{
		$document =& JFactory::getDocument();
		$viewName = 'addmany';
		$viewType = $document->getType();

		$view = &$this->getView($viewName, $viewType);

		$model	= &$this->getModel('addmany');
		if (!JError::isError($model)) {
			$view->setModel($model, true);
		}
		$view->assign('error', $this->getError());

		$renderOrder = JRequest::getInt('renderOrder', 0);
		$tableModel =& $this->getModel( 'Table' );
		$tableModel->setId(JRequest::getVar('id', 0));
		//$formModel =& $tableModel->getFormModel();
		$params =& $tableModel->getParams();
		$addmany_to_fk = $params->get( 'addmany_to_fk', '');
		if (is_array($addmany_to_fk)) {
			$addmany_to_fk = $addmany_to_fk[$renderOrder];
		}
		$addmany_to_table = $params->get( 'addmany_to_table', '');
		if (is_array($addmany_to_table)) {
			$addmany_to_table = $addmany_to_table[$renderOrder];
		}
		$toTableModel =& $this->getModel( 'Table' );
		$toTableModel->setId($addmany_to_table);
		$toFormModel =& $toTableModel->getFormModel();
		$elementModel =& $toFormModel->getElement($addmany_to_fk);
		$options = $elementModel->_getOptions(JRequest::get('request'));
		$label = $elementModel->getElement()->label;
		//$addmany_to_fk = FabrikString::safeColNameToArrayKey( $addmany_to_fk );
		$view->assign('addmany_to_fk_options', $options);
		$view->assign('addmany_to_fk', $addmany_to_fk);
		$view->assign('addmany_to_fk_label', $label);

		return $view->display();
	}

	function doaddmany()
	{
		$app =& JFactory::getApplication();

		$renderOrder = JRequest::getInt('renderOrder', 0);
		$recordids = explode(',', JRequest::getVar('recordids'));
		$addmany_to_fk_val = JRequest::getInt('addmany_to_fk_val');
		$tableModel =& $this->getModel('Table');
		$tableModel->setId(JRequest::getVar('id', 0));
		//$formModel =& $tableModel->getForm();
		//$this->formModel =& $formModel;
		$params =& $tableModel->getParams();
		$addmany_to_table = $params->get('addmany_to_table');
		if (is_array($addmany_to_table)) {
			$addmany_to_table = $addmany_to_table[$renderOrder];
		}
		$addmany_to_fk = $params->get('addmany_to_fk');
		if (is_array($addmany_to_fk)) {
			$addmany_to_fk = $addmany_to_fk[$renderOrder];
		}
		$addmany_from_fk = $params->get('addmany_from_fk');
		if (is_array($addmany_from_fk)) {
			$addmany_from_fk = $addmany_from_fk[$renderOrder];
		}

		$toTableModel =& $this->getModel( 'Table' );
		$toTableModel->setId($addmany_to_table);
		$toFormModel =& $toTableModel->getFormModel();

		$addmany_to_date = '';
		$date_elementModel =& $toFormModel->getElement('date_time');
		if (!empty($date_elementModel)) {
			$addmany_to_date = FabrikString::safeColName($date_elementModel->getFullName(false, true, false));
		}

		$addmany_to_fk = FabrikString::safeColName($addmany_to_fk);
		$addmany_to_fk_short = FabrikString::shortColName($addmany_to_fk);
		$addmany_from_fk = FabrikString::safeColName($addmany_from_fk);
		$toDb = $toTableModel->getDb();
		$addmany_to_db_table_name = $toDb->nameQuote($toTableModel->getTable()->db_table_name);
		$addmany_from_db_table_name = $toDb->nameQuote($tableModel->getTable()->db_table_name);
		$addmany_to_fk_val = $toDb->Quote($addmany_to_fk_val);

		$addmany_added = 0;
		$addmany_already_exist = 0;
		$addmany_add_errors = 0;

		$addmany_additional_child_fields = $params->get('addmany_additional_child_fields', '');
		if (is_array($addmany_additional_child_fields)) {
			$addmany_additional_child_fields = JArrayHelper::getValue($addmany_additional_child_fields, $renderOrder, '');
		}
		$addmany_additional_child_from_fields = array();
		$addmany_additional_child_to_fields = array();
		$addmany_additional_child_from_field_values = array();
		if (!empty($addmany_additional_child_fields)) {
			$toJoinModel = $toFormModel->getElement($addmany_to_fk_short);
			//$toJoinElement = $toJoinModel->getElement();
			$toJoinParams = $toJoinModel->getParams();
			$addmany_child_db_table_name = $toJoinParams->get('join_db_name', '');
			$addmany_child_pk = $toJoinParams->get('join_key_column', '');
			foreach (explode(';', $addmany_additional_child_fields) as $field_pairs) {
				list($from_field,$to_field) = explode(',', $field_pairs);
				$addmany_additional_child_from_fields[] = $from_field;
				$addmany_additional_child_to_fields[] = $to_field;
			}
			$query = "SELECT " . implode(',', $addmany_additional_child_from_fields) . " FROM $addmany_child_db_table_name WHERE $addmany_child_pk = $addmany_to_fk_val";
			$toDb->setQuery($query);
			$addmany_additional_child_from_field_values = $toDb->loadObject();
		}

		$addmany_additional_parent_fields = $params->get('addmany_additional_parent_fields', '');
		if (is_array($addmany_additional_parent_fields)) {
			$addmany_additional_parent_fields = JArrayHelper::getValue($addmany_additional_parent_fields, $renderOrder, '');
		}
		$addmany_additional_parent_from_fields = array();
		$addmany_additional_parent_to_fields = array();
		$addmany_additional_parent_from_field_values = array();
		if (!empty($addmany_additional_parent_fields)) {
			$addmany_from_pk = $params->get('addmany_from_pk');
			if (is_array($addmany_from_pk)) {
				$addmany_from_pk = $addmany_from_pk[$renderOrder];
			}
			$addmany_from_pk = FabrikString::safeColName($addmany_from_pk);
			$addmany_from_pk_short = FabrikString::shortColName($addmany_from_pk);
			$fromDb = $tableModel->getDb();
			foreach (explode(';', $addmany_additional_parent_fields) as $field_pairs) {
				list($from_field,$to_field) = explode(',', $field_pairs);
				$addmany_additional_parent_from_fields[] = $from_field;
				$addmany_additional_parent_to_fields[] = $to_field;
			}
			$query = "SELECT $addmany_from_pk_short, " . implode(',', $addmany_additional_parent_from_fields) . " FROM $addmany_from_db_table_name WHERE $addmany_from_pk IN (" . implode(',', $recordids) . ")";
			$fromDb->setQuery($query);
			$addmany_additional_parent_from_field_values = $fromDb->loadObjectList($addmany_from_pk_short);
		}

		foreach ($recordids as $recordid) {
			$addmany_from_fk_val = $toDb->Quote($recordid);
			$query = "SELECT COUNT(*) FROM $addmany_to_db_table_name WHERE $addmany_to_fk = $addmany_to_fk_val AND $addmany_from_fk = $addmany_from_fk_val";
			$toDb->setQuery($query);
			$exists = (int)$toDb->loadResult();
			if (empty($exists)) {
				$field_values = array();
				$field_names = array();
				$field_names[] = $addmany_to_fk;
				$field_values[] = $addmany_to_fk_val;
				$field_names[] = $addmany_from_fk;
				$field_values[] = $addmany_from_fk_val;
				if (!empty($addmany_to_date)) {
					$field_names[] = $addmany_to_date;
					$field_values[] = "NOW()";
				}
				if (!empty($addmany_additional_parent_from_field_values)) {
					if (array_key_exists($recordid, $addmany_additional_parent_from_field_values)) {
						foreach($addmany_additional_parent_from_fields as $from_index => $from_field) {
							$field_values[] = $toDb->Quote($addmany_additional_parent_from_field_values[$recordid]->$from_field);
							$field_names[] = $addmany_additional_parent_to_fields[$from_index];
						}
					}
				}
				if (!empty($addmany_additional_child_from_field_values)) {
						foreach($addmany_additional_child_from_fields as $from_index => $from_field) {
							$field_values[] = $toDb->Quote($addmany_additional_child_from_field_values->$from_field);
							$field_names[] = $addmany_additional_child_to_fields[$from_index];
						}
				}
				$query = "INSERT INTO $addmany_to_db_table_name (" . implode(',', $field_names) . ") VALUES (" . implode(',', $field_values) . ")";
				$toDb->setQuery($query);
				if ($toDb->query() === false) {
					$addmany_add_errors++;
				}
				else {
					$addmany_added++;
				}
			}
			else {
				$addmany_already_exist++;
			}
		}

		$lang =& JFactory::getLanguage();
		$lang->load('com_fabrik.plg.table.addmany');

		echo '<p>' . JText::sprintf('PLG_TABLE_ADDMANY_X_ADDED', $addmany_added) . '</p>';
		if ($addmany_already_exist > 0) {
			echo '<p>' . JText::sprintf('PLG_TABLE_ADDMANY_X_EXISTED', $addmany_already_exist) . '</p>';
		}
		if ($addmany_add_errors > 0) {
			echo '<p>' . JText::sprintf('PLG_TABLE_ADDMANY_X_ERRORS', $addmany_add_errors) . '</p>';
		}
		echo "<button onclick='alert(window.parent.location);window.parent.location.reload()'>OK</button>";

	}

}
?>