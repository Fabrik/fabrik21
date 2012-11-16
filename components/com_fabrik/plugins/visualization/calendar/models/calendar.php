<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'visualization.php');

class fabrikModelCalendar extends FabrikModelVisualization { //JModel

	var $_eventTables = null;

	/** @var object form model for standard add event form **/
	var $_formModel = null;

	/** js name for calendar **/
	var $calName = null;

	var $_events = null;

	/** @var array filters from url*/
	var $filters = array();

	/** @var bol can add to tables **/
	var $canAdd = null;

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function renderAdminSettings()
	{
		JHTML::stylesheet('fabrikadmin.css', 'administrator/components/com_fabrik/views/');
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="pluginSettings"
	style="display: none"><?php echo $pluginParams->render('params'); ?>
<fieldset><legend><?php echo JText::_('DATA') ?></legend> <?php echo $pluginParams->render('params', 'fields');?>
</fieldset>
<fieldset><legend><?php echo JText::_('MONTH VIEW OPTIONS') ?></legend>
		<?php echo $pluginParams->render('params', 'monthview');?></fieldset>
</div>
		<?php
		return;
	}

	function setTableIds()
	{
		if (!isset($this->tableids)) {
			$params =& $this->getParams();
			$this->tableids = $params->get('calendar_table', array(), '_default', 'array');
		}
	}

	function &getEventTables()
	{
		if (is_null($this->_eventTables)) {
			$db =& FabrikWorker::getDbo();
			$params 		=& $this->getPluginParams();
			$tables 		= $params->get('calendar_table', array(), '_default',  'array');
			$dateFields = $params->get('calendar_startdate_element', array(), '_default', 'array');
			$dateFields2 = $params->get('calendar_enddate_element', array(), '_default', 'array');
			$labels 		= $params->get('calendar_label_element', array(), '_default', 'array');
			$colours 		= $params->get('colour', array(), '_default', 'array');

			$db->setQuery("SELECT id AS value, label AS text FROM #__fabrik_tables WHERE id IN ('" . implode("','", $tables) . "')");
			$rows = $db->loadObjectList();
			for ($i=0; $i<count($rows);$i++) {
				if (!isset($colours[$i])) {
					$colours[$i] = '';
				}
				$rows[$i]->startdate_element = $dateFields[$i];
				$rows[$i]->enddate_element = JArrayHelper::getValue($dateFields2, $i);
				$rows[$i]->label_element = $labels[$i];
				$rows[$i]->colour = $colours[$i];
			}
			$this->_eventTables =& $rows;
		}
		return $this->_eventTables;
	}

	function getAddStandardEventFormId()
	{
		$config =& JFactory::getConfig();
		$prefix = $config->getValue('config.dbprefix');
		$db =& FabrikWorker::getDbo();
		$db->setQuery("SELECT form_id FROM #__fabrik_tables WHERE db_table_name = '{$prefix}fabrik_calendar_events' AND private = '1'");
		return $db->loadResult();
	}

	function getAddStandardEventFormInfo()
	{
		$config =& JFactory::getConfig();
		$prefix = $config->getValue('config.dbprefix');
		$params =& $this->getParams();
		$db =& FabrikWorker::getDbo();
		$db->setQuery("SELECT form_id, id FROM #__fabrik_tables WHERE db_table_name = '{$prefix}fabrik_calendar_events' AND private = '1'");
		$o = $db->loadObject();
		if (is_object($o)) {
			// there are standard events recorded
			return $o;
		} else {
			// they aren't any standards events recorded
			// Did we specify we want to use standard event table ?
			if ($params->get('use_standard_event_table')) {
				$o = new stdClass();
				$o->id = 0;
				$o->form_id = 0;
				return $o;
			}
			return null;
		}
	}

	/**
	 * Save the calendar
	 * @return boolean false if not saved, otherwise id of saved calendar
	 */

