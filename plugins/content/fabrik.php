<?php
/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined( '_JEXEC' ) or die();

jimport( 'joomla.plugin.plugin');

/**
 * Fabrik content plugin - renders forms and tables
 *
 * @package		Joomla
 * @subpackage	Content
 * @since 		1.5
 */

class plgContentFabrik extends JPlugin
{

	/**
	 * Constructor
	 *
	 * For php4 compatability we must not use the __constructor as a constructor for plugins
	 * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
	 * This causes problems with cross-referencing necessary for the observer design pattern.
	 *
	 * @param object $subject The object to observe
	 * @param object $params  The object that holds the plugin parameters
	 * @since 1.5
	 */

	function plgContentFabrik(&$subject, $params = null)
	{
		parent::__construct($subject, $params);
	}

	/**
	 *  prepare content method
	 *
	 * Method is called by the view
	 *
	 * @param 	object		The article object.  Note $article->text is also available
	 * @param 	object		The article params
	 * @param 	int			The 'page' number
	 */

	function onPrepareContent(&$article, &$params, $limitstart=0)
	{
		//load fabrik language
		$lang =& JFactory::getLanguage();
		$lang->load('com_fabrik');

		// Get plugin info
		$plugin =& JPluginHelper::getPlugin('content', 'fabrik');
		// $$$ hugh had to rename this, it was stomping on com_content and friends $params
		// $$$ which is passed by reference to us!
		$fparams = new JParameter($plugin->params);

		// simple performance check to determine whether bot should process further
		$botRegex = $fparams->get('Botregex') != '' ? $fparams->get('Botregex') : 'fabrik';

		if (JString::strpos($article->text, $botRegex) === false) {
			return true;
		}
		jimport('joomla.filesystem.file');
		$defines = JFile::exists(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php') ? JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php' : JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'defines.php';
		require_once($defines);
		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php');
		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php');
		// $$$ hugh - having to change this to use {[]}
		//regex to get nested { {} } string - one layer deep e.g. you cant do { {{}} }
		/*
		 $regex = "/{" .$botRegex ."\s*.*{*.*}*.*?}/i";
		$article->text = preg_replace_callback( $regex, array($this, 'parse'), $article->text);
		*/

		$regex = "/{" .$botRegex ."\s*.*?}/i";
		$res = preg_replace_callback($regex, array($this, 'replace'), $article->text);
		if (!JError::isError($res)) {
			$article->text = $res;
		}
	}

	function parse($match)
	{
		$match = $match[0];
		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'json.php');
		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php');
		$w =new FabrikWorker();
		$w->replaceRequest($match);
		// stop [] for ranged filters from being removed
		$match = str_replace('{}', '[]', $match);
		$match = $w->parseMessageForPlaceHolder($match);
		return $match;
	}

	/**
	 * the function called from the preg_replace_callback - replace the {} with the correct HTML
	 *
	 * @param string plug-in match
	 * @return unknown
	 */

