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
jimport('joomla.filesystem.file');

class FabrikModelPluginmanager extends JModel{

	/** @var array plugins */
	var $_plugIns = array();
	var $_loading = null;
	var $_group = null;
	var $_runPlugins = 0;

	var $_paths = array();

	/** @var array element lists */
	var $_elementLists = array();

	/** @var array containing out put from run plugins */
	var $_data = array();

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * get a html drop down list of the elment types with this objs element type selected as default
	 * @param string default selected option
	 * @param string html name for drop down
	 * @param string extra info for drop down
	 * @return string html element type list
	 */

	function getElementTypeDd($default, $name='plugin', $extra='class="inputbox elementtype"  size="1"', $defaultlabel='')
	{
		$hash = $default.$name.$extra.$defaultlabel;
		if (!array_key_exists($hash, $this->_elementLists)) {
			if ($defaultlabel == '') {
				$defaultlabel = JText::_('COM_FABRIK_PLEASE_SELECT');
			}
			$a = array(JHTML::_('select.option', '', $defaultlabel));
			$elementstypes = $this->_getList();
			$elementstypes = array_merge($a, $elementstypes);
			$this->_elementLists[$hash] = JHTML::_('select.genericlist',  $elementstypes, $name, $extra , 'value', 'text', $default);
		}
		return $this->_elementLists[$hash];
	}

	function canUse()
	{
		return true;
	}

	/**
	 * get an unordered list of plugins
	 * @param string plugin group
	 * @param string ul id
	 */

	function getList($group, $id)
	{
		$str = "<ul id='$id'>";
		$elementstypes = $this->_getList();
		foreach ($elementstypes as $plugin) {
			$str .= "<li>" . $plugin->text . "</li>";
		}
		$str .= "</ul>";
		return $str;
	}

	/**
	 * get a list of plugin ids/names for usin in a drop down list
	 * if no group set defaults to element list
	 * @return array plugin list
	 */

	function _getList()
	{
		$db =& JFactory::getDBO();
		if (is_null($this->_group)) {
			$this->_group = 'element';
		}
		$db->setQuery("SELECT name AS value, label AS text FROM #__fabrik_plugins WHERE type=".$db->Quote($this->_group)." AND state ='1' ORDER BY text");
		$elementstypes = $db->loadObjectList();
		return $elementstypes;
	}

	/**
	 * get a certain group of plugins
	 * @param string plugin group to load
	 * @return array plugins
	 */

	function &getPlugInGroup($group)
	{
		if (array_key_exists($group, $this->_plugIns))
		{
			return $this->_plugIns[$group];
		} else {
			return $this->loadPlugInGroup($group);
		}
	}

	/**
	 * add to the document head all element js files
	 * used in calendar to ensure all element js files are loaded from unserialized form
	 */

	function loadJS()
	{
		$files = JFolder::files(JPATH_SITE . '/components/com_fabrik/plugins/element',  'javascript.js$', true, true);
		foreach ($files as $f) {
			$f =  str_replace("\\", "/", str_replace(JPATH_SITE, '', $f));
			$file = basename($f);
			$folder = dirname($f);
			$folder = FabrikString::ltrimword($folder, '/') .'/';
			FabrikHelperHTML::script($file, $folder, true);
		}
	}

	/**
	 *@param string plugin type - element/form/table/validationrule supported
	 *loads ABSTRACT version of a plugin group
	 */

	function &loadPlugInGroup($group)
	{
		$db =& JFactory::getDBO();
		$this->_plugIns[$group] = array();
		$this->_group = $group;
		$db->setQuery("SELECT * FROM #__fabrik_plugins WHERE type = ".$db->Quote($group));
		$plugIns = $db->loadObjectList();
		$n = count($plugIns);
		for ($i = 0; $i < $n; $i++) {
			$plugIn = $plugIns[$i];
			$this->_loadPlugin($group, $plugIn);
		}
		return $this->_plugIns[$group];
	}