	function save()
	{
		$user	  = &JFactory::getUser();
		$post	= JRequest::get('post');
		if (!$this->bind( $post)) {
			return JError::raiseWarning(500, $this->getError());
		}

		$params = JRequest::getVar('params', array(), 'post');
		$this->attribs = $this->updateAttribsFromParams($params);
		if ($this->id == 0) {
			$this->created 		= date( 'Y-m-d H:i:s');
			$this->created_by 	= $user->get('id');
		} else {
			$this->modified 	= date( 'Y-m-d H:i:s');
			$this->modified_by 	= $user->get('id');
		}

		if (!$this->check()) {
			return JError::raiseWarning(500, $this->getError());
		}

		if (!$this->store()) {
			return JError::raiseWarning(500, $this->getError());
		}
		$this->checkin();
		return $this->id;
	}

	function getParams()
	{
		if (!isset($this->_params)) {
			$v =& $this->getVisualization();
			$this->_params 	= new fabrikParams($v->attribs, JPATH_SITE . '/administrator/components/com_fabrik/xml/connection.xml', 'component');
		}
		return $this->_params;
	}

	function setupEvents()
	{
		if (is_null($this->_events)) {
			$params =& $this->getParams();
			$tables 			= $params->get('calendar_table', '', '_default',  'array');
			$table_label 	= $params->get('calendar_label_element', '', '_default',  'array');
			$table_startdate 	= $params->get('calendar_startdate_element', '', '_default',  'array');
			$table_enddate 	= $params->get('calendar_enddate_element', '', '_default',  'array');
			$colour 			= $params->get('colour', '#ccccff', '_default',  'array');
			$this->_events = array();
			for ($i=0; $i<count($tables); $i++) {
				$listModel =& JModel::getInstance('Table', 'FabrikModel');
				if ($tables[$i] != 'undefined') {
					$listModel->setId($tables[$i]);
					$table =& $listModel->getTable();
					$endDate = JArrayHelper::getValue($table_enddate, $i, '');
					$startDate = JArrayHelper::getValue($table_startdate, $i, '');

					$startShowTime = true;
					$startDateEl = $listModel->getFormModel()->getElement($startDate);
					if ($startDateEl !== false) {
						$startShowTime = $startDateEl->getParams()->get('date_showtime', true);
					}

					$endShowTime = true;
					if ($endDate !== '') {
						$endDateEl = $listModel->getFormModel()->getElement($endDate);
						if ($endDateEl !== false) {
							$endShowTime = $endDateEl->getParams()->get('date_showtime', true);
						}
					}

					if (!isset($colour[$i])) {
						$colour[$i] = '';
					}
					if (!isset($table_label[$i])) {
						$table_label[$i] = '';
					}
					$this->_events[$tables[$i]][] = array(
						'startdate'=> $startDate,
						'enddate'=> $endDate,
						'startShowTime' => $startShowTime,
						'endShowTime' => $endShowTime,
						'label'=>$table_label[$i],
						'colour'=>$colour[$i] ,
						'formid'=>$table->form_id,
						'tableid'=>$tables[$i]
					);
				}
			}
		}

		return $this->_events;
	}

	function getLinkedFormIds()
	{
		$this->setUpEvents();
		$return = array();
		foreach ($this->_events as $arr) {
			foreach ($arr as $a) {
				$return[] = $a['formid'];
			}
		}
		return array_unique( $return);
	}

	/**
	 * go over all the tables whose data is displayed in the calendar
	 * if any element is found in the request data, assign it to the session
	 * This will then be used by the table to filter its data.
	 * nice :)
	 */

	function setRequestFilters()
	{
		$this->setupEvents();
		$request =& JRequest::get('request');
		$listModel =& JModel::getInstance('Table', 'FabrikModel');

		foreach ($this->_events as $tableId	 => $record) {
			$listModel->setId($tableId);
			$table =& $listModel->getTable();
			$formModel =& $listModel->getFormModel();
			foreach ($request as $key=>$val) {
				if ($formModel->hasElement($key)) {
					$o = new stdClass();
					$o->key = $key;
					$o->val = $val;
					$this->filters[]  =$o;
				}
			}
		}
	}

