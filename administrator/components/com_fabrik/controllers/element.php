<?php
/**
 * @version
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'adminhtml.php');

/**
 * @package		Joomla
 * @subpackage	Fabrik
 */

class FabrikControllerElement extends JController
{

	/**
	 * Constructor
	 */

	function __construct($config = array())
	{
		parent::__construct($config);
		// Register Extra tasks
		$this->registerTask('add',			'edit');
		$this->registerTask('apply',		'save');
		$this->registerTask('unpublish',	'publish');
		$this->registerTask('removeFromTableview', 'addToTable');
		$this->registerTask('addToTableView', 'addToTable');
		$this->registerTask('orderDownElement', 'reorder');
		$this->registerTask('orderUpElement', 'reorder');
	}

	function reorder()
	{

		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=element');

		$task		= JRequest::getCmd('task');

		$direction 	= ($task == 'orderUpElement') ? -1 : 1;

		// Initialize variables
		$db		= & JFactory::getDBO();
		$cid	= JRequest::getVar('cid', array(), 'post', 'array');
		if (isset($cid[0]))
		{
			$row = & JTable::getInstance('element', 'Table');
			$row->load((int) $cid[0]);
			$where = " group_id = '" . $row->group_id . "'";
			$row->move($direction, $where);

		}
		$this->setMessage( JText::_('ITEMS REORDERED'));
	}

	/**
	 * used when top save order button pressed
	 *
	 * @return unknown
	 */
	function saveOrder()
	{

		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		// Initialize variables
		$db			= & JFactory::getDBO();

		$cid			= JRequest::getVar('cid', array(0), 'post', 'array');
		$order		= JRequest::getVar('order', array (0), 'post', 'array');
		$redirect	= JRequest::getVar('redirect', 0, 'post', 'int');
		$rettask	= JRequest::getVar('returntask', '', 'post', 'cmd');
		$total		= count($cid);
		$conditions	= array();

		JArrayHelper::toInteger($cid, array(0));
		JArrayHelper::toInteger($order, array(0));

		// Instantiate an article table object
		$row = & JTable::getInstance('element', 'Table');

		// Update the ordering for items in the cid array
		for ($i = 0; $i < $total; $i ++)
		{
			$row->load((int) $cid[$i]);
			if ($row->ordering != $order[$i]) {
				$row->ordering = $order[$i];
				if (!$row->store()) {
					JError::raiseError(500, $db->getErrorMsg());
					return false;
				}
				// remember to updateOrder this group
				$condition = 'group_id = '.(int) $row->group_id;
				$found = false;
				foreach ($conditions as $cond)
				if ($cond[1] == $condition) {
					$found = true;
					break;
				}
				if (!$found)
				$conditions[] = array ($row->id, $condition);
			}
		}

		// execute updateOrder for each group
		foreach ($conditions as $cond)
		{
			$row->load($cond[0]);
			$row->reorder($cond[1]);
		}

		$cache = & JFactory::getCache('com_fabrik');
		$cache->clean();

		$this->setRedirect('index.php?option=com_fabrik&c=element', JText::_('NEW ORDERING SAVED'));
	}

	/**
	 * add/remove from table view
	 * @param mixed array/int elements to add/remove to table
	 * @param bol add = true/remove = false;
	 */

	function addToTable()
	{

		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=element');

		// Initialize variables
		$db			=& JFactory::getDBO();
		$user		=& JFactory::getUser();
		$cid		= JRequest::getVar('cid', array(), 'post', 'array');
		$task		= JRequest::getCmd('task');
		$publish	= ($task == 'addToTableView');
		$n			= count($cid);

		if (empty($cid)) {
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}

		JArrayHelper::toInteger($cid);
		$cids = implode(',', $cid);

		$query = 'UPDATE #__fabrik_elements'
		. ' SET show_in_table_summary = ' . (int) $publish
		. ' WHERE id IN ( '. $cids.'  )'
		. ' AND ( checked_out = 0 OR ( checked_out = ' .(int) $user->get('id'). ') )'
		;
		$db->setQuery($query);
		if (!$db->query()) {
			return JError::raiseWarning(500, $row->getError());
		}
		$this->setMessage( JText::sprintf( $publish ? 'Items added to table view' : 'Items removed from table view', $n));
	}


	/**
	 * Edit an element
	 */

