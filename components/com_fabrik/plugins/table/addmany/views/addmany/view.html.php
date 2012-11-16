<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class fabrikViewAddmany extends JView
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
		
		$this->assign('addmany_to_fk_select', JHTML::_('select.genericlist', $this->addmany_to_fk_options, 'addmany_to_fk_val', 'class="fabrikinput inputbox" size="1"', 'value', 'text', '', $this->addmany_to_fk));
		

		JHTML::stylesheet('template.css', 'components/com_fabrik/plugins/table/addmany/views/addmany/tmpl/',true);
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