	function replace($match)
	{
		$match = $match[0];
		$match = trim($match, "{ ");
		$match = trim($match, "} ");
		$match = str_replace('[','{', $match);
		$match = str_replace(']','}', $match);
		$match = $this->parse(array($match));
		$match = preg_replace('/\s+/', ' ', $match);
		$match = explode(" ", $match);
		array_shift($match);
		$user = JFactory::getUser();
		$usersConfig 	=& JComponentHelper::getParams('com_fabrik');
		$unused = array();
		$element = false; // special case if we are wanting to write in an element's data
		$repeatcounter = 0;
		$showfilters = JRequest::getVar('showfilters', 1);
		$clearfilters = JRequest::getVar('clearfilters', 0);
		$resetfilters = JRequest::getVar('resetfilters', 0);
		$this->origRequestVars = array();
		$id = 0;
		$origLayout= JRequest::getVar('layout');
		$origFFlayout = JRequest::getVar('flayout');
		$layoutFound = false;
		$rowid = 0;
		$usekey = '';
		$usersConfig->set('rowid', 0);
		foreach ($match as $m) {
			$m = explode("=", $m);
			// $$$ hugh - deal with %20 as space in arguments
			$m[1] = urldecode($m[1]);
			switch ($m[0])
			{
				case 'view':
					$viewName = strtolower($m[1]);
					break;
				case 'id':
					$id = $m[1];
					break;
				case 'layout':
					$layoutFound = true;
					$layout = $m[1];
					$origLayout = JRequest::getVar('layout');
					JRequest::setVar('layout', $layout);
					break;
				case 'row':
				case 'rowid':
					$row = $m[1];
					$matches = array();
					if ($row == -1) {
						$row = $user->get('id');
					}
					$usersConfig->set('rowid', $row);
					$rowid = $row;
					break;
				case 'element':
					//{fabrik view=element table=3 rowid=364 element=fielddatatwo}
					$viewName = 'table';
					$element = $m[1];
					break;
				case 'table':
					$tableid = $m[1];
					break;
				case 'usekey':
					$usekey = $m[1];
					break;
				case 'repeatcounter':
					$repeatcounter = $m[1];
					break;
				case 'showfilters':
					$showfilters = $m[1];
					break;
					/*case 'subview':
					 $subview = $m[1];
					//for viz to define subview
					JRequest::setVar('subview', $subview);
					break;*/

					// $$$ rob for these 2 grab the qs var in priority over the plugin settings
				case 'clearfilters':
					$clearfilters = JRequest::getVar('clearfilters', $m[1]);
					break;
				case 'resetfilters':
					$resetfilters = JRequest::getVar('resetfilters', $m[1]);
					break;
				default:
				//get ranged prefilters working: e.g.
				// {fabrik view=table id=4 jos_element_test___time_date[value][]=2011-01-01 jos_element_test___time_date[value][]=2012-12-12 jos_element_test___time_date[condition]=BETWEEN}
				$this->updateUnusedData($m[0], $m[1], $unused);
			}
		}
		//moved out of switch as otherwise first plugin to use this will effect all subsequent plugins
		JRequest::setVar('usekey', $usekey);
		//$$$rob for table views in category blog layouts when no layout specified in {} the blog layout
		// was being used to render the table - which was not found which gave a 500 error
		$option = JRequest::getCmd('originalOption', JRequest::getCmd('option', 'com_content'));
		if (!$layoutFound)
		{
			if ($option === 'com_content' && JRequest::getVar('layout') === 'blog')
			{
				$layout = 'default';
				JRequest::setVar('layout', $layout);
			}
		}
		// $$$ hugh - added this so the fabrik2article plugin can arrange to have form CSS
		// included when the article is rendered by com_content, by inserting ...
		// {fabrik view=form_css id=X layout=foo}
		// ... at the top of the article.
		if ($viewName == 'form_css') {
			// the getFormCss() call blows up if we don't do this
			jimport('joomla.filesystem.file');
			$this->generalIncludes('form');
			$document 	=& JFactory::getDocument();
			$viewType		= $document->getType();
			$controller = $this->_getController('form', $id);
			$view 			=& $this->_getView($controller, 'form', $id);
			$model 			=& $this->_getModel($controller, 'form', $id);
			$model->setId($id);
			$model->_editable = false;
			$form =& $model->getForm();
			$tableModel =& $model->getTableModel();
			$table =& $tableModel->getTable();
			$layout = !empty($layout) ? $layout : 'default';
			$view->setModel($model, true);
			$model->getFormCss($layout);
			return '';
		}
		$this->generalIncludes($viewName);
		if ($element !== false) {
			//special case for rendering element data
			$controller = $this->_getController('table', $tableid);
			$model 			=& $this->_getModel($controller, 'table', $tableid);
			$model->setId($tableid);
			$formModel =& $model->getForm();
			$groups =& $formModel->getGroupsHiarachy();
			foreach ($groups as $groupModel) {
				$elements =& $groupModel->getMyElements();
				foreach ($elements as &$elementModel) {
					// $$$ rob 26/05/2011 changed it so that you can pick up joined elements without specifying plugin
					// param 'element' as joinx[x][fullname] but simpy 'fullname'
					if ($element == $elementModel->getFullName(false, true, false)) {
						$activeEl = $elementModel;
						continue 2;
					}
				}
			}
			// $$$ hugh in case they have a typo in their elementname
			if (empty($activeEl)) {
				JError::raiseNotice(500, 'You are trying to embed an element called ' . $element . ' which is not present in the table');
				return;
			}
			$row =& $model->getRow($rowid, false, true);

			if (substr($element, strlen($element) - 4, strlen($element)) !== "_raw") {
				$element = $element . "_raw";
			}
			//$elval = is_object( $row ) ? $row->$element : '';
			//$defaultdata = array( $name => $elval);
			// $$$ hugh - need to pass all row data, or calc elements that use {placeholders} won't work
			$defaultdata = get_object_vars($row);
			// $$$ hugh - if we don't do this, our passed data gets blown away when render() merges the form data
			// not sure why, but apparently if you do $foo =& $bar and $bar is NULL ... $foo ends up NULL
			$activeEl->_form->_data = $defaultdata;
			$activeEl->_editable 	= false;
			//set row id for things like user element
			$origRowid = JRequest::getVar('rowid');
			JRequest::setVar('rowid', $rowid);

			$defaultdata = (array)$defaultdata;
			$res = $activeEl->render($defaultdata, $repeatcounter);
			JRequest::setVar('rowid', $origRowid);
			return $res;
		}

		if (!isset($viewName)) {
			return;
		}

		$document 	=& JFactory::getDocument();
		$viewType		= $document->getType();
		$controller = $this->_getController($viewName, $id);
		$view 			=& $this->_getView($controller, $viewName, $id);
		$model 			=& $this->_getModel($controller, $viewName, $id);

		$origid = JRequest::getVar('id');
		$origView = JRequest::getVar('view');

		//for fabble
		JRequest::setVar('origid', $origid);
		JRequest::setVar('origview', $origView);
		//end for fabble

		JRequest::setVar('id', $id);
		JRequest::setVar('view', $viewName);
		// $$$ hugh - at least make the $origid available for certain corner cases, like ...
		// http://fabrikar.com/forums/showthread.php?p=42960#post42960
		JRequest::setVar('origid', $origid, 'GET', false);

		if (!JError::isError($model)) {
			$view->setModel($model, true);
		}

		// Display the view
		$view->assign('error', $controller->getError());
		$view->_isMambot = true;
		$displayed = false;
		// do some view specific code
		switch ($viewName) {
			case 'form_css':
				$model->getFormCss();
				break;
			case 'form':
			case 'details':
				if ($id === 0) {
					JError::raiseWarning(500, 'No id set in fabrik plugin declaration');
					return;
				}
				JRequest::setVar('fabrik', $id);
				$view->setId($id);
				$model->_postMethod = 'ajax';
				$model->setId($id);
				//set default values set in plugin declaration
				// - note cant check if the form model has the key' as its not yet loaded
				$this->_setRequest($unused);
				//$$$ rob - flayout is used in form/details view when _isMamot = true
				JRequest::setVar('flayout', JRequest::getVar('layout'));
				JRequest::setVar('rowid', $rowid);
				break;
			case 'table':
				/// $$$ rob 15/02/2011 addded this as otherwise when you filtered on a table with multiple filter set up subsequent tables were showing
				//the first tables data
				if (JRequest::getInt('activetableid') === 0) {
					JRequest::setVar('activetableid', JRequest::getInt('tableid'));
				}
				JRequest::setVar('tableid', $id);
				$this->_setRequest($unused);
				JRequest::setVar('showfilters', $showfilters);
				JRequest::setVar('clearfilters', $clearfilters);
				JRequest::setVar('resetfilters', $resetfilters);

				if ($id === 0) {
					JError::raiseWarning(500, 'No id set in fabrik plugin declaration');
					return;
				}
				$view->setId($id);
				$model->setId($id);
				$task = JRequest::getVar('task');
				if (method_exists($controller, $task) && JRequest::getInt('activetableid') == $id) {
					//enable delete() of rows
					//table controller deals with display after tasks is called
					//set $displayed to true to stop controller running twice
					$displayed = true;
					ob_start();
					$controller->$task();
					$result = ob_get_contents();
					ob_end_clean();

				}
				$model->setOrderByAndDir();
				$formModel =& $model->getForm();
				//apply filters set in mambot
				foreach ($unused as $k => $v) {

					//allow for element_test___id[match]=1 to set the match type
					if (strstr($k, "[match]")){
						$k2 = str_replace("[match]", "", $k);
						if (array_key_exists($k2, $_REQUEST)) {
							$v2 = JRequest::getVar($k2);
							$v2 = array('value'=>$v2, 'match'=>$v);
						}
						JRequest::setVar($k2, $v2);
					}
					else {
						JRequest::setVar($k, $v);
					}
				}

				break;
			case 'visualization':
				JRequest::setVar('showfilters', $showfilters);
				JRequest::setVar('clearfilters', $clearfilters);
				JRequest::setVar('resetfilters', $resetfilters);
				foreach ($unused as $k=>$v) {
					JRequest::setVar($k, $v, 'get');
				}
				break;
		}
		//hack for gallery viz as it may not use the default view
		$controller->_isMambot = true;
		if (!$displayed) {
			//$result = $controller->display();
			ob_start();
			$controller->display();
			$result = ob_get_contents();
			ob_end_clean();
		}
		JRequest::setVar('id', $origid);
		JRequest::setVar('view', $origView);

		if ($origLayout != '') {
			JRequest::setVar('layout', $origLayout);
		}
		if ($origFFlayout != '') {
			JRequest::setVar('flayout', $origFFlayout);
		}
		$this->resetRequest();
		$view->_isMambot = false;
		return $result;
	}

