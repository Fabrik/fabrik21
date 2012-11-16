<?php
/**
 * Plugin element to render series of checkboxes
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikCheckbox  extends FabrikModelElement {

	var $_pluginName = 'fabrikcheckbox';

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
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		$return = '';
		if (is_array($val)) {
			foreach ($val as $key=>$v) {
				if (is_array($v)) {
					//checkboxes in repeat group
					$return .= implode(GROUPSPLITTER2, $v);
					$return .= GROUPSPLITTER;
				} else {
					//not in repeat group
					$return .= $v .GROUPSPLITTER2;
				}
			}
		} else {
			if (is_null($val)) {
				$return = '';
			}
			else {
				$return = $val; //for inline edit
			}
		}
		$return = FabrikString::rtrimword($return, GROUPSPLITTER);
		$return = FabrikString::rtrimword($return, GROUPSPLITTER2);
		return $return;
	}

	function renderTableData_csv( $data, $oAllRowsData )
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
		if (empty($data)) {
			return "";
		}
		$params =& $this->getParams();
		$split_str = $params->get('options_split_str', GROUPSPLITTER2);
		$tableModel =& $this->getTableModel();
		$element 	=& $this->getElement();
		$values 	= explode("|", $element->sub_values);
		$labels 	= explode("|", $element->sub_labels);
		//repeat group data
		$gdata = explode(GROUPSPLITTER, $data);
		$uls = array();
		$useIcon = ($params->get('icon_folder') == -1 || $params->get('icon_folder') == '') ? false : true;
		foreach ($gdata as $i => $data) {
			$lis = array();
			$vals = explode(GROUPSPLITTER2, $data);
			foreach ($vals as $val) {
				if ($useIcon) {
					$l = $this->_replaceWithIcons($val);
				}
				if (!$this->iconsSet == true) {
					$l = $this->getLabelForValue($val);
					$l = $this->_replaceWithIcons( $l);
				}
				$l = $tableModel->_addLink($l, $this, $oAllRowsData, $i);
				if (trim($l) == '') { $l = '&nbsp';}
				$lis[] = $this->renderWithHTML ? "<li>$l</li>" : $l;
			}
			if (!empty($lis)) {
				if ($this->renderWithHTML) {
					$uls[] = "<ul class=\"fabrikRepeatData\">".implode(" ", $lis)."</ul>";
				} else {
					$uls[] = implode($split_str, $lis);
				}
			}
		}
		return implode(" ", $uls);
	}

	/**
	 * render raw data
	 *
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderRawTableData($data, $thisRow)
	{
		if (is_array($data)) {
			return implode(GROUPSPLITTER2, $data);
		} else {
			return $data;
		}
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name 		= $this->getHTMLName($repeatCounter);
		$id 			= $this->getHTMLId($repeatCounter);
		$element 	=& $this->getElement();
		$params 	=& $this->getParams();
		$str 			= "<div class=\"fabrikSubElementContainer\" id=\"$id\">";
		$arVals 	= explode("|", $element->sub_values);
		$arTxt 		= explode("|", $element->sub_labels);

		$options_per_row = intval($params->get('ck_options_per_row', 0)); // 0 for one line

		$selected = $this->getValue($data, $repeatCounter);
		$aRoValues = array();
		if ($options_per_row > 0) {
			$percentageWidth = floor(floatval(100) / $options_per_row) - 2;
			$div = "<div class=\"fabrik_subelement\" style=\"float:left;width:" . $percentageWidth . "%\">\n";
		}

		for ($ii = 0; $ii < count($arVals); $ii ++) {
			if ($options_per_row > 0) {
				$str .= $div;
			}
			$thisname = FabrikString::rtrimword($name, '[]') . "[$ii]";
			$label = "<span>".$arTxt[$ii]."</span>";
			$value = htmlspecialchars($arVals[$ii], ENT_QUOTES); //for values like '1"'
			$chx = "<input type=\"checkbox\" class=\"fabrikinput checkbox\" name=\"$thisname\" value=\"".$value."\" ";
			if (is_array($selected ) and in_array($arVals[$ii], $selected)) {

				if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
					$aRoValues[] = $this->_replaceWithIcons($arVals[$ii]);
				} else {
					$aRoValues[] = $arTxt[$ii];
				}
				$chx .= " checked=\"checked\" />\n";
			} else {
				$chx .= " />\n";
			}
			$str .= ($params->get('element_before_label')  == '1') ? "<label>".$chx.$label."</label>\n" : "<label>".$label.$chx."</label>\n";
			if ($options_per_row > 0) {
				$str .= "</div> <!-- end row div -->\n";
			}
		}


		if (!$this->_editable) {
			$splitter = ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') ? ' ' : ', ';
			return implode($splitter, $aRoValues);
		}
		if ($options_per_row > 0) {
			$str .= "<br />";
		}
		$str .="</div>";
		if ($params->get('allow_frontend_addtocheckbox', false)) {
			$onlylabel = $params->get('chk-allowadd-onlylabel');
			$str .= $this->getAddOptionFields($onlylabel, $repeatCounter);
		}
		return $str;
	}

	/**
	 * trigger called when a row is stored
	 * check if new options have been added and if so store them in the element for future use
	 * @param array data to store
	 */

	function onStoreRow($data)
	{
		$params =& $this->getParams();
		if (!$params->get('chk-savenewadditions')) {
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
	 * can be overwritten by plugin class
	 * determines the label used for the browser title
	 * in the form/detail views
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string default value
	 */

	function getTitlePart($data, $repeatCounter = 0, $opts = array())
	{
		$val = $this->getValue($data, $repeatCounter, $opts);
		$element =& $this->getElement();
		$labels = explode('|', $element->sub_labels);
		$values = explode('|',  $element->sub_values);
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
	 * @return unknown_type
	 */

	function getDefaultValue($data = array())
	{
		if (!isset($this->_default)) {
			$element		=& $this->getElement();
			$this->_default	 	= explode('|', $element->sub_intial_selection);
		}
		return $this->_default;
	}

	/**
	 * determines the value for the element in the form view
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		if (is_null($this->defaults)) {
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults)) {
			$groupModel =& $this->_group;
			$group			=& $groupModel->getGroup();
			$joinid			= $group->join_id;
			$formModel 	=& $this->getForm();
			$element		=& $this->getElement();
			// $$$rob - if no search form data submitted for the checkbox search element then the default
			// selecton was being applied instead
			if (array_key_exists('use_default', $opts) && $opts['use_default'] == false) {
				$default = array();
			} else {
				$default    = $this->getDefaultValue($data);
			}
			$name = $this->getFullName(false, true, false);

			if ($groupModel->isJoin()) {
				if ($groupModel->canRepeat()) {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) &&  array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
						$default = $data['join'][$joinid][$name][$repeatCounter];
					}
				} else {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid])) {
						// $$$ hugh - if in a non-joined repeat group, we may still have multiple row selections due to other
						// repeated join groups on the table.  So test to see if we have an array of joined data, and if so just
						// grab the first.
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
							$a = 	explode(GROUPSPLITTER, $data[$name]);
						}
						$default = JArrayHelper::getValue($a, $repeatCounter, $default);
					}
				} else {
					if (array_key_exists($name, $data)) {
						if (is_array($data[$name])) {
							//occurs on form submission for fields at least
							$default = $data[$name];
						} else {
							//occurs when getting from the db
							$default = explode(GROUPSPLITTER2, $data[$name]);
						}
					}
				}
			}
			if ($default === '') { //query string for joined data
				$default = JArrayHelper::getValue($data, $name);
			}
			$element->default = $default;
			$formModel =& $this->getForm();
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

	/**
	 * defines the type of database table field that is created to store the element's data
	 */
	function getFieldDescription()
	{
		$p =& $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "TEXT";
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$params =& $this->getParams();
		$id = $this->getHTMLId($repeatCounter);
		$element =& $this->getElement();

		$arVals = explode("|", $element->sub_values);
		$arTxt 	= explode("|", $element->sub_labels);
		$data 		=& $this->_form->_data;
		$arSelected = $this->getValue($data, $repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);

		$opts->value    = $arSelected;
		$opts->defaultVal  = $this->getDefaultValue($data);

		$opts->data = array_combine($arVals, $arTxt);
		$opts->allowadd = $params->get('allow_frontend_addtocheckbox', 0) == 0 ? false : true;
		$opts = json_encode($opts);
		return "new fbCheckBox('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikcheckbox/', true);
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		FabrikHelperHTML::script('admin.js', 'components/com_fabrik/plugins/element/fabrikcheckbox/', true);
		$params =& $this->getParams();
		$element =& $this->getElement();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	FabrikHelperAdminHTML::subElementFields($element);
	echo $pluginParams->render();
	?>
<fieldset><legend><?php echo JText::_('Sub elements');?></legend>
<table style="width: 100%">
	<tr>
		<th style="width: 5%"></th>
		<th style="width: 30%"><?php echo JText::_('VALUE')?></th>
		<th style="width: 30%"><?php echo JText::_('LABEL')?></th>
		<th style="width: 30%"><?php echo JText::_('DEFAULT')?></th>
	</tr>
</table>
<ul id="chk_subElementBody" class="subelements">
	<li></li>
</ul>
<a class="addButton" href="#" id="addCheckbox"> <?php echo JText::_('Add');?>
</a></fieldset>
<fieldset><legend><?php echo JText::_('Add options') ?></legend> <?php echo $pluginParams->render('params', 'add'); ?>
</fieldset>
<fieldset><legend><?php echo JText::_('Advanced') ?></legend> <?php echo $pluginParams->render('params', 'advanced'); ?>
</fieldset>
</div>
	<?php

	}

	function getAdminJS()
	{
		$element =& $this->getElement();
		$mooversion = (FabrikWorker::getMooVersion() == 1) ? 1.2 : 1.1;
		$sub_values 	= explode("|", $element->sub_values);
		$sub_texts 	= explode("|", $element->sub_labels);
		$sub_intial_selections = explode("|", $element->sub_intial_selection);

		$script = "\tvar fabrikcheckbox = new fabrikAdminCheckbox({'mooversion':'$mooversion'});\n".
		"\tpluginControllers.push({element:'fabrikcheckbox', controller:fabrikcheckbox});\n";

		$json = array();
		for ($ii = 0; $ii < count($sub_values) && $ii < count($sub_texts); $ii ++) {
			$bits = array(html_entity_decode($sub_values[$ii], ENT_QUOTES), html_entity_decode($sub_texts[$ii], ENT_QUOTES));
			if (in_array($sub_values[$ii], $sub_intial_selections)) {
				$bits[] = 'checked';
			}
			$json[] = $bits;
		}
		$script .= "\tfabrikcheckbox.addSubElements(". json_encode($json) . ");\n";
		return $script;
	}

	/**
	 * used to format the data when shown in the form's email
	 * @param mixed element's data
	 */

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$params 	=& $this->getParams();
		$split_str = $params->get('options_split_str', '');
		$element 	=& $this->getElement();
		$values 	= explode("|", $element->sub_values);
		$labels 	= explode("|", $element->sub_labels);
		$aLabels 	= array();

		if (is_string($value)) {
			$value = array($value);
		}

		if (is_array($value)) {
			foreach ($value as $tmpVal) {
				$key = array_search($tmpVal, $values);
				if ($key !== false) {
					$aLabels[] = $labels[$key];
				}
			}
		}
		if ($split_str == '') {
			$val = "<ul><li>".implode("</li><li>", $aLabels ) . "</li></ul>";
		} else {
			$val = implode($split_str, $aLabels);
		}
		if ($val === '') {
			$val = $params->get('ck_default_label');
		}
		return $val;
	}

	/**
	 * OPTIONAL
	 * If your element risks not to post anything in the form (e.g. check boxes with none checked)
	 * the this function will insert a default value into the database
	 * @param array form data
	 * @return array form data
	 */

	function getEmptyDataValue(&$data)
	{
		$params =& $this->getParams();
		$element =& $this->getElement();
		if (!array_key_exists($element->name, $data)) {
			$data[$element->name] = $params->get('ck_value');
		}
	}

	/**
	 * Get the table filter for the element
	 * @param bol do we render as a normal filter or as an advanced searc filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 * @return string filter html
	 */

	function getFilter($counter = 0, $normal = true)
	{
		$element			=& $this->getElement();
		$params 			=& $this->getParams();
		$elName 			= $this->getFullName(false, true, false);
		$htmlid				= $this->getHTMLId() . 'value';
		$table		=& $this->getTableModel()->getTable();
		$elName2 			= $this->getFullName(false, false, false);

		$v = 'fabrik___filter[table_'.$table->id.'][value]';
		$v .= ($normal) ? '['.$counter.']' : '[]';

		//corect default got
		$default = $this->getDefaultFilterVal($normal, $counter);
		//filter the drop downs lists if the table_view_own_details option is on
		//other wise the lists contain data the user should not be able to see
		// note, this should now use the prefilter data to filter the list

		$values 	= explode("|", $element->sub_values);
		$labels 	= explode("|", $element->sub_labels);

		if (in_array($element->filter_type, array('range', 'dropdown'))) {
			$rows = $this->filterValueList($normal);
			$this->unmergeFilterSplits($rows);
			$this->reapplyFilterLabels($rows);
			JArrayHelper::sortObjects($rows, $params->get('filter_groupby', 'text'));
			if (!in_array('', $values)) {
				array_unshift($rows, JHTML::_('select.option',  '', $this->filterSelectLabel()));
			}
		}
		$size = $params->get('filter_length', 20);

		//@TODO - $rows values are not htmlspecialchar'd so data like '1"' gives html validation error
		switch ($element->filter_type)
		{
			case "range":
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
				$return = "<input type=\"text\" class=\"inputbox fabrik_filter\" name=\"$v\" size=\"$size\" value=\"$default\" id=\"$htmlid\" />";
				break;

			case 'auto-complete':
				if (get_magic_quotes_gpc()) {
					$default			= stripslashes($default);
				}
				$default = htmlspecialchars($default);
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
		$element =& $this->getElement();
		$arVals = explode("|", $element->sub_values);
		$arTxt 	= explode("|", $element->sub_labels);
		for ($i=0; $i<count($arTxt); $i++) {
			if (strtolower($arTxt[$i]) == strtolower($val)) {
				$val =  $arVals[$i];
				return $val;
			}
		}
		return $val;
	}

	/**
	 * used in isempty validation rule
	 *
	 * @param array $data
	 * @return bol
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		$data = (array)$data;
		foreach ($data as $d) {
			if ($d != '') {
				return false;
			}
		}
		return true;
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
	 * build the filter query for the given element.
	 * @param $key element name in format `tablename`.`elementname`
	 * @param $condition =/like etc
	 * @param $value search string - already quoted if specified in filter array options
	 * @param $originalValue - original filter value without quotes or %'s applied
	 * @param string filter type advanced/normal/prefilter/search/querystring/searchall
	 * @return string sql query part e,g, "key = value"
	 */

	function getFilterQuery($key, $condition, $value, $originalValue, $type = 'normal')
	{
		$originalValue = trim($value, "'");
		$this->encryptFieldName($key);
		switch ($condition) {
			case '=':
				$str = " ($key $condition $value OR $key LIKE '$originalValue".GROUPSPLITTER2."%'".
				" OR $key LIKE '%".GROUPSPLITTER2."$originalValue".GROUPSPLITTER2."%'".
				" OR $key LIKE '%".GROUPSPLITTER2."$originalValue'".
				" )";
				break;
			default:
				$str = " $key $condition $value ";
				break;
		}
		return $str;
	}

	/**
	 * if no filter condition supplied (either via querystring or in posted filter data
	 * return the most appropriate filter option for the element.
	 * @return string default filter condition ('=', 'REGEXP' etc)
	 */

	function getDefaultFilterCondition()
	{
		return '=';
	}

	/**
	 * this builds an array containing the filters value and condition
	 * @param string initial $value
	 * @param string intial $condition
	 * @param string eval - how the value should be handled
	 * @return array (value condition)
	 */

	function getFilterValue($value, $condition, $eval )
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