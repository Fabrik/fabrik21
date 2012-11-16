<?php
/**
 * Plugin element to render dropdown
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikDropdown extends FabrikModelElement {

	var $_pluginName = 'fabrikdropdown';

	/**
	 * Constructor
	 */

	var $defaults = null;

	/** should the table render functions use html to display the data */
	var $renderWithHTML = true;

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
		$params 	=& $this->getParams();
		$tableModel =& $this->getTableModel();
		$multiple = $params->get('multiple', 0);
		$sLabels 	= array();
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
					$l = $this->_replaceWithIcons($l);
				}
				$l = $tableModel->_addLink($l, $this, $oAllRowsData, $i);
				if (trim($l) !== '') {
					$lis[] = ($multiple && $this->renderWithHTML) ? "<li>$l</li>" : $l;
				}
			}
			if (!empty($lis)) {
				$uls[] = ($multiple && $this->renderWithHTML) ? "<ul class=\"fabrikRepeatData\">".implode(" ",$lis)."</ul>" : implode(" ",$lis);
			}
		}
		//$$$rob if only one repeat group data then dont bother encasing it in a <ul>
		if ($this->renderWithHTML) {
			return count($gdata) !== 1 ? "<ul class=\"fabrikRepeatData\">" . implode(" ", $uls ) . "</ul>" : implode(" ", $uls);
		} else {
			return implode(" ", $uls);
		}
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 * @return string data formatted to be stored in the database
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
			$return = $val;// was commented our for test for inline table edit but this messed up csv import
		}
		$return = FabrikString::rtrimword( $return, GROUPSPLITTER);
		$return = FabrikString::rtrimword( $return, GROUPSPLITTER2);
		return $return;
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
		$allowAdd = $params->get('allow_frontend_addtodropdown', false);
		$arVals 	= explode("|", $element->sub_values);
		$arTxt 		= explode("|", $element->sub_labels);
		$multiple = $params->get('multiple', 0);
		$multisize = $params->get('dropdown_multisize', 3);
		$selected = $this->getValue($data, $repeatCounter);
		$errorCSS = (isset($this->_elementError) &&  $this->_elementError != '') ? " elementErrorHighlight" : '';
		$attribs 	= 'class="fabrikinput inputbox'.$errorCSS."\"";

		if ($multiple == "1") {
			$attribs 	.= " multiple=\"multiple\" size=\"$multisize\" ";
		}
		$i 					= 0;
		$aRoValues 	= array();
		$opts 			= array();
		foreach ($arVals as $tmpval) {
			$tmpval = htmlspecialchars($tmpval, ENT_QUOTES); //for values like '1"'
			$opts[] = JHTML::_('select.option', $tmpval, JArrayHelper::getValue($arTxt, $i));
			if (is_array($selected) && in_array($tmpval, $selected)) {
				if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
					$aRoValues[] = $this->_replaceWithIcons($tmpval);
				} else {
					$aRoValues[] = $arTxt[$i];
				}
			}
			$i ++;
		}
		//if we have added an option that hasnt been saved to the database. Note you cant have
		// it not saved to the database and asking the user to select a value and label
		if ($params->get('allow_frontend_addtodropdown', false) && !empty($selected)) {
			// $$$ hugh - no idea why but sometimes $selected is an int, not an array
			if (is_array($selected)) {
				foreach ($selected as $sel) {
					if (!in_array($sel, $arVals) && $sel !== '') {
						$opts[] = JHTML::_('select.option', $sel, $sel);
					}
				}
			}
			else {
				if (!in_array($selected, $arVals) && $selected !== '') {
					$opts[] = JHTML::_('select.option', $selected, $selected);
				}
			}
		}
		$str = JHTML::_('select.genericlist', $opts, $name, $attribs, 'value', 'text', $selected, $id);
		if (!$this->_editable) {
			return implode(', ', $aRoValues);
		}
		if ($params->get('allow_frontend_addtodropdown', false)) {
			$onlylabel = $params->get('dd-allowadd-onlylabel');
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
		if (!$params->get('dd-savenewadditions')) {
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
			if(array_key_exists($k, $data)) {
				$this->addSubElement($data[$k]);
			}
		}
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikdropdown/', false);
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$element =& $this->getElement();
		$data =& $this->_form->_data;
		$arSelected = $this->getValue($data, $repeatCounter);
		$arVals = explode("|", $element->sub_values);
		$arTxt 	= explode("|", $element->sub_labels);
		$params =& $this->getParams();

		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts->allowadd = $params->get('allow_frontend_addtodropdown', false) ? true : false;
		$opts->value = $arSelected;
		$opts->defaultVal = $this->getDefaultValue($data);

		$opts->data = array_combine($arVals, $arTxt);
		$opts->splitter = GROUPSPLITTER2;
		$opts = json_encode($opts);
		return "new fbDropdown('$id', $opts)";
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
	 * @return array
	 */

	function getDefaultValue($data = array())
	{
		$params =& $this->getParams();

		if (!isset($this->_default)) {
			if ($this->getElement()->default != '') {

				$default = $this->getElement()->default;
				// nasty hack to fix #504 (eval'd default value)
				// where _default not set on first getDefaultValue
				// and then its called again but the results have already been eval'd once and are hence in an array
				if (is_array($default)) {
					$v = $default;
				} else {
					$w = new FabrikWorker();
					$v = $w->parseMessageForPlaceHolder($default, $data);
					if ($params->get('eval') == true) {
						$v = @eval($v);
						FabrikWorker::logEval($v, 'Caught exception on eval in '.$this->getElement()->name.'::getDefaultValue() : %s');
					}
				}
				$this->_default = is_string($v) ? explode('|', $v) : $v;
			} else {
				$this->_default = explode('|', $this->getElement()->sub_intial_selection);
			}
		}
		return $this->_default;
	}

	/**
	 * determines the value for the element in the form view
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return array default values
	 */

	function getValue($data, $repeatCounter = 0, $opts = array() )
	{
		if (!isset($this->defaults)) {
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults)) {
			$groupModel =& $this->_group;
			$group			=& $groupModel->getGroup();
			$joinid			= $group->join_id;
			$formModel 	=& $this->_form;
			$element		=& $this->getElement();
			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			if (array_key_exists('use_default', $opts) && $opts['use_default'] == false) {
				$value = array();
			} else {
				$value   = $this->getDefaultValue($data);
			}

			$name = $this->getFullName(false, true, false);

			if ($groupModel->isJoin()) {
				if ($groupModel->canRepeat()) {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) &&  array_key_exists($name, $data['join'][$joinid]) && array_key_exists($repeatCounter, $data['join'][$joinid][$name])) {
						$value = $data['join'][$joinid][$name][$repeatCounter];
					}
				} else {
					if (array_key_exists('join', $data) && array_key_exists($joinid, $data['join']) && is_array($data['join'][$joinid]) && array_key_exists($name, $data['join'][$joinid])) {
						$value = $data['join'][$joinid][$name];
					}
				}
			} else {
				if ($groupModel->canRepeat()) {
					//repeat group NO join
					if (array_key_exists($name, $data)) {
						if (is_array($data[$name])) {
							//occurs on form submission for fields at least
							$a = $data[$name];
						} else {
							//occurs when getting from the db
							$a = 	explode(GROUPSPLITTER, $data[$name]);
						}
						if (array_key_exists($repeatCounter, $a)) {
							$value = $a[$repeatCounter];
						}
					}
				} else {
					if (array_key_exists($name, $data)) {
						$value = $data[$name];
					}
				}
			}
			if ($value === '') { //query string for joined data
				$value = JArrayHelper::getValue($data, $name);
			}
			$element->default = $value;
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
	 * get the field description
	 * @return string field description e.g. varchar(255)
	 */

	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "VARCHAR(255)";
	}

	/**
	 * render the admin settings
	 */

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		$params =& $this->getParams();
		$element =& $this->getElement();
		FabrikHelperHTML::script('admin.js', 'components/com_fabrik/plugins/element/fabrikdropdown/', true);
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php FabrikHelperAdminHTML::subElementFields($element); ?>