	function _setRequest($unused)
	{
		$this->origRequestVars = array();
		foreach ($unused as $k=>$v) {
			$origVar = JRequest::getVar($k);
			$this->origRequestVars[$k] = $origVar;
			JRequest::setVar($k, $v);
		}
		// $$$ rob set this array here - we will use in the tablefilter::getQuerystringFilters()
		//code to determine if the filter is a querystring filter or one set from the plugin
		//if its set from here it becomes sticky and is not cleared from the session. So we basically
		//treat all filters set up inside {fabrik.....} as prefilters
		JRequest::setVar('fabrik_sticky_filters', array_keys($unused));
	}

	function resetRequest()
	{
		foreach ($this->origRequestVars as $k => $v) {
			JRequest::setVar($k, $v);
		}
	}

	/**
	 * get the model
	 * @param object controller
	 * @param string $viewName
	 * @param int id
	 */

	function _getModel(&$controller, $viewName, $id)
	{
		if ($viewName == 'visualization') {
			$viewName = $this->_getPluginVizName($id);
		}
		if ($viewName == 'details') {
			$viewName = 'form';
		}
		if (!isset($controller->_model)) {
			$controller->_model = $controller->getModel($viewName);
		}
		return $controller->_model;
	}

	/**
	 * get a view
	 * @param object controller
	 * @param string $viewName
	 * @param int id
	 */

