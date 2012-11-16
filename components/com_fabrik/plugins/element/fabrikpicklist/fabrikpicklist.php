<?php
/**
 * Plugin element to two lists - one to select from the other to select into
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikPicklist extends FabrikModelElement {

	var $_pluginName = 'picklist';

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		$params =& $this->getParams();
		$element =& $this->getElement();
		$values = explode(GROUPSPLITTER2, $element->sub_values);
		$labels 	= explode(GROUPSPLITTER2, $element->sub_labels);
		//check if the data is in csv format, if so then the element is a multi drop down
		if (strstr($data, GROUPSPLITTER2)) {
			$aData = explode(GROUPSPLITTER2, $data);
			$sLabels = '';
			foreach ($aData as $tmpVal) {
				if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
					$sLabels .= $this->_replaceWithIcons($tmpVal). "<br />";
				}else{
					$key = array_search($tmpVal, $values);
					$sLabels.= $labels[$key]. "<br />";
				}
			}
			return FabrikString::rtrimword( $sLabels, "<br />");
		} else {
			if ($params->get('icon_folder') != -1 && $params->get('icon_folder') != '') {
				return $this->_replaceWithIcons($data). "<br />";
			}else{
				$key = array_search($data, $values);
				return $labels[$key];
			}

		}
		return parent::renderTableData($data, $oAllRowsData);
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name 		= $this->getHTMLName($repeatCounter);
		$id				= $this->getHTMLId($repeatCounter);
		$element 	=& $this->getElement();
		$params 	=& $this->getParams();
		$arVals 	= explode(GROUPSPLITTER2, $element->sub_values);
		$arTxt 		= explode(GROUPSPLITTER2, $element->sub_labels);
		$arSelected = $this->getValue($data, $repeatCounter);
		$errorCSS  = (isset($this->_elementError) &&  $this->_elementError != '') ?  " elementErrorHighlight" : '';
		$attribs = 'class="picklistcontainer'.$errorCSS."\"";
		$style = ".frompicklist, .topicklist{\n"
		."background-color:#efefef;\n"
		."padding:5px !important;\n"
		."}\n"
		."\n"
		."div.picklistcontainer{\n"
		."width:40%;\n"
		."margin-right:10px;\n"
		."margin-bottom:10px;\n"
		."float:left;\n"
		."}\n"
		."\n"
		.".frompicklist li, .topicklist li, li.picklist{\n"
		."background-color:#FFFFFF;\n"
		."margin:3px;\n"
		."padding:5px !important;\n"
		."cursor:move;\n"
		."}\n"
		."\n"
		."li.emptyplicklist{\n"
		."background-color:transparent;\n"
		."cursor:pointer;\n"
		."}";
		$document =& JFactory::getDocument();
		$document->addStyleDeclaration($style);
		$i = 0;
		$aRoValues = array();
		$fromlist = "from:<ul id='$id" . "_fromlist' class='frompicklist'>\n";
		$tolist = "to:<ul id='$id" . "_tolist'class='topicklist'>\n";
		foreach ($arVals as $v) {
			//$tmptxt = addslashes(htmlspecialchars($arTxt[$i]));
			if (!in_array($v, $arSelected)) {
				$fromlist .= "<li id='{$id}_value_$v' class='picklist'>". $arTxt[$i] . "</li>\n";
			}
			$i ++;
		}
		$i = 0;
		$lookup = array_flip($arVals);
		foreach ($arSelected as $v) {
			if ($v == '' || $v == '-') {
				continue;
			}
			$k = JArrayHelper::getValue($lookup, $v);
			$tmptxt = addslashes(htmlspecialchars(JArrayHelper::getValue($arTxt, $k)));
			$tolist .= "<li id=\"{$id}_value_$v\" class=\"$v\">". $tmptxt . "</li>\n";
			$aRoValues[] = $tmptxt;
			$i ++;
		}
		if (empty($arSelected)) {
			$fromlist .= "<li class=\"emptyplicklist\">". JText::_('Drag options here') . "</li>\n";
		}
		if (empty($aRoValues)) {
			$tolist .= "<li class=\"emptyplicklist\">". JText::_('Drag options here') . "</li>\n";
		}

		$fromlist .= "</ul>\n";
		$tolist .= "</ul>\n";

		$str = "<div $attribs>$fromlist</div><div class='picklistcontainer'>$tolist</div>";
		$str .=  $this->getHiddenField($name, implode(GROUPSPLITTER2, $arSelected), $id);

		if (!$this->_editable) {
			return implode(', ', $aRoValues);
		}
		if ($params->get('allowadd', false)) {
			$onlylabel = $params->get('allowadd-onlylabel');
			$str .= $this->getAddOptionFields($onlylabel, $repeatCounter);
		}
		return $str;
	}

	/**
	 * trigger called when a row is stored
	 * check if new options have been added and if so store them in the element for future use
	 * @param array data to store
	 */

	function onStoreRow(&$data)
	{
		$element =& $this->getElement();
		$params =& $this->getParams();
		if ($params->get('savenewadditions') && array_key_exists($element->name . '_additions', $data)) {
			$added = stripslashes($data[$element->name . '_additions']);
			if (trim($added) == '') {
				return;
			}
			$json = new Services_JSON();
			$added = $json->decode($added);
			$arVals = explode(GROUPSPLITTER2, $element->sub_values);
			$arTxt 	= explode(GROUPSPLITTER2, $element->sub_labels);
			$d 			= explode(GROUPSPLITTER2, $data[$element->name]);
			$found = false;
			foreach ($added as $obj) {
				if (!in_array($obj->val, $arVals)) {
					$arVals[] = $obj->val;
					$found = true;
					$arTxt[] = $obj->label;
				}
			}
			if ($found) {
				$element->sub_values = implode(GROUPSPLITTER2, $arVals);
				$element->sub_labels = implode(GROUPSPLITTER2, $arTxt);
				$element->store();
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
		FabrikHelperHTML::script('Event.Mock.js', 'components/com_fabrik/libs/mootools1.2/', false);
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikpicklist/', false);
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id 				= $this->getHTMLId($repeatCounter);
		$element 		=& $this->getElement();
		$data 			=& $this->_form->_data;
		$arVals 		= explode(GROUPSPLITTER2, $element->sub_values);
		$arTxt 			= explode(GROUPSPLITTER2, $element->sub_labels);
		$params 		=& $this->getParams();

		$opts 								=& $this->getElementJSOptions($repeatCounter);
		$opts->mooversion 		= FabrikWorker::getMooVersion() == 1 ? 1.2 : 1.1;
		$opts->allowadd 			= $params->get('allowadd', false);;
		$opts->defaultVal 		= $this->getValue($data, $repeatCounter);;
		$opts->data 					= array_combine( $arVals, $arTxt);
		$opts->splitter 			= GROUPSPLITTER2;
		$opts->hovercolour 		= $params->get('picklist-hovercolour', '#AFFFFD');
		$opts->bghovercolour 	= $params->get('picklist-bghovercolour', '#FFFFDF');
		$opts 								= json_encode($opts);
		$lang = new stdClass();
		$lang->dropstuff = JText::_('Drag items from other list and drop here');
		$lang = json_encode($lang);
		return "new fbPicklist('$id', $opts, $lang)";
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
      $this->_default = explode('|', $this->getElement()->sub_intial_selection);
    }
    return $this->_default;
  }

	/**
	 * determines the value for the element in the form view
	 * @param array data
	 * @param bol editable element default = true
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
			$joinid			=& $groupModel->getGroup()->join_id;
			$formModel 	=& $this->_form;
			$element		=& $this->getElement();

			// $$$rob - if no search form data submitted for the search element then the default
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
						$default = $data['join'][$joinid][$name];
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
							$a =	explode(GROUPSPLITTER, $data[$name]);
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
			if (is_null($element->default)) {
				$element->default = array();
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
		FabrikHelperHTML::script('admin.js', 'components/com_fabrik/plugins/element/fabrikpicklist/', true);
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	FabrikHelperAdminHTML::subElementFields($element);
	echo $pluginParams->render();
	?>
<fieldset><legend><?php echo JText::_('Sub elements');?></legend> <a
	href="#" id="addPickList" class="addButton" style="text-align: right"><?php echo JText::_('Add'); ?></a>
<div id="picklist_subElementBody"></div>
</fieldset>
</div>
<input type="hidden"
	name="params[picklist_initial_selection]" value=""
	id="params_picklist_initial_selection" />
	<?php
	}

	function getAdminJS()
	{
		$element =& $this->getElement();
		$script = "\tvar fabrikpicklist = new fabrikAdminPicklist({
		'splitter':'" . GROUPSPLITTER2 . "'
	});\n".
		"\tpluginControllers.push({element:'fabrikpicklist', controller: fabrikpicklist});\n";
		$sub_values 	= explode(GROUPSPLITTER2, $element->sub_values);
		$sub_texts 	= explode(GROUPSPLITTER2, $element->sub_labels);
		$sub_intial_selections = explode(GROUPSPLITTER2, $element->sub_intial_selection);

		$json = array();
		for ($ii = 0; $ii < count($sub_values) && $ii < count($sub_texts); $ii ++) {
			$bits = array(html_entity_decode($sub_values[$ii], ENT_QUOTES), html_entity_decode($sub_texts[$ii], ENT_QUOTES));
			if (in_array($sub_values[$ii], $sub_intial_selections)) {
				$bits[] = 'checked';
			}
			$json[] = $bits;
		}
		$script .= "\tfabrikpicklist.addSubElements(". json_encode($json) . ");\n";
		return $script;
	}

	/**
	 * used to format the data when shown in the form's email
	 * @param mixed element's data
	 * @param array form records data
	 * @param int repeat group counter
	 * @return string formatted value
	 */

	function getEmailValue($value, $data, $c)
	{
		$params =& $this->getParams();
		$element =& $this->getElement();
		$labels = explode(GROUPSPLITTER2, $element->sub_labels);
		$values = explode(GROUPSPLITTER2,  $element->sub_values);
		$sLabels = '';
		if (is_string($value)) {
			$value = array($value);
		}
		foreach ($value as $tmpVal) {
			$key = array_search($tmpVal, $values);
			$sLabels.= $labels[$key]. "\n";
		}
		$val =  FabrikString::rtrimword( $sLabels, "\n");
		return $val;
	}

}
?>
