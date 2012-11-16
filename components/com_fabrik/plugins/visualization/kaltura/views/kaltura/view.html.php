<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewKaltura extends JView
{

	function display( $tmpl = 'default')
	{
		$app =& JFactory::getApplication();
		FabrikHelperHTML::packageJS();
		$params 	   	=& $app->getParams('com_fabrik');
		$document 		=& JFactory::getDocument();
		$usersConfig 	= &JComponentHelper::getParams('com_fabrik');
		$model				= &$this->getModel();
		$model->setId(JRequest::getVar('id', $usersConfig->get('visualizationid', JRequest::getInt('visualizationid', 0))));
		$this->row 		=& $model->getVisualization();
		$this->params =& $model->getParams();

		$pluginParams =& $model->getPluginParams();
		$this->assignRef('params', $pluginParams);
		$tmpl = $pluginParams->get('fb_gm_layout', $tmpl);
		$tmplpath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'visualization'.DS.'kaltura'.DS.'views'.DS.'kaltura'.DS.'tmpl'.DS.$tmpl;


		$js = <<<EOT
		<script type="text/javascript" >
function entryClicked ( entry_id )
{
	window.location = "./player.php?entry_id=" + entry_id;
}
</script>
EOT;
		JHTML::stylesheet('table.css', 'media/com_fabrik/css/');
		$this->assign('requiredFiltersFound', $this->get('RequiredFiltersFound'));
		if ($this->requiredFiltersFound) {
			$this->assignRef('data', $this->get('Data'));
		} else {
			$this->assignRef('data', '');
		}

		FabrikHelperHTML::addScriptDeclaration($js);

		$ab_css_file = $tmplpath.DS."template.css";

		if (JFile::exists($ab_css_file))
		{
			JHTML::stylesheet('template.css', 'components/com_fabrik/plugins/visualization/googlemap/views/googlemap/tmpl/'.$tmpl.'/', true);
		}
		//check and add a general fabrik custom css file overrides template css and generic table css
		FabrikHelperHTML::stylesheetFromPath("media".DS."com_fabrik".DS."css".DS."custom.css");
		//check and add a specific biz  template css file overrides template css generic table css and generic custom css
		FabrikHelperHTML::stylesheetFromPath("components".DS."com_fabrik".DS."plugins".DS."visualization".DS."kaltura".DS."views".DS."kaltura".DS."tmpl".DS.$tmpl.DS."custom.css");
		$template = null;
		$this->assign('containerId', $this->get('ContainerId'));
		$this->assign('showFilters', JRequest::getInt('showfilters', 1) === 1 ?  1 : 0);
		$this->assignRef('filters', $this->get('Filters'));
		$this->assign('filterFormURL', $this->get('FilterFormURL'));
		$this->_setPath('template', $tmplpath);

		//ensure we don't have an incorrect version of mootools loaded
		FabrikHelperHTML::cleanMootools();
		echo parent::display($template);
	}

}
?>