	function _getView(&$controller, $viewName, $id)
	{
		$document =& JFactory::getDocument();
		$viewType	= $document->getType();
		if ($viewName == 'visualization') {
			$viewName = $this->_getPluginVizName($id);
		}
		if ($viewName == 'details') {
			$viewName = 'form';
		}
		$view = &$controller->getView($viewName, $viewType);
		return $view;
	}

	/**
	 * get the viz plugin name
	 *
	 * @param int $id
	 * @return string viz plugin name
	 */

	function _getPluginVizName($id)
	{
		if (!isset($this->pluginVizName)) {
			$this->pluginVizName = array();
		}
		if (!array_key_exists($id, $this->pluginVizName)) {
			$db =& JFactory::getDBO();
			$db->setQuery( 'SELECT plugin FROM #__fabrik_visualizations WHERE id = '.$id);
			$this->pluginVizName[$id] = $db->loadResult();
		}
		return $this->pluginVizName[$id];
	}

	/**
	 * get the controller
	 *
	 * @param string $viewName
	 * @param int $id
	 * @return object controller
	 */

	function _getController($viewName, $id)
	{
		if (!isset($this->controllers)) {
			$this->controllers = array();
		}
		switch ($viewName) {
			case 'visualization':
				$name = $this->_getPluginVizName($id);
				$path = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.$name.DS.'controllers'.DS.$name.'.php';
				if (file_exists($path)) {
					require_once $path;
				}
				$controllerName = 'FabrikControllerVisualization'.$name;
				$controller = new $controllerName();
				$controller->addViewPath(COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.$name.DS.'views');
				$controller->addViewPath(COM_FABRIK_FRONTEND.DS.'views');

				//add the model path
				$modelpaths = JModel::addIncludePath(COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.$name.DS.'models');
				$modelpaths = JModel::addIncludePath(COM_FABRIK_FRONTEND.DS.'models');
				break;
			case 'form':
				// $$$ hugh - had to add [$id] for cases where we have multiple plugins with different tableid's
				if (array_key_exists('form', $this->controllers)) {
					if (array_key_exists($id, $this->controllers['form'])) {
						return $this->controllers['form'][$id];
					}
				}
				$this->controllers['form'][$id] = new FabrikControllerForm();
				$controller = $this->controllers['form'][$id];
				break;
			case 'table':
				// $$$ hugh - had to add [$id] for cases where we have multiple plugins with different tableid's
				if (array_key_exists('table', $this->controllers)) {
					if (array_key_exists($id, $this->controllers['table'])) {
						return $this->controllers['table'][$id];
					}
				}
				$this->controllers['table'][$id] = new FabrikControllerTable();
				$controller = $this->controllers['table'][$id];
				break;
			case 'package':
				$controller = new FabrikControllerPackage();
				break;
			default:
				$controller = new FabrikController();
				break;
		}
		//set a cacheId so that the controller grabs/creates unique caches for each form/table rendered
		$controller->cacheId = $id;
		return $controller;
	}

