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

class FabrikViewTable extends JView
{

	var $_id 				= null;
	var $_isMambot 	= null;

	function setId($id)
	{
		$this->_id = $id;
	}

	protected function getManagementJS($data = array())
	{
		global $Itemid;
		// $$$ rob ALWAYS load the calendar (so its avaible in ajax forms)
		FabrikHelperHTML::loadcalendar();
		$app	 =& JFactory::getApplication();
		$model =& $this->getModel();
		$table =& $model->getTable();

		$formModel =& $model->getForm();
		$elementsNotInTable =& $formModel->getElementsNotInTable();
		$keys = array('id'=>'', 'name'=>'', 'label'=>'');
		foreach ($elementsNotInTable as &$i) {
			if (is_a($i, 'TableElement')) {
				$i = array_intersect_key($i->getPublicProperties(), $keys);
			}
		}
		FabrikHelperHTML::packageJS();
		$document =& JFactory::getDocument();
		if ($model->requiresSlimbox()) {
			FabrikHelperHTML::slimbox();
		}
		if ($model->requiresMocha()) {
			FabrikHelperHTML::mocha();
		}

		FabrikHelperHTML::script('table.js', 'media/com_fabrik/js/', true);
		$tmpl = $this->getTmpl();

		FabrikHelperHTML::stylesheet('table.css', 'media/com_fabrik/css/');

		// check for a custom css file and include it if it exists
		FabrikHelperHTML::stylesheetFromPath("components".DS."com_fabrik".DS."views".DS."table".DS."tmpl".DS.$tmpl.DS."template.css");

		//check and add a general fabrik custom css file overrides template css and generic table css
		FabrikHelperHTML::stylesheetFromPath("media".DS."com_fabrik".DS."css".DS."custom.css");

		//check and add a specific table template css file overrides template css generic table css and generic custom css
		FabrikHelperHTML::stylesheetFromPath("components".DS."com_fabrik".DS."views".DS."table".DS."tmpl".DS.$tmpl.DS."custom.css");

		// check for a custom js file and include it if it exists
		$aJsPath = JPATH_SITE.DS."components".DS."com_fabrik".DS."views".DS."table".DS."tmpl".DS.$tmpl.DS."javascript.js";
		if (JFile::exists($aJsPath)) {
			FabrikHelperHTML::script("javascript.js", 'components/com_fabrik/views/table/tmpl/'.$tmpl . '/', true);
		}

		$origRows 	= $this->rows;
		$this->rows = array(array());

		$tmpItemid = !isset($Itemid) ?  0 : $Itemid;

		$this->_c = 0;
		$this->_row = new stdClass();
		$script = '';
		// $$$ rob done in HTMLHelper
		//$script = "/* <![CDATA[ */ \n";

		static $tableini;
		if (!$tableini) {
			$tableini = true;
			$script .= "var oTables = \$H();\n";
		}

		$opts 						= new stdClass();
		$opts->admin 			= $app->isAdmin();
		$opts->postMethod 	= $model->getPostMethod();
		$opts->filterMethod = $this->filter_action;
		$opts->form 			= 'tableform_' . $model->_id;
		$opts->headings 	= $model->_jsonHeadings();
		$labels = $this->headings;
		foreach ($labels as &$l) {
			$l = strip_tags($l);
		}
		$opts->labels 		= $labels;
		$opts->primaryKey = $table->db_primary_key;
		$opts->Itemid 		= $tmpItemid;
		$opts->formid 		= $model->_oForm->getId();
		$opts->canEdit 		= $model->canEdit() ? "1" : "0";
		$opts->canView 		= $model->canView() ? "1" : "0";
		$opts->page 			= JRoute::_('index.php');
		$opts->isGrouped = $this->isGrouped;
		$opts->mooversion = (FabrikWorker::getMooVersion() == 1 ) ? 1.2 : 1.1;
		if (FabrikWorker::nativeMootools12()) {
			$opts->mooversion = 1.24;
		}
		$opts->formels		= $elementsNotInTable;
		//if table data starts as empty then we need the html from the row
		// template otherwise we can't add a row to the table

		if ($model->_postMethod == 'ajax') {
			ob_start();
			$this->_row = new stdClass();
			$this->_row->id = '';
			$this->_row->class = 'fabrik_row';
			require(COM_FABRIK_FRONTEND.DS.'views'.DS.'table'.DS.'tmpl'.DS.'default'.DS.'default_row.php');
    	$opts->rowtemplate = ob_get_contents();
			ob_end_clean();
		}
		//$$$rob if you are loading a table in a window from a form db join select record option
		// then we want to know the id of the window so we can set its showSpinner() method
		$opts->winid			= JRequest::getVar('winid', '');

		$opts->ajaxEditViewLink = $model->ajaxEditViewLink() ? 1 : 0;
		$opts 						= json_encode($opts);

		$lang 							= new stdClass();
		$lang->select_rows 	= JText::_('SELECT SOME ROWS FOR DELETION');
		$lang->yes 					= JText::_('Yes');
		$lang->no 					= JText::_('No');
		$lang->select_colums_to_export = JText::_('SELECT_COLUMNS_TO_EXPORT');
		$lang->include_filters = JText::_('INCLUDE_FILTERS');
		$lang->include_data = JText::_('INCLUDE_DATA');
		$lang->inlcude_raw_data = JText::_('INCLUDE_RAW_DATA');
		$lang->include_calculations = JText::_('INLCUDE_CALCULATIONS');
		$lang->export 		= JText::_('EXPORT');
		$lang->loading 		= JText::_('loading');
		$lang->savingto 	= JText::_('Saving to');
		$lang->confirmDelete = JText::_('CONFIRMDELETE');
		$lang->csv_downloading = JText::_('COM_FABRIK_CSV_DOWNLOADING');
		$lang->download_here = JText::_('COM_FABRIK_DOWNLOAD_HERE');
		$lang->csv_complete = JText::_('COM_FABRIK_CSV_COMPLETE');
		$lang = json_encode($lang);

		$script .= "\n" . "var oTable{$model->_id} = new fabrikTable($model->_id,";
		$script .= $opts.",".$lang;
		$script .= "\n" . ");";
		$script .= "\n" . "oTable{$model->_id}.addListenTo('form_{$model->_oForm->_id}');";
		$script .= "\n" . "oTable{$model->_id}.addListenTo('table_{$model->_id}');";
		$script .= "\n" . "oPackage.addBlock('table_{$model->_id}', oTable{$model->_id});";

		//add in plugin objects
		$plugins = $this->get('PluginJsObjects');
		$script .= "\noTable{$model->_id}.addPlugins([\n";
		$script .= "  " . implode(",\n  ", $plugins);
		$script .= "]\n);\n";

		$script .= "oTables.set($model->_id, oTable{$model->_id});\n";

		FabrikHelperHTML::addScriptDeclaration($script);
		$this->getElementJs();
		//reset data back to original settings
		$this->rows = $origRows;
		$this->get('CustomJsAction');
	}