<fieldset><legend><?php echo JText::_('Sub elements');?></legend>
<table style="width: 100%">
	<tr>
		<th style="width: 5%"></th>
		<th style="width: 30%"><?php echo JText::_('VALUE')?></th>
		<th style="width: 30%"><?php echo JText::_('LABEL')?></th>
		<th style="width: 30%"><?php echo JText::_('DEFAULT')?></th>
	</tr>
</table>
<ul id="drd_subElementBody" class="subelements">
	<li></li>
</ul>
<a class="addButton" href="#" id="addDropDown"><?php echo JText::_('Add'); ?></a>
</fieldset>
<fieldset><?php echo $pluginParams->render(); ?></fieldset>
<fieldset><legend><?php echo JText::_('Add options') ?></legend> <?php echo $pluginParams->render('params', 'add'); ?>
</fieldset>
</div>
<input type="hidden"
	name="params[drd_initial_selection]" value=""
	id="params_drd_initial_selection" />
		<?php
	}

	function getAdminJS()
	{
		$element =& $this->getElement();
		$mooversion = (FabrikWorker::getMooVersion() == 1 ) ? 1.2 : 1.1;
		$script = "\tvar fabrikdropdown = new fabrikAdminDropdown({'mooversion':'$mooversion'});\n".
		"\tpluginControllers.push({element:'fabrikdropdown', controller: fabrikdropdown});\n";
		$sub_values 	= explode("|", $element->sub_values);
		$sub_texts 	= explode("|", $element->sub_labels);
		$sub_intial_selections = explode("|", FabrikString::ltrimword($element->sub_intial_selection, '|'));
		$json = array();
		for ($ii = 0; $ii < count($sub_values) && $ii < count($sub_texts); $ii ++) {
			$bits = array(html_entity_decode($sub_values[$ii], ENT_QUOTES), html_entity_decode($sub_texts[$ii], ENT_QUOTES));
			if (in_array($sub_values[$ii], $sub_intial_selections)) {
				$bits[] = 'checked';
			}
			$json[] = $bits;
		}
		$script .= "\tfabrikdropdown.addSubElements(". json_encode($json) . ");\n";
		return $script;
	}

	/**
	 * used to format the data when shown in the form's email
	 * @param mixed element's data
	 * @param array form records data
	 * @param int repeat group counter
	 * @return string formatted value
	 */

	function getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		if ($this->_inRepeatGroup) {
			$val = array();
			foreach ($value as $v2) {
				$val[] = $this->_getEmailValue($v2, $data, $repeatCounter);
			}
		} else {
			$val = $this->_getEmailValue($value, $data, $repeatCounter);
		}

		return $val;
	}

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		$params =& $this->getParams();
		$split_str = $params->get('options_split_str', '');
		$element =& $this->getElement();
		$labels = explode('|', $element->sub_labels);
		$values = explode('|', $element->sub_values);
		$aLabels = array();
		if (is_string($value)) {
			$value = strstr($value, GROUPSPLITTER2) ? explode(GROUPSPLITTER2, $value) : array($value);
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
			if (count($aLabels) == 1) {
				$val = implode("", $aLabels);
			} else {
				$val = "<ul><li>".implode("</li><li>", $aLabels)."</li></ul>";
			}
		} else {
			$val = implode($split_str, $aLabels);
		}
		return $val;
	}

	/**
	 * Examples of where this would be overwritten include drop downs whos "please select" value might be "-1"
	 * @param string data posted from form to check
	 * @return bol if data is considered empty then returns true
	 */

	function dataConsideredEmpty($data, $repeatCounter)
	{
		// $$$ hugh - $data seems to be an array now?
		if (is_array($data)) {
			if (empty($data[0])) {
				return true;
			}
		} else {
			if ($data == '' || $data == '-1') {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get the table filter for the element
	 * @param bol do we render as a normal filter or as an advanced search filter
	 * if normal include the hidden fields as well (default true, use false for advanced filter rendering)
	 * @return string filter html
	 */

	function getFilter($counter = true, $normal = true)
	{
		$element 	=& $this->getElement();
		$values 	= explode('|', $element->sub_values);
		$default 	= $this->getDefaultFilterVal($normal, $counter);
		$elName 	= $this->getFullName(false, true, false);
		$htmlid		= $this->getHTMLId() . 'value';
		$table		=& $this->getTableModel()->getTable();
		$params		=& $this->getParams();
		$v = 'fabrik___filter[table_'.$table->id.'][value]';
		$v .= ($normal) ? '['.$counter.']' : '[]';

		if (in_array($element->filter_type, array('range', 'dropdown', ''))) {
			$rows = $this->filterValueList($normal);
			$this->unmergeFilterSplits($rows);
			$this->reapplyFilterLabels($rows);
			JArrayHelper::sortObjects($rows, $params->get('filter_groupby', 'text'));
			if (!in_array('', $values)) {
				array_unshift($rows, JHTML::_('select.option',  '', $this->filterSelectLabel()));
			}
		}

		$attribs = 'class="inputbox fabrik_filter" size="1" ';
		$size = $params->get('filter_length', 20);
		switch ($element->filter_type)
		{
			case "range":
				$default1 = is_array($default)? $default[0] : '';
				$return = JHTML::_('select.genericlist', $rows, $v.'[0]', $attribs, 'value', 'text', $default1, $element->name . "_filter_range_0");
				$default1 = is_array($default) ? $default[1] : '';
				$return .= JHTML::_('select.genericlist', $rows, $v.'[1]', $attribs, 'value', 'text', $default1 , $element->name . "_filter_range_1");
				break;
			case "dropdown":
			default:
				$return = JHTML::_('select.genericlist', $rows, $v, $attribs, 'value', 'text', $default, $htmlid);
				break;

			case "field":
				if (get_magic_quotes_gpc()) {
					$default = stripslashes($default);
				}
				$default = htmlspecialchars($default);
				$return = "<input type=\"text\" name=\"$v\" class=\"inputbox fabrik_filter\" size=\"$size\" value=\"$default\" id=\"$htmlid\" />";
				break;

			case 'auto-complete':
				if (get_magic_quotes_gpc()) {
					$default			= stripslashes($default);
				}
				$default = htmlspecialchars($default);
				$return = "<input type=\"hidden\" name=\"$v\" class=\"inputbox fabrik_filter\" value=\"$default\" id=\"$htmlid\"  />";
				$return .= "<input type=\"text\" name=\"$v-auto-complete\" class=\"inputbox fabrik_filter autocomplete-trigger\" size=\"$size\" value=\"$default\" id=\"$htmlid-auto-complete\" />";
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
	 * replace the label with the value
	 * @param mixed labels
	 * @param bool $loose - should a loose match be applied. Used in search all so you can search for partial labels. E.g search on 'oo' will match the label 'foo' and return the value 'bar'
	 */

	protected function replaceLabelWithValue($selected, $loose = false)
	{
		$selected = (array)$selected;
		foreach ($selected as &$s) {
			$s = str_replace("'", "", $s);
		}
		$element =& $this->getElement();
		$vals = explode("|", $element->sub_values);
		$labels = explode("|", $element->sub_labels);
		$return = array();
		$aRoValues	= array();
		$opts = array();
		$i = 0;
		foreach ($labels as $label) {
			if (!$loose) {
				if (in_array($label, $selected)) {
					$return[] = $vals[$i];
				}
			} else {
				foreach ($selected as $s) {
					if (stristr(trim($label), $s)) {
						$return[] = $vals[$i];
					}
				}
			}
			$i++;
		}
		return $return;
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

	function getFilterQuery($key, $condition, $label, $originalValue, $type = 'normal')
	{
		$value = $label;
		if ($type == 'searchall') {
			// $$$ hugh - (sometimes?) $label is already quoted, which is causing havoc ...
			$db =& JFactory::getDBO();
			$label = trim($label, "'");
			$values = $this->replaceLabelWithValue($label, true);
			if (empty($values)) {
				$value = '';
			}
			else {
				$value = $values[0];
			}
			if ($value == '') {
				$value = $label;
			}
			if (!preg_match('#^\'.*\'$#', $value)) {
				// $$$ 30/06/2011 rob dont escape the search as it may contain \\\ from preg_escape (e.g. search all on 'c+b)
				$value = $db->Quote($value, false);
			}
		}
		$this->encryptFieldName($key);
		$params =& $this->getParams();
		if ($params->get('multiple')) {
			$originalValue = trim($value, "'");
			$this->filterQuery = " ($key $condition $value OR $key LIKE '$originalValue".GROUPSPLITTER2."%'".
				" OR $key LIKE '%".GROUPSPLITTER2."$originalValue".GROUPSPLITTER2."%'".
				" OR $key LIKE '%".GROUPSPLITTER2."$originalValue'".
				" )";
		} else {
			$this->filterQuery = parent::getFilterQuery($key, $condition, $value, $originalValue, $type);
		}
		return $this->filterQuery;
	}

	/**
	 * Examples of where this would be overwritten include timedate element with time field enabled
	 * @param int repeat group counter
	 * @return array html ids to watch for validation
	 */

	function getValidationWatchElements($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$ar = array(
			'id' 			=> $id,
			'triggerEvent' => 'change'
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
				$val = $arVals[$i];
				return $val;
			}
		}
		return $val;
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