<?php

/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class FabrikViewTable extends JView{

	/*var $_data 			= null;
	 var $_aLinkElements = null;*/
	var $_id 				= null;
	var $_isMambot 	= null;

	function setId($id )
	{
		$this->_id = $id;
	}

	function getManagementJS( $data = array() )
	{
		global $Itemid;
		$app	 =& JFactory::getApplication();
		$model =& $this->getModel();
		$table =& $model->getTable();

		FabrikHelperHTML::packageJS();
		$document =& JFactory::getDocument();
		FabrikHelperHTML::slimbox();
		FabrikHelperHTML::mocha();
		FabrikHelperHTML::script('table.js', 'media/com_fabrik/js/', true);

		$tmpl = JRequest::getVar('layout', $table->template);

		// check for a custom css file and include it if it exists
		$ab_css_file = JPATH_SITE.DS."components".DS."com_fabrik".DS."views".DS."table".DS."tmpl".DS.$tmpl.DS."template.css";
		if (file_exists($ab_css_file)) {
			JHTML::stylesheet('template.css', 'components/com_fabrik/views/table/tmpl/'.$tmpl . '/');
		}

		// check for a custom js file and include it if it exists
		$aJsPath = JPATH_SITE.DS."components".DS."com_fabrik".DS."views".DS."table".DS."tmpl".DS.$tmpl.DS."javascript.js";
		if (file_exists($aJsPath)) {
			FabrikHelperHTML::script("javascript.js", 'components/com_fabrik/views/table/tmpl/'.$tmpl . '/', true);
		}

		// temporarily set data to load requierd info for js templates

		$origRows 	= $this->rows;
		$this->rows = array(array());

		$tmpItemid = (!isset($Itemid)) ?  0 : $Itemid;

		$this->_c = 0;
		$this->_row = new stdClass();

		$script = '';
		static $tableini;
		if (!$tableini) {
			$tableini = true;
			$script .= "var oTables = \$H();\n";
		}

		$opts 				= new stdClass();
		$opts->admin 		= $app->isAdmin();
		$opts->postMethod 	= $model->getPostMethod();
		$opts->filterMethod = $this->filter_action;
		$opts->form 		= 'tableform_' . $model->getId();
		$opts->headings 	= $model->_jsonHeadings();
		$opts->labels 		= $this->headings;
		$opts->primaryKey 	= $table->db_primary_key;
		$opts->data 		= $data;
		$opts->Itemid 		= $tmpItemid;
		$opts->formid 		= $model->_oForm->getId();
		$opts->canEdit 		= $model->canEdit() ? "1" : "0";
		$opts->canView 		= $model->canView() ? "1" : "0";
		$opts->page 		= JRoute::_('index.php');
		$opts->mooversion = (FabrikWorker::getMooVersion() == 1 ) ? 1.2 : 1.1;
		$opts 				= json_encode($opts);

		$lang = new stdClass();
		$lang->select_rows =  JText::_('SELECT SOME ROWS FOR DELETION');
		$lang = json_encode($lang);

		$script .= "\n" . "var oTable = new fabrikTable(".$model->getId().",";
		$script .= $opts.",".$lang;
		$script .= "\n" . ");";
		$script .= "\n" . "oTable.addListenTo('form_".$model->_oForm->getId()."');";
		$script .= "\n" . "oTable.addListenTo('table_".$model->getId()."');";
		$script .= "\n" . "oPackage.addBlock('table_".$model->getId()."', oTable);";

		//add in plugin objects
		$plugins = $this->get('PluginJsObjects');
		$script .= "\noTable{$model->_id}.addPlugins([\n";
		$script .= "  " . implode(",\n  ", $plugins);
		$script .= "]\n);\n";

		$script .= "oTables.set($model->_id, oTable);\n";
		FabrikHelperHTML::addScriptDeclaration($script);
		//reset data back to original settings
		$this->rows = $origRows;
	}

	/**
	 * display the template
	 *
	 * @param sting $tpl
	 */

	function display($tpl = null)
	{
		global $_PROFILER;
		$app =& JFactory::getApplication();
		global $Itemid;
		// turn off deprecated warnings in 5.3 or greater,
		// or J!'s PDF lib throws warnings about set_magic_quotes_runtime()
		if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
			$current_level = error_reporting();
    		error_reporting($current_level & ~E_DEPRECATED);
		}
		require_once(COM_FABRIK_FRONTEND.DS.'views'.DS.'modifiers.php');
		$user 		=& JFactory::getUser();
		$model		=& $this->getModel();

		$document =& JFactory::getDocument();

		//this gets the component settings
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		$this->getId();

		$table			=& $model->getTable();
		$model->getPostMethod();
		$model->render();

		$w = new FabrikWorker();
		if (!$this->_isMambot) {
			$document->setTitle($w->parseMessageForPlaceHolder($table->label, $_REQUEST));
		}
		$document->setName($w->parseMessageForPlaceHolder($table->label, $_REQUEST));
		$data =& $model->getData();

		//add in some styling short cuts
		$c 		= 0;
		$form =& $model->getForm();
		$nav 	=& $model->getPagination();

		foreach ($data as $groupk => $group) {
			$last_pk = '';
			$last_i = 0;
			for ($i=0; $i<count($group); $i++) {
				$o = new stdClass();
				// $$$ rob moved merge wip code to FabrikModelTable::formatForJoins() - should contain fix for pagination
				$o->data = $data[$groupk][$i];
				$o->cursor = $i + $nav->limitstart;
				$o->total = $nav->total;
				$o->id = "table_".$table->id."_row_".@$o->data->__pk_val;
				$o->class = "fabrik_row oddRow".$c;
				$data[$groupk][$i] = $o;
				$c = 1-$c;
			}
		}
		$groups =& $form->getGroupsHiarachy();
		foreach ($groups as $groupModel) {
			$elementModels =& $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel) {
				$e =& $elementModel->getElement();
				$elementModel->setContext($groupModel, $form, $model);
				$elparams =& $elementModel->getParams();
				$col 	= $elementModel->getFullName(false, true, false);
				$col .= "_raw";
				$rowclass = $elparams->get('use_as_row_class');
				if ($rowclass == 1) {
					foreach ($data as $groupk => $group) {
						for ($i=0; $i<count($group); $i++) {
							$data[$groupk][$i]->class .= " ". preg_replace('/[^A-Z|a-z|0-9]/', '-', $data[$groupk][$i]->data->$col);
						}
					}
				}
			}
		}
		$this->rows =& $data;
		reset($this->rows);
		$firstRow = current($this->rows); //cant use numeric key '0' as group by uses groupd name as key
		$this->nodata = (empty($this->rows) || (count($this->rows) == 1 && empty($firstRow))) ? true : false;
		$this->tableStyle = $this->nodata ? 'display:none' : '';
		$this->emptyStyle = $this->nodata ? '' : 'display:none';
		$params	=& $model->getParams();

		if (!$model->canPublish()) {
			echo JText::_('SORRY THIS TABLE IS NOT PUBLISHED');
			return false;
		}

		if (!$model->canView()) {
			echo JText::_('ALERTNOTAUTH');
			return false;
		}

		$this->table 					= new stdClass();
		$this->table->label 	= $w->parseMessageForPlaceHolder($table->label, $_REQUEST);
		$this->table->intro 	= $w->parseMessageForPlaceHolder($table->introduction);
		$this->table->id			= $table->id;
		$this->group_by				= $table->group_by;
		$this->formid = 'tableform_' . $table->id;
		$page = $model->getPostMethod() == 'ajax' ? "index.php?format=raw" : "index.php?";
		$this->table->action 	=  $page . str_replace('&', '&amp;', JRequest::getVar('QUERY_STRING', 'index.php?option=com_fabrik', 'server'));

		if ($model->getPostMethod() == 'ajax') {
			$this->table->action .= '&format=raw';
			$this->table->action = str_replace("task=package", "task=viewTable", $this->table->action);
			//$this->table->action 	= JRoute::_($this->table->action);
		}
		$this->table->action 	= JRoute::_($this->table->action);

		$this->showCSV 				= $model->canCSVExport();
		$this->showCSVImport	= $model->canCSVImport();
		$this->nav 						= $params->get('show-table-nav', 1) ? $nav->getListFooter($model->getId(), $this->getTmpl()) : '';
		$this->fabrik_userid 	= $user->get('id');
		$this->canDelete 			= $model->canDelete() ? true : false;
		$jsdelete =  "oPackage.submitfabrikTable($table->id, 'delete')";
		$this->deleteButton 	= $model->canDelete() ?  "<input class='button' type='button' onclick=\"$jsdelete\" value='" . JText::_('DELETE') . "' name='delete'/>" : '';

		$this->showPDF = false;
		$this->pdfLink = false;

		$this->emptyButton = $model->canEmpty() ? "<input class='button' type='button' value='" . JText::_('EMPTY') . "' name='doempty'/>" : "";

		$this->csvImportLink = $this->showCSVImport ? JRoute::_("index.php?option=com_fabrik&c=import&view=import&filetype=csv&tableid=" . $table->id ) : '';
		$this->showAdd = $model->canAdd();
		if ($this->showAdd) {
			if ($app->isAdmin()) {
				$this->addRecordLink = $model->getPostMethod() == 'ajax' ? "#" : JRoute::_("index.php?option=com_fabrik&c=form&task=form&fabrik=" . $table->form_id . "&tableid=" . $model->_id ."&rowid=");
			} else {
				$this->addRecordLink = $model->getPostMethod() == 'ajax' ? "#" : JRoute::_("index.php?option=com_fabrik&c=form&view=form&Itemid=$Itemid&fabrik=" . $table->form_id . "&tableid=" . $model->_id ."&rowid=");
			}
		}
		$this->showRSS = $params->get('rss', 0) == 0 ?  0 : 1;

		if ($this->showRSS) {
			$this->rssLink = $model->getRSSFeedLink();
			if ($this->rssLink != '') {
				$attribs = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
				if (method_exists($document, 'addHeadLink')) {
					$document->addHeadLink($this->rssLink, 'alternate', 'rel', $attribs);
				}
			}
		}
		list($this->headings, $groupHeadings, $this->headingClass, $this->cellClass ) = $this->get('Headings');

		$this->filter_action = $model->getFilterAction();
		$this->filters =& $model->getFilters('tableform_'. $model->getId(), 'table');
		$this->assign('clearFliterLink', $this->get('clearButton'));
		JDEBUG ? $_PROFILER->mark('fabrik getfilters end') : null;
		$form->getGroupsHiarachy();
		$this->assign('showFilters', (count($this->filters) > 0 && $params->get('show-table-filters', 1)) && JRequest::getVar('showfilters', 1) == 1 ?  1 : 0);

		$this->assign('requiredFiltersFound', $this->get('RequiredFiltersFound'));
		$msg = (!$this->requiredFiltersFound) ? JText::_('PLEASE_SELECT_ALL_REQUIRED_FILTERS') : $params->get('empty_data_msg');
		$this->assign('emptyDataMessage', $msg);
		$this->calculations 	= $this->_getCalculations($this->headings);

		$this->assign('isGrouped', $table->group_by);
		$this->assign('colCount', count($this->headings));
		$this->assignRef('grouptemplates', $model->grouptemplates);
		$this->assignRef('params', $params);
		$this->assignRef('groupheadings', $groupHeadings);
		$this->_loadTemplateBottom();

		$this->getManagementJS( $this->rows);

		// get dropdown list of other tables for quick nav in admin
		$this->tablePicker = ($app->isAdmin()) ? FabrikHelperHTML::tableList($this->table->id ) : '';

		$this->pluginButtons = $model->getPluginButtons();

		//force front end templates
		$this->_basePath = COM_FABRIK_FRONTEND . DS . 'views';

		$tmpl = $params->get('pdf_template');
		if ($tmpl == -1) {
			$tmpl = JRequest::getVar('layout', $table->template);
		}

		$this->_setPath('template', $this->_basePath.DS.$this->_name.DS.'tmpl'.DS.$tmpl);
		//ensure we don't have an incorrect version of mootools loaded

		$this->fixForPDF();

		parent::display();
	}

	/**
	 * ensure vars are correct for pdf output
	 *
	 */
	function fixForPDF()
	{
		$this->pluginButtons = array();
		$this->nav = null;
		$this->emptyButton  = '';
		$this->assign('showFilters', false);
		$this->showCSV 				= false;
		$this->showCSVImport	= false;
		$this->canDelete 			= false;
		$this->deleteButton 	='';
		$this->showPDF = false;
		$this->showAdd = false;
		$this->showRSS = false;
	}

	/**
	 *
	 */

	function _getCalculations($aCols )
	{

		$aData = array();
		$found = false;
		$model = $this->getModel();
		foreach ($aCols as $key=>$val) {
			$calc = '';
			$res = '';
			$oCalcs = new stdClass();
			$oCalcs->grouped = array();

			if (array_key_exists($key, $model->_aRunCalculations['sums'])) {
				$found = true;
				$res = $model->_aRunCalculations['sums'][$key];
				$calc .= JText::_('SUM') . ": " . $res . "<br />";
				$tmpKey = str_replace(".", "___", $key) . "_calc_sum";
				$oCalcs->$tmpKey = $res;
			}
			if (array_key_exists($key . '_obj', $model->_aRunCalculations['sums'])) {
				$found = true;
				$res = $model->_aRunCalculations['sums'][$key. '_obj'];
				foreach ($res as $k=>$v) {
					if ($k != 'calc') {
						@$oCalcs->grouped[$k] .= JText::_('SUM') . ": " . $v->value . "<br />";
					}
				}
			}

			if (array_key_exists($key, $model->_aRunCalculations['avgs'])) {
				$found = true;
				$res = $model->_aRunCalculations['avgs'][$key];
				$calc .= JText::_('AVERAGE') . ": " . $res . "<br />";
				$tmpKey = str_replace(".", "___", $key) . "_calc_average";
				$oCalcs->$tmpKey = $res;
			}

			if (array_key_exists($key . '_obj', $model->_aRunCalculations['avgs'])) {
				$found = true;
				$res = $model->_aRunCalculations['avgs'][$key. '_obj'];
				foreach ($res as $k=>$v) {
					if ($k != 'calc') {
						@$oCalcs->grouped[$k] .=  JText::_('AVERAGE') . ": " . $v->value . "<br />";
					}
				}
			}

			if (array_key_exists($key. '_obj', $model->_aRunCalculations['medians'])) {
				$found = true;
				$res = $model->_aRunCalculations['medians'][$key. '_obj'];
				foreach ($res as $k=>$v) {
					if ($k != 'calc') {
						@$oCalcs->grouped[$k] .=  JText::_('MEDIAN') . ": " . $v->value . "<br />";
					}
				}
			}

			if (array_key_exists($key, $model->_aRunCalculations['medians'])) {
				$found = true;
				$res = $model->_aRunCalculations['medians'][$key];
				$calc .= JText::_('MEDIAN') . ": " . $res . "<br />";
				$tmpKey = str_replace(".", "___", $key) . "_calc_median";
				$oCalcs->$tmpKey = $res;
			}

			if (array_key_exists($key. '_obj', $model->_aRunCalculations['count'])) {
				$found = true;
				$res = $model->_aRunCalculations['count'][$key. '_obj'];
				foreach ($res as $k=>$v) {
					if ($k != 'calc') {
						@$oCalcs->grouped[$k] .=  JText::_('COUNT') . ": " . $v->value . "<br />";
					}
				}
			}

			if (array_key_exists($key, $model->_aRunCalculations['count'])) {
				$res = $model->_aRunCalculations['count'][$key];
				$calc .= JText::_('COUNT') . ": " . $res . "<br />";
				$tmpKey = str_replace(".", "___", $key) . "_calc_count";
				$oCalcs->$tmpKey = $res;
				$found = true;
			}
			$key = str_replace(".", "___", $key);
			$oCalcs->calc = $calc;
			$aData[$key] = $oCalcs;
		}
		$this->assign('hasCalculations', $found);
		return $aData;
	}

	/**
	 *
	 */

	function _loadTemplateBottom()
	{
		//no fields in pdfs!
		$this->hiddenFields = '';
		return;
	}

	private function getId()
	{
		$app =& JFactory::getApplication();
		$model		=& $this->getModel();
		//this gets the component settings
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		if (!isset($this->_id)) {
			if (!$app->isAdmin()) {
				$model->setId(JRequest::getVar('tableid', $usersConfig->get('tableid')));
			}
		} else {
			//when in a package the id is set from the package view
			$model->setId($this->_id);
		}
		return $model->getId();
	}

	/**
	 * get the view template name
	 * @return string template name
	 */

	private function getTmpl()
	{
		$app 		=& JFactory::getApplication();
		$model		=& $this->getModel();
		$table =& $model->getTable();
		$params =& $model->getParams();
		if ($app->isAdmin()) {
			$tmpl = $params->get('admin_template');
			if ($tmpl == -1 || $tmpl == '') {
				$tmpl = JRequest::getVar('layout', $table->template);
			}
		} else {
			$tmpl = JRequest::getVar('layout', $table->template);
		}
		return $tmpl;
	}
}
?>