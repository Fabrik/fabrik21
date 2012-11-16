<?php
/**
 * @version
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
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

class FabrikControllerForm extends JController
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
		$this->registerTask('menulinkForm', 'save');
		$this->registerTask('unpublish',	'publish');
		$this->registerTask('go2menu', 'save');
		$this->registerTask('go2menuitem', 'save');

		//editing an existing record in admin
		//$this->registerTask('', 'form');
	}

	/**
	 * process submitted form
	 */

	function _processForm()
	{
		list($view, $model) = $this->setUpProcess();
		if (!$model->validate()) {
			$view->display();
		} else {
			$model->process();
			$link = "index.php?option=com_fabrik&c=table&view=viewTable&task=viewTable&cid=" . JRequest::getVar('tableid');
			$msg = JText::_('RECORD SAVED');
			$this->setRedirect($link, $msg);
		}
	}

	function processForm()
	{
		$document =& JFactory::getDocument();
    $viewName	= JRequest::getVar('view', 'form', 'default', 'cmd');
    $viewType	= $document->getType();
    $view 		= &$this->getView($viewName, $viewType);
    $model		= &$this->getModel( 'form');

    if (!JError::isError($model)) {
      $view->setModel( $model, true);
    }

    $model->setId(JRequest::getInt('form_id', 0));
    $model->getPostMethod();

    $this->_isMambot = JRequest::getVar('_isMambot', 0);
    $model->getForm();
    $model->_rowId = JRequest::getVar('rowid', '');

    // Check for request forgeries
    $fbConfig =& JComponentHelper::getParams('com_fabrik');
    if ($model->getParams()->get('spoof_check', $fbConfig->get('spoofcheck_on_formsubmission', true)) == true) {
    	JRequest::checkToken() or die('Invalid Token');
    }
    if (JRequest::getVar('fabrik_ignorevalidation', 0) != 1) { //put in when saving page of form
      if (!$model->validate()) {
      	//if its in a module with ajax or in a package
      	if (JRequest::getInt('_packageId') !== 0) {
		      	$data = array('modified' => $model->_modifiedValidationData);
			    //validating entire group when navigating form pages
			    $data['errors'] = $model->_arErrors;
			    echo FastJSON::encode( $data);
			    return;
      	}
        if ($this->_isMambot) {
          //store errors in session
          $_SESSION['fabrik']['mambot_errors'][$model->_id] = $model->_arErrors;
	        JRequest::setVar('fabrik_referrer', JArrayHelper::getValue($_SERVER, 'HTTP_REFERER', ''), 'post');
					// $$$ hugh - testing way of preserving form values after validation fails with form plugin
					// might as well use the 'savepage' mechanism, as it's already there!
					$this->savepage();
	        $this->makeRedirect('', $model);
        } else {
          $view->display();
        }
        return;
      }
    }

    //reset errors as validate() now returns ok validations as empty arrays
    $model->_arErrors = array();

    $defaultAction = $model->process();

    //check if any plugin has created a new validation error
    if (!empty($model->_arErrors)) {
      $pluginManager 	=& $model->getPluginManager();
      $pluginManager->runPlugins('onError', $model);
      $view->display();
      return;
    }

    // $$$ rob 31/01/2011
    // Now redirect always occurs even with redirect thx message, $this->setRedirect
    // will look up any redirect url specified in the session by a plugin and use that or
    // fall back to the url defined in $this->makeRedirect()

    //one of the plugins returned false stopping the default redirect
    // action from taking place
    /*
    if (!$defaultAction) {
      return;
    }
    */

    $tableModel				=& $model->getTableModel();
		$tid = $tableModel->getTable()->id;
    $tableModel->_table = null;

    $msg = $model->getParams()->get('submit-success-msg', JText::_('RECORD ADDED/UPDATED'));

    if (JRequest::getInt('_packageId') !== 0) {
    	$rowid = JRequest::getInt('rowid');
    	echo FastJSON::encode( array('msg' => $msg, 'rowid' => $rowid));
    	return;
    }
    if (JRequest::getVar('format') == 'raw') {
			$url = COM_FABRIK_LIVESITE .'/index.php?option=com_fabrik&view=table&format=raw&tableid=' . $tid;
			$this->setRedirect($url, $msg);
    } else {
      $this->makeRedirect($msg, $model);
    }
	}

	  /**
   * generic function to redirect
   */

  function makeRedirect($msg = null, &$model )
  {
	    if (is_null($msg)) {
	      $msg = JText::_('RECORD ADDED/UPDATED');
	    }
      	if (array_key_exists('apply', $model->_formData)) {
    		$page = "index.php?option=com_fabrik&c=form&task=form&fabrik=".JRequest::getInt('fabrik')."&tableid=".JRequest::getInt('tableid')."&rowid=".JRequest::getInt('rowid');
    	} else {
      		$page = "index.php?option=com_fabrik&c=table&task=viewTable&cid[]=".$model->getTableModel()->getTable()->id;
    	}
		$this->setRedirect($page, $msg);
  }

  /**
  * (non-PHPdoc) adds redirect url and message to session
  * @see JController::setRedirect()
  */

  function setRedirect($url, $msg = null, $type = 'message')
  {
  	$session =& JFactory::getSession();
  	$formdata = $session->get('com_fabrik.form.data');
  	$context = 'com_fabrik.form.'.$formdata['fabrik'].'.redirect.';
  	//if the redirect plug-in has set a url use that in preference to the default url
  	$surl = $session->get($context.'url', array($url));
  	if (!is_array($surl)) {
  		$surl = array($surl);
  	}
  	if (empty($surl)) {
  		$surl[] = $url;
  	}
  	$smsg = $session->get($context.'msg', array($msg));
  	if (!is_array($smsg)) {
  		$smsg = array($smsg);
  	}
  	if (empty($smsg)) {
  		$smsg[] = $msg;
  	}

  	// $$$ hugh - hmmm, array_shift re-orders array keys, which will screw up plugin ordering?
  	$url = array_shift($surl);
  	// $$$ hugh - something changed in the way the redirect plugin works, so we can't remove
  	// the msg any more.

  	// $$$ rob Was using array_shift to set $msg, not to really remove it from $smsg
  	// without the array_shift the custom message is never attached to the redirect page.
  	// use case 'redirct plugin with jump page pointing to a J page and thanks message selected.
  	$custommsg = JArrayHelper::getValue($smsg, array_shift(array_keys($smsg)));
  	if ($custommsg != '') {
  		$msg = $custommsg;
  	}
  	$app =& JFactory::getApplication();
  	$q = $app->getMessageQueue();
  	$found = false;
  	foreach ($q as $m) {
  		//custom message already queued - unset default msg
  		if ($m['type'] == 'message' && trim($m['message']) !== '') {
  			$found= true;
  			break;
  		}
  	}
  	if ($found) {
  		$msg = null;
  	}
  	$session->set($context.'url', $surl);
  	$session->set($context.'msg', $smsg);
  	$showmsg = array_shift($session->get($context.'showsystemmsg', array(true)));
  	$msg = $showmsg == 1 ? $msg : null;
  	parent::setRedirect($url, $msg, $type);
  }

	private function setUpProcess()
	{
		$model =& JModel::getInstance('Form', 'FabrikModel');
		$model->setId(JRequest::getInt('form_id', 0));
		$model->getForm();
		$model->_rowId = JRequest::getVar('rowid', '');
		$post	= JRequest::get('post');
		$document =& JFactory::getDocument();
		JRequest::setVar('view', 'Form');
		$viewType	= $document->getType();
		$viewName	= JRequest::getCmd('view', $this->_name);
		$viewLayout	= JRequest::getCmd('layout', 'default');
		$view = & $this->getView($viewName, $viewType, '');
		$view->setModel($model, true);
		return array($view, $model);
	}

	/*
	 * view the form
	 */

	function form()
	{
		JRequest::setVar('view', 'form');
		$this->_form();
	}

	function details()
	{
		JRequest::setVar('view', 'details');
		$this->_form();
	}

	function cck()
	{
		$catid = JRequest::getInt('catid');
		$db =& JFactory::getDBO();
		$db->setQuery('SELECT id FROM #__fabrik_forms WHERE attribs LIKE "%cck_category='.$catid.'\n%" OR attribs LIKE "%cck_category='.$catid.'"');
		$id = $db->loadResult();
		if (!$id) {
			FabrikHelperHTML::stylesheet('system.css', 'administrator/templates/system/css/');
			echo "<a target=\"_blank\" href=\"index.php?option=com_fabrik&c=form\">".JText::_('VIEW_FORMS') . "</a>";
			return JError::raiseNotice(500, JText::_('SET_FORM_CCK_CATEGORY'));
		}
		JRequest::setVar('fabrik', $id);
		JRequest::setVar('iframe', 1);//tell fabrik to load js scripts normally
		$this->_form();
	}

	private function _form()
	{
		$document =& JFactory::getDocument();
		$model = JModel::getInstance('Form', 'FabrikModel');
		$viewType	= $document->getType();
		$viewName	= JRequest::getCmd('view', $this->_name);

		$viewLayout	= JRequest::getCmd('layout', 'default');
		$view = & $this->getView('form', $viewType, '');
		$view->setModel( $model, true);

		// Set the layout
		$view->setLayout( $viewLayout);

		//todo check for cached version
		$view->display();
	}

	/**
	 * Edit a form
	 */

	function edit()
	{
		$user	  = &JFactory::getUser();
		$session =& JFactory::getSession();
		$db =& JFactory::getDBO();
		$lists 	= array();
		$row =& JTable::getInstance('form', 'Table');
		if ($this->_task == 'edit') {
			$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
			$cid	= array((int) $cid[0]);
		} else {
			$cid	= array(0);
		}

		$row->load($cid[0]);

		if ($cid) {
			$row->checkout( $user->get('id'));
		}
		$model = JModel::getInstance('Form', 'FabrikModel');
		$model->setId($cid[0]);
		$model->getTable();
		$groupModels =& $model->getGroupsHiarachy();
		$possible_email_receipt_fields[] = JHTML::_('select.option','', 'n/a');
		foreach ($groupModels as $groupModel) {
			$groupModel->_form =& $model;
			$elementModels =& $groupModel->getMyElements();
			foreach ($elementModels as $elementModel) {
				if ($elementModel->isReceiptElement()) {
					$element =& $elementModel->getElement();
					$possible_email_receipt_fields[] = JHTML::_('select.option', $element->name, $element->label);
				}
			}
		}

		// get params definitions
		$params = new fabrikParams($row->attribs, JPATH_COMPONENT.DS.'xml'.DS.'form.xml');
		require_once(JPATH_COMPONENT.DS.'views'.DS.'form.php');


		// get a list of used groups
		$sql = "SELECT  #__fabrik_formgroup.group_id AS value,
			#__fabrik_groups.name AS text
			FROM #__fabrik_formgroup
			LEFT JOIN #__fabrik_groups
			ON #__fabrik_formgroup.group_id = #__fabrik_groups.id
			WHERE  #__fabrik_formgroup.form_id = '".$cid[0]."'
			AND #__fabrik_groups.name <> ''
			ORDER BY  #__fabrik_formgroup.ordering";
		$db->setQuery($sql);
		$current_groups = $db->loadObjectList();
		$lists['current_groups'] 	= $current_groups;
		$lists['current_grouplist'] = JHTML::_('select.genericlist',  $current_groups, 'current_groups', "class=\"inputbox\" style=\"width:100%;\" size=\"10\" ", 'value', 'text', '/');
		// get a list of available groups - need to make the sql only return groups not already listed in mos_fabrik_fromgroup for $id

		//$$$ only unused groups can be assigned now - simplifies a load of stuff for us!
		$db->setQuery("SELECT DISTINCT(group_id) FROM #__fabrik_formgroup");
		$usedgroups = $db->loadResultArray();
		if (!empty($usedgroups)) {
			$db->setQuery("SELECT id AS value, name AS text FROM #__fabrik_groups WHERE id NOT IN(".implode(",", $usedgroups) .") ORDER BY `text`");
			$groups 			= $db->loadObjectList();
		} else {
			$groups = array();
		}
		$lists['groups'] 	= $groups;
		$lists['grouplist']	= JHTML::_('select.genericlist', $groups, 'groups', "class=\"inputbox\" size=\"10\" style=\"width:100%;\" ", 'value', 'text', null);
		if ($cid[0] != 0) {
			$row->_database_name = $model->getTableName();
			$row->_connection_id = $model->getListModel()->getTable()->connection_id;
		} else {
			//this is a new form so fill in some default values
			$row->error 		= JText::_('SOME OF THE FORM DATA IS MISSING');
			$row->submit_button_label 	= JText::_('SUBMIT');
			$row->_database_name 		= '';
			$row->_connection_id 		= '';
			$menus = array();
		}
		//get the view only templates
		$viewTemplate = ($row->view_only_template == '') ? "default" : $row->view_only_template;
		$lists['viewOnlyTemplates'] = FabrikHelperAdminHTML::templateList('form', 'view_only_template', $viewTemplate);

		//get the form templates
		$formTemplate = ($row->form_template == '') ? "default" : $row->form_template;
		$lists['formTemplates'] = FabrikHelperAdminHTML::templateList('form', 'form_template', $formTemplate);

		$pluginManager = JModel::getInstance('Pluginmanager', 'FabrikModel');
		$pluginManager->getPlugInGroup('form');

		// Create the form
		$form = new JParameter( '', JPATH_COMPONENT.DS.'models'.DS.'form.xml');

		$form->bind($row);
		if ($cid[0] == 0 || $form->get('publish_down') == '' || $form->get('publish_down') ==  $db->getNullDate()) {
			$form->set('publish_down', JText::_('Never'));
		} else {
			$form->set('publish_down', JHTML::_('date', $row->publish_down, '%Y-%m-%d %H:%M:%S'));
		}

		$form->set('created', JHTML::_('date', $row->created, '%Y-%m-%d %H:%M:%S'));
		$form->set('publish_up', JHTML::_('date', $row->publish_up, '%Y-%m-%d %H:%M:%S'));

		$form->loadINI($row->attribs);
		$session->set('com_fabrik.admin.form.edit.model', $model);
		FabrikViewForm::edit($row, $pluginManager, $lists, $params, $form);
	}

	/**
	 * cancel editing
	 */

	function cancel()
	{
		JRequest::checkToken() or die('Invalid Token');

		// clear form from session
		$session =& JFactory::getSession();
		$session->clear('com_fabrik.admin.form.edit.model');

		$row 		=& JTable::getInstance('form', 'Table');
		$id 		= JRequest::getInt('id', 0, 'post');
		$row->load($id);
		$row->checkin();
		$this->setRedirect('index.php?option=com_fabrik&c=form');
	}

	/**
	 * Save a connection
	 */

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		// clear form from session
		$session =& JFactory::getSession();
		$session->clear('com_fabrik.admin.form.edit.model');

		jimport('joomla.utilities.date');

		$db =& JFactory::getDBO();
		$user = &JFactory::getUser();
		$formModel =& JModel::getInstance('Form', 'FabrikModel');
		$formModel->setId(JRequest::getInt('id'));
		$formModel->getForm();

		$row =& JTable::getInstance('form', 'Table');

		$post	= JRequest::get('post');

		if (!$row->bind($post)) {
			return JError::raiseWarning(500, $row->getError());
		}

		list($dofilter, $filter) = FabrikWorker::getContentFilter();
		//$filter	= new JFilterInput(null, null, 1, 1);
		$intro = JRequest::getVar('intro', '', 'post', 'string', JREQUEST_ALLOWRAW);
		$row->intro = $dofilter ? $filter->clean($intro) : $intro;

		$details	= JRequest::getVar('details', array(), 'post', 'array');
		$row->bind($details);

		FabrikHelper::prepareSaveDate($row->publish_down);
		FabrikHelper::prepareSaveDate($row->created);
		FabrikHelper::prepareSaveDate($row->publish_up);

		// save params

		$params = new fabrikParams($row->attribs, JPATH_COMPONENT.DS.'model'.DS.'form.xml');
		$row->attribs = $params->updateAttribsFromParams(JRequest::getVar('params', array(), 'post', 'array'));

		if ($row->id != 0) {
			$datenow =& JFactory::getDate();
			$row->modified 		= $datenow->toMySQL();
			$row->modified_by 	= $user->get('id');
		}

		if (!$row->store()) {
			return JError::raiseWarning(500, $row->getError());
		}
		$row->checkin();
		$formModel->_id = $row->id;
		$formModel->_form =& $row;
		$formModel->saveFormGroups();

		$task = JRequest::getCmd('task');

		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_fabrik&c=form&task=edit&cid[]='. $row->id;
				break;

			case 'save':
			default:
				$link = 'index.php?option=com_fabrik&c=form';
				break;
		}
		$cache = & JFactory::getCache('com_fabrik');
		$cache->clean();
		$this->setRedirect($link, JText::_('FORM SAVED'));
		//for prefab
		return $formModel;
	}

	/**
	 * Publish a form
	 */

	function publish()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=form');

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

		$query = 'UPDATE #__fabrik_forms'
		. ' SET state = ' . (int) $publish
		. ' WHERE id IN ( '. $cids.'  )'
		. ' AND ( checked_out = 0 OR ( checked_out = ' .(int) $user->get('id'). ') )'
		;
		$db->setQuery($query);
		if (!$db->query()) {
			return JError::raiseWarning(500, $row->getError());
		}
		$this->setMessage( JText::sprintf( $publish ? 'Items published' : 'Items unpublished', $n));
	}

	/**
	 * Display the list of forms
	 */

	function display()
	{
		$app =& JFactory::getApplication();
		$db =& JFactory::getDBO();
		$user =& JFactory::getUser();
		// get the total number of records
		$context			= 'com_fabrik.form.list.';
		$filter_order		= $app->getUserStateFromRequest( $context.'filter_order',	'filter_order',	'f.label',	'cmd');
		$filter_order_Dir	= $app->getUserStateFromRequest( $context.'filter_order_Dir',	'filter_order_Dir',	'',	'word');
		$limit				= $app->getUserStateFromRequest( $context.'limit', 'limit', $app->getCfg('list_limit'), 'int');
		$limitstart 		= $app->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int');
		$filter_form 		= $app->getUserStateFromRequest( $context."filter_form", 'filter_form', '');

		// table ordering
		$lists['order_Dir']	= $filter_order_Dir;
		$lists['order']		= $filter_order;

		$where = array();
		if ($filter_form != '') {
			$where[] = " f.label LIKE '%$filter_form%' ";
		}

		/*if ($user->gid <= 24) {
			$where[] = " f.private = '0'";
			}*/
		$where		= count($where ) ? ' WHERE ' . implode(' AND ', $where ) : '';
		$orderby	= ' ORDER BY '. $filter_order .' '. $filter_order_Dir;

		$db->setQuery("SELECT count(*) FROM #__fabrik_forms AS f $where");
		$total = $db->loadResult();

		jimport('joomla.html.pagination');
		$pageNav = new JPagination($total, $limitstart, $limit);

		$sql = "SELECT *, u.name AS editor, f.id AS id, t.id as _table_id, f.state AS state
		, f.label, f.attribs AS attribs FROM #__fabrik_forms AS f" .
			"\n LEFT JOIN #__users AS u ON u.id = f.checked_out " .
			"\n LEFT JOIN #__fabrik_tables as t ON f.id = t.form_id" .
			"\n $where $orderby";
		$db->setQuery($sql, $pageNav->limitstart, $pageNav->limit);
		$rows = $db->loadObjectList();
		if ($db->getErrorMsg() != '') {
			JError::raiseError(500, $db->getErrorMsg());
		}

		$lists['filter_form'] =  '<input type="text" value="' . $filter_form . '" name="filter_form" onblur="document.adminForm.submit();" />';
		require_once(JPATH_COMPONENT.DS.'views'.DS.'form.php');
		FabrikViewForm::show($rows, $pageNav, $lists);
	}

	/**
	 * copy a Form
	 */

	function copy()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=form');

		$cid		= JRequest::getVar('cid', null, 'post', 'array');
		$db			=& JFactory::getDBO();
		$rule		=& JTable::getInstance('form', 'Table');
		$user		= &JFactory::getUser();
		$n			= count($cid);

		$model =& JModel::getInstance('form', 'FabrikModel');

		if ($n > 0)
		{
			foreach ($cid as $id)
			{
				$model->setId($id);
				$form =& $model->getForm();
				if ($form->record_in_database == 1) {
					$ok = $model->getTableModel()->copy();
				} else {
					$ok = $model->copy();
				}
				if (JError::isError($ok)) {
					JRequest::set($origRequest);
					return JError::getErrors();
				}
			}
		}

		else {
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}
		$this->setMessage( JText::sprintf( 'Items copied', $n));
	}

	/**
	 * delete form
	 */

	function remove()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=form');

		// Initialize variables
		$db		=& JFactory::getDBO();
		$cid	= JRequest::getVar('cid', array(), 'post', 'array');
		$n		= count($cid);
		JArrayHelper::toInteger($cid);

		if ($n)
		{
			$query = 'DELETE FROM #__fabrik_forms'
			. ' WHERE id = ' . implode(' OR id = ', $cid )
			;
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500, $db->getError());
			}
		}
		// added by CyberFabrik aka CyberTiger 08 Sep 2008 - start
		// This extra piece of code will also remove "group to form" mappings...
		// Just deleting the form is not enough..We also need to dissasociate the
		// form from any of the groups that have been added to the form...
		//
		$query = 'DELETE FROM #__fabrik_formgroup'
		. ' WHERE form_id = ' . implode(' OR form_id = ', $cid )
		;
		$db->setQuery($query);
		if (!$db->query()) {
			JError::raiseWarning(500, $db->getError());
		}
		$this->setMessage( JText::sprintf( 'Items removed', $n));

	}

	/**
	 * called when form groups saved and record in database is true.
	 * Will either call methods to create or alter existing database table
	 * @return boolean false if not saved
	 */

	function updatedatabase()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$this->setRedirect('index.php?option=com_fabrik&c=form');
		$db =& JFactory::getDBO();
		$cid		= JRequest::getVar('cid', null, 'post', 'array');
		$formId = $cid[0];
		$model =& JModel::getInstance('Form', 'FabrikModel');
		$model->setId($formId);
		$form =& $model->getForm();

		//use this in case there is not table view linked to the form
		if ($form->record_in_database == 1) {
			//there is a table view linked to the form so lets load it
			$tableModel =& $model->getTableModel();
			$tableModel->loadFromFormId($form->id);
			$dbExisits = $tableModel->databaseTableExists();
			if (!$dbExisits) {
				$tableModel->createDBTable($model);
			} else {
				$tableModel->ammendTable($model);
			}
		}
		$this->setMessage( JText::_('DATABASE UPDATED'));
	}

}
?>