<?php
/**
 * Plugin element to a series of radio buttons
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikRadiobutton extends FabrikModelElement {

	var $_pluginName = 'fabrikradiobutton';

	var $hasLabel = false;

	/** should the table render functions use html to display the data */
	var $renderWithHTML = true;

	/**
	 * Constructor
	 */

	function __construct()
	{
		$this->hasSubElements = true;
		parent::__construct();
	}

	/**
	 * shows the data formatted for the csv data
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData_csv($data, $oAllRowsData)
	{
		$this->renderWithHTML = false;
		$d = $this->renderTableData($data, $oAllRowsData);
		$this->renderWithHTML = true;
		return $d;
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		$data = explode(GROUPSPLITTER, $data);
		for ($i=0; $i <count($data); $i++) {
			$data[$i] =  $this->_renderTableData($data[$i], $oAllRowsData);
		}
		$data = implode(GROUPSPLITTER, $data);
		return parent::renderTableData($data, $oAllRowsData);
	}

	function _renderTableData($data, $oAllRowsData)
	{
		$params =& $this->getParams();
		//check if the data is in csv format, if so then the element is a multi drop down
		if (strstr($data, ',') && $params->get('multiple', 0) == 1) {
			$aData = explode(',', $data);
			$sLabels = '';
			foreach ($aData as $tmpVal) {
				if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
					$sLabels .= $this->_replaceWithIcons($tmpVal). "<br />";
				} else {
					$sLabels .= $this->getLabelForValue($tmpVal). "<br />";
				}
			}
			return FabrikString::rtrimword( $sLabels, "<br />");
		} else {
			//$$$rob ok it shouldnt ever been in an array but with specific access settings the data can be
			//stored as ' 	1|-|'
			$data2 = explode(GROUPSPLITTER2, $data);

			// $$$ rob if emtpy data revert to the default value
			if (count($data2) == 1 && $data2[0] == '') {
				$data2 = $this->getDefaultValue();
			}
			foreach ($data2 as $data) {
				if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
					$icon = $this->_replaceWithIcons($data);
					return $this->iconsSet == true ? $icon : $this->getLabelForValue($data);
				} else {
					return $this->getLabelForValue($data);
				}
			}
		}
		return $data;
	}

	/**
	 *  can be overwritten in add on classes
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		$str = '';
		if (!is_array($val)) {
			//import from csv the data is in a string format
			$val = explode(GROUPSPLITTER, $val);
		}
		$element = $this->getElement();
		foreach ($val as $v) {
			if (is_array($v)) { //repeat group
				foreach ($v as $w) {
					$str .= $w . GROUPSPLITTER;
				}
			} else {
				$str .= $v . GROUPSPLITTER;
			}
		}
		$str = FabrikString::rtrimword( $str, GROUPSPLITTER);
		return $str;
	}

	/**
	 * get the radio buttons possible values
	 * @return array of radio button values
	 */

	public function getOptionValues()
	{
		$element 	=& $this->getElement();
		return explode("|", $element->sub_values);
	}

	/**
	 * get the radio buttons possible labels
	 * @return array of radio button labels
	 */

	protected function getOptionLabels()
	{
		$element 	=& $this->getElement();
		return explode("|", $element->sub_labels);
	}

	/**
	 * format the read only output for the page
	 * @param string $value
	 * @param string label
	 * @return string value
	 */

	protected function getReadOnlyOutput($value, $label)
	{
		$params =& $this->getParams();
		if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
			$label = $this->_replaceWithIcons($value);
		}
		return $label;
	}

	/**
	 * draws the form element
	 * @param array data
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name 		= $this->getHTMLName($repeatCounter);
		$id				= $this->getHTMLId($repeatCounter);
		$params 	=& $this->getParams();
		$element 	=& $this->getElement();
		$arVals 	= $this->getOptionValues();
		$arTxt 		= $this->getOptionLabels();
		$selected = $this->getValue($data, $repeatCounter);
		$options_per_row = intval( $params->get('options_per_row', 0)); // 0 for one line
		if ($options_per_row > 0) {
			$percentageWidth = floor(floatval(100) / $options_per_row) - 2;
			$div = "<div class=\"fabrik_subelement\" style=\"float:left;width:" . $percentageWidth . "%\">\n";
		}
		$str = "<div class=\"fabrikSubElementContainer\" id=\"$id\">";
		$aRoValues = array();

		//if we have added an option that hasnt been saved to the database. Note you cant have
		// it not saved to the database and asking the user to select a value and label
		if ($params->get('allow_frontend_addtoradio', false) && !empty($selected)) {
			foreach ($selected as $sel) {
				if (!in_array($sel, $arVals)) {
					if (!empty($sel)) {
						$arVals[] = $sel;
						$arTxt[] = $sel;
					}
				}
			}
		}
		//$$$ rob removed subelement ids for repeat group validation & element js code
		for ($ii = 0; $ii < count($arVals); $ii ++) {
			if ($options_per_row > 0) {
				$str .= $div;
			}
			if (is_array($selected) and in_array($arVals[$ii], $selected)) {
				$aRoValues[] = $this->getReadOnlyOutput($arVals[$ii], $arTxt[$ii]);
				$checked = "checked=\"checked\"";
			} else {
				$checked = "";
			}
			$value = htmlspecialchars( $arVals[$ii], ENT_QUOTES); //for values like '1"'
			$input = "<input class=\"fabrikinput\" type=\"radio\" name=\"$name\" value=\"$value\" $checked />";
			if ($params->get('radio_element_before_label')  == '1') {
				$str .= "<label>$input<span>$arTxt[$ii]</span></label>\n";
			} else {
				$str .= "<label><span>$arTxt[$ii]</span>$input</label>\n";
			}
			if ($options_per_row > 0) {
				$str .= "</div> <!-- end row div -->\n";
			}
		}
		if (!$this->_editable) {
			return implode(',', $aRoValues);
		}

		$str .="</div>";
		if ($params->get('allow_frontend_addtoradio', false)) {
			$onlylabel = $params->get('rad-allowadd-onlylabel');
			$str .= $this->getAddOptionFields($onlylabel, $repeatCounter);
		}
		return $str;
	}

	/**
	 * can be overwritten by plugin class
	 * determines the label used for the browser title
	 * in the form/detail views
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string default value
	 */

	function getTitlePart($data, $repeatCounter = 0, $opts = array() )
	{
		$val = $this->getValue($data, $repeatCounter, $opts);
		$labels = $this->getOptionLabels();
		$values = $this->getOptionValues();
		$str = '';
		if (is_array($val)) {
			foreach ($val as $tmpVal) {
				$key = array_search($tmpVal, $values);
				$str.= ($key === false) ? $tmpVal : $labels[$key];
				$str.= " ";
			}
		} else {
			$str = $val;
		}
		return $str;
	}

	/**
	 * this really does get just the default value (as defined in the element's settings)
	 * @return array
	 */

	function getDefaultValue($data = array() )
	{
		if (!isset($this->_default)) {
			$this->_default	 	= str_replace('|', GROUPSPLITTER2, FabrikString::rtrimword($this->getElement()->sub_intial_selection, '|'));
			$this->_default	 	= explode(GROUPSPLITTER2, $this->_default);
		}
		return $this->_default;
	}

	/**
	 * determines the value for the element in the form view
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return array default value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array() )
	{
		if (!isset($this->defaults)) {
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults)) {
			$groupModel =& $this->_group;
			$joinid			= $groupModel->getGroup()->join_id;
			$formModel 	=& $this->_form;
			$element 		=& $this->getElement();

			// 	$$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			if (array_key_exists('use_default', $opts) && $opts['use_default'] == false) {
				$default = array();
			} else {
				$default = $this->getDefaultValue($data);
			}
			$name = $this->getFullName(false, true, false);

			if ($groupModel->isJoin()) {
				if ($groupModel->canRepeat()) {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) &&  array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
						$default = $data['join'][$joinid][$name][$repeatCounter];
					}
				} else {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid])) {
						if (is_array($data['join'][$joinid][$name])) {
							$default = $data['join'][$joinid][$name][0];
						}
						else {
							$default = $data['join'][$joinid][$name];
						}
					}
				}
			} else {
				if ($groupModel->canRepeat()) {
					//can repeat NO join
					if (array_key_exists($name, $data)) {
						if (is_array($data[$name])) {
							//occurs on form submission for fields at least
							$a = $data[$name];
						} else {
							//occurs when getting from the db
							$a = $data[$name] == '' ? array() :	explode(GROUPSPLITTER, $data[$name]);
						}
						$default = JArrayHelper::getValue($a, $repeatCounter, $default);
					}
				} else {
					$default = JArrayHelper::getValue($data, $name, $default);
				}
			}
			if ($default === '') { //query string for joined data
				$default = JArrayHelper::getValue($data, $name);
			}
			$element->default = $default;
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1) {
				$formModel->getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			if (is_string($element->default)) {
				$element->default = explode(GROUPSPLITTER2, $element->default);
			}
			$this->defaults[$repeatCounter] = $element->default;

		}
		return $this->defaults[$repeatCounter];
	}

	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "TEXT";
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings()
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		$element =& $this->getElement();
		FabrikHelperHTML::script('admin.js', 'components/com_fabrik/plugins/element/fabrikradiobutton/', true);
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	FabrikHelperAdminHTML::subElementFields($element);?>
<fieldset><?php echo $pluginParams->render();?></fieldset>
<fieldset><legend><?php echo JText::_('Sub elements');?></legend>
<table style="width: 100%">
	<tr>
		<th style="width: 5%"></th>
		<th style="width: 30%"><?php echo JText::_('VALUE')?></th>
		<th style="width: 30%"><?php echo JText::_('LABEL')?></th>
		<th style="width: 30%"><?php echo JText::_('DEFAULT')?></th>
	</tr>
</table>

<ul id="rad_subElementBody" class="subelements">
	<li></li>
</ul>
<a class="addButton" href="#" id="addRadio"> <?php echo JText::_('Add'); ?>
</a></fieldset>
<fieldset><legend><?php echo JText::_('Add options') ?></legend> <?php echo $pluginParams->render('params', 'add'); ?>
</fieldset>
</div>
	<?php

	}

	function getAdminJS()
	{
		$element =& $this->getElement();
		$mooversion = (FabrikWorker::getMooVersion() == 1 ) ? 1.2 : 1.1;
		$script  = "\tvar fabrikradiobutton = new fabrikAdminRadiobutton({'mooversion':'$mooversion'});\n".
		"\tpluginControllers.push({element:'fabrikradiobutton', controller: fabrikradiobutton});\n";
		$json = array();
		$sub_values 	= $this->getOptionValues();
		$sub_texts 	= $this->getOptionLabels();
		$sub_intial_selections = explode("|", $element->sub_intial_selection);
		$sub_intial_selections = (array)$sub_intial_selections;
		for ($ii = 0; $ii < count($sub_values) && $ii < count($sub_texts); $ii ++) {
			$bits = array(html_entity_decode($sub_values[$ii], ENT_QUOTES), html_entity_decode($sub_texts[$ii], ENT_QUOTES));
			if (in_array($sub_values[$ii], $sub_intial_selections)) {
				$bits[] = 'checked';
			}
			$json[] = $bits;
		}
		$script .= "\tfabrikradiobutton.addSubElements(". json_encode($json) . ");\n";
		return $script;
	}


	/**
	 * used to format the data when shown in the form's email
	 * @param array radio button ids
	 * @return string formatted value
	 */

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		if (empty($value)) {
			return '';
		}
		$labels = $this->getOptionLabels();
		$values = $this->getOptionValues();
		$key = array_search($value[0], $values);
		$val = ($key === false) ? $value[0] : $labels[$key];
		return $val;
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$params 		=& $this->getParams();
		$id 				= $this->getHTMLId($repeatCounter);
		$data 			=& $this->_form->_data;
		$arVals 		= $this->getOptionValues();
		$arTxt 			= $this->getOptionLabels();
		$opts =& $this->getElementJSOptions($repeatCounter);

		$opts->value    = $this->getValue($data, $repeatCounter);
		$opts->defaultVal  = $this->getDefaultValue($data);

		$opts->data = array_combine($arVals, $arTxt);
		$opts->allowadd = $params->get('allow_frontend_addtoradio', false) ? true : false;
		$opts = json_encode($opts);
		return "new fbRadio('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikradiobutton/', true);
	}

	/**
	 * Get the table filter for the element
	 * @return string filter html
	 */

	function getFilter($counter = 0, $normal = true)
	{
		$tableModel  	= $this->getTableModel();
		$groupModel		= $this->getGroup();
		$table				=& $tableModel->getTable();
		$element			=& $this->getElement();

		$params 			=& $this->getParams();
		$elName 			= $this->getFullName(false, true, false);
		$htmlid				= $this->getHTMLId() . 'value';
		$v = 'fabrik___filter[table_'.$table->id.'][value]';
		$v .= ($normal) ? '['.$counter.']' : '[]';
		$values 	= $this->getOptionValues();
		//corect default got
		$default = $this->getDefaultFilterVal($normal, $counter);

		if (!$normal || in_array($element->filter_type, array('range', 'dropdown'))) {
			$rows = $this->filterValueList($normal);
			$this->unmergeFilterSplits($rows);
			$this->reapplyFilterLabels($rows);
			if (!in_array('', $values)) {
				array_unshift($rows, JHTML::_('select.option',  '', $this->filterSelectLabel()));
			}
		}
		$size = $params->get('filter_length', 20);
		switch ($element->filter_type)
		{
			case "range":
				if (!is_array($default)) {
					$default = array('', '');
				}

				$attribs = 'class="inputbox" size="1" ';
				$return = JHTML::_('select.genericlist', $rows , $v.'[0]', 'class="inputbox fabrik_filter" size="1" ', 'value', 'text', $default[0], $element->name . "_filter_range_0");
				$return .= JHTML::_('select.genericlist', $rows , $v.'[1]', 'class="inputbox fabrik_filter" size="1" ', 'value', 'text', $default[1], $element->name . "_filter_range_0");
				break;

			case "dropdown":
			default:
			case '':
				$return = JHTML::_('select.genericlist', $rows, $v, 'class="inputbox fabrik_filter" size="1" ', 'value', 'text', $default, $htmlid);
				break;

			case "field":
				$return = "<input type=\"text\" class=\"inputbox fabrik_filter\" name=\"$v\" value=\"$default\" size=\"$size\" id=\"$htmlid\" />";
				break;

			case 'auto-complete':
				$return = "<input type=\"hidden\" name=\"$v\" class=\"inputbox fabrik_filter\" value=\"$default\" id=\"$htmlid\"  />";
				$return .= "<input type=\"text\" name=\"$v-auto-complete\" class=\"inputbox fabrik_filter autocomplete-trigger\" size=\"$size\" value=\"$default\" id=\"$htmlid-auto-complete\"  />";
				FabrikHelperHTML::autoComplete($htmlid, $this->getElement()->id, $this->_pluginName);
				break;

		}
		if ($normal) {
			$return .= $this->getFilterHiddenFields($counter, $elName);
		} else {
			$return .= $this->getAdvancedFilterHiddenFields();
		}
		return $return;
	}

	/**
	 * Get the sql for filtering the table data and the array of filter settings
	 * @param string filter value
	 * @return string filter value
	 */

	function prepareFilterVal($val)
	{
		$arVals = $this->getOptionValues();
		$arTxt 	= $this->getOptionLabels();
		for ($i=0; $i<count($arTxt); $i++) {
			if (strtolower($arTxt[$i]) == strtolower($val)) {
				$val =  $arVals[$i];
				return $val;
			}
		}
		return $val;
	}

	/**
	 * trigger called when a row is stored
	 * check if new options have been added and if so store them in the element for future use
	 * @param array data to store
	 */

	function onStoreRow($data)
	{
		$params =& $this->getParams();
		if (!$params->get('rad-savenewadditions')) {
			return;
		}
		$element =& $this->getElement();
		$group =& $this->getGroup();
		if ($group->canRepeat()){
			$data = JRequest::get('post');
			$repeatCounts = JRequest::getVar('fabrik_repeat_group', array());
			$repeatCount = JArrayHelper::getValue($repeatCounts, $group->getGroup()->id);
			for ($i =0; $i < $repeatCount; $i++) {
				$k = $this->getHTMLId($i). '_additions';
				if(array_key_exists($k, $data)) {
					$this->addSubElement($data[$k]);
				}
			}
		} else {
			$k = $element->name . '_additions';
			if (array_key_exists($k, $data)) {
				$this->addSubElement($data[$k]);
			}
		}
	}

	/**
	 * OPTIONAL
	 * If your element risks not to post anything in the form (e.g. check boxes with none checked)
	 * the this function will insert a default value into the database
	 * @param array form data
	 * @return array form data
	 */

	function getEmptyDataValue(&$data )
	{
		$params 					=& $this->getParams();
		$element =& $this->getElement();
		if (!array_key_exists($element->name, $data)) {
			$sel = explode("|", $element->sub_intial_selection);
			$sel = $sel[0];
			$arVals = $this->getOptionValues();
			$data[$element->name] = array($arVals[$sel]);
		}
	}

	/**
	 *
	 * Examples of where this would be overwritten include drop downs whos "please select" value might be "-1"
	 * @param array data posted from form to check
	 * @return bol if data is considered empty then returns true
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		if (is_array($data)) {
			foreach ($data as $d) {
				if ($d !== '') {
					return false;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 * @param int repeat group counter
	 * @return array html ids to watch for validation
	 */

	function getValidationWatchElements($repeatCounter)
	{
		$id 			= $this->getHTMLId($repeatCounter);
		$ar = array(
			'id' 			=> $id,
			'triggerEvent' => 'click'
			);
			return array($ar);
	}

	/**
	 * this builds an array containing the filters value and condition
	 * @param string initial $value
	 * @param string intial $condition
	 * @param string eval - how the value should be handled
	 * @return array (value condition)
	 */

	function getFilterValue($value, $condition, $eval)
	{
		$value = $this->prepareFilterVal($value);
		$return = parent::getFilterValue($value, $condition, $eval);
		return $return;
	}

	function autocomplete_options()
	{
		$rows = $this->filterValueList(true);
		$this->unmergeFilterSplits($rows);
		$this->reapplyFilterLabels($rows);
		$v = addslashes(JRequest::getVar('value'));
		for ($i = count($rows)-1; $i >= 0; $i--) {
			if (!preg_match("/$v(.*)/i", $rows[$i]->text)) {
				unset($rows[$i]);
			}
		}
		$rows = array_values($rows);
		echo json_encode($rows);
	}
}
?>