	/**
	 * can the user add a record into the calendar
	 * @return bol
	 */
	function getCanAdd()
	{
		if (!isset($this->canAdd)) {
			$params =& $this->getParams();
			if ($params->get(' use_standard_event_table')) {
				$this->canAdd = true;
				return true;
			}
			$tables = $params->get('calendar_table', array(), '_default',  'array');
			foreach ($tables as $tid) {
				$listModel =& JModel::getInstance('Table', 'FabrikModel');
				$listModel->setId($tid);
				if (!$listModel->canAdd()) {
					$this->canAdd = false;
					return false;
				}
			}
			$this->canAdd = true;
		}
		return $this->canAdd;
	}

	/**
	 * query all tables linked to the calendar and return them
	 * @return string javascript array containg json objects
	 */

	function getEvents()
	{
		global $Itemid;
		$session 		=& JFactory::getSession();
		$db					=& FabrikWorker::getDbo();
		$config =& JFactory::getConfig();
		$tzoffset = (int)$config->getValue('config.offset');

		$this->setupEvents();

		$calendar 	=& $this->_row;
		$aLegend 		= "$this->calName.addLegend([";
		$jsevents 	= array();
		foreach ($this->_events as $tableId	=> $record) {
			$listModel =& JModel::getInstance('Table', 'FabrikModel');
			$listModel->setId($tableId);
			$table =& $listModel->getTable();

			$els = $listModel->getElements();
			foreach ($record as $data) {

				$db =& $listModel->getDb();
				$startdate = trim($data['startdate']) !== '' ? FabrikString::safeColName($data['startdate']) : "''";
				$enddate = trim($data['enddate']) !== '' ? FabrikString::safeColName($data['enddate']) : "''";
				$label = trim($data['label']) !== '' ? FabrikString::safeColName($data['label']) : "''";
				$qlabel = FabrikString::safeColName($label);
				if (array_key_exists($qlabel, $els)) {
					// if db join selected for the label we need to get the label element and not the value
					$label = FabrikString::safeColName($els[$qlabel]->getOrderByName());
					// $$$ hugh @TODO doesn't seem to work for join elements, so adding hack till I can talk
					// to rob about this one.
					if (method_exists($els[$qlabel], 'getJoinLabelColumn')) {
						$label = $els[$qlabel]->getJoinLabelColumn();
					}
					else {
						$label = FabrikString::safeColName($els[$qlabel]->getOrderByName());
					}
				}
				//$pk = $listModel->getPrimaryKeyAndExtra();
				//$pk = $table->db_table_name.'.'.$pk['colname'];
				//above wont work in a view
				$pk = $listModel->getTable()->db_primary_key;
				$where = $listModel->_buildQueryWhere();
				$join = $listModel->_buildQueryJoin();
				$formid = (int)$table->form_id;
				$sql = "SELECT $pk AS id, $startdate AS startdate, $enddate AS enddate, '' AS link, $label AS 'label', '{$data['colour']}' AS colour, $formid AS formid FROM $table->db_table_name $join $where ORDER BY $startdate ASC";

				$db->setQuery($sql);
				$formdata = $db->loadObjectList();
				if (is_array($formdata)) {
					foreach ($formdata as $row) {
						if ($row->startdate != '') {

							$row->link = ("index.php?option=com_fabrik&Itemid=$Itemid&view=form&fabrik=$table->form_id&rowid=$row->id&tmpl=component");
							$row->_tableid = $table->id;
							$row->_canDelete = $listModel->canDelete();
							$row->_canEdit = $listModel->canEdit($row);

							// $$$ rob added timezone offset how on earth was this not picked up before :o
							// $$$ hugh because we suck?
							if ($row->startdate !== $db->getNullDate() && $data['startShowTime'] == true) {
								$date 	= JFactory::getDate($row->startdate);
								$row->startdate = $date->toMySQL();
								$date 	= JFactory::getDate($row->startdate);
								$date->setOffset($tzoffset);
								$row->startdate =  $date->toFormat('%Y-%m-%d %H:%M:%S');
							}

							if ($row->enddate !== $db->getNullDate() && $row->enddate !== '') {
								if ($data['endShowTime'] == true) {
									$date 	= JFactory::getDate($row->enddate);
									$date->setOffset($tzoffset);
									$row->enddate =  $date->toFormat('%Y-%m-%d %H:%M:%S');
								}
							} else {
								$row->enddate = $row->startdate;
							}
							$jsevents[$table->id.'_'.$row->id.'_'.$row->startdate] = clone($row);
						}
					}
				}
			}
		}
		$params =& $this->getParams();
		if ($params->get('use_standard_event_table')) {
			$db =& JFactory::getDBO();
			//get internal events for the calendar
			$config =& JFactory::getConfig();
			$prefix = $config->getValue('config.dbprefix');
			$db->setQuery("SELECT id FROM #__fabrik_tables WHERE db_table_name = '{$prefix}fabrik_calendar_events'");
			$tableid = $db->loadResult();
			$db->setQuery("SELECT id, '' AS link, `start_date` AS 'date', `end_date` AS 'enddate', `label`, '#EEEEEE' AS colour, 0 AS formid FROM #__fabrik_calendar_events WHERE visualization_id = ".(int)$calendar->id);
			$defEvents = $db->loadObjectList();
			for ($i=0; $i<count($defEvents); $i++) {
				$defEvents[$i]->_tableid = $tableid;
				$jsevents[$tableid.'_'.$defEvents[$i]->id] =& $defEvents[$i];
			}
		}
		$addEvent = json_encode($jsevents);
		return $addEvent;
	}