	protected function getElementJs()
	{
		$model =& $this->getModel();
		$model->getElementJs();
	}

	private function getId()
	{
		$app =& JFactory::getApplication();
		$model		=& $this->getModel();
		//this gets the component settings
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		if (!isset($this->_id)) {
			if (!$app->isAdmin()) {
				$id = JRequest::getInt('tableid', $usersConfig->get('tableid'));
				$model->setId($id);
			}
		} else {
			//when in a package the id is set from the package view
			$model->setId($this->_id);
		}
		return $model->_id;
	}

	/**
	 * display the template
	 *
	 * @param sting $tpl
	 */

	function display($tpl = null)
	{
		if ($this->getLayout() == '_advancedsearch') {
			$this->advancedSearch($tpl);
			return;
		}

		global $_PROFILER;
		$app 				=& JFactory::getApplication();
		require_once(COM_FABRIK_FRONTEND.DS.'views'.DS.'modifiers.php');
		$user 		=& JFactory::getUser();
		$model		=& $this->getModel();

		$document =& JFactory::getDocument();

		//this gets the component settings
		$this->getId();

		$table			=& $model->getTable();
		//$model->getPostMethod(); $$$ rob dont think we need it here?
		$model->render();

		$w = new FabrikWorker();

		$data =& $model->getData();

		//add in some styling short cuts
		$c 		= 0;
		$form =& $model->getForm();
		$nav 	=& $model->getPagination();
		foreach ($data as $groupk => $group) {
			$last_pk = '';
			$last_i = 0;
			$num_rows = 1;
			foreach (array_keys($group) as $i) {
				$o = new stdClass();
				// $$$ rob moved merge wip code to FabrikModelTable::formatForJoins() - should contain fix for pagination
				$o->data = $data[$groupk][$i];
				$o->cursor = $num_rows + $nav->limitstart;
				$o->total = $nav->total;
				$o->id = "table_".$table->id."_row_".@$o->data->__pk_val;
				$o->class = "fabrik_row oddRow".$c;
				$data[$groupk][$i] = $o;
				$c = 1-$c;
				$num_rows++;
			}
		}

		$groups =& $form->getGroupsHiarachy();
		foreach ($groups as $groupModel) {
			$elementModels =& $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel) {
				$elementModel->setContext($groupModel, $form, $model);
				$col 	= $elementModel->getFullName(false, true, false);
				$col .= "_raw";
				$rowclass = $elementModel->getParams()->get('use_as_row_class');
				if ($rowclass == 1) {
					foreach ($data as $groupk => $group) {
						for ($i=0; $i<count($group); $i++) {
							$c = preg_replace('/[^A-Z|a-z|0-9]/', '-', $data[$groupk][$i]->data->$col);
							// $$$ rob 24/02/2011 can't have numeric class names so prefix with element name
							if (is_numeric($c)) {
								$c = $elementModel->getElement()->name . $c;
							}
							$data[$groupk][$i]->class .= " " . $c;
						}
					}
				}
			}
		}
		$this->rows =& $data;
		reset($this->rows);

