<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewSlideshow extends JView
{

	function display($tmpl = 'default')
	{
		$document =& JFactory::getDocument();
		FabrikHelperHTML::packageJS();
		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');
		$model =& $this->getModel();
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		$model->setId(JRequest::getVar('id', $usersConfig->get('visualizationid', JRequest::getInt('visualizationid', 0))));

		$this->row =& $model->getVisualization();
		$model->setTableIds();

		if ($this->row->state == 0) {
			JError::raiseWarning(500, JText::_('ALERTNOTAUTH'));
			return '';
		}
		$this->assign('js', $this->get('JS'));
		$viewName = $this->getName();
		$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$plugin =& $pluginManager->getPlugIn('slideshow', 'visualization');
		$this->assign('showFilters', JRequest::getInt('showfilters', 1) === 1 ?  1 : 0);
		$this->assignRef('filters', $this->get('Filters'));
		$this->assign('filterFormURL', $this->get('FilterFormURL'));
		$pluginParams =& $model->getPluginParams();
		$this->assignRef('params', $pluginParams);
		$tmpl = $pluginParams->get('slideshow_viz_layout', $tmpl);
		JHTML::stylesheet('table.css', 'media/com_fabrik/css/');
		$tmplpath = $model->pathBase.'slideshow'.DS.'views'.DS.'slideshow'.DS.'tmpl'.DS.$tmpl;
		$this->_setPath('template', $tmplpath);

		FabrikHelperHTML::script('table.js', 'media/com_fabrik/js/', true);

		if ($this->get('RequiredFiltersFound')) {
			FabrikHelperHTML::script('slideshow.js', 'components/com_fabrik/libs/slideshow2/js/', true);
			$slideshow_viz_type = $pluginParams->get('slideshow_viz_type', 1);
			switch ($slideshow_viz_type) {
				case 1:
					break;
				case 2:
					FabrikHelperHTML::script('slideshow.kenburns.js', 'components/com_fabrik/libs/slideshow2/js/', true);
					break;
				case 3:
					FabrikHelperHTML::script('slideshow.push.js', 'components/com_fabrik/libs/slideshow2/js/', true);
					break;
				case 4:
					FabrikHelperHTML::script('slideshow.fold.js', 'components/com_fabrik/libs/slideshow2/js/', true);
					break;
				default:
					break;
			}


			JHTML::stylesheet('slideshow.css', 'components/com_fabrik/libs/slideshow2/css/');

			FabrikHelperHTML::script('slideshow.js', 'components/com_fabrik/plugins/visualization/slideshow/', true);
		}
		FabrikHelperHTML::addScriptDeclaration($this->js);

		$ab_css_file = $tmplpath.DS."template.css";

		if (JFile::exists($ab_css_file)) {
			JHTML::stylesheet('template.css', $this->srcBase.'slideshow/views/slideshow/tmpl/'.$tmpl.'/', true);
		}
		//check and add a general fabrik custom css file overrides template css and generic table css
		FabrikHelperHTML::stylesheetFromPath("media".DS."com_fabrik".DS."css".DS."custom.css");
		//check and add a specific biz  template css file overrides template css generic table css and generic custom css
		FabrikHelperHTML::stylesheetFromPath("components".DS."com_fabrik".DS."plugins".DS."visualization".DS."slideshow".DS."views".DS."slideshow".DS."tmpl".DS.$tmpl.DS."custom.css");
		//ensure we don't have an incorrect version of mootools loaded
		FabrikHelperHTML::cleanMootools();
		echo parent::display();
	}

}
?>