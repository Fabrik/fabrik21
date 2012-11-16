<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewFusion_gantt_chart extends JView
{

	function display( $tmpl = 'default')
	{
		JHTML::_('behavior.calendar');
		FabrikHelperHTML::packageJS();
		FabrikHelperHTML::script('table.js', 'media/com_fabrik/js/');
		FabrikHelperHTML::script('advanced-search.js', 'media/com_fabrik/js/');
		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');
		$model		= &$this->getModel();
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		$model->setId(JRequest::getVar('id', $usersConfig->get('visualizationid', JRequest::getInt('visualizationid', 0))));
		$this->row =& $model->getVisualization();
		$model->setTableIds();


		if ($this->row->state == 0) {
			JError::raiseWarning(500, JText::_('ALERTNOTAUTH'));
			return '';
		}
		$this->assign('requiredFiltersFound', $this->get('RequiredFiltersFound'));
		if ($this->requiredFiltersFound) {
			$this->assign('chart', $this->get('Chart'));
		}
		$viewName = $this->getName();
		$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$plugin =& $pluginManager->getPlugIn('calendar', 'visualization');
		$this->assign('containerId', $this->get('ContainerId'));
    $this->assignRef('filters', $this->get('Filters'));
    $this->assign('filterFormURL', $this->get('FilterFormURL'));
    $this->assign('showFilters', JRequest::getInt('showfilters', 1));
		$pluginParams =& $model->getPluginParams();
		$this->assignRef('params', $pluginParams);
		JHTML::stylesheet('table.css', 'media/com_fabrik/css/');
		$tmpl = $pluginParams->get('fusion_gantt_chart_layout', $tmpl);
		$tmplpath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.'fusion_gantt_chart'.DS.'views'.DS.'fusion_gantt_chart'.DS.'tmpl'.DS.$tmpl;
		$this->_setPath('template', $tmplpath);

		$ab_css_file = $tmplpath.DS."template.css";

		if (JFile::exists($ab_css_file))
		{
			JHTML::stylesheet('template.css', 'components/com_fabrik/plugins/visualization/fusion_gantt_chart/views/fusion_gantt_chart/tmpl/'.$tmpl.'/', true);
		}
		//check and add a general fabrik custom css file overrides template css and generic table css
		FabrikHelperHTML::stylesheetFromPath("media".DS."com_fabrik".DS."css".DS."custom.css");
		//check and add a specific biz  template css file overrides template css generic table css and generic custom css
		FabrikHelperHTML::stylesheetFromPath("components".DS."com_fabrik".DS."plugins".DS."visualization".DS."fusion_gantt_chart".DS."views".DS."fusion_gantt_chart".DS."tmpl".DS.$tmpl.DS."custom.css");
		//ensure we don't have an incorrect version of mootools loaded

		//assign something to oPackage to ensure we can clear filters
		$str = "window.addEvent('domready', function(){
			fabrikChart{$this->row->id} = {};";
		$str .= "\n" . "oPackage.addBlock('vizualization_{$this->row->id}', fabrikChart{$this->row->id});
		});";
		FabrikHelperHTML::addScriptDeclaration($str);

		FabrikHelperHTML::cleanMootools();
		echo parent::display();
	}

}
?>