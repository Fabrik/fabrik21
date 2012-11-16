<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewPackage extends JView
{

	var $_template 	= null;
	var $_errors 	= null;
	var $_data 		= null;
	var $_rowId 	= null;
	var $_params 	= null;

	/** @var object table view */
	var $_tableView = null;

	/** @var object form view */
	var $_formView = null;

	var $_id;
	
	var $blocks = array();

	function fabrikViewPackage( &$oForm )
	{
		$this->_oForm = $oForm;
	}


	function display( $tpl = null )
	{
		$config		=& JFactory::getConfig();
		$user 		=& JFactory::getUser();
		$model		= &$this->getModel();
		$formModel =& $this->_formView->getModel();

		//Get the active menu item
		$usersConfig = &JComponentHelper::getParams('com_fabrik');

		$model->setId($usersConfig->get('packageid', JRequest::getInt('packageid', 0)));
		$package		=& $model->getPackage();
		$model->_lastTask 		= JRequest::getVar('task', '');
		$model->_senderBlock 	= JRequest::getVar('fabrik_senderBlock', '');
		//@TODO: not sure if this is used?
		$model->_lastTaskStatus 	= JRequest::getVar('taskstatus', '');

		/** @var string any data created by the lasttask - e.g. data to create a new table row with */
		$model->_lastTaskData = JRequest::getVar('taskData', '');
		// TODO: query table/forms to find out which blocks are releated to the block that has updated itself
		$model->_updateBlocks = JRequest::getVar('fbUpdateBlocks', array());

		$model->loadTables();

		$package =& $model->getPackage();

		$usedForms = array();
		if ($package->tables != '') {
			$tableids = explode(",", $package->tables);
			foreach ($tableids as $i) {
				if ($i === '') {
					continue;
				}
				//in PHP5 objects are assigned by reference as default -
				//cloning object doesnt deep clone other oject references either??
				//this copy method might be intensive

				$tableView = clone( $this->_tableView);
				$tableView->setId($i);
				$tableView->_isMambot = true;
				$tableModel =& $tableView->getModel();
				$tableModel->setId($i);
				$tableModel->_packageId = $this->_id;
				$tableModel->_postMethod = 'ajax';
				$table =& $tableModel->getTable();
				$this->blocks[$table->label] = $tableView->display();

				$table = $tableModel->getTable();
				$formModel =& $tableModel->getForm();

				$formModel->_editable = 1;
				$formView = clone( $this->_formView);
				//used to buffer output
				$formView->_isMambot = true;
				$formView->setId($table->form_id);
				$formModel->_postMethod = 'ajax';
				$formModel->_packageId = $this->_id;
				$usedForms[] = $formModel->_id;
				$formView->setModel($formModel, true);
				$this->blocks['form_' .$formModel->_id] = $formView->display();

				//creating a read only view
				$formModel->_editable = 0;
				$formView->setModel($formModel, true);
				$orgiView = JRequest::getVar('view', 'form');
				$view = JRequest::setVar('view', 'details');

				$this->blocks[$table->label .' details'] = $formView->display();
				JRequest::setVar('view', $orgiView);
			}
		}
		// see if we have any forms that dont record to the database that need to be added to blocks[]
		// can occur when redering a form in a module with use ajax turned on

		if ($package->forms != '') {
			$formids = explode(",", $package->forms);
			foreach ($formids as $fid) {
				if (!array_key_exists('form_' .$fid, $this->blocks)) {
					$formModel->_editable = 1;
					$formView = clone( $this->_formView);
					//used to buffer output
					$formView->_isMambot = true;
					$formView->setId($fid);
					$formModel->_postMethod = 'ajax';
					$formModel->_packageId = $this->_id;
					$usedForms[] = $formModel->_id;
					$formView->setModel($formModel, true);
					$this->blocks['form_' .$formModel->_id] = $formView->display();
				}
			}
		}

		$model->render();

		$this->_basePath = COM_FABRIK_FRONTEND . DS . 'views';
		$tmpl = JRequest::getVar('layout', 'default');
		//$this->blocks = $model->_blocks;
		$this->_setPath('template', $this->_basePath.DS.'package'.DS.'tmpl'.DS.$tmpl);

		if(!isset($package->template)) {
				
			$package->template = 'default';
			$tmpl = JRequest::getVar('layout', $package->template);
		} else {
			//set by table module in ajax mode
			$tmpl = $package->template;
		}

		$this->_includeCSS( $tmpl);
		$this->_basePath = COM_FABRIK_FRONTEND . DS . 'views';
		$this->_setPath('template', $this->_basePath.DS.'package'.DS.'tmpl'.DS.$tmpl);
		$liveTmplPath = JURI::root() . '/components/com_fabrik/views/package/tmpl/' . $tmpl . '/';
		FabrikHelperHTML::mootools();
		FabrikHelperHTML::mocha();

		// check for a custom js file and include it if it exists
		$aJsPath = JPATH_SITE.DS."components".DS."com_fabrik".DS."views".DS."package".DS."tmpl".DS.$tmpl.DS."javascript.js";
		if (file_exists($aJsPath)) {
			FabrikHelperHTML::script("javascript.js", 'components/com_fabrik/views/package/tmpl/'.$tmpl . '/', true);
		}

		//ensure we don't have an incorrect version of mootools loaded
		FabrikHelperHTML::cleanMootools();
		if ($this->_isMambot) {
			$res = $this->loadTemplate();
			if(JError::isError($res)) {
				print_r(JError::getErrors());
			}else{
				return $res;
			}
		} else {
			parent::display();
		}
	}

	/**
	 * include the template.css file
	 * @param string template name
	 */

	function _includeCSS( $tmpl )
	{
		$ab_css_file = COM_FABRIK_FRONTEND.DS."views".DS."package".DS."tmpl".DS.$tmpl.DS."template.css";
		if (file_exists($ab_css_file))
		{
			JHTML::stylesheet('template.css', 'components/com_fabrik/views/package/tmpl/'.$tmpl.'/', true);
		}
	}

}
?>