	//@TODO: json encode the returned value and move the $this->calName.addLegend to the view
	function getLegend()
	{
		$db		=& FabrikWorker::getDbo();
		$params =& $this->getParams();
		$this->setupEvents();
		$tables 			= $params->get('calendar_table', '', '_default',  'array');
		$colour 		= $params->get('colour', '#ccccff', '_default',  'array');
		$calendar = $this->_row;
		$aLegend = "$this->calName.addLegend([";
		$jsevents = array();
		foreach ($this->_events as $tableId	 => $record) {
			$listModel =& JModel::getInstance('Table', 'FabrikModel');
			$listModel->setId($tableId);
			$table =& $listModel->getTable();
			foreach ($record as $data) {
				$rubbish = $table->db_table_name . '___';
				$colour 	= FabrikString::ltrimword($data['colour'], $rubbish);
				$aLegend  	.= "{'label':'" .  $table->label . "','colour':'" . $colour . "'},";
			}
		}
		if ($params->get('use_standard_event_table')) {
			$aLegend  	.= "{'label':'Events','colour':'#EEEEEE'},";
		}
		$aLegend = rtrim($aLegend, ","). "]);";
		return $aLegend;
	}

	function getCalName()
	{
		if(is_null($this->calName)) {
			$calendar =& $this->_row;
			$this->calName = "oCalendar{$calendar->id}";
		}
		return $this->calName;
	}

	function updateevent()
	{
		$oPluginManager =& $this->_formModel->getPluginManager();
	}

	/**
	 * delete an event
	 */

	function deleteEvent()
	{
		$id = (int)JRequest::getVar('id');
		$tableid = JRequest::getInt('tableid');
		$listModel =& JModel::getInstance('Table', 'FabrikModel');
		$listModel->setId($tableid);
		$table =& $listModel->getTable();
		$tableDb =& $listModel->getDb();
		$db =& JFactory::getDBO();
		$db->setQuery("SELECT db_table_name FROM #__fabrik_tables WHERE id = $tableid");
		$tablename = $db->loadResult();
		$tableDb->setQuery("DELETE FROM ".FabrikString::safeColName($tablename)." WHERE $table->db_primary_key = $id");
		$tableDb->query();
	}

}

?>