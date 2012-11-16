<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewPivotpopupwin extends JView
{

  var $_isMambot = false;


	function display($tmpl = 'default')
	{
		$model		= &$this->getModel();
		$this->assignRef('data', $this->get('Pivot'));
		$this->assign('heading', $this->get('Heading'));
		$tmplpath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'table'.DS.'pivot'.DS.'views'.DS.'pivotpopupwin'.DS.'tmpl';
		$this->_setPath('template', $tmplpath);

		//ensure we don't have an incorrect version of mootools loaded
		FabrikHelperHTML::cleanMootools();
		if ($this->_isMambot) {
			return $this->loadTemplate();
		} else {
			parent::display();
		}
	}

}
?>