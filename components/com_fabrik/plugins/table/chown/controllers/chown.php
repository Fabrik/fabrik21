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
class FabrikControllerTablechown extends JController
{
	/*
	 * Fix up broken layout search path.
	 */

	function &getView($name = '', $type = '', $prefix = '', $config = array())
	{
		$config['base_path'] = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'table'.DS.'chown';
		return parent::getView($name,$type,$prefix,$config);
	}

	/*
	 * Fix up view and model names to normal values.
	 */
	function display()
	{
		$document =& JFactory::getDocument();
		$viewName = 'chown';
		$viewType = $document->getType();

		$view = &$this->getView($viewName, $viewType);

		$model	= &$this->getModel('chown');
		if (!JError::isError($model)) {
			$view->setModel($model, true);
		}
		$view->assign('error', $this->getError());

		$renderOrder = JRequest::getInt('renderOrder', 0);
		$tableModel =& $this->getModel( 'Table' );
		$tableModel->setId(JRequest::getVar('id', 0));
		//$formModel =& $tableModel->getFormModel();
		$params =& $tableModel->getParams();
		$chown_to_table = $params->get( 'chown_to_table', '');
		if (is_array($chown_to_table)) {
			$chown_to_table = $chown_to_table[$renderOrder];
		}
		if (empty($chown_to_table)) {
			$chown_to_table_name = '#__users';
			$chown_to_pk_name = 'id';
			$chown_to_label_name = 'name';
			$db = JFactory::getDBO();
			$db->setQuery("SELECT DISTINCT($chown_to_pk_name) AS value, $chown_to_label_name AS text FROM $chown_to_table_name");
			$options = $db->loadObjectList();
		}
		else {
			$chown_to_pk = $params->get( 'chown_to_pk', '');
			if (is_array($chown_to_pk)) {
				$chown_to_pk = $chown_to_pk[$renderOrder];
			}
			$chown_to_pk_name = FabrikString::safeColName($chown_to_pk);
			$chown_to_label = $params->get( 'chown_to_label', '');
			if (is_array($chown_to_label)) {
				$chown_to_label = $chown_to_label[$renderOrder];
			}
			$chown_to_label_name = FabrikString::safeColName($chown_to_label);
			$toTableModel =& $this->getModel( 'Table' );
			$toTableModel->setId($chown_to_table);
			//$toTable = $toTableModel->getTable();
			$toDb = $toTableModel->getDb();
			$chown_to_table_name = $toDb->nameQuote($toTableModel->getTable()->db_table_name);
			$toDb->setQuery("SELECT DISTINCT($chown_to_pk_name) AS value, $chown_to_label_name AS text FROM $chown_to_table_name");
			$options = $toDb->loadObjectList();
		}
		$chown_to_menu_label = $params->get( 'chown_to_menu_label', 'New Owner');
		if (is_array($chown_to_menu_label)) {
			$chown_to_menu_label = $chown_to_menu_label[$renderOrder];
		}
		$chown_to_intro = $params->get( 'chown_to_intro', '');
		if (is_array($chown_to_intro)) {
			$chown_to_intro = $chown_to_intro[$renderOrder];
		}
		$view->assign('chown_to_options', $options);
		$view->assign('chown_to', $chown_to_pk_name);
		$view->assign('chown_to_menu_label', $chown_to_menu_label);
		$view->assign('chown_to_intro', $chown_to_intro);

		return $view->display();
	}

	function dochown()
	{
		$app =& JFactory::getApplication();
		$tableModel =& $this->getModel('Table');
		$tableModel->setId(JRequest::getVar('id', 0));
		$db = $tableModel->getDb();
		$params =& $tableModel->getParams();
		$renderOrder = JRequest::getInt('renderOrder', 0);
		$recordids = explode(',', JRequest::getVar('recordids'));
		$lang =& JFactory::getLanguage();
		$lang->load('com_fabrik.plg.table.chown');

		$chown_to_val = JRequest::getInt('chown_to_val', 0);

		if (empty($chown_to_val)) {
			echo '<div class="chown_result">' . JText::_('PLG_TABLE_CHOWN_NO_SELECTION') . '</div>';
			return;
		}

		$chowns = array();
		$chown_field_to_change_1 = $params->get('chown_field_to_change_1');
		if (is_array($chown_field_to_change_1)) {
			$chown_field_to_change_1 = JArrayHelper::getValue($chown_field_to_change_1, $renderOrder, '');
		}
		if (!empty($chown_field_to_change_1)) {
			$chowns[] = "$chown_field_to_change_1 = " . $db->Quote($chown_to_val);
		}
		$chown_field_to_change_2 = $params->get('chown_field_to_change_2');
		if (is_array($chown_field_to_change_2)) {
			$chown_field_to_change_2 = JArrayHelper::getValue($chown_field_to_change_2, $renderOrder, '');
		}
		if (!empty($chown_field_to_change_2)) {
			$chowns[] = "$chown_field_to_change_2 = " . $db->Quote($chown_to_val);
		}
		$chown_field_to_change_3 = $params->get('chown_field_to_change_3');
		if (is_array($chown_field_to_change_3)) {
			$chown_field_to_change_3 = JArrayHelper::getValue($chown_field_to_change_3, $renderOrder, '');
		}
		if (!empty($chown_field_to_change_3)) {
			$chowns[] = "$chown_field_to_change_3 = " . $db->Quote($chown_to_val);
		}

		if (empty($chowns)) {
			echo '<div class="chown_result">' . JText::_('PLG_TABLE_CHOWN_NO_SELECTION') . '</div>';
			return;
		}

		$table_name = $db->nameQuote($tableModel->getTable()->db_table_name);
		$pk_field = FabrikString::safeColName($tableModel->getTable()->db_primary_key);
		$query = ("UPDATE $table_name SET " . implode(',', $chowns) . " WHERE $pk_field IN (" . implode(',', $recordids) . ")");
		$db->setQuery($query);
		if (!$db->query()) {
			echo '<div class="chown_result">' . JText::sprintf('PLG_TABLE_CHOWN_X_ERRORS', $db->getErrorMsg()) . '</div>';
		}
		else {
			echo '<div class="chown_result">' . JText::sprintf('PLG_TABLE_CHOWN_X_CHANGED', count($recordids)) . '</div>';
		}
		echo '<div class="chown_result"><button onclick="window.parent.location.reload()">OK</button></div>';

		/*
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
		*/
	}

}
?>