	/**
	 * load the required fabrik files
	 *
	 * @param string $view
	 */

	function generalIncludes($view)
	{
		$view = trim($view);
		require_once(COM_FABRIK_FRONTEND.DS.'controller.php');
		require_once(COM_FABRIK_FRONTEND.DS.'controllers'.DS.'form.php');
		require_once(COM_FABRIK_FRONTEND.DS.'controllers'.DS.'package.php');
		require_once(COM_FABRIK_FRONTEND.DS.'controllers'.DS.'table.php');
		require_once(COM_FABRIK_FRONTEND.DS.'controllers'.DS.'visualization.php');
		require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'parent.php');
		JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables');
		JModel::addIncludePath(COM_FABRIK_FRONTEND.DS.'models');
		if ($view == 'details') {
			$view = 'form';
		}
		if ($view == ''){
			JError::raiseError(500, 'Please specify a view in your fabrik {} code');
		}
		//$$$rob looks like including the view does something to the layout variable
		$layout = JRequest::getVar('layout');
		require_once(COM_FABRIK_FRONTEND.DS.'views'.DS.$view.DS.'view.html.php');
		JRequest::setVar('layout', $layout);
		FabrikHelperHTML::packageJS();
	}

	protected function updateUnusedData($key, $val, &$unused)
	{
		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php');
		$key = trim($key);
		$key = str_replace('{', '[', $key);
		$key = str_replace('}', ']', $key);
		$key = str_replace('[', '.', $key);
		$key = str_replace(']', '', $key);
		$key = FabrikString::rtrimword($key, '.');
		if (strstr($key, '.')) {

			$nodes = explode('.', $key);
			$count = count($nodes);
			$pathNodes = $count - 1;
			if ($pathNodes < 0) {
				$pathNodes = 0;
			}
			$ns =& $unused;
			for ($i = 0; $i <= $pathNodes; $i ++) {
				// If any node along the registry path does not exist, create it
				if (!isset($ns[$nodes[$i]])) {
					$ns[$nodes[$i]] = array();
				}
				$ns =& $ns[$nodes[$i]];
			}
			if (is_string($ns)) {
				//turn node into array an append value
				$ns = (array)$ns;
				$ns[] = $val;
			} else {
				$ns = $val;
			}
		} else {
			$unused[$key] = $val;
		}
	}

}