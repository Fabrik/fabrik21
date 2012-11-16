<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewTimeline extends JView
{

	function display($tmpl = 'default')
	{
		FabrikHelperHTML::packageJS();
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		$model = $this->getModel();
		$id = JRequest::getVar('id', $usersConfig->get('visualizationid', JRequest::getInt('visualizationid', 0)));
		$model->setId($id);
		$row =& $model->getVisualization();
		$model->setTableIds();

		$model->render();
		$this->assign('containerId', $this->get('ContainerId'));
		$this->assignRef('row', $row);
		$this->assign('showFilters', JRequest::getInt('showfilters', 1) === 1 ?  1 : 0);
		$this->assignRef('filters', $this->get('Filters'));
		$this->assign('filterFormURL', $this->get('FilterFormURL'));
		$pluginParams =& $model->getPluginParams();
		$this->assignRef('params', $pluginParams);
		$tmplpath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.'timeline'.DS.'views'.DS.'timeline'.DS.'tmpl'.DS.$tmpl;

		$this->_setPath('template', $tmplpath);
		//ensure we don't have an incorrect version of mootools loaded
		JHTML::stylesheet('table.css', 'media/com_fabrik/css/');
		FabrikHelperHTML::script('table.js', 'media/com_fabrik/js/', true);

		//check and add a general fabrik custom css file overrides template css and generic table css
		FabrikHelperHTML::stylesheetFromPath( "media".DS."com_fabrik".DS."css".DS."custom.css");
		//check and add a specific biz  template css file overrides template css generic table css and generic custom css
		FabrikHelperHTML::stylesheetFromPath( "components".DS."com_fabrik".DS."plugins".DS."visualization".DS."timeline".DS."views".DS."timeline".DS."tmpl".DS.$tmpl.DS."custom.css");

		FabrikHelperHTML::cleanMootools();
		echo parent::display();
	}
}
?>