		$firstRow = current($this->rows); //cant use numeric key '0' as group by uses groupd name as key
		$this->assign('requiredFiltersFound', $this->get('RequiredFiltersFound'));
		$this->nodata = (empty($this->rows) || (count($this->rows) == 1 && empty($firstRow)) || !$this->requiredFiltersFound) ? true : false;
		$this->tableStyle = $this->nodata ? 'display:none' : '';
		$this->emptyStyle = $this->nodata ? '' : 'display:none';
		$params =& $model->getParams();

		if (!$model->canPublish()) {
			echo JText::_('SORRY THIS TABLE IS NOT PUBLISHED');
			return false;
		}

		if (!$model->canView()) {
			echo JText::_('ALERTNOTAUTH');
			return false;
		}
		if (!class_exists('JSite'))
		{
			require_once(JPATH_ROOT.DS.'includes'.DS.'application.php');
		}
		$menus = &JSite::getMenu();
		$menu	= $menus->getActive();

		// because the application sets a default page title, we need to get it
		// right from the menu item itself
		//if there is a menu item available AND the form is not rendered in a content plugin or module
		if (is_object($menu) && !$this->_isMambot) {
			$menu_params = new JParameter($menu->params);
			if (!$menu_params->get('page_title') || $menu_params->get('show_page_title') == 0) {
				$params->set('page_title', '');
				$params->set('show_page_title', 0);
			} else {
				$params->set('page_title', $menu_params->get('page_title'));
				$params->set('show_page_title', $menu_params->get('show_page_title', 0));
			}

		} else {
			$params->set('show_page_title', JRequest::getInt('show_page_title', 0));
			$params->set('page_title', JRequest::getVar('title', ''));
			$params->set('show-title', JRequest::getInt('show-title', 1));
		}
		if (!$this->_isMambot) {
			$document->setTitle($w->parseMessageForPlaceHolder($params->get('page_title'), $_REQUEST));
		}
		$this->table 					= new stdClass();
		$this->table->label 	= $w->parseMessageForPlaceHolder($table->label, $_REQUEST);
		$this->table->intro 	= $w->parseMessageForPlaceHolder($table->introduction);
		$this->table->id			= $table->id;
		$this->table->db_table_name = $table->db_table_name;
		$this->group_by				= $table->group_by;
		$this->form = new stdClass();
		$this->form->id = $table->form_id;
		$this->formid = 'tableform_' . $table->id;
		$form =& $model->getForm();