	/**
	 * @param string plugin name e.g. fabrikfield
	 * @param string plugin type element/ form or table
	 */

	function getPlugIn($className, $group)
	{
		if (array_key_exists($group, $this->_plugIns) && array_key_exists($className, $this->_plugIns[$group])) {
			return $this->_plugIns[$group][$className];
		} else {
			return $this->loadPlugIn($className, $group);
		}
	}

	/**
	 * @param string plugin name e.g. fabrikfield
	 * @param string plugin type element/ form or table
	 */

	function &loadPlugIn($className, $group)
	{
		$db =& JFactory::getDBO();
		$db->setQuery("SELECT * FROM #__fabrik_plugins WHERE type = ".$db->Quote($group)." AND name = ".$db->Quote($className));
		$plugIn = $db->loadObject();
		if (!$this->_loadPlugin($group, $plugIn)) {
			$msg = JText::sprintf("DID NOT FIND PLUGIN %s TO LOAD", $className);
			if (JRequest::getVar('format') === 'raw') {
				return "alert('$msg');\n";
			} else {
				return JError::raiseError(500, $msg);
			}
		}
		return $this->_plugIns[$group][$className];
	}

	/**
	 * load all the forms element plugins
	 *
	 * @param object form model
	 * @return array of group objects with plugin objects loaded in group->elements
	 */

	function getFormPlugins(&$form)
	{
		if (!isset($this->formplugins)) {
			$this->formplugins = array();
		}
		if (!array_key_exists($form->_id, $this->formplugins)) {
			$this->formplugins[$form->_id] = array();
			$groupIds = $form->getGroupIds();

			if (empty($groupIds)) { //new form
				return array();
			}
			$db =& JFactory::getDBO();
			$query = "SELECT *, e.name AS name, e.id AS id, e.state AS state, e.label AS label FROM #__fabrik_elements AS e\n".
			"INNER JOIN #__fabrik_plugins AS p \n".
			"ON p.name = e.plugin \n".
			"WHERE group_id IN (" . implode(',', $groupIds) . ") AND p.type = 'element'\n".
			"ORDER BY group_id, ordering";
			$db->setQuery($query);
			$elements = $db->loadObjectList();

			$groupModels = $form->getGroups();

			$group = 'element';
			foreach ($elements as $element) {

				$this->_loadPaths($group, $element->plugin);
				$pluginModel =& JModel::getInstance($element->plugin, 'FabrikModel');
				if (!is_object($pluginModel)) {
					continue;
				}
				$pluginModel->_type = $group;

				$pluginModel->_xmlPath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.$group.DS.$element->plugin.DS.$element->plugin.'.xml';

				$pluginModel->setId($element->id);
				$groupModel =& $groupModels[$element->group_id];
				$pluginModel->setContext($groupModel, $form, $form->_table);
				$pluginModel->bindToElement($element);

				$groupModel->elements[$pluginModel->_id] = $pluginModel;
			}

			foreach ($groupModels as $groupid => $g) {
				$this->formplugins[$form->_id][$groupid] = $g;
			}
		}
		return $this->formplugins[$form->_id];
	}

	function getElementPlugin($id)
	{
		$elementModel =& JModel::getInstance('element', 'FabrikModel');
		$elementModel->setId($id);
		$element = $elementModel->getElement();
		$pluginModel =& JModel::getInstance($element->plugin, 'FabrikModel');
		$pluginModel->_type = 'element';
		$pluginModel->_xmlPath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'element'.DS.$element->plugin.DS.$element->plugin.'.xml';;
		$pluginModel->setId($element->id);
		$pluginModel->bindToElement($element);
		return $pluginModel;
	}

	/**
	 * @param string name of plugin group to load
	 * @param array list of default element lists
	 * @param array list of default and plugin element lists
	 */

