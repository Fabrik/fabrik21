<?php
/**
 * @version
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

/**
 * @package		Joomla
 * @subpackage	Fabrik
 * @license		GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'menu.php');
require_once(COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'adminhtml.php');
require_once(COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'fabrik.php');

/**
 * @package		Joomla
 * @subpackage	Fabrik
 */

class FabrikControllerTable extends JController
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
		$this->registerTask('menuLinkTable', 'save');
		$this->registerTask('unpublish',	'publish');
		$this->registerTask('go2menu', 'save');
		$this->registerTask('go2menuitem', 'save');
		$this->registerTask('filter', 'viewTable');
		$this->registerTask('navigate', 'viewTable');
	}

	/**
	 * reorder the data in the table
	 * @return null
	 */
	function order()
	{
		$modelName	= JRequest::getVar('view', 'table', 'default', 'cmd');
		$model	= &$this->getModel( $modelName);
		$model->setId(JRequest::getInt('tableid'));
		$model->setOrderByAndDir();
		$this->viewTable();
	}

	/**
	 * set up the import csv file form
	 */

	function import()
	{
		require_once(JPATH_COMPONENT.DS.'views'.DS.'table.php');
		$connModel =& $this->getModel( 'Connection');
		$realCnns 				= $connModel->getConnections();
		$connection 	= $connModel->getConnectionsDd($realCnns, '' , 'connection_id', '');
		FabrikViewTable::import( $connection);
	}

	/**
	 * process the uploaded csv file, store its data in the session
	 * and set up a form to ask the user what they want to do with
	 * each of the imported csv elements
	 */

	function importChooseElements()
	{

		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		require_once(JPATH_COMPONENT.DS.'views'.DS.'table.php');
		$db_table_name = JRequest::getVar('db_table_name');
		$label = JRequest::getVar('label');

		$model = &$this->getModel( 'Importcsv');

		$tableModel	=& $this->getModel('Table');
		$tableModel->setId(JRequest::getVar('tableid'));
		$this->table 	=& $tableModel->getTable();

		$tmp_file = $model->checkUpload();
		if ($tmp_file === false) {
			$this->import();
		}

		//$userfile = JRequest::getVar('userfile', null, 'files');

		$model->readCSV( $tmp_file );
		$model->findExistingElements($tableModel);
		$lists = array();

		$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$pluginManager->loadPlugInGroup('element');

		$lists['elementtype'] = $pluginManager->getElementTypeDd('fabrikfield', 'elementtype[]');
		FabrikViewTable::importChooseElements($model->newHeadings, $lists);
	}

	/**
	 * now we have the uploaded csv file in the session and the results of what the
	 * user wants to do with each field, lets process this thing!
	 * Creates a db table
	 * Creates fabrik table/form/group/elements as per selected choices
	 *
	 */

	function doImport()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$cnn = JModel::getInstance('Connection', 'FabrikModel');#
		$cnnid = JRequest::getInt('connection_id');
		$cnn->setId($cnnid);
		$db =& $cnn->getDb();
		$db_table_name = JRequest::getVar('db_table_name');
		$keys = JRequest::getVar('primarykey', array());
		$autoinc = JRequest::getVar('autoinc', array());
		$fields = JRequest::getVar('field');
		JRequest::setVar('elementlabels', JRequest::getVar('label'));
		$addkey = JRequest::getInt('addkey');
		if ($addkey == 1) {
			foreach ($fields as $key => $val) {
				$keys[$val][0] = 0;
			}
			$newkey = 'id';
			if (in_array($newkey, $fields)) {
				$newkey .= rand(0, 20);
			}
			$keys[$newkey][0] = 1;
			$fields[] = $newkey;
			$autoinc[$newkey][0] = 1;
		}

		// $$$ hugh - testing
		// normalize the names
		$safe_fields = array();

		//$$$rob already normalized in form, but lets do it here as well just in case
		foreach ($fields as $key => $val) {
			$safe_fields[$key] = FabrikString::clean($val);
		}
		$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');

		$elementtypes = JRequest::getVar('elementtype', array());
		if ($addkey == 1) {
			$elementtypes[] = 'fabrikinternalid';
		}

		$pkey = '';
		$query = "CREATE TABLE IF NOT EXISTS `$db_table_name` (";
		for ($i=0; $i<count($fields); $i++) {
			//sanitize field names
			$fields[$i] = strtolower(str_replace(' ', '', $fields[$i]));
			$plugin =& $pluginManager->getPlugIn($elementtypes[$i], 'element');
			if ($safe_fields[$i] !== '') {
				if ($keys[$fields[$i]][0] == 1) {
					$inc = $autoinc[$fields[$i]][0];
					$k = $fields[$i];
					$query .= " " . $db->nameQuote($safe_fields[$i]) . " INT(6) ";
					if ($inc == 1) {
						$query .= "NOT NULL AUTO_INCREMENT";
					}
					$pkey = ", PRIMARY KEY ( ".$db->nameQuote($k)." )";
				} else {
					$query .= " `$safe_fields[$i]` " . $plugin->getFieldDescription() . " NOT NULL";
				}
				$query .=",\n";
			}
		}

		//  no pkey select so make one
		if ($pkey == '') {
			$query .= " `id` INT(6) NOT NULL AUTO_INCREMENT";
			$pkey = ", PRIMARY KEY ( `id` )";
		}

		$query = FabrikString::rtrimword( $query, ",\n");
		$query .= $pkey;
		$query .=  ");";


		$db->setQuery($query);
		if (!$db->query()) {
			return JError::raiseWarning(500, $db->getErrorMsg());
		}
		$user =& JFactory::getUser();
		$tableModel		=& $this->getModel( 'Table');

		//set up some default data for the table to use when saving itself
		JRequest::setVar('id', 0);
		JRequest::setVar('state', 1);
		JRequest::setVar('filter_action', 'onchange');
		JRequest::setVar('created_by', $user->get('id'));
		JRequest::setVar('created_by_alias', $user->get('username')	);
		JRequest::setVar('label', JRequest::getVar('db_table_label'));
		JRequest::setVar('attribs', $tableModel->getDefaultAttribs());

		$tableModel->save();

		//insert the date
		$session =& JFactory::getSession();
		$csvdata = $session->get('com_fabrik.csvdata');
		foreach ($csvdata as $data) {
			$aRow = array();
			for ($i=0; $i < count($data); $i++) {
				//$$$rob if there is a trailing comma in the CSV line a blank piece of data could be
				//added causing an error/warning
				if (array_key_exists($i, $fields)) {
					$aRow[$fields[$i]] = $data[$i];
				}
			}

			$aRow = $tableModel->removeTableNameFromSaveData($aRow);
			$tableModel->storeRow($aRow, 0);
			$addedCount ++;
		}
		//redirect
		$this->setRedirect("index.php?option=com_fabrik&c=table", JText::_('TABLE_IMPORTED'));
	}

	/**
	 * delete table rows
	 */

	function delete()
	{
		$model =& JModel::getInstance('Table', 'FabrikModel');
		$tableid = JRequest::getInt('tableid');
		$model->setId($tableid);
		$model->getTable();
		$ids = JRequest::getVar('ids');

		$limitstart = JRequest::getVar('limitstart'. $tableid);
		$length = JRequest::getVar('limit' . $tableid);
		$total = $model->getTotalRecords() - count($ids);
		$ok = $model->deleteRows($ids);

		if ($total >= $limitstart) {
			$newlimitstart = $limitstart - $length;
			if ($newlimitstart < 0) {
				$newlimitstart = 0;
			}
			$app 				=& JFactory::getApplication();
			$context					= 'com_fabrik.table'.$tableid.'.list.';
			$app->setUserState($context.'limitstart'.$tableid, $newlimitstart);
		}

		if ($ok) {
			$msg = JText::_('RECORDS DELETED');
		} else {
			$msg = '';
		}
		$link = "index.php?option=com_fabrik&c=table&task=viewTable&cid[]=".(int)$model->_id;
		$this->setRedirect($link, $msg);
	}

	/**
	 * empty table data
	 */

	function doempty()
	{
		$model	= &$this->getModel( 'table');
		$model->truncate();
		$msg = JText::_('Table emptied');
		$link = "index.php?option=com_fabrik&c=table&task=viewTable&cid[]=".(int)$model->_id;
		$this->setRedirect($link, $msg);
	}

	/**
	 * run a table plugin
	 */

	function tableplugin()
	{
		$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
		if(is_array($cid)) {$cid = $cid[0];}
		$model =& JModel::getInstance('Table', 'FabrikModel');
		$model->setId(JRequest::getInt('tableid', $cid));
		$msg = $model->processPlugin();
		$this->setRedirect('index.php?option=com_fabrik&c=table&task=viewTable&cid[]='.JRequest::getInt('tableid', $cid));
		$this->setMessage( $msg);
	}

	/**
	 *
	 */

	function viewTable()
	{
		$document =& JFactory::getDocument();
		// $$$ hugh - bandaid for CSV export on backend, 'cid' isn't set
		// but tableid is ...
		$default_cid = JRequest::getInt('tableid', 0);
		$cid	= JRequest::getVar('cid', array($default_cid), 'method', 'array');
		if(is_array($cid)) {$cid = $cid[0];}

		$model =& JModel::getInstance('Table', 'FabrikModel');
		$model->setId(JRequest::getInt('tableid', $cid));
		$model->getTable();
		JRequest::setVar('view', 'Table');

		$viewType	= $document->getType();
		$viewName	= JRequest::getCmd('view', $this->_name);
		$viewLayout	= JRequest::getCmd('layout', 'default');
		$view = & $this->getView($viewName, $viewType, '', array('base_path'=>$this->_basePath));

		$view->setModel( $model, true);

		// Set the layout
		$view->setLayout( $viewLayout);

		$view->display();
	}

	/**
	 * Edit a table
	 */

	function edit()
	{
		$app =& JFactory::getApplication();
		$session =& JFactory::getSession();
		$session->clear('com_fabrik.admin.table.edit.model');
		require_once(JPATH_COMPONENT.DS.'views'.DS.'table.php');
		$user	=& JFactory::getUser();
		$db 	=& JFactory::getDBO();
		$row 	=& JTable::getInstance('table', 'Table');
		$acl 	=& JFactory::getACL();
		$config =& JFactory::getConfig();

		if ($this->_task == 'edit') {
			$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
			$cid	= array((int)$cid[0]);
		} else {
			$cid	= array(0);
		}

		$connectionTables = array();
		//  this only appears if you are automatically creating the table from an existing form - which has no table associated with it
		$fabrikid = JRequest::getVar('fabrikid', '', 'get');
		$row->load($cid[0]);
		$params = new fabrikParams($row->attribs, JPATH_COMPONENT.DS.'model'.DS.'table.xml');
		$lists['tablejoin'] = "";
		//$lists['defaultJoinTables'] = '[]';
		$lists['defaultJoinTables'] = array();
		$lists['linkedtables']  = array();
		$connModel 	=& JModel::getInstance('Connection', 'FabrikModel');
		$model =& JModel::getInstance('Table', 'FabrikModel');
		$model->setId($cid[0]);
		$model->_table =& $row;
		$formModel 	=& $model->getForm();

		$aJoinObjs = array();
		if ($this->_task != 'edit') {
			$row->template 		= 'default';
			$realCnns 				= $connModel->getConnections();

			$defaultCid = '';
			foreach ($realCnns as $realCnn) {
				if ($realCnn->default == 1) {
					$defaultCid = $realCnn->id;
				}
			}
			$javascript 			= "onchange=\"changeDynaList('db_table_name', connectiontables, document.adminForm.connection_id.options[document.adminForm.connection_id.selectedIndex].value, 0, 0);\"";
			$lists['connections'] 	= $connModel->getConnectionsDd($realCnns, $javascript , 'connection_id', $defaultCid);
			$connectionTables 		= $connModel->getConnectionTables($realCnns);

			$javascript 	= '';
			$lists['tablename'] = 	'<select name="db_table_name" id="tablename" class="inputbox" size="1" >';
			if ($defaultCid == '') {
				$lists['tablename'] .= '<option value="" selected="selected">' . JText::_('CHOOSE A CONNECTION FIRST') .'</option>';
			} else {
				foreach ($connectionTables[$defaultCid] as $t) {
					$lists['tablename'] .= '<option value="'.$t->value.'">' . $t->text .'</option>';
				}
			}
			$lists['tablename'] .= '</select>';

			$lists['order_by'][]			= JText::_('AVAILABLE AFTER TABLE SAVED');
			$lists['group_by'] 				= JText::_('AVAILABLE AFTER TABLE SAVED');
			$lists['filter-fields'] 	= JText::_('AVAILABLE AFTER TABLE SAVED');
			$lists['db_primary_key'] 	= JText::_('AVAILABLE AFTER TABLE SAVED');

		} else {
			//if record already exists then you can't change the form or table it points to
			// fail if checked out not by 'me'
			if ($row->checked_out && $row->checked_out != $user->get('id')) {
				$app->redirect('index.php?option=com_fabrik', 'The connection '. $row->description .' is currently being edited by another administrator');
			}
			$row->checkout( $user->get('id'));

			if ($row->connection_id != "-1") {
				$sql = "SELECT description FROM #__fabrik_connections WHERE id = " . (int)$row->connection_id;
				$db->setQuery($sql);
				$lists['connections'] = $db->loadResult();
				$lists['tablename'] = "<input type='hidden' name='db_table_name' value='$row->db_table_name' />$row->db_table_name";
			} else {
				$lists['connections'] = "no database";
				$lists['tablename'] = "no table";
			}

			$lists['connections'] .= "<input type=\"hidden\" value=\"$row->connection_id\" id=\"connection_id\" name=\"connection_id\" />";
			$formModel->setId($row->form_id);
			$formTable =& $formModel->getForm();
			$formModel->getGroupsHiarachy();

			//table join info
			$sql = "SELECT * FROM #__fabrik_joins WHERE table_id = " . (int)$row->id . " AND element_id = 0";
			$db->setQuery($sql);
			$aJoinObjs = $db->loadObjectList();
			$lists['joins'] 		 = &$aJoinObjs;
			$lists['order_by'] = array();
			$orderbys = explode(GROUPSPLITTER2, $row->order_by);

			foreach ($orderbys as $orderby) {
				$lists['order_by'][] = $formModel->getElementList('order_by[]', $orderby, true, false, true);
			}
			$lists['group_by'] 		 = $formModel->getElementList('group_by', $row->group_by, true, false, true);

			//needs to be table.element format for where statement to work
			$formModel->_addDbQuote = true;
			$lists['filter-fields']  = $formModel->getElementList('params[filter-fields][]', '', false, false, true);
			$lists['db_primary_key'] = $formModel->getElementList('db_primary_key', $row->db_primary_key);
			$formModel->_addDbQuote = false;

			//but you can now add table joins
			$connModel->setId($row->connection_id);
			$connModel->getConnection($row->connection_id);

			///load in current connection
			$joinFromTables[] 			= JHTML::_('select.option', '', '-');
			$joinFromTables[] 			= JHTML::_('select.option', $row->db_table_name, $row->db_table_name);
			$lists['defaultjoin'] 	= $connModel->getTableDdForThisConnection('', 'table_join[]', '', 'inputbox table_key');
			$lists['tablejoin'] 		= $connModel->getTableDdForThisConnection('', 'table_join[]', '', 'inputbox table_join_key');

			//make a drop down validation type for each validation
			$aActiveJoinTypes = array();

			if (is_array($aJoinObjs)) {
				for ($ff = 0; $ff < count($aJoinObjs); $ff++) {
					$oJoin = $aJoinObjs[$ff];
					$fields = array();
					$aFields = $model->getDBFields($oJoin->join_from_table);
					foreach ($aFields as $o) {
						if (is_array($o)) {
							foreach ($o as $f) {
								$fields[] = $f->Field;
							}
						} else {
							$fields[] = $o->Field;
						}
					}
					$aJoinObjs[$ff]->joinFormFields = $fields;
					$aFields = $model->getDBFields($oJoin->table_join);
					$fields = array();
					foreach ($aFields as $o) {
						if (is_array($o)) {
							foreach ($o as $f) {
								$fields[] = $f->Field;
							}
						} else {
							$fields[] = $o->Field;
						}
					}
					$aJoinObjs[$ff]->joinToFields = $fields;
				}
				$lists['defaultJoinTables'] = $connModel->getThisTables(true);
			}
		}
		if ($row->id != '') {
			//only existing tables can have a menu linked to them
			$and = "\n AND link LIKE '%index.php?option=com_fabrik%' AND link LIKE '%view=table%'";
			$and .= " AND params LIKE '%tableid=".$row->id."'";
			$menus = FabrikHelperMenu::Links2Menu( 'component', $and);
		} else {
			$menus = null;
		}
		$lists['filter-access'] 	= $this->getFilterACLList($row);
		$lists['menuselect'] 		 	= FabrikHelperMenu::MenuSelect();
		$lists['tableTemplates'] 	= FabrikHelperAdminHTML::templateList('table', 'template', $row->template);

		// make the filter action drop down
		$filterActions[] 			 	= JHTML::_('select.option', 'onchange', JText::_('ON CHANGE'));
		$filterActions[] 			 	= JHTML::_('select.option', 'submitform', JText::_('SUBMIT FORM'));
		$lists['filter_action'] = JHTML::_('select.genericlist',  $filterActions, 'filter_action', 'class="inputbox" size="1" ', 'value', 'text', $row->filter_action);
		//make the order direction drop down
		$orderDir[] 				 		= JHTML::_('select.option', 'ASC', JText::_('ASCENDING'));
		$orderDir[] 				 		= JHTML::_('select.option', 'DESC', JText::_('DESCENDING'));
		$orderdirs = explode(GROUPSPLITTER2, $row->order_dir);
		$lists['order_dir'] = array();
		foreach ($orderdirs as $orderdir) {
			$lists['order_dir'][] 		= JHTML::_('select.genericlist', $orderDir, 'order_dir[]', 'class="inputbox" size="1" ', 'value', 'text', $orderdir);
		}

		$linkedTables = $model->getJoinsToThisKey();
		$aExisitngLinkedTables 	= $params->get('linkedtable', '', '_default', 'array');

		$aExisitngLinkedForms 	= $params->get('linkedform', '', '_default', 'array');
		$aExistingTableHeaders 	= $params->get('linkedtableheader', '', '_default', 'array');

		$aExistingFormHeaders 	= $params->get('linkedformheader', '', '_default', 'array');

		$linkedform_linktype 		= $params->get('linkedform_linktype', '', '_default', 'array');
		$linkedtable_linktype 	= $params->get('linkedtable_linktype', '', '_default', 'array');

		$tableLinkTexts 					= $params->get('linkedtabletext', '', '_default', 'array');
		$formLinkTexts 					= $params->get('linkedformtext', '', '_default', 'array');

		$lists['linkedtables']  = array();
		$f = 0;
		$used = array();
		foreach ($linkedTables as $linkedTable) {
			$key = $linkedTable->table_id.'-'.$linkedTable->form_id.'-'.$linkedTable->element_id;
			if (!array_key_exists($f, $aExisitngLinkedTables)) {
				$aExisitngLinkedTables[$f] = '0';
			}

			if (!array_key_exists($f, $linkedtable_linktype)) {
				$linkedtable_linktype[$f] = '0';
			}

			//fiddle ordering
			$index = array_search($key, $aExisitngLinkedTables);
			$index = $index === false ? $f : $index;

			if (!array_search($key, $aExisitngLinkedTables)) {
				for ($fcounter = 0; $fcounter <= count($linkedTables); $fcounter ++) {
					if (!in_array($fcounter, $used)) {
						$index = $fcounter;
						break;
					}
				}
			}
			$used[] = $index;

			$yeschecked = (in_array($linkedTable->db_table_name, $aExisitngLinkedTables) || JArrayHelper::getValue($aExisitngLinkedTables, $index, 0) != '0') ? 'checked="checked"' : $checked = '';
			$nochecked = ( $yeschecked == '') ? 'checked="checked"' : $checked = '';

			$el =  '<label><input name="params[linkedtable][' . $key . ']" value="0" ' .$nochecked . ' type="radio">' . JText::_('NO') . '</label>';
			$el.=  '<label><input name="params[linkedtable][' . $key . ']" value="' . $key .'" ' .$yeschecked . ' type="radio">' . JText::_('YES') . '</label>';

			$yeschecked = (in_array($linkedTable->db_table_name, $linkedtable_linktype) || JArrayHelper::getValue($linkedtable_linktype, $index, 0) != '0')? 'checked="checked"': $checked = '';
			$nochecked = ( $yeschecked == '') ? 'checked="checked"' : $checked = '';

			$linkType1 =  '<label><input name="params[linkedtable_linktype][' . $key . ']" value="0" ' .$nochecked . ' type="radio">' . JText::_('NO') . '</label>';
			$linkType1.=  '<label><input name="params[linkedtable_linktype][' . $key . ']" value="' . $key .'" ' .$yeschecked . ' type="radio">' . JText::_('YES') . '</label>';

			$tableHeader = '<input name="params[linkedtableheader][' . $key . ']" value="' . @$aExistingTableHeaders[$index] .'" size="16" >';
			$label = str_replace(array("\n", "\r", "<br>", "</br>") , '', $linkedTable->tablelabel);
			$hover = JText::_('ELEMENT') . ': ' . $linkedTable->element_label . " [$linkedTable->plugin]. {tmpl key =".$linkedTable->element_id."_table_heading}";

			$tableLinkText = '<input name="params[linkedtabletext][' . $key . ']" value="' . @$tableLinkTexts[$index] .'" size="16" >';
			$linkedArray = array($label, $hover, $el, $tableHeader, $linkType1, $tableLinkText);
			$lists['linkedtables'][$index] = $linkedArray;

		}
		ksort($lists['linkedtables']);

		$lists['linkedforms']  = array();
		$f = 0;
		$used = array();
		/***/

		foreach ($linkedTables as $linkedTable) {
			$key = $linkedTable->table_id.'-'.$linkedTable->form_id.'-'.$linkedTable->element_id;

			if (!array_key_exists($f, $aExisitngLinkedForms)) {
				$aExisitngLinkedForms[$f] = '0';
			}

			if (!array_key_exists($f, $linkedform_linktype)) {
				$linkedform_linktype[$f] = '0';
			}

			//fiddle ordering
			$index = array_search($key, $aExisitngLinkedForms);
			$index = $index === false ? $f : $index;

			if (!array_search($key, $aExisitngLinkedForms)) {
				for ($fcounter = 0; $fcounter <= count($linkedTables); $fcounter ++) {
					if (!in_array($fcounter, $used)) {
						$index = $fcounter;
						break;
					}
				}
			}

			$used[] = $index;

			$yeschecked = (in_array($linkedTable->db_table_name, $aExisitngLinkedForms) || JArrayHelper::getValue($aExisitngLinkedForms, $index, 0) != '0')? 'checked="checked"': $checked = '';
			$nochecked = ( $yeschecked == '') ? 'checked="checked"' : $checked = '';

			$el2 =  '<label><input name="params[linkedform][' . $key . ']" value="0" ' .$nochecked . ' type="radio">' . JText::_('NO') . '</label>';
			$el2.=  '<label><input name="params[linkedform][' . $key . ']" value="' . $key .'" ' .$yeschecked . ' type="radio">' . JText::_('YES') . '</label>';

			$yeschecked = (in_array($linkedTable->db_table_name, $linkedform_linktype) || JArrayHelper::getValue($linkedform_linktype, $index, 0) != '0')? 'checked="checked"': $checked = '';
			$nochecked = ( $yeschecked == '') ? 'checked="checked"' : $checked = '';

			$linkType2 =  '<label><input name="params[linkedform_linktype][' . $key . ']" value="0" ' .$nochecked . ' type="radio">' . JText::_('NO') . '</label>';
			$linkType2.=  '<label><input name="params[linkedform_linktype][' . $key . ']" value="' . $key .'" ' .$yeschecked . ' type="radio">' . JText::_('YES') . '</label>';

			$formHeader = '<input name="params[linkedformheader][' . $key . ']" value="' . @$aExistingFormHeaders[$index] .'" size="16" >';
			$label = str_replace(array("\n", "\r", "<br>", "</br>") , '', $linkedTable->tablelabel);

			$formLinkText = '<input name="params[linkedformtext][' . $key . ']" value="' . @$formLinkTexts[$index] .'" size="16" >';
			$linkedArray = array($label, $el2, $formHeader, $linkType2, $formLinkText);
			$linkedArray['formhover'] = JText::_('ELEMENT') . ': ' . $linkedTable->element_label . " [$linkedTable->plugin]. {tmpl key =".$linkedTable->element_id."_form_heading}";
			$lists['linkedforms'][$index] = $linkedArray;

		}
		ksort($lists['linkedforms']);

		/*****/

		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikModel');
		$pluginManager->getPlugInGroup('table');

		// Create the form (publish dates should be stored as UTC)
		$form = new JParameter('', JPATH_COMPONENT.DS.'models'.DS.'table.xml');
		$form->bind($row);
		$form->set('created', JHTML::_('date', $row->created, '%Y-%m-%d %H:%M:%S'));
		$form->set('publish_up', JHTML::_('date', $row->publish_up, '%Y-%m-%d %H:%M:%S'));

		if ($cid[0] == 0 || $form->get('publish_down') == '' || $form->get('publish_down') ==  $db->getNullDate()) {
			$form->set('publish_down', JText::_('NEVER'));
		} else {
			$form->set('publish_down', JHTML::_('date', $row->publish_down, '%Y-%m-%d %H:%M:%S'));
		}
		$form->loadINI($row->attribs);
		$session->set('com_fabrik.admin.table.edit.model', $model);
		FabrikViewTable::edit($row, $lists, $connectionTables, $menus, $fabrikid, $params, $pluginManager, $model, $form);
	}


	function getFilterACLList(&$row)
	{
		if (defined('_JACL')) {
			$_JACL =& JACLPlus::getJACL();
			$where = "\n WHERE id IN (0,1,2)";
			$db =& JFactory::getDBO();
			if ($_JACL->enable_jaclplus) {
				$user =& JFactory::getUser();
				if(is_numeric($value)) $where = " OR id = '".(int) $value."'";
				else $where = "";
				switch ($_JACL->publish_alstype) {
					case "1" :
						$where = (( $user->get('gid')==25 ) ? "" : "\n WHERE id IN (".$user->get('jaclplus', '0').")".$where);
						break;
					case "2" :
						$where = (( $user->get('gid')==25 ) ? "" : "\n WHERE id NOT IN (0,1,2)".$where);
						break;
					case "3" :
						$where = (( $user->get('gid')==25 ) ? "" : "\n WHERE id IN (".$user->get('jaclplus', '0').") AND id NOT IN (0,1,2)".$where);
						break;
					case "4" :
						$where = (( $user->get('gid')==25 ) ? "" : "\n WHERE id IN (". $db->getEscaped( $_JACL->publish_jaclplus ) .")".$where);
						break;
					case "0" :
					default :
						$where = "";
						break;
				}
			}
			$query = 'SELECT id AS value, name AS text'
			. ' FROM #__groups'
			. $where
			. ' ORDER BY id'
			;
			$db->setQuery($query);
			$gtree = $db->loadObjectList();

		} else {
			$acl 	=& JFactory::getACL();
			$gtree = $acl->get_group_children_tree( null, 'USERS', false);
			$optAll = array(JHTML::_('select.option', "26", 'Nobody'));
			$gtree = array_merge( $optAll, $gtree);
		}
		$optAll = array(JHTML::_('select.option', '0', ' - Everyone'));
		$gtree = array_merge( $optAll, $gtree);

		return JHTML::_('select.genericlist', $gtree, 'params[filter-access][]', 'class="inputbox" size="1"', 'value', 'text', (intval( $row->access) == 0)? 29: intval( $row->access));

	}

	/**
	 * cancel editing
	 */

	function cancel()
	{
		JRequest::checkToken() or die('Invalid Token');
		$row 		=& JTable::getInstance('table', 'Table');
		$id 		= JRequest::getInt('id', 0, 'post');
		$row->load($id);
		$row->checkin();
		$this->setRedirect('index.php?option=com_fabrik&c=table');
	}

	/**
	 * Save a table
	 */

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$model =& JModel::getInstance('table', 'FabrikModel');
		$ok = $model->save();
		if (JError::isError($ok)) {
			$msg = '';
		} else {
			$msg = JText::_('TABLE SAVED');
			$row =& $model->getTable();
			$task = JRequest::getCmd('task');
		}

		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_fabrik&c=table&task=edit&cid[]='. $row->id;
				break;

			case 'save':
			default:
				$link = 'index.php?option=com_fabrik&c=table';
				break;
		}
		$cache = & JFactory::getCache('com_fabrik');
		$cache->clean();

		$this->setRedirect($link, $msg);
	}

	/**
	 * un/Publish a table
	 */

	function publish()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=table');

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

		$query = 'UPDATE #__fabrik_tables'
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
	 *
	 */

	function showlinkedelements()
	{
		$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
		$row 	=& JTable::getInstance('table', 'Table');
		$row->load($cid[0]);
		$formId = $row->form_id;
		$formModel = JModel::getInstance('Form', 'FabrikModel');
		$formModel->setId($formId);
		$form =& $formModel->getForm();
		$formGroupEls = $formModel->getFormGroups( false);
		require_once(JPATH_COMPONENT.DS.'views'.DS.'table.php');
		FabrikViewTable::showTableDetail( $form, $formGroupEls);
	}

	/**
	 * Display the list of tables
	 */

	function display()
	{
		$app 							=& JFactory::getApplication();
		$db 							=& JFactory::getDBO();
		$document 				=& JFactory::getDocument();
		$user 						=& JFactory::getUser();
		$newFilterTable 	= JRequest::getVar('filter_table');

		$document->addStyleDeclaration("	.icon-32-search 		{ background-image: url(templates/".$app->getTemplate()."/images/toolbar/icon-32-search.png); }");

		$context					= 'com_fabrik.table.list.';
		$filter_order			= $app->getUserStateFromRequest($context.'filter_order',		'filter_order',		't.label',	'cmd');
		$filter_order_Dir	= $app->getUserStateFromRequest($context.'filter_order_Dir',	'filter_order_Dir',	'',			'word');
		$limit						= $app->getUserStateFromRequest($context.'limit', 'limit', $app->getCfg('list_limit'), 'int');
		$limitstart 			= $app->getUserStateFromRequest($context.'limitstart', 'limitstart', 0, 'int');
		$filter_table 		= $app->getUserStateFromRequest($context."filter_table", 'filter_table', '');
		$selPackage   		= $app->getUserStateFromRequest($context."package", 'packages', '');

		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;
		$lists['order']			= $filter_order;

		$where = array();
		if ($selPackage != '') {
			$db->setQuery("SELECT tables FROM #__fabrik_packages WHERE id = " .(int)$selPackage);
			$tables = $db->loadResult();
			echo $db->getErrorMsg();
			if ($tables != '') {
				$where[] = " #__fabrik_tables.id IN (" .  $tables . ") ";
			} else {
				$where[] = " #__fabrik_tables.id IN (0) ";
			}
		}

		if ($filter_table != '') {
			$where[] = " label LIKE '%$filter_table%' ";
		}

		if ($user->gid <= 24) {
			$where[] = " private = '0'";
		}
		$where		= count($where) ? ' WHERE ' . implode(' AND ', $where) : '';
		$orderby	= ' ORDER BY '. $filter_order .' '. $filter_order_Dir;

		// get the total number of records
		$db->setQuery("SELECT count(*) FROM #__fabrik_tables $where");
		$total = $db->loadResult();
		echo $db->getErrorMsg();

		jimport('joomla.html.pagination');
		$pageNav = new JPagination($total, $limitstart, $limit);

		$sql = "SELECT *, u.name AS editor, t.id AS id FROM #__fabrik_tables AS t" .
			"\n LEFT JOIN #__users AS u ON t.checked_out = u.id " .
			" $where $orderby";
		$db->setQuery($sql, $pageNav->limitstart, $pageNav->limit);

		$rows = $db->loadObjectList();
		$lists['filter_table'] =  '<input type="text" value="' . $filter_table . '" name="filter_table" onblur="document.adminForm.submit();" />';

		//get list of packages
		$db->setQuery("SELECT id AS value, label AS text FROM #__fabrik_packages");
		$packages =  array_merge( array(JHTML::_('select.option', '', '- ' . JText::_('SELECT PACKAGE') . ' -')), $db->loadObjectList());
		$lists['packages'] = JHTML::_('select.genericlist',  $packages, 'packages', 'class="inputbox" onchange="document.adminForm.submit();"','value','text',  $selPackage);

		$db->setQuery("SELECT DISTINCT(t.id) AS id, fg.group_id AS group_id
FROM #__fabrik_tables AS t inner
JOIN #__fabrik_formgroup AS fg ON t.form_id = fg.form_id");
		$lists['table_groups'] = $db->loadObjectList('id');
		$format = JRequest::getVar('format', 'html');
		if ($format == 'csv') {

			$this->tableid = JRequest::getVar('tableid', 0);
			$tableModel =& JModel::getInstance('Table', 'FabrikModel');
			$tableModel->setId($this->tableid);
			$this->table =& $tableModel->getTable();
			$document =& JFactory::getDocument();
			$viewName	= JRequest::getVar('view', 'form', 'default', 'cmd');
			$viewType	= $document->getType();
			// Set the default view name from the Request
			$view = &$this->getView($viewName, $viewType);
			$view->setModel($tableModel, true);
			$view->display();

		} else {
			require_once(JPATH_COMPONENT.DS.'views'.DS.'table.php');
			FabrikViewTable::show($rows, $pageNav, $lists);
		}

	}

	/**
	 * ask what name to rename things to
	 *
	 */

	function copy()
	{
		$cid = JRequest::getVar('cid', null, 'post', 'array');
		$model =& JModel::getInstance('table', 'FabrikModel');
		$n = count($cid);
		$tables = array();
		if ($n > 0)
		{
			foreach ($cid as $id)
			{
				$model->setId($id);
				$table =& $model->getTable();
				$formModel = $model->getForm();
				$row = new stdClass();
				$row->id = $id;
				$row->formid = $table->form_id;
				$row->label = $table->label;
				$row->formlabel = $formModel->getForm()->label;
				$groups = $formModel->getGroupsHiarachy();
				$row->groups = array();
				foreach ($groups as $group) {
					$grouprow = new stdClass();
					$g = $group->getGroup();
					$grouprow->id = $g->id;
					$grouprow->name = $g->name;
					$row->groups[] = $grouprow;
				}
				$tables[] = $row;
			}
			require_once(JPATH_COMPONENT.DS.'views'.DS.'table.php');
			FabrikViewTable::copyRename($tables);
		}
		else {
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}
	}

	/**
	 * copy a table
	 */

	public function doCopy()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$this->setRedirect('index.php?option=com_fabrik&c=table');
		$cid = JRequest::getVar('cid', null, 'post', 'array');
		$model =& JModel::getInstance('table', 'FabrikModel');
		$n = count($cid);

		if ($n > 0)
		{
			foreach ($cid as $id)
			{
				$model->setId($id);
				$table =& $model->getTable();
				$ok = $model->copy();
				if (JError::isError($ok)) {
					JRequest::set($origRequest);
					return JError::getErrors();
				}
			}
		}
		else {
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}
		JRequest::set($origRequest);
		$this->setMessage(JText::sprintf( 'ITEMS COPIED', $n));
	}

	/**
	 * delete table
	 */

	function remove()
	{
		$cid	= JRequest::getVar('cid', array(), 'post', 'array');
		$db =& JFactory::getDBO();
		$db->setQuery("SELECT db_table_name FROM #__fabrik_tables WHERE id IN(". implode(",", $cid) . ")");
		$names = $db->loadResultArray();
		require_once(JPATH_COMPONENT.DS.'views'.DS.'table.php');
		FabrikViewTable::askDeleteTableMethod( $names);
	}

	/**
	 *
	 *
	 */

	function doRemove()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$deleteMethod = JRequest::getInt('deleteMethod', 0);

		$dropDbTable = JRequest::getInt('droptable', 0);
		// Initialize variables
		$db		=& JFactory::getDBO();
		$cid	= JRequest::getVar('cid', array(), 'post', 'array');
		$n		= count($cid);
		JArrayHelper::toInteger($cid);

		if ($deleteMethod == 1) {
			$model =& JModel::getInstance('Table', 'FabrikModel');
			foreach ($cid as $id) {
				$model->setId($id);
				$model->_table = null;
				$table = $model->getTable();
				if ($dropDbTable) {
					$model->drop();
				}
				$model->_oForm  = null;
				$formModel =& $model->getForm();
				$formModel->getGroupsHiarachy();
				foreach ($formModel->_groups as $groupModel) {
					$groupModel->setContext($formModel, $model);
					$elementModels =& $groupModel->getMyElements();
					foreach ($elementModels as $elementModel) {
						$db->setQuery("DELETE FROM #__fabrik_elements WHERE id = ".(int)$elementModel->_id);
						$db->query();

						$db->setQuery("DELETE FROM #__fabrik_jsactions WHERE element_id = ".(int)$elementModel->_id);
						$db->query();
					}
					$db->setQuery("DELETE FROM #__fabrik_groups WHERE id = ".(int)$groupModel->_id);
					$db->query();
				}
				$db->setQuery("DELETE FROM #__fabrik_forms WHERE id = ".(int)$formModel->_id);
				$db->query();

				$db->setQuery("DELETE FROM #__fabrik_formgroup WHERE form_id = ".(int)$formModel->_id);
				$db->query();
			}
		}
		if ($n)
		{
			$query = 'DELETE FROM #__fabrik_tables'
			. ' WHERE id = ' . implode(' OR id = ', $cid)
			;
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500, $db->getError());
			}
		}
		$this->setMessage( JText::sprintf( 'ITEMS REMOVED', $n));
		$this->setRedirect('index.php?option=com_fabrik&c=table');
	}

	// Insert the following code in your \administrator\components\com_fabrik\controllers\table.php
	// directly below the original function doRemove

	// updated with diff code from svn 430


	function doCyberTigerRemove()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$recordsDeleteDepth = JRequest::getInt('recordsDeleteDepth', 0);
		$dropTablesFromDB = JRequest::getInt('dropTablesFromDB', 0);
		$feedbackMessage = "";

		// Initialize variables
		$db =& JFactory::getDBO();
		$cid = JRequest::getVar('cid', array(), 'post', 'array');
		$n = count($cid);

		JArrayHelper::toInteger($cid);

		$model =& JModel::getInstance('Table', 'FabrikModel');
		foreach ($cid as $id) {
			$model->setId($id);
			$model->_table = null;
			$table = $model->getTable();
			if ($dropTablesFromDB==1) {
				// if user has declared that he wants the actual tables removed...
				// then lets remove them...but only if they are not joomla tables
				// i.e. tables that have the prefix in them
				$dbconfigprefix = JApplication::getCfg("dbprefix");

				if (strncasecmp($table->db_table_name,$dbconfigprefix, strlen($dbconfigprefix))==0) {
					// do nothing
					$feedbackMessage .= JText::sprintf( "The table %s has will not be dropped from the database.", $table->db_table_name);
					$feedbackMessage .= "</li><li>";
				} else {
					$model->drop();
					$feedbackMessage .= JText::sprintf( "The table %s has now been dropped from the database.", $table->db_table_name);
					$feedbackMessage .= "</li><li>";
				}
			} else {
				$feedbackMessage .= JText::sprintf( "The table %s has been left in the database.", $table->db_table_name);
				$feedbackMessage .= "</li><li>";
			}


			$model->_oForm  = null;
			$formModel =& $model->getForm();
			// This is a bug i think in Rob's code. The getGroupsHiarachy returns the groups and the elements
			// But they are not stored so the subsequent foreach loops will be skipped.
			// $formModel->getGroupsHiarachy( false, false);
			// So to fix the bug the following line has been placed insted.
			$groups =& $formModel->getGroupsHiarachy();

			foreach ($groups as $groupModel) {
				$groupModel->setContext($formModel, $model);
				$elementModels =& $groupModel->getMyElements();
				if ($recordsDeleteDepth==3) {
					// user wants the elements also deleted
					// now lets delete the elements that belong to the groups
					// that are used in the forms that are associated with the tables.
					foreach ($elementModels as $elementModel) {
						$db->setQuery("DELETE FROM #__fabrik_elements WHERE id = ".(int)$elementModel->_id);
						$db->query();

						$db->setQuery("DELETE FROM #__fabrik_jsactions WHERE element_id = ".(int)$elementModel->_id);
						$db->query();
					}
				} else {
					// The user does not want his custom elements deleted. So,
					// dont remove the elements that the user has created. But do remove the elements
					// that were auto created for the table (id and time_date)
					//
					// The following lines will remove id and time_date elements from the group
					// However, they have been commented because i am not sure how they would affect the db
					// in case of joins and multiple forms...To be further looked into by Hugh and Rob.
					// $db->setQuery("DELETE FROM #__fabrik_elements WHERE name = 'id' and group_id = '$groupModel->_id'");
					// $db->query();
					// $db->setQuery("DELETE FROM #__fabrik_elements WHERE name = 'time_date' and group_id = '$groupModel->_id'");
					// $db->query();
				}

				if ($recordsDeleteDepth>1) {
					// user wants groups also removed
					$db->setQuery("DELETE FROM #__fabrik_groups WHERE id = ".(int)$groupModel->_id);
					$db->query();
				}
			}

			// Lets do some housekeeping for the forms
			if ($recordsDeleteDepth>0) {
				// user wants forms, groups and elements records removed
				$db->setQuery("DELETE FROM #__fabrik_forms WHERE id = ".(int)$formModel->_id);
				$db->query();

				$db->setQuery("DELETE FROM #__fabrik_formgroup WHERE form_id = ".(int)$formModel->_id);
				$db->query();
			} else {
				// The table records are always deleted.. see below
				// (maybe we should check if the table has been deleted too?
				// update the formgroup table so that the form is not marked as bound to any table
				$db->setQuery("UPDATE #__fabrik_forms SET record_in_database = 0 WHERE id = ".(int)$formModel->_id);
				$db->query();
			}

		}
		if ($n) {
			$query = 'DELETE FROM #__fabrik_tables'
			. ' WHERE id = ' . implode(' OR id = ', $cid )
			;
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500, $db->getError());
			}
		}

		$this->setMessage( $feedbackMessage . JText::sprintf( 'ITEMS REMOVED', $n));
		$this->setRedirect('index.php?option=com_fabrik&c=table');
	}

	/**
	 * run a table plugin
	 */

	function doPlugin()
	{
		$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
		if(is_array($cid)) {$cid = $cid[0];}
		$model	= &$this->getModel( 'table');
		$id = JRequest::getInt('tableid', $cid);
		$model->setId($id);
		$msg = $model->processPlugin();
		if (is_array($msg)) {
			$msg = implode('<br />', $msg);
		}
		if (JRequest::getVar('format') == 'raw') {
			JRequest::setVar('view', 'table');
			$this->display();
		} else {
			$this->setRedirect('index.php?option=com_fabrik&c=table&task=viewTable&cid[]='.$id);
			$this->setMessage( $msg);
		}
	}

	/**
	 * ajax load drop down of all columns in a given table
	 */

	function ajax_loadTableDropDown()
	{
		$db =& JFactory::getDBO();
		$conn = JRequest::getInt('conn', 1);
		$oCnn = JModel::getInstance('Connection', 'FabrikModel');
		$oCnn->setId($conn);
		$oCnn->getConnection();
		$db = $oCnn->getDb();
		$table 	= JRequest::getVar('table', '');
		$name 	= JRequest::getVar('name', 'table_key[]');
		$sql 	= "DESCRIBE ". $db->nameQuote($table);
		$db->setQuery($sql);
		$aFields = $db->loadObjectList();
		$fieldNames = array();
		if (is_array($aFields)) {
			foreach ($aFields as $oField) {
				$fieldNames[] = JHTML::_('select.option', $oField->Field);
			}
		}
		$fieldDropDown = JHTML::_('select.genericlist',  $fieldNames, $name, "class=\"inputbox\"  size=\"1\" ", 'value', 'text', '');
		echo $fieldDropDown;
	}

	/**
	 * ajax load drop down of all tables in a connection
	 */

	function ajax_loadTableListDropDown()
	{
		$db =& JFactory::getDBO();
		$conn = JRequest::getInt('conn', 1);
		$oCnn = JModel::getInstance('Connection', 'FabrikModel');
		$class = "inputbox " . JRequest::getVar('class', '');
		$oCnn->setId($conn);
		$oCnn->getConnection();
		$name = JRequest::getVar('name', 'table_join');
		$tableDropDown = $oCnn->getTableDdForThisConnection('', $name,  '', $class);
		echo $tableDropDown;
	}

}
?>