		FabrikHelperHTML::tips('.hasTip', array(), "$('".$this->formid."')");

		$this->table->action = $this->get('TableAction');
		$this->showCSV 				= $model->canCSVExport();
		$this->showCSVImport	= $model->canCSVImport();
		$this->assignRef('navigation', $nav);
		$this->nav = JRequest::getInt('fabrik_show_nav', $params->get('show-table-nav', 1)) ? $nav->getListFooter($model->_id, $this->getTmpl()) : '';
		$this->nav = '<div class="fabrikNav">'.$this->nav.'</div>';
		$this->fabrik_userid = $user->get('id');
		$this->canDelete = $model->deletePossible() ? true : false;
		$jsdelete = "oPackage.submitfabrikTable($table->id, 'delete')";
		$this->deleteButton = $model->deletePossible() ? "<input class=\"button\" type=\"button\" onclick=\"$jsdelete\" value=\"" . JText::_('DELETE') . "\" name=\"delete\"/>" : '';
		$this->showPDF = $params->get('pdf', 0);
		if ($this->showPDF) {
			$this->pdfLink = FabrikHelperHTML::pdfIcon($model, $params);
		}
		$this->emptyButton 		= $model->canEmpty() ? "<input class=\"button\" type=\"button\" value=\"" . JText::_('EMPTY') . "\" name=\"doempty\"/>" : "";
		$this->csvImportLink 	= $this->showCSVImport ? JRoute::_("index.php?option=com_fabrik&c=import&view=import&filetype=csv&tableid=" . $table->id) : '';
		$this->showAdd 				= $model->canAdd();
		if ($this->showAdd) {
			if ($params->get('show-table-add', 1)) {
				$this->assign('addRecordLink', $this->get('AddRecordLink'));
				$this->assign('addRecordLabel', $this->get('AddRecordLabel'));
			}
			else {
				$this->showAdd = false;
			}
		}
		$this->assign('addRecordId', "table_" . $model->_id . "_addRecord");
		$this->showRSS = $params->get('rss', 0) == 0 ? 0 : 1;
		if ($this->showRSS) {
			$this->rssLink = $model->getRSSFeedLink();
			if ($this->rssLink != '') {
				$attribs = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
				$document->addHeadLink($this->rssLink, 'alternate', 'rel', $attribs);
			}
		}
		list($this->headings, $groupHeadings, $this->headingClass, $this->cellClass) = $this->get('Headings');
		$this->filter_action = $model->getFilterAction();
		JDEBUG ? $_PROFILER->mark('fabrik getfilters start') : null;
		$this->filters =& $model->getFilters('tableform_'. $model->_id, 'table');
		$this->assign('clearFliterLink', $this->get('clearButton'));
		JDEBUG ? $_PROFILER->mark('fabrik getfilters end') : null;
		$form->getGroupsHiarachy();
		$this->assign('showFilters', (count($this->filters) > 0 && $params->get('show-table-filters', 1)) && JRequest::getVar('showfilters', 1) == 1 ?  1 : 0);

		$this->assign('emptyDataMessage', $this->get('EmptyDataMsg'));

		$this->assignRef('groupheadings', $groupHeadings);
		$this->assignRef('calculations', $this->_getCalculations($this->headings));
		$this->assign('isGrouped', $table->group_by);
		$this->assign('colCount', count($this->headings));
		$this->assignRef('grouptemplates', $model->grouptemplates);
		$this->assignRef('params', $params);
		$this->_loadTemplateBottom();
		$this->getManagementJS($this->rows);

		// get dropdown list of other tables for quick nav in admin
		$this->tablePicker = $app->isAdmin() ? FabrikHelperHTML::tableList($this->table->id) : '';

		$this->pluginButtons = $model->getPluginButtons();

		//force front end templates
		$this->_basePath = COM_FABRIK_FRONTEND . DS . 'views';

		$tmpl = $this->getTmpl();

		$this->_setPath('template', $this->_basePath.DS.$this->_name.DS.'tmpl'.DS.$tmpl);
		$this->setLayout('default'); // kludge for convincing J! to look for the right files in loadTemplate()