	function loadLists($group, $lists, &$elementModel)
	{
		if (empty($this->_plugIns)) {
			$this->loadPlugInGroup($group);
		}
		foreach ($this->_plugIns[$group] as $plugIn) {
			if (method_exists($plugIn->object, 'getAdminLists')) {
				$lists = $plugIn->object->getAdminLists($lists, $elementModel, $plugIn->params);
			}
		}
		return $lists;
	}

	/**
	 * @param string group name (currently only 'element' is supported)
	 * @param object database row of plugin info
	 * @return bol true if loaded ok
	 */

	function _loadPlugin($group, &$row)
	{
		if (!is_object($row)) {
			return false;
		}
		$folder     = $row->type;
		$element    = $row->name;
		$published  = $row->state;
		$params     = $row->params;
		$p 					= COM_FABRIK_FRONTEND.DS.'plugins'.DS.$folder.DS.$element.DS;
		$path 			= $p . $element . '.php';
		$xmlPath 		= $p . $element . '.xml';
		JModel::getInstance('Element', 'FabrikModel');
		JModel::getInstance('Plugin', 'FabrikModel');
		JModel::getInstance('Visualization', 'FabrikModel');

		$this->_loadPaths($group, $element);
		$plugIn = & JModel::getInstance($element, 'FabrikModel');

		if (!is_object($plugIn)) {
			//plugin filename / folder name incorrect
			JError::raiseWarning(500, 'Could not load the fabrik plugin: ' . $element);
			return false;
		}
		if (JError::isError($plugIn)) {
			JError::handleMessage($plugIn);
			return false;
		}
		$plugIn->row = $row;
		$plugIn->_type = $folder;
		$plugIn->_pluginLabel = $row->label;
		$plugIn->_xmlPath = $xmlPath;
		$this->_plugIns[$group][$element] 	=& $plugIn;
		$plugIn->_loading = null;
		return true;
	}

	/**
	 * check and load in required plugin paths
	 *
	 * @param string plugin $group
	 * @param string plugin name
	 * @return unknown
	 */

	function _loadPaths($group, $element)
	{
		if (!array_key_exists("$group.$element", $this->_paths)) {
			//@TODO hmm still loads in x times per table element - guess we have n instanaces of the pluginmanager going on?
			$p 					= COM_FABRIK_FRONTEND.DS.'plugins'.DS.$group.DS.$element.DS;
			$path 			= $p . $element . '.php';
			$xmlPath 		= $p . $element . '.xml';
			if (!JFile::exists($path)) {
				$path = COM_FABRIK_FRONTEND.DS.'plugins'.DS.$group.DS.$element.DS.'models'.DS.$element.'.php';
				if (!file_exists($path)) {
					return JError::raiseWarning(E_NOTICE, "cant load $group:$element - missng files $path");
				}
			}
			if (!JFile::exists($xmlPath)) {
				return JError::raiseWarning(E_NOTICE, "cant load $group:$element - missng files $xmlPath");
			}
			$cPaths = JModel::addIncludePath($p);

			$cPaths = JModel::addIncludePath(COM_FABRIK_FRONTEND.DS.'plugins'.DS.$group.DS.$element);

			//for viz & MVC plugins
			$cPaths = JModel::addIncludePath(COM_FABRIK_FRONTEND.DS.'plugins'.DS.$group.DS.$element.DS.'models');

			// Load common language files
			$lang =& JFactory::getLanguage();
			$langfile = 'com_fabrik.plg.'. $group . '.'.$element;
			$lang->load($langfile);
			$lang->load($langfile, COM_FABRIK_BASE);
			$this->_paths["$group.$element"] = 1;
		}
	}

	/**
	 * run form & element plugins - yeah!
	 * @param string method to check and call - corresponds to stage of form processing
	 * @param object model calling the plugin form/table
	 * @param string plugin type to call form/table
	 * @return array of bools: false if error found and processed, otherwise true
	 */