	function edit()
	{
		global $_PROFILER;
		JDEBUG ? $_PROFILER->mark('edit: start') : null;
		$app 		=& JFactory::getApplication();
		$user		=& JFactory::getUser();
		$db 		=& JFactory::getDBO();
		$acl 		=& JFactory::getACL();
		$model	=& JModel::getInstance('element', 'FabrikModel');
		if ($this->_task == 'edit') {
			$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
			$cid	= array((int) $cid[0]);
		} else {
			$cid	= array(0);
		}

		$model->setId($cid[0]);
		$row =& $model->getElement();
		if ($cid) {
			$row->checkout($user->get('id'));
		}

		// get params definitions
		$params =& $model->getParams();
		require_once(JPATH_COMPONENT.DS.'views'.DS.'element.php');

		$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');

		$db->setQuery("SELECT COUNT(*) FROM #__fabrik_groups");
		$total 			= $db->loadResult();
		if ($total == 0) {
			$app->redirect("index.php?option=com_fabrik&c=group&task=new", JText::_('PLEASE CREATE A GROUP BEFORE CREATING AN ELEMENT'));
			return;
		}
		$lists = array();

		if ($cid[0] != '0') {

			$aEls = array();
			$aGroups = array();

			$db->setQuery("SELECT form_id FROM #__fabrik_formgroup AS fg\n".
				"WHERE fg.group_id = $row->group_id");
			$formrow = $db->loadObject();

			if (is_null($formrow)) {
				$aEls[] = $aGroups[] = JText::_('GROUP MUST BE IN A FORM');
			} else {

				$formModel = JModel::getInstance('form', 'FabrikModel');
				$formModel->setId($formrow->form_id);

				//get available element types
				$groups =& $formModel->getGroupsHiarachy();

				foreach ($groups as $groupModel) {
					$group =& $groupModel->getGroup();
					$o = new stdClass();
					$o->label = $group->name;
					$o->value = "fabrik_trigger_group_group".$group->id;
					$aGroups[] = $o;
					$elementModels =& $groupModel->getMyElements();
					foreach ($elementModels as $elementModel) {
						$o = new stdClass();
						$element =& $elementModel->getElement();
						$o->label = FabrikString::getShortDdLabel( $element->label);
						$o->value = "fabrik_trigger_element_".$elementModel->getFullName(false, true, false);
						$aEls[] = $o;
					}
				}
			}
			asort($aEls);
			$o = new StdClass();
			$o->groups = $aGroups;
			$o->elements = array_values($aEls);

			$lists['elements'] = $o;
		} else {
			// set the publish default to 1
			$row->state = '1';
			$lists['elements'] = array(JText::_('AVAILABLE ONCE SAVED'));
		}
		JDEBUG ? $_PROFILER->mark('edit: after element types') : null;
		$pluginManager->getPlugInGroup('validationrule');
		$pluginManager->loadPlugInGroup('element');

		$j = new JRegistry();
		$lists['jsActions'] = $model->getJSActions();
		//merge the js attribs back into the array
		foreach ($lists['jsActions'] as $js) {
			$j->loadINI( $js->attribs);
			$a = $j->toArray();
			foreach ($a as $k=>$v) {
				$js->$k = $v;
			}
			unset($js->attribs);
		}

		$no_html = JRequest::getBool('no_html', 0);

		// Create the form
		$form = new fabrikParams('', JPATH_COMPONENT.DS.'models'.DS.'element.xml');
		$form->bind($row);
		$form->loadINI( $row->attribs);
		$row->parent_id = (int)$row->parent_id;
		if ($row->parent_id === 0) {
			$lists['parent'] = 0;
		} else {
			$sql = "SELECT * FROM #__fabrik_elements WHERE id = ".(int)$row->parent_id;
			$db->setQuery($sql);
			$parent = $db->loadObject();
			if (is_null($parent)) {
				//perhaps the parent element was deleted?
				$lists['parent'] = 0;
				$row->parent_id = 0;
			} else {
				$lists['parent'] = $parent;
			}
		}
		JDEBUG ? $_PROFILER->mark('view edit: start') : null;
		if ($no_html != 1) {
			FabrikViewElement::edit($row, $pluginManager, $lists, $params, $form);
		}
	}

	/**
	 * when you go from a child to parent element, check in child before redirect
	 */

	function parentredirect()
	{
		$id = JRequest::getInt('id', 0, 'post');
		$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$className = JRequest::getVar('plugin', 'fabrikfield', 'post');
		$elementModel = $pluginManager->getPlugIn($className, 'element');
		$elementModel->setId($id);
		$row =& $elementModel->getElement();
		$row->checkin();
		$to = JRequest::getInt('redirectto');
		$this->_task = 'edit';
		JRequest::setVar('cid', array($to));
		$this->edit();
	}

	/**
	 * cancel editing
	 */

	function cancel()
	{
		JRequest::checkToken() or die('Invalid Token');
		$row 		=& JTable::getInstance('element', 'Table');
		$id 		= JRequest::getInt('id', 0, 'post');
		$row->load($id);
		$row->checkin();
		$this->setRedirect('index.php?option=com_fabrik&c=element');
	}

	/**
	 * make the redirect link when you save the form
	 * also sets the controller redirct to that link
	 *
	 * @access private
	 * @param string $task
	 * @param int $id
	 */

