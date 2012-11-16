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


/**
 * @package		Joomla
 * @subpackage	Fabrik
 */

class FabrikControllerGroup extends JController
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
	}

	/**
	 * Edit a connection
	 */

	function edit()
	{
		$user	  = &JFactory::getUser();
		$db =& JFactory::getDBO();
		$row =& JTable::getInstance('Group', 'FabrikTable');
		if ($this->_task == 'edit') {
			$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
			$cid	= array((int) $cid[0]);
		} else {
			$cid	= array(0);
		}

		$row->load($cid[0]);
		if ($cid) {
			$row->checkout( $user->get('id'));
		}
		$model		= &$this->getModel( 'Group');

		// Create the form
		$form = new JParameter( '', JPATH_COMPONENT.DS.'models'.DS.'group.xml');
		$form->loadINI( $row->attribs);

		require_once(JPATH_COMPONENT.DS.'views'.DS.'group.php');
		FabrikViewGroup::edit($row, $form);
	}

  /**
   * cancel editing
   */

  function cancel()
  {
    JRequest::checkToken() or die('Invalid Token');
  	$row 		=& JTable::getInstance('Group', 'FabrikTable');
  	$id 		= JRequest::getInt('id', 0, 'post');
  	$row->load($id);
  	$row->checkin();
  	$this->setRedirect('index.php?option=com_fabrik&c=group');
  }

	/**
	 * Save group
	 */

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$db =& JFactory::getDBO();

		$row =& JTable::getInstance('Group', 'FabrikTable');

		$post	= JRequest::get('post');
		if (!$row->bind($post)) {
			return JError::raiseWarning(500, $row->getError());
		}

		// 	save params
		$params = new fabrikParams($row->attribs, JPATH_COMPONENT.DS.'xml'.DS.'group.xml');
		$row->attribs = $params->updateAttribsFromParams(JRequest::getVar('params', array(), 'post', 'array'));


		if (!$row->store()) {
			return JError::raiseWarning(500, $row->getError());
		}
		$row->checkin();
		$task = JRequest::getCmd('task');

		//update the group's element table definitons, currently used for database join element
		//which if part of a repeat group should revert to varchar(255) regardless of what table
		//field it joins to
		$groupModel =& JModel::getInstance('Group', 'FabrikModel');
		$groupModel->setId($row->id);
		$elements =& $groupModel->getMyElements();
		$c = 0;
		foreach ($elements as $element) {
			if ($c === 0) {
				$tableModel =& $element->getTableModel();
				// $$$ hugh - this was screwing up if it's a joined group, tried to modify
				// columns on main form's table instead of joined table!
				// So if it's a joined group, need to get table name from join model
				if ($groupModel->isJoin()) {
					$joinModel =& $groupModel->getJoinModel();
					$joinTable =& $joinModel->getJoin();
					$tableName = $joinTable->table_join;
				}
				else {
					$table =& $tableModel->getTable();
					$tableName = $table->db_table_name;
				}
				// $$$ hugh - at the moment, connection details will be same even it's a join, as
				// we don't do cross database joins.  But at some point may need to modify this
				// this bit as well to get connection from join.
				$fabrikDb =& $tableModel->getDb();
		  }
		  $elementName = $element->getElement()->name;
		  $objtype = $element->getFieldDescription();
		  // $$$ hugh - changed this to use $tableName derived above instead of $table->db_table_name
		  $query = "ALTER TABLE " . FabrikString::safeColName($tableName) . " CHANGE " . $fabrikDb->nameQuote($elementName) . " " . $fabrikDb->nameQuote($elementName) ." $objtype";
		  $fabrikDb->setQuery($query);
		  $fabrikDb->query();
		   $c ++;
		}
		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_fabrik&c=group&task=edit&cid[]='. $row->id;
				break;

			case 'save':
			default:
				$link = 'index.php?option=com_fabrik&c=group';
				break;
		}
		$cache = & JFactory::getCache('com_fabrik');
		$cache->clean();
		$this->setRedirect($link, JText::_('GROUP SAVED'));
	}

	/**
	 * Publish a group
	 */

	function publish()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=group');

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

		$query = 'UPDATE #__fabrik_groups'
		. ' SET state = ' . (int) $publish
		. ' WHERE id IN ( '. $cids.'  )'
		. ' AND ( checked_out = 0 OR ( checked_out = ' .(int) $user->get('id'). ') )'
		;
		$db->setQuery($query);
		if (!$db->query()) {
			return JError::raiseWarning(500, $row->getError());
		}
		$this->setMessage( JText::sprintf( $publish ? 'ITEMS PUBLISHED' : 'ITEMS UPUBLISHED', $n));
	}

	/**
	 * Display the list of groups
	 */

	function display()
	{
		$app =& JFactory::getApplication();
		$db =& JFactory::getDBO();
		$user =& JFactory::getUser();
		$context			= 'com_fabrik.group.list.';
		$limit				= $app->getUserStateFromRequest( $context.'limit', 'limit', $app->getCfg('list_limit'), 'int');
		$limitstart 		= $app->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int');
		$filter_order		= $app->getUserStateFromRequest( $context.'filter_order',		'filter_order',		'g.name',	'cmd');
		$filter_order_Dir	= $app->getUserStateFromRequest( $context.'filter_order_Dir',	'filter_order_Dir',	'',			'word');

		$filter_group 	= $app->getUserStateFromRequest(  $context.'filter_group', 'filter_group', '');
		$filter_formId 	= $app->getUserStateFromRequest(  $context.'filter_formId', 'filter_formId', 0);

		$lists = array();
		$where = array();

		if ($filter_group != '') {
			$where[] = " (g.label LIKE '%$filter_group%' || g.label LIKE '%$filter_group') || (g.name LIKE '%$filter_group%' || g.name LIKE '%$filter_group') ";
		}
		if ($filter_formId >= 1) {
			$where[] = " fg.form_id = '$filter_formId' ";
		}

		/*if ($user->gid <= 24) {
	    	$where[] = " f.private = '0'";
	    }*/
		$where		= count($where ) ? ' WHERE ' . implode(' AND ', $where ) : '';
		$orderby	= ' ORDER BY '. $filter_order .' '. $filter_order_Dir;

		// get the total number of records
		$db->setQuery("SELECT COUNT(g.id) FROM #__fabrik_groups AS g RIGHT JOIN #__fabrik_formgroup AS fg ON g.id = fg.group_id ". $where);
		$total = $db->loadResult();

		jimport('joomla.html.pagination');
		$pageNav = new JPagination($total, $limitstart, $limit);

		$sql = "SELECT *, u.name AS editor, g.id AS id, g.state as state, g.name FROM #__fabrik_groups AS g
			LEFT JOIN #__fabrik_formgroup AS fg ON g.id = fg.group_id " .
			"LEFT JOIN #__users AS u ON u.id = g.checked_out" .
			"\n LEFT JOIN #__fabrik_forms AS f ON f.id = fg.form_id " .
			"\n $where $orderby";
		$db->setQuery($sql, $pageNav->limitstart, $pageNav->limit);

		$rows = $db->loadObjectList();
		if ($db->getErrorMsg() != '') {
			JError::raiseError(500, $db->getErrorMsg());
		}
		$arElcount = array();

		$db->setQuery("SELECT COUNT(id) AS count, group_id FROM #__fabrik_elements group by group_id");
		$elementcount = $db->loadObjectList('group_id');

		for ($i=0; $i < count($rows); $i++) {
			$rows[$i]->_elementCount = @$elementcount[$rows[$i]->id]->count;
		}

		$groupModel =& JModel::getInstance('Group', 'FabrikModel');
		$lists['groupId'] =  JText::_('GROUP') . ': <input type="text" value="' . $filter_group . '" name="filter_group" onblur="document.adminForm.submit();" />';

		$formModel =& JModel::getInstance('Form', 'FabrikModel');
		$lists['formId'] = $formModel->makeDropDown( $filter_formId, "- " .JText::_('FORMS') . " -");

		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;
		$lists['order']		= $filter_order;

		require_once(JPATH_COMPONENT.DS.'views'.DS.'group.php');
		FabrikViewGroup::show($rows, $pageNav, $lists);
	}

	function copy()
	{
		$cid		= JRequest::getVar('cid', null, 'post', 'array');
		$db =& JFactory::getDBO();
		$db->setQuery("SELECT id, label FROM #__fabrik_groups WHERE id IN (" . implode(',', $cid ) . ")");
		$rows =& $db->loadObjectList();
		require_once(JPATH_COMPONENT.DS.'views'.DS.'group.php');
		FabrikViewGroup::confirmCopyElements($rows);
	}

	/**
	 * copy a group
	 * @param int group id
	 */

	function doCopy()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=group');
		$allgroups = JRequest::getVar('gid', array(), 'post', 'array');
		$cid		= JRequest::getVar('cid', array(), 'post', 'array');
		$db			=& JFactory::getDBO();
		$group	=& JTable::getInstance('Group', 'FabrikTable');
		$groupModel =& JModel::getInstance('Group', 'FabrikModel');
		$user		= &JFactory::getUser();
		$n			= count($cid);

		if ($n > 0)
		{
			foreach ($allgroups as $id)
			{
				if ($group->load((int)$id))
				{

					$group->id				= 0;
					$group->label	= 'Copy of ' . $group->label;
					$group->name	= 'Copy of ' . $group->name;
					unset($group->join_id);
					if (!$group->store()) {
						return JError::raiseWarning(500, $group->getError());
					}

				//load group model and get elements
					if (in_array($id, $cid)) {
						$tmpModel = clone( $groupModel);
						$tmpModel->setId($id);
						$elements =& $tmpModel->getMyElements();
						foreach ($elements as $elementModel) {
							$element =& $elementModel->getElement();
            	$copy = $elementModel->copyRow($element->id, '', $group->id);
						}
					}
				}
				else {
					return JError::raiseWarning(500, $group->getError());
				}
			}
		}
		else {
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}
		$this->setMessage( JText::sprintf( 'ITEMS COPIED', $n));
	}

	/**
	 * delete group
	 */

	function remove()
	{

		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=group');

		// Initialize variables
		$db		=& JFactory::getDBO();
		$cid	= JRequest::getVar('cid', array(), 'post', 'array');
		$n		= count($cid);
		JArrayHelper::toInteger($cid);
		if ($n)
		{
			$query = 'DELETE FROM #__fabrik_groups'
			. ' WHERE id = ' . implode(' OR id = ', $cid )
			;
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500, $db->getError());
			}
		}

		$this->setMessage( JText::sprintf( 'ITEMS REMOVED', $n));

	}
}
?>