	function runPlugins($method, &$oRequest, $type = 'form')
	{
		if ($type == 'form') {
			// $$$ rob allow for table plugins to hook into form plugin calls - methods are mapped as:
			//form method = 'onLoad' => table method => 'onFormLoad'
			$tmethod = 'onForm'.FabrikString::ltrimword($method, 'on');
			$this->runPlugins($tmethod, $oRequest->getTableModel(), 'table');
		}
		$params =& $oRequest->getParams();
		$this->getPlugInGroup($type);
		$return = array();
		$usedPlugins = $params->get('plugin', "", "_default", "array");
		$usedLocations = $params->get('plugin_locations', "", "_default",  "array");
		$usedEvents = $params->get('plugin_events', "", "_default",  "array");
		$this->_data = array();

		if ($type != 'table') {

			if (method_exists($oRequest, 'getGroupsHiarachy')) {
				$groups =& $oRequest->getGroupsHiarachy();
				foreach ($groups as $groupModel) {
					$elementModels =& $groupModel->getPublishedElements();
					foreach ($elementModels as $elementModel) {
						if (method_exists($elementModel, $method)) {
							$elementModel->$method($oRequest);
						}
					}
				}
			}
		}
		$c = 0;
		$runPlugins = 0;
		// if true then a plugin has returned true from runAway() which means that any other plugin in the same group
		// should not be run.
		$runningAway = false;

		foreach ($usedPlugins as $usedPlugin) {
			if ($runningAway) {
				// "I soiled my armour I was so scared!"
				break;
			}
			if ($usedPlugin != '') {
				$plugin = JArrayHelper::getValue($this->_plugIns[$type], $usedPlugin);
				if (!is_object($plugin)) {
					continue;
				}
				//testing this if statement as onLoad was being called on form email plugin when no method availbale
				$plugin->renderOrder = $c;
				if (method_exists($plugin, $method)) {
					$modelTable = $oRequest->getTable();
					//$$$ rob ensure we set params
					unset($plugin->pluginParams);
					$pluginParams =& $plugin->setParams($params, $usedLocations, $usedEvents);

					$location = JArrayHelper::getValue($usedLocations, $c);
					$event = JArrayHelper::getValue($usedEvents, $c);

					if ($plugin->canUse($oRequest, $location, $event) && method_exists($plugin, $method)) {
						$pluginArgs = array();
						if (func_num_args() > 3) {
							$t =& func_get_args();
							$pluginArgs =& array_splice($t, 3);
						}
						$preflightMethod = $method."_preflightCheck";
						$preflightCheck = method_exists($plugin, $preflightMethod) ? $plugin->$preflightMethod($pluginParams, $oRequest, $pluginArgs) : true;
						if ($preflightCheck) {
							$ok = $plugin->$method($pluginParams, $oRequest, $pluginArgs);
							if ($ok === false) {
								$return[] = false;
							} else {
								$thisreturn = $plugin->customProcessResult($method, $oRequest);
								$return[] = $thisreturn;
								$m = $method.'_result';
								if (method_exists($plugin, $m)) {
									$this->_data[] = $plugin->$m($c);
								}
							}
							$runPlugins ++;

							if ($plugin->runAway($method)) {
								$runningAway = true;
							}
							$mainData = $this->_data;
							if ($type == 'table' && $method !== 'observe') {
								$this->runPlugins('observe', $oRequest, 'table', $plugin, $method);
							}
							$this->_data = $mainData;
						}
					}
				}
				$c ++;
			}
		}
		$this->_runPlugins = $runPlugins;
		return array_unique($return);
	}

	/**
	 * test if a plugin is installed
	 * @param $group
	 * @param $plugin
	 * @return bol
	 */

	function pluginExists($group, $plugin)
	{
		$plugins =& $this->loadPlugInGroup($group);
		if (in_array($plugin, array_keys($plugins))) {
			return true;
		}
		return false;
	}

	/**
	 * admin: get the js to create an array of the views plugin controllers
	 * each controller contains at least the plugin html to be duplicated
	 * @param string group form/table
	 * @return string js
	 */