	function _setSaveRedirect($task, $id)
	{
		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_fabrik&c=element&task=edit&cid[]='. $id;
				break;

			case 'save':
			default:
				$link = 'index.php?option=com_fabrik&c=element';
				break;
		}
		$this->setRedirect($link);
	}

	/**
	 * Save a connection
	 */

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		jimport('joomla.utilities.date');

		$session =& JFactory::getSession();
		$user = &JFactory::getUser();
		$db =& JFactory::getDBO();
		$pluginManager	=& JModel::getInstance('Pluginmanager', 'FabrikModel');

		$task = JRequest::getCmd('task');
		$id = JRequest::getInt('id', 0, 'post');
		$details = JRequest::getVar('details', array(), 'post', 'array');
		$className = $details['plugin'];
		$elementModel = $pluginManager->getPlugIn($className, 'element');
		$elementModel->setId($id);
		$row =& $elementModel->getElement();
		$origRow = clone($row);
		$this->_setSaveRedirect($task, $row->id);

		$name = JRequest::getVar('name', '', 'post', 'CMD');
		$name = str_replace('-', '_', $name);

		if (FabrikWorker::isReserved($name)) {
			return JError::raiseWarning(500, JText::_('SORRY THIS NAME IS RESERVED FOR FABRIK'));
		}

		if (JRequest::getInt('id') === 0) {
			//have to forcefully set group id otherwise tablemodel id is blank
			$elementModel->getElement()->group_id = $details['group_id'];
		}
		$tableModel =& $elementModel->getTableModel();

		//are we updating the name of the primary key element?
		if ($row->name === str_replace('`', '', $tableModel->_shortKey())) {
			if ($name !== $row->name) {
				//yes we are so update the table
				$table =& $tableModel->getTable();
				$table->db_primary_key = str_replace($row->name, $name, $table->db_primary_key);
				$table->store();
			}
		}
		//test for duplicate names
		//unlinking produces this error
		if (!JRequest::getVar('unlink', false)) {
			$row->group_id = (int)$details['group_id'];

			$db->setQuery("SELECT t.id, group_id FROM `#__fabrik_joins` AS j ".
			"\n LEFT JOIN #__fabrik_tables AS t ".
			"\n ON j.table_join = t.db_table_name ".
			"\n WHERE group_id = ".(int)$row->group_id." AND element_id = 0");
			$res = $db->loadObject();

			if (is_null($res)) {
				// no join found
				if ($tableModel->fieldExists(JRequest::getVar('name'), array($id))) {
					return JError::raiseWarning(500, JText::_('SORRY THIS NAME IS ALREADY IN USE'));
				}
			} else {
				$jointableModel =& JModel::getInstance('table', 'fabrikModel');
				$jointableModel->setId((int)$res->id);
				$joinEls = $jointableModel->getElements();
				$ignore = array($id);
				foreach ($joinEls as $joinEl) {
					if ($joinEl->getElement()->name == JRequest::getVar('name')) {
						$ignore[] = $joinEl->getElement()->id;
					}
				}

				if ($jointableModel->fieldExists(JRequest::getVar('name'), $ignore)) {
					JError::raiseNotice(500, JText::_('SORRY THIS NAME IS ALREADY IN USE'));
				}
			}
		}
		//end  duplicate name test

		$post	= JRequest::get('post', 4); // $$$ hugh allows "safe" HTML.
		//$$$ rob default etc may require you to have \" or < recored - safe html filter removes these
		$raws = array('default', 'sub_values');
		foreach ($raws as $raw) {
			$post[$raw] = JRequest::getVar($raw, null, 'default', 'none', 2);
		}

		$tableParams =& $tableModel->getParams();
		//only update the element name if we can alter existing columns, otherwise the name and
		//field name become out of sync
		//if ($tableParams->get('alter_existing_db_cols') == 1 || $id == 0) {
		// $$$ hugh - check to see if there's actually a table
		if (empty($tableModel->_id) || ($tableModel->_canAlterFields() || $id == 0)) {
			$post['name'] = $name;
		} else {
			$post['name'] = JRequest::getVar('name_orig', '', 'post', 'cmd');
		}

		$ar 	= array('state', 'use_in_page_title', 'show_in_table_summary', 'link_to_detail', 'can_order', 'filter_exact_match');
		foreach ($ar as $a) {
			if (!array_key_exists($a, $post)) {
				$post[$a] = 0;
			}
		}

		// $$$ rob - test for change in element type
		//(eg if changing from db join to field we need to remove the join
		//entry from the #__fabrik_joins table
		$origElementModel =& JModel::getInstance('Element', 'FabrikModel');
		$origElementModel->setId($id);
		$origEl =& $origElementModel->getElement();
		$origElementPluginModel 	=& $pluginManager->getPlugIn($origEl->plugin, 'element');
		$origElementPluginModel->beforeSave($row);
		if (!$row->bind($post)) {
			return JError::raiseWarning(500, $row->getError());
		}
		//unlink linked elements
		if (JRequest::getVar('unlink') == 'on') {
			$row->parent_id = 0;
		}
		//merge details params into element table fields

		if (!array_key_exists('eval', $details)) {
			$details['eval'] = 0;
		}
		if (!array_key_exists('hidden', $details)) {
			$details['hidden'] = 0;
		}
		$row->bind($details);

		$datenow = new JDate();
		if ($row->id != 0) {
			$row->modified 		= $datenow->toFormat();
			$row->modified_by = $user->get('id');
		} else {
			$row->created 		= $datenow->toFormat();
			$row->created_by = $user->get('id');
			$row->created_by_alias = $user->get('username');
		}
		// 	save params
		$params = $elementModel->getParams();
		$row->attribs = $params->updateAttribsFromParams(JRequest::getVar('params', array(), 'post', 'array'));
		$cond = 'group_id = '.(int) $row->group_id;

		//hack for width option
		if ($row->width == '') {
			$row->width = 40;
		}
		$new = $row->id == 0 ? true : false;
		if ($new) {
			$row->ordering = $row->getNextOrder($cond);
		}

		if (!$row->store()) {
			return JError::raiseWarning(500, $row->getError());
		}
		$row->checkin();

		$row->reorder($cond);
		$elementModel->setId($row->id);

		$oldParams = $elementModel->_params;
		//unset and reload the params with newly saved values
		unset($elementModel->_params);
		$elementModel->getParams();
		$elementModel->updateJavascript();
		if (!$elementModel->onSave()) {
			//revert row back to original data
			foreach ($origRow as $k=>$v) {
				$row->$k = $v;
				$row->store();
			}
			$this->setRedirect('index.php?option=com_fabrik&c=element&task=edit&cid[]='. $row->id );
			return;
		}
		//set flags in session to ensure we de/encrypt columns data when the field's structure is updated
		$session->clear('com_fabrik.admin.element.encryptCol');
		$session->clear('com_fabrik.admin.element.decryptCol');

		$encryptCol = ($oldParams->get('encrypt') == 0 && $elementModel->getParams()->get('encrypt') == 1);
		$session->set('com_fabrik.admin.element.encryptCol', $encryptCol);
		$decryptCol = ($oldParams->get('encrypt') == 1 && $elementModel->getParams()->get('encrypt') == 0);
		$session->set('com_fabrik.admin.element.decryptCol', $decryptCol);
		$this->updateChildIds($row);

		$this->setMessage(JText::_('ELEMENT SAVED'));
		$origName = JRequest::getVar('name_orig', '', 'post', 'cmd');

		list($update, $q, $oldName, $newdesc, $origDesc, $dropIndex) = $tableModel->shouldUpdateElement($elementModel, $origName);

		// If new, check if the element's db table is used by other tables and if so add the element
		// to each of those tables' groups

		if ($new) {
			$this->addElementToOtherDbTables($elementModel, $row);
		}

		$elementModel->createRepeatElement();
		if ($update) {
			$origplugin = JRequest::getVar('plugin_orig');
			$session->set('com_fabrik.admin.element.updatequery', $q);
			$session->set('com_fabrik.admin.element.oldname', $oldName);
			$session->set('com_fabrik.admin.element.newdesc', $newdesc);
			$session->set('com_fabrik.admin.element.origdesc', $origDesc);
			$session->set('com_fabrik.admin.element.newname', $name);
			$session->set('com_fabrik.admin.element.dropindex', $dropIndex);
			$this->setRedirect('index.php?option=com_fabrik&c=element&task=confirmElementUpdate&id='.(int)$row->id."&origplugin=$origplugin&&origtaks=$task&plugin=$row->plugin");
		} else {
			$this->_setSaveRedirect($task, $row->id);
		}

		$cache = & JFactory::getCache('com_fabrik');
		$cache->clean();
		if((int)$tableModel->getTable()->id !== 0) {
			$this->updateIndexes($elementModel, $tableModel, $row);
		}

		// $$$ hugh - adding afterSave(), for things like join element to handle adding
		// rows to joins table for any children we created (can't use onSave 'cos children
		// haven't been create at that point).
		$elementModel->onAfterSave($row);

		//used for prefab
		return $elementModel;
	}

	private function addElementToOtherDbTables($elementModel, $row)
	{
		$db =& JFactory::getDBO();
		$table = $elementModel->getTableModel()->getTable();
		$origElid = $row->id;
		$tmpgroupModel =& $elementModel->getGroup();
		if ($tmpgroupModel->isJoin()) {
			$dbname = $tmpgroupModel->getJoinModel()->getJoin()->table_join;
		} else {
			$dbname = $table->db_table_name;
		}

		$query = "SELECT DISTINCT(t.id), db_table_name, t.label, t.form_id, f.label AS form_label, g.id AS group_id
FROM #__fabrik_tables AS t
INNER JOIN #__fabrik_forms AS f ON t.form_id = f.id
LEFT JOIN #__fabrik_formgroup AS fg ON f.id = fg.form_id
LEFT JOIN #__fabrik_groups AS g ON fg.group_id = g.id
WHERE db_table_name = ". $db->Quote($dbname)."
AND t.id !=".(int)$table->id."
AND is_join =0";

		$db->setQuery($query);
		// $$$ rob load keyed on table id to avoid creating element in every one of the table's group
		$othertables = $db->loadObjectList('id');
		if (!empty($othertables)) {
			// $$$ hugh - we use $row after this, so we need to work on a copy, otherwise
			// (for instance) we redirect to the wrong copy of the element
			$rowcopy = clone($row);
			foreach ($othertables as $t) {
				$rowcopy->id = 0;
				$rowcopy->parent_id = $origElid;
				$rowcopy->group_id = $t->group_id;
				$rowcopy->name = str_replace('`', '', $rowcopy->name);
				$rowcopy->store();
			}
		}
	}

	/**
	 * update table indexes based on element settings
	 * @param $elementModel
	 * @param $tableModel
	 * @param $row
	 * @return unknown_type
	 */

	private function updateIndexes(&$elementModel, &$tableModel, &$row)
	{
		if ($elementModel->getGroup()->isJoin()) {
			return;
		}
		// $$$ hugh @TODO - should really check to see if we are double indexing,
		// for instance if a can_order element is also a filter we currently end up
		// with two indexes on the same element, which can be very wasteful on big tables.
		//update table indexes
		$ftype = $elementModel->getFieldDescription();
		//int elements cant have a index size attrib
		$size = stristr($ftype, 'int') || $ftype == 'DATETIME' || stristr($ftype, 'timestamp') ? '' : '10';
		// $$$ hugh - check to see if VARCHAR(x) length is < $size
		$matches = array();
		if (preg_match('#varchar\((\d+)\)#i', $ftype, $matches)) {
			if ((int)$matches[1] < (int)$size) {
				$size = $matches[1];
			}
		}
		if ($elementModel->getElement()->can_order) {
			$tableModel->addIndex($row->name, 'order', 'INDEX', $size);
		} else {
			$tableModel->dropIndex($row->name, 'order', 'INDEX');
		}
		if ($row->filter_type != '') {
			$tableModel->addIndex($row->name, 'filter', 'INDEX', $size);
		} else {
			$tableModel->dropIndex($row->name, 'filter', 'INDEX');
		}
	}

	/**
	 * update child elements
	 * @param object row element
	 * @return mixed
	 */

	private function updateChildIds(&$row)
	{
		$db =& JFactory::getDBO();
		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikModel');
		$db->setQuery("SELECT id FROM #__fabrik_elements WHERE parent_id = ". (int)$row->id);
		$childids = $db->loadResultArray();
		$mainElement = $pluginManager->getPlugIn($row->plugin, 'element');
		$mainElement->setId($row->id);
		$item = $mainElement->getElement(true);
		$parentParams = $mainElement->getParams();
		
		$ignore = array('_tbl', '_tbl_key', '_db', 'id', 'group_id', 'created', 'created_by', 'parent_id', 'ordering');
		
		foreach ($childids as $id)
		{
			$mainParams = clone($parentParams);
			$elementModel = clone($mainElement);
			$elementModel->setId($id);
			$item = $elementModel->getElement(true);
			unset($elementModel->_params);
			$leave = $elementModel->getFixedChildParameters();
			$params = $elementModel->getParams();
			foreach ($row as $key => $val)
			{
				if (!in_array($key, $ignore))
				{
					if ($key == 'attribs')
					{
						foreach ($mainParams->_registry['_default']['data'] as $pKey => $pVal)
						{
							if (!in_array($pKey, $leave))
							{
								$params->set($pKey, $pVal);
							}
						}
						$val = $params->toString();
					}
					else
					{
						// $$$rob - i can't replicate bug #138 but this should fix things anyway???
						if ($key == 'name')
						{
							$val = str_replace("`", "", $val);
						}
					}
					$item->$key = $val;
				}
			}
			if (!$item->store())
			{
				JError::raiseWarning(500, $item->getError());
			}
		}
		return true;
	}

	/**
	 * Publish a element
	 */

	function publish()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=element');

		// Initialize variables
		$db			=& JFactory::getDBO();
		$user		=& JFactory::getUser();
		$cid		= JRequest::getVar('cid', array(), 'post', 'array');
		$task		= JRequest::getCmd('task');
		$publish	= ($task == 'publish');
		$n			= count($cid);

		if (empty($cid)) {
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}

		JArrayHelper::toInteger($cid);
		$cids = implode(',', $cid);

		$query = 'UPDATE #__fabrik_elements'
		. ' SET state = ' . (int) $publish
		. ' WHERE id IN ( '. $cids.'  )'
		. ' AND ( checked_out = 0 OR ( checked_out = ' .(int) $user->get('id'). ') )'
		;
		$db->setQuery($query);
		if (!$db->query()) {
			return JError::raiseWarning(500, $row->getError());
		}
		$this->setMessage( JText::sprintf( $publish ? 'Items published' : 'Items unpublished', $n));
	}

	/**
	 * Display the list of elements
	 */

	function display()
	{
		$app =& JFactory::getApplication();
		$db =& JFactory::getDBO();
		$context					= 'com_fabrik.element.list.';
		$filter_order			= $app->getUserStateFromRequest( $context.'filter_order',		'filter_order',	'e.ordering',	'cmd');
		$filter_order_Dir	= $app->getUserStateFromRequest( $context.'filter_order_Dir',	'filter_order_Dir',	'',			'word');
		$limit						= $app->getUserStateFromRequest( $context.'limit', 'limit', $app->getCfg('list_limit'), 'int');
		$limitstart 			= $app->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int');
		$filter_elementTypeId	= $app->getUserStateFromRequest( $context."filter_elementTypeId", 'filter_elementTypeId', '');
		$filter_groupId 		= $app->getUserStateFromRequest( $context."filter_groupId", 'filter_groupId', 0, 'int');
		$search 						= $app->getUserStateFromRequest( $context."filter_elementName", 'filter_elementName', '');
		$filter_showInTable	= $app->getUserStateFromRequest( $context."filter_showInTable", 'filter_showInTable', '');
		$filter_published 	= $app->getUserStateFromRequest( $context."filter_published", 'filter_published', '');
		$filter_formId			 	= $app->getUserStateFromRequest( $context."filter_formId", 'filter_formId', '');

		$lists = array();
		$where = array();

		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;
		$lists['order']		= $filter_order;


		// used by filter
		if ($filter_elementTypeId != '') {
			$where[] = " e.plugin = '$filter_elementTypeId' ";
		}
		//used by filter
		if ($filter_groupId >= 1) {
			$where[] = " e.group_id = '$filter_groupId' ";
		}
		if ($filter_formId != '' && $filter_formId != -1) {
			$where[] = " fg.form_id = ".(int)$filter_formId;
		}
		// filter the element names
		if ($search != '') {
			$where[] = " (e.name LIKE '%$search%' OR e.label LIKE '%$search%')";
		}
		// filter if its shown in table
		if ($filter_showInTable != '') {
			$where[] = " e.show_in_table_summary  = '$filter_showInTable'";
		}

		// filter if its published
		if ($filter_published != '') {
			$where[] = " e.state  = '$filter_published'";
		}
		$where		= count($where) ? ' WHERE ' . implode(' AND ', $where ) : '';
		$orderby	= ' ORDER BY '. $filter_order .' '. $filter_order_Dir .', g.id,  e.ordering';

		$join = "\n LEFT JOIN #__fabrik_groups AS g " .
			"\n ON e.group_id = g.id " .
			"\n LEFT JOIN #__fabrik_plugins  " .
			"\n ON e.plugin = #__fabrik_plugins.name " .
    "\n LEFT JOIN #__fabrik_formgroup AS fg  " .
			"\n ON fg.group_id = g.id " .
			"\n LEFT JOIN #__users AS u ON e.checked_out = u.id ";
		//$join .= "\n LEFT JOIN jos_fabrik_joins as j ON j.group_id = g.id ";
		$join .= "\n LEFT JOIN #__fabrik_tables as t on fg.form_id = t.form_id ";

		// get the total number of records
		$db->setQuery("SELECT COUNT(*) FROM #__fabrik_elements AS e $join". $where);
		$total = $db->loadResult();
		echo $db->getErrorMsg();

		jimport('joomla.html.pagination');
		$pageNav 			= new JPagination($total, $limitstart, $limit);

		// $this will only get the element full name for those elements in a joined group
		// $$$ hugh - altered this query as ...
		// WHERE (jj.table_id != 0 AND jj.element_id = 0)
		// ...instead of ...
		// WHERE jj.table_id != 0
		//... otherwiose we pick up repeat elements, as they have both table and element set
		// and he query fails with "returns multiple values" for the fullname select
		$fullname = "
		(SELECT DISTINCT(
		IF( ISNULL(jj.table_join), CONCAT(tt.db_table_name, '___', ee.name), CONCAT(jj.table_join, '___', ee.name))
		)
		FROM #__fabrik_elements AS ee
		LEFT JOIN #__fabrik_joins AS jj ON jj.group_id = ee.group_id
		LEFT JOIN #__fabrik_formgroup as fg ON fg.group_id = ee.group_id
		LEFT JOIN #__fabrik_tables as tt ON tt.form_id = fg.form_id
		WHERE (jj.table_id != 0 AND jj.element_id = 0)
		AND jj.group_id != 0
		AND ee.id = e.id)  AS tablename";

		$sql = "SELECT $fullname, e.*, u.name AS editor, e.id AS id, " .
			"\n e.checked_out AS checked_out, #__fabrik_plugins.label AS pluginlabel,	 " .
			"\n e.checked_out_time AS checked_out_time, " .
			"\n e.state as state, g.name AS group_name, " .
		"\n fg.group_id, t.db_table_name, " .
			"\n e.name AS name, e.label AS label, e.ordering AS ordering " .
			"\n FROM #__fabrik_elements AS e  " .
		$join.
			"\n $where $orderby ";

		$db->setQuery($sql, $pageNav->limitstart, $pageNav->limit);
		$rows = $db->loadObjectList();
		if ($db->getErrorNum() != 0) {
			JError::raiseNotice(500, $db->getErrorMsg());
		}
		//get the join elemnent name of those elements not in a joined group
		foreach ($rows as &$row) {
			if ($row->tablename == '') {
				$row->tablename = $row->db_table_name . '___' . $row->name;
			}
		}
		$groupModels = array();

		//element types
		$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$pluginManager->_group = 'element';
		$lists['elementId'] = $pluginManager->getElementTypeDd($filter_elementTypeId, 'filter_elementTypeId', 'class="inputbox"  onchange="document.adminForm.submit();"', '- ' . JText::_('ELEMENT TYPE') . ' -');

		//groups into a drop down list
		$groupModel = JModel::getInstance('Group', 'FabrikModel');
		$lists['groupId'] = $groupModel->makeDropDown($filter_groupId,  '- ' . JText::_('GROUP') . ' -');

		$yesNoList 			= FabrikHelperHTML::yesNoOptions('', '- ' . JText::_('SHOW IN TABLE') . ' -');
		$lists['filter_showInTable'] = JHTML::_('select.genericlist',  $yesNoList, 'filter_showInTable', 'class="inputbox"  onchange="document.adminForm.submit();"', 'value', 'text', $filter_showInTable);

		//filter on published list
		$yesNoList = FabrikHelperHTML::yesNoOptions('', '- ' . JText::_('PUBLISHED') . ' -');
		$lists['filter_published'] = JHTML::_('select.genericlist', $yesNoList, 'filter_published', 'class="inputbox"  onchange="document.adminForm.submit();"', 'value', 'text', $filter_published);
		$lists['search'] = $search;

		$formModel =& JModel::getInstance('Form', 'FabrikModel');
		$lists['filter_formId'] = $formModel->makeDropDown($filter_formId, "- " .JText::_('FORMS') . " -");

		require_once(JPATH_COMPONENT.DS.'views'.DS.'element.php');
		FabrikViewElement::show($rows, $pageNav, $lists);
	}

	function copySelectGroup()
	{
		$db =& JFactory::getDBO();
		$db->setQuery("SELECT id, name FROM #__fabrik_groups ORDER BY name");
		$groups = $db->loadObjectList();
		$ids = JRequest::getVar('cid', array());
		$db->setQuery("SELECT id, label FROM #__fabrik_elements WHERE id IN (" . implode(', ', $ids).")");
		$elements = $db->loadObjectList();
		require_once(JPATH_COMPONENT.DS.'views'.DS.'element.php');
		FabrikViewElement::setCopyElementToolbar();
		FabrikViewElement::copySelectGroup($elements, $groups);
	}

	/**
	 * copy an element
	 * @param int element id
	 */

	function copy()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$groups = JRequest::getVar('group', array());
		$names = JRequest::getVar('name', array());
		$this->setRedirect('index.php?option=com_fabrik&c=element');

		$db			=& JFactory::getDBO();
		$rule		=& JTable::getInstance('element', 'Table');
		$join		=& JTable::getInstance('join', 'Table');
		$jsaction	=& JTable::getInstance('jsaction', 'Table');

		$user		= &JFactory::getUser();
		$n			= count($groups);

		$pluginManager	=& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$copied = $n;
		if ($n > 0)
		{
			foreach ($groups as $id => $groupid)
			{
				if ($rule->load((int)$id))
				{
					$rule->name = JArrayHelper::getValue($names, $id, $rule->name);
					$groupModel = JModel::getInstance('Group', 'FabrikModel');
					$groupModel->setId($groupid);
					$groupTable = $groupModel->getTableModel();
					if ($groupTable->fieldExists($rule->name)) {
						JError::raiseWarning(500, JText::_('SORRY THIS NAME IS ALREADY IN USE'));
						$copied --;
						continue;
					}
					$rule->id				= 0;
					$rule->group_id = $groupid;
					//$rule->name	= 'copy_of_' . $rule->name;
					if (!$rule->store()) {
						JError::raiseWarning($rule->getError());
						$copied --;
						continue;
					}
					//get js actions
					$db->setQuery("SELECT id FROM #__fabrik_jsactions WHERE ".$db->nameQuote('element_id') . ' = ' . (int)$id);
					$jsids = $db->loadResultArray();
					foreach ($jsids as $jsid) {
						$jsaction->load($jsid);
						$jsaction->id = 0;
						$jsaction->element_id = $rule->id;
						$jsaction->store();
					}
					//copy joins if neccesary
					$join->_tbl_key = 'element_id';
					$join->load($id);
					$join->_tbl_key = 'id';
					$join->id = 0;
					$join->element_id = $rule->id;
					if ($join->table_join != '') {
						$join->store();
					}
				} else {
					JError::raiseWarning(500, $rule->getError());
					$copied --;
					continue;
				}
				$elementModel = $pluginManager->getPlugIn($rule->plugin, 'element');
				$elementModel->setId($rule->id);
				// $$$ rob 15/03/2011 force a reload other wise if copying elements of they same
				// plugin type we reuse the same element name when
				//trying to create the db fields in shouldUpdateElement();
				$element =& $elementModel->getElement(true);
				$tableModel =& $elementModel->getTableModel();
				list($update, $q, $oldName, $newdesc, $origDesc) = $tableModel->shouldUpdateElement($elementModel, $rule->name);
				$this->addElementToOtherDbTables($elementModel, $rule);
			}
		}
		else {
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}
		$this->setMessage(JText::sprintf( 'Items copied', $copied));
	}

	/**
	 * ask if the user wants to delete the db column as well as the element
	 */

	function checkRemove()
	{
		//@TODO can we skip this if all elements aren't assigned to a table?
		require_once(JPATH_COMPONENT.DS.'views'.DS.'element.php');
		$db 	=& JFactory::getDBO();
		$cid	= JRequest::getVar('cid', array(), 'post', 'array');
		$db->setQuery("SELECT id, label FROM #__fabrik_elements WHERE id = " . implode(' OR id = ', $cid));
		$elements =& $db->loadObjectList();
		FabrikViewElement::checkRemove($elements);
	}

	/**
	 * ask if the user really wants to update core field structure
	 */

	function confirmElementUpdate()
	{
		$pluginManager	=& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$className 			= JRequest::getVar('plugin', 'fabrikfield');
		$model 	=& $pluginManager->getPlugIn($className, 'element');
		$model->setId(JRequest::getInt('id', 0, 'request'));
		require_once(JPATH_COMPONENT.DS.'views'.DS.'element.php');
		FabrikViewElement::confirmElementUpdate($model);
	}

	/**
	 * user agreed to updating field structure
	 */

	function elementUpdate()
	{
		// Check for request forgeries
		$session =& JFactory::getSession();
		JRequest::checkToken() or die('Invalid Token');
		$model	=& JModel::getInstance('element', 'FabrikModel');
		$model->setId(JRequest::getInt('id'));
		$tableModel =& $model->getTableModel();
		$fabrikDb =& $tableModel->getDB();
		$q = $session->get('com_fabrik.admin.element.updatequery');
		$dropIndex = $session->get('com_fabrik.admin.element.dropindex');
		if ($dropIndex) {
			$oldname = $session->get('com_fabrik.admin.element.oldname');
			$tableModel->dropColumnNameIndex($oldname);
		}
		$fabrikDb->setQuery($q);


		if (!$fabrikDb->query()) {
			JError::raiseWarning(E_WARNING, $fabrikDb->stderr(true));
			$msg = '';
		} else {

			if ($session->get('com_fabrik.admin.element.encryptCol')) {
				$model->encryptColumn();
			} else {
			if ($session->get('com_fabrik.admin.element.decryptCol')) {
				$model->decryptColumn();
			}
			}
			$session->clear('com_fabrik.admin.element.encryptCol');
			$session->clear('com_fabrik.admin.element.decryptCol');
			$msg = JText::_('STRUCTUREUPDATED');
			// $$$ hugh - FIXME need to update names for any child elements, if we succesfully changed column name
			// I think the following code should do it.  Not sure if we need to change anything else, like plugin type?
			/*
			$newname = $session->get('com_fabrik.admin.element.newname');
			$oldname = $session->get('com_fabrik.admin.element.oldname');
			if ($oldname != $newname) {
				$db =& JFactory::getDBO();
				$parent_id = JRequest::getInt('id', 0);
				// Just a sanity check in case we didn't get an ID, otherwise we'd fubar entire elements table!
				if ($parent_id > 0) {
					$db->setQuery("UPDATE #__fabrik_elements SET name = '$newname' WHERE parent_id = '$parent_id'");
					$db->Query();
				}
			}
			 */
		}
		if (JRequest::getVar('origtaks') == 'save') {
			$this->setRedirect("index.php?option=com_fabrik&c=element", $msg);
		} else {
			$this->setRedirect('index.php?option=com_fabrik&c=element&task=edit&cid[]='. $model->_id, $msg);
		}
	}

	/**
	 * user decided to cancel update
	 */

	function elementRevert()
	{
		JRequest::checkToken() or die('Invalid Token');
		$model	=& JModel::getInstance('element', 'FabrikModel');
		$model->setId(JRequest::getInt('id'));
		$element         =& $model->getElement();
		$element->name   = JRequest::getWord('oldname');
		$element->plugin = JRequest::getWord('origplugin');
		$element->store();
		if (JRequest::getVar('origtaks') == 'save') {
			$this->setRedirect("index.php?option=com_fabrik&c=element", $msg);
		} else {
			$this->setRedirect('index.php?option=com_fabrik&c=element&task=edit&cid[]='. $model->_id, $msg);
		}
	}

	/**
	 * delete element
	 */

	function remove()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=element');

		// Initialize variables
		$db		=& JFactory::getDBO();
		$pluginManager	=& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$cid	= JRequest::getVar('cid', array(), 'post', 'array');
		$n		= count($cid);
		JArrayHelper::toInteger($cid);
		$drops = JRequest::getVar('drop');

		//drop any selected columns
		// $$$ hugh - also we need to tell element it is being removed, so added onRemove
		// for instance so join elements can remove join table rows.  Also need to handle
		// parent / child stuff
		/*
		 $all_cids = array();
		 foreach ($cid as $id) {
			$drop = array_key_exists($id, $drops) && $drops[$id][0] == '1';
			if ($drop) {
			// $$$ hugh - dealing with element copies ....
			// if we are dropping the column, we need to remove all elements based on
			// the same column from the same table.  The getElementExtendedFamily() method
			// ignores parent / child relationships, as we need to catch elements which
			// have been unlinked.
			$all_cids = array_merge($this->getElementExtendedFamily($id), $all_cids);
			}
			}
			*/
		foreach ($cid as $id) {
			$drop = array_key_exists($id, $drops) && $drops[$id][0] == '1';
			//$model	=& JModel::getInstance('element', 'FabrikModel');
			//$model->setId($id);
			$db->setQuery("SELECT plugin FROM #__fabrik_elements WHERE id = ".(int)$id);
			$pluginClass = $db->loadResult();
			$pluginModel 	=& $pluginManager->getPlugIn($pluginClass, 'element');
			$pluginModel->setId($id);
			$pluginModel->onRemove($drop);
			$element =& $pluginModel->getElement();
			if ($drop) {
				$tableModel =& $pluginModel->getTableModel();
				$table =& $tableModel->getTable();
				// $$$ hugh - might be a tableless form!
				if (!empty($table->id)) {
					$tableDb =& $tableModel->getDb();
					$tableDb->setQuery("ALTER TABLE ".$db->nameQuote($table->db_table_name)." DROP ".$db->nameQuote($element->name));
					$tableDb->query();
				}
			}
		}

		if ($n)
		{
			$query = 'DELETE FROM #__fabrik_elements'
			. ' WHERE id = ' . implode(' OR id = ', $cid )
			;
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500, $db->getErrorMsg());
			}

			//remove element joins
			$query = 'DELETE FROM #__fabrik_joins WHERE element_id = ' . implode(' OR element_id = ', $cid );
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500, $db->getErrorMsg());
			}
		}

		$this->setMessage( JText::sprintf( 'Items removed', $n));

	}

	// $$$ hguh - testing stuff for getting element family trees

	function getElementAncestors($id)
	{
		$db = JFactory::getDBO();
		$db->setQuery("SELECT parent_id FROM #__fabrik_elements WHERE id = ".(int)$id);
		$pid = $db->loadResult();
		$path = array();
		if (!empty($pid)) {
			$path[] = $pid;
			$path = array_merge( $this->getElementAncestors($pid), $path);
		}
		return $path;
	}

	function getElementDescendents($id)
	{
		$db = JFactory::getDBO();
		$db->setQuery("SELECT id FROM #__fabrik_elements WHERE parent_id = ".(int)$id);
		$kids = $db->loadObjectList();
		$all_kids = array();
		foreach ($kids as $kid) {
			$all_kids[] = $kid->id;
			$all_kids = array_merge( $this->getElementDescendents($kid->id), $all_kids);
		}
		return $all_kids;
	}

	function getElementFamily($id)
	{
		$family = array();
		$ancestors = $this->getElementAncestors($id);
		if (!empty($ancestors)) {
			$family = $this->getElementDescendents($ancestors[0]);
			$family[] = $ancestors[0];
		}
		else {
			$family = $this->getElementDescendents($id);
			$family[] = $id;
		}
		return $family;
	}

	function getElementExtendedFamily($id)
	{
		$db =& JFactory::getDBO();
		$model	=& JModel::getInstance('element', 'FabrikModel');
		$model->setId($id);
		$element =& $model->getElement();
		$query = "
			SELECT e.id
			FROM #__fabrik_tables AS t
			LEFT JOIN #__fabrik_forms AS f ON f.id = t.form_id
			LEFT JOIN #__fabrik_formgroup AS fg ON fg.form_id = f.id
			LEFT JOIN #__fabrik_tables AS t2 ON t2.db_table_name = t.db_table_name
			LEFT JOIN #__fabrik_formgroup AS fg2 ON fg2.form_id = t2.form_id
			LEFT JOIN #__fabrik_elements AS e ON e.group_id = fg2.group_id
			WHERE fg.group_id = ".$db->Quote($element->group_id)."
			AND e.name = ".$db->Quote($element->name);
		$db->setQuery($query);
		$ids = $db->loadResultArray();
		return $ids;
	}
}
?>