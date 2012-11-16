<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewPopupwin extends JView
{

  var $_isMambot = false;


	function display($tmpl = 'default')
	{
		$model		= &$this->getModel();
		$document =& JFactory::getDocument();
		$renderOrder = JRequest::getInt('renderOrder');
		$usersConfig = &JComponentHelper::getParams('com_fabrik');

		$tmplpath = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'table'.DS.'emailtable'.DS.'views'.DS.'popupwin'.DS.'tmpl';
		$this->_setPath('template', $tmplpath);
		$this->assign('fieldList', $this->get('ToField'));
		$records = $this->get('records');
		if (count($records) == 0) {
			JError::raiseNotice(500, 'None of the selected records can be emailed');
			return;
		}
		$this->assign('recordcount', count($records));
		$this->assign('renderOrder', $renderOrder);
		$this->assign('recordids', implode(',', $records));
		$this->assign('tableid', $this->get('id', 'table'));

		$this->assign('showSubject', $this->get('showSubject'));
		$this->assign('subject', $this->get('subject'));
		$this->assign('message', $this->get('message'));
		$this->assign('allowAttachment', $this->get('allowAttachment'));
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