	function getAdminPluginJs($group, $row, $lists)
	{
		$c = 0;
		$js = "var aPlugins = [];\n";
		$plgs = array();
		foreach ($this->_plugIns[$group] as $usedPlugin => $plugin) {
			if (is_a($plugin, 'FabrikModelPlugin')) {
				$plugin->_adminVisible = false;
				//Needed for counter override in radio buttons
				//(set from -1 to the correct num in adminform.js func addAction
				$plugin->_counter = -1;
				$plugin->_counter = 0;
				$plugin->setId($row->id);
				$o = $plugin->getAdminJs($row, $lists);
		  	$plgs[$plugin->_pluginLabel] = $o;
		  	$c ++;
			}
		}
		ksort($plgs);
		foreach ($plgs as $o) {
			$js .= "aPlugins.push($o);\n";
		}
		return $js;
	}

	function getAdminSelectedPluginJS($group, $row, $lists, $params)
	{
		$c = 0;
		$usedPlugins 		= $params->get('plugin', '', '_default', 'array');
	 	$usedLocations 	= $params->get('plugin_locations', '', '_default', 'array');
	 	$usedEvents 		= $params->get('plugin_events', '', '_default', 'array');
		$js = '';
		//go through all of this form's ACTIVE plugins
		foreach ($usedPlugins as $usedPlugin) {
			if (trim($usedPlugin) == '') {
				continue;
			}
			$plugin 				= $this->_plugIns[$group][$usedPlugin];
			$pluginParams 	= new fabrikParams($row->attribs, $plugin->_xmlPath, 'fabrikplugin');
			$names 					= $pluginParams->_getParamNames();
			$tmpAttribs 		= '';
			foreach ($names as $name) {
				$pluginElOpts = $params->get($name, '', '_default', 'array');
				$val 					= JArrayHelper::getValue($pluginElOpts, $c, '');
				//backslash any "|"'s in the data (otherwise the ini.php format thinks you are storing an array
				$val 					= preg_replace('#(?<!\\\)\|#', '\|', $val);
				$tmpAttribs 	.= $name . "=" . $val . "\n";
			}
	    //redo the parmas with the exploded data
	    $pluginParams = new fabrikParams($tmpAttribs, $plugin->_xmlPath, 'fabrikplugin');
	    $pluginParams->_duplicate = true;
	    $plugin->_adminVisible = true;
	    $plugin->_counter = $c;
			$data = $plugin->renderAdminSettings($usedPlugin, $row, $pluginParams, $lists, $c);
			//sanitize data as its no longer sanitized in renderAdminSettings
			$data = addslashes(str_replace("\n", "", $data));
			$js .= "controller.addAction('".$data."', '".$usedPlugins[$c]."', '".@$usedLocations[$c]."', '".@$usedEvents[$c]."', false);\n";
			$c ++;
	  }
	  return $js;
	}

	/**
	 * load up bare bones element plugins with enough info to render (as if they were in a form view)
	 * without generating warnings errors
	 * @param int form id pass in a form id to be used to generate element form/group ids from If not supplied then fabrik request var is used
	 * @return array of render output keyed on plugin name
	 */
	public function renderAbstractElements($formid = null)
	{
		$els = $this->loadPlugInGroup('element');
		$model = JModel::getInstance('Form', 'FabrikModel');
		$groupModel = JModel::getInstance('Group', 'FabrikModel');
		if (is_null($formid)) {
			$formid = JRequest::getInt('fabrik');
		}
		$model->setId($formid);
		$formTable = $model->getForm();
		$output = array();

		$groups = $model->getGroups();
		$group = array_shift($groups);
		$group->_formsIamIn = array($model->_id);
		foreach ($els as $pname => $el) {
			$element->sub_values = '';
			$element->sub_labels = JText::_('COM_FABRIK_CLICK_TO_EDIT');
			$el->_form =& $model;
			$el->getElement()->group_id = $group->_id;
			$el->_group =& $group;
			$el->_editable = true;
			$s = $el->render(array());
			$output[$el->_name] = $s;
		}
		return $output;
	}
}
?>