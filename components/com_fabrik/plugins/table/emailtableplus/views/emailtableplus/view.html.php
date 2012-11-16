<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewEmailtableplus extends JView
{
	var $_isMambot = false;

	function display( $tmpl = 'default')
	{
		FabrikHelperHTML::debug($this, 'view');
		$records = JRequest::getVar('ids', array());
		$renderOrder = JRequest::getVar('renderOrder');
		$tableid = JRequest::getVar('id', 0);
		$this->assign('recordcount', count($records));
		$this->assign('renderOrder', $renderOrder);
		$this->assign('recordids', implode(',', $records));
		$this->assign('tableid', $tableid);
		$this->assign('fieldList', $this->get('ElementList', 'form'));

		JHTML::stylesheet('template.css', 'components/com_fabrik/plugins/table/emailtableplus/views/emailtableplus/tmpl/',true);
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