		$text = $this->loadTemplate();
		if ($params->get('process-jplugins')) {
			$opt = JRequest::getVar('option');
			JRequest::setVar('option', 'com_content');
			jimport('joomla.html.html.content');
			$text .= '{emailcloak=off}';
			$text = JHTML::_('content.prepare', $text);
			$text = preg_replace('/\{emailcloak\=off\}/', '', $text);
			JRequest::setVar('option', $opt);
		}

		FabrikHelperHTML::cleanMootools();
		JDEBUG ? $_PROFILER->mark('end fabrik display') : null;
		// $$$ rob 09/06/2011 no need for isMambot test? should use ob_start() in module / plugin to capture the output
		echo $text;
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

	/**
	 * get the table calculations
	 */

	protected function _getCalculations($aCols)
	{
		$aData = array();
		$found = false;
		$model = $this->getModel();
		$modelCals = $model->getCalculations();

		foreach ($aCols as $key=>$val) {
			$calc = '';
			$res = '';
			$oCalcs = new stdClass();
			$oCalcs->grouped = array();

			if (array_key_exists($key, $modelCals['sums'])) {
				$found = true;
				$res = $modelCals['sums'][$key];
				$calc .= $res;
				$tmpKey = str_replace(".", "___", $key) . "_calc_sum";
				$oCalcs->$tmpKey = $res;
			}
			if (array_key_exists($key . '_obj', $modelCals['sums'])) {
				$found = true;
				$res = $modelCals['sums'][$key. '_obj'];
				foreach ($res as $k => $v) {
					if ($k != 'calc') {
						@$oCalcs->grouped[$k] .= "<span class=\"calclabel\">".$v->calLabel . ":</span> " . $v->value . "<br />";
					}
				}
			}

			if (array_key_exists($key, $modelCals['avgs'])) {
				$found = true;
				$res = $modelCals['avgs'][$key];
				$calc .= $res;
				$tmpKey = str_replace(".", "___", $key) . "_calc_average";
				$oCalcs->$tmpKey = $res;
			}

			if (array_key_exists($key . '_obj', $modelCals['avgs'])) {
				$found = true;
				$res = $modelCals['avgs'][$key. '_obj'];
				foreach ($res as $k => $v) {
					if ($k != 'calc') {
						@$oCalcs->grouped[$k] .=  "<span class=\"calclabel\">".$v->calLabel . ":</span> " . $v->value . "<br />";
					}
				}
			}

			if (array_key_exists($key. '_obj', $modelCals['medians'])) {
				$found = true;
				$res = $modelCals['medians'][$key. '_obj'];
				foreach ($res as $k => $v) {
					if ($k != 'calc') {
						@$oCalcs->grouped[$k] .=  "<span class=\"calclabel\">".$v->calLabel . ":</span> " . $v->value . "<br />";
					}
				}
			}

			if (array_key_exists($key, $modelCals['medians'])) {
				$found = true;
				$res = $modelCals['medians'][$key];
				$calc .= $res;
				$tmpKey = str_replace(".", "___", $key) . "_calc_median";
				$oCalcs->$tmpKey = $res;
			}

			if (array_key_exists($key. '_obj', $modelCals['count'])) {
				$found = true;
				$res = $modelCals['count'][$key. '_obj'];
				foreach ($res as $k => $v) {
					if ($k != 'calc') {
						@$oCalcs->grouped[$k] .= "<span class=\"calclabel\">".$v->calLabel . ":</span> " . $v->value . "<br />";
					}
				}
			}

			if (array_key_exists($key, $modelCals['count'])) {
				$res = $modelCals['count'][$key];
				$calc .= $res;
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
	 * get the table's forms hidden fields
	 * @return string hidden fields
	 */

	function _loadTemplateBottom()
	{
		global $Itemid;
		$app =& JFactory::getApplication();
		$model =& $this->getModel();
		$table =& $model->getTable();
		$formid = (int)$model->getForm()->_id;
		$reffer = JRequest::getVar('REQUEST_URI', '', 'server');
		$this->hiddenFields = array();

		$option = JRequest::getCmd('originalOption', JRequest::getCmd('option', 'com_fabrik'));

		$this->hiddenFields[] = "<input type=\"hidden\" name=\"option\" value=\"" . $option . "\" id = \"table_".$table->id."_option\" />";
		$this->hiddenFields[] = "<input type=\"hidden\" name=\"orderdir\" value=\"\" id =\"table_".$table->id."_orderdir\" />";
		$this->hiddenFields[] = "<input type=\"hidden\" name=\"orderby\" value=\"\" id = \"table_".$table->id."_orderby\" />";
		if (!$this->_isMambot) {
			$this->hiddenFields[] = "<input type=\"hidden\" name=\"controller\" value=\"table\" />";
		}
		// $$$rob if the content plugin has temporarily set the view to table then get view from origview var, if that doesn't exist
		//revert to view var. Used when showing table in article/blog layouts
		$view = JRequest::getVar('view', 'table');
		$view = $option == 'com_content' ? JRequest::getVar('origview', $view) : $view;
		$this->hiddenFields[] = "<input type=\"hidden\" name=\"view\" value=\"" . $view . "\" id = \"table_".$table->id."_view\" />";

		$this->hiddenFields[] = "<input type=\"hidden\" name=\"tableid\" value=\"" . $model->_id . "\" id = \"table_".$table->id."_tableid\" />";
		$this->hiddenFields[] = "<input type=\"hidden\" name=\"Itemid\" value=\"" . $Itemid . "\" id = \"table_".$table->id."_Itemid\" />";
		//removed in favour of using table_{id}_limit dorop down box

		$this->hiddenFields[] = "<input type=\"hidden\" name=\"fabrik_referrer\" value=\"" . $reffer . "\" />";
		$this->hiddenFields[] = JHTML::_('form.token');

		$this->hiddenFields[] = "<input type=\"hidden\" name=\"format\" id=\"table_".$table->id."_format\" value=\"html\" />";
		//$packageId = JRequest::getInt('_packageId', 0);
		// $$$ rob testing for ajax table in module
		$packageId = $model->_packageId;
		$this->hiddenFields[] = "<input type=\"hidden\" name=\"_packageId\" value=\"$packageId\" id=\"table_".$table->id."_packageId\" />";
		if ($app->isAdmin()) {
			$this->hiddenFields[] = "<input type=\"hidden\" name=\"c\" value=\"table\" />";
			$this->hiddenFields[] = "<input type=\"hidden\" name=\"task\" value=\"viewTable\" />";
		} else {
			$this->hiddenFields[] = "<input type=\"hidden\" name=\"task\" value=\"\" />";
		}
		//needed for db join select
		$this->hiddenFields[] = "<input type=\"hidden\" name=\"formid\" value=\"$formid\" />";
		$this->hiddenFields[] = "<input type=\"hidden\" name=\"fabrik_tableplugin_name\" value=\"\" />";
		$this->hiddenFields[] = "<input type=\"hidden\" name=\"fabrik_tableplugin_renderOrder\" value=\"\" />";

		// $$$ hugh - added this so plugins have somewhere to stuff any random data they need during submit
		$this->hiddenFields[] = "<input type=\"hidden\" name=\"fabrik_tableplugin_options\" value=\"\" />";

		$this->hiddenFields[] = "<input type=\"hidden\" name=\"incfilters\" value=\"1\" />";
		$this->hiddenFields[] = "<input type=\"hidden\" name=\"module\" value=\"{$this->_isMambot}\" />";
		$this->hiddenFields = implode("\n", $this->hiddenFields);
	}

	protected function advancedSearch($tpl)
	{
		$model =& $this->getModel();
		$id = $this->getId();
		FabrikHelperHTML::script('advanced-search.js', 'media/com_fabrik/js/');
		$this->assignRef('rows', $this->get('advancedSearchRows'));
		$this->get('AdvancedSearchOpts');
		$action = JRequest::getVar('HTTP_REFERER', 'index.php?option=com_fabrik', 'server');
		$this->assign('action', $action);
		$this->assign('tableid', $id);
		parent::display($tpl);
	}
}
?>