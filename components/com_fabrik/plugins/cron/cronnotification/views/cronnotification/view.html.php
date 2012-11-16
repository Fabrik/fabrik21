<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewCronnotification extends JView
{

  var $_isMambot = false;

	function display( $tmpl = 'default')
	{


		$this->assignRef('rows', $this->get('UserNotifications'));

		$viewName = $this->getName();

		$tmplpath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'cron'.DS.'cronnotification'.DS.'views'.DS.'cronnotification'.DS.'tmpl'.DS.$tmpl;
		$this->_setPath('template', $tmplpath);

		$ab_css_file = $tmplpath.DS."template.css";

		if (file_exists($ab_css_file))
		{
			JHTML::stylesheet('template.css', 'components/com_fabrik/plugins/cron/cronnotification/views/cronnotification/tmpl/'.$tmpl.'/', true);
		}
		//ensure we don't have an incorrect version of mootools loaded
		FabrikHelperHTML::cleanMootools();
		// $$$ hugh @TODO - _isMambot ain't defined?
		if ($this->_isMambot) {
			return $this->loadTemplate();
		} else {
			parent::display();
		}
	}


}
?>
