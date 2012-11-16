<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'element.php');

/**
 * Renders a list of elements found in a fabrik table
 *
 * @package 	Joomla
 * @subpackage	Articles
 * @since		1.5
 */

class JElementTablefields extends JElement
{
	/**
	 * Element name
	 * @access	protected
	 * @var		string
	 */
	var	$_name = 'Tablefields';

	var $_array_counter = null;

	/** @var array objects resulting from this elements queries - keyed on idetifying hash */
	var $results = null;


	function fetchElement($name, $value, &$node, $control_name)
	{
		if (is_null($this->results)) {
			$this->results = array();
		}

		$db =& JFactory::getDBO();
		$controller = JRequest::getVar('c');
		$session =& JFactory::getSession();
		$aEls 		= array();
		$onlytablefields = (int)$node->attributes('onlytablefields', 1);

		$onlytablefields = ($onlytablefields === 1) ? "show_in_table_summary = 1" : "";

		$pluginFilters = trim($node->attributes('filter')) == '' ? array() : explode("|", $node->attributes('filter'));
		$bits = array();

		$id 			= ElementHelper::getId($this, $control_name, $name);
		$fullName = ElementHelper::getFullName($this, $control_name, $name);

		$c = ElementHelper::getRepeatCounter( $this);
		switch($controller)
		{
			case 'element':
				//@TODO this seems like we could refractor it to use the formModel class as per the table and form switches below?
				//$connectionDd = $node->attributes( 'connection', '');
				$connectionDd = ($c === false) ? $node->attributes('connection') :  $node->attributes('connection') . '-' . $c;

				if ($node->attributes( 'connection', '') == '') {
					$oGroup = & JModel::getInstance('Group', 'FabrikModel');
					$groupid = $this->_parent->get('group_id');
					if ($groupid == '') {
						$cid = JRequest::getVar('cid');
						if (is_array($cid)) {
							$cid = $cid[0];
						}
						$db->setQuery("select group_id from #__fabrik_elements where id = ".(int)$cid);
						$groupid = $db->loadResult();
					}
					$oGroup->setId($groupid);
					$formModel =& $oGroup->getForm();
					$optskey = $node->attributes('valueformat', 'tableelement') == 'tableelement' ? 'name' : 'id';
					$onlytablefields = (int)$node->attributes( 'onlytablefields', 0);
					$res = $formModel->getElementOptions(false, $optskey, $onlytablefields, false, $pluginFilters);
					$hash = "$controller.".implode('.', $bits);
					if (array_key_exists($hash, $this->results)) {
						$res = $this->results[$hash];
					} else {

						$this->results[$hash] =& $res;
					}
				} else {

					//****************************//
					$repeat 	= ElementHelper::getRepeat($this);
					$tableDd = $node->attributes( 'table', '');
					FabrikHelperHTML::script('tablefields.js', 'administrator/components/com_fabrik/elements/', true);
					$opts = new stdClass();
					$opts->table = ($repeat) ? 'params' . $tableDd . "-" .$c : 'params' . $tableDd;

					$opts->livesite = COM_FABRIK_LIVESITE;
					$opts->conn = 'params' .$connectionDd;
					$opts->value = $value;
					$opts->repeat = $value;
					$opts = FastJSON::encode($opts);

					$script = "window.addEvent('domready', function() {\n";
					$script .= 	"new tablefieldsElement('$id', $opts);\n";
					$script .="});\n";
					$document =& JFactory::getDocument();
					$document->addScriptDeclaration($script);
					$rows = array(JHTML::_('select.option', '', JText::_('SELECT A CONNECTION FIRST') ), 'value', 'text');
					$o = new stdClass();
					$o->table_name = '';
					$o->name = '';
					$o->value = '';
					$o->text = JText::_('SELECT A TABLE FIRST');
					$res[] = $o;
					//****************************//
				}
				break;
			case 'table':
				$id = $this->_parent->get('id', false);
				if ($id === false) {
					$id = JRequest::getVar('cid', array(0));
					if (is_array($id)) {
						$id = $id[0];
					}
				}
				$tableModel =& $session->get('com_fabrik.admin.table.edit.model');
				$formModel =& $tableModel->getForm();
				$valfield = $node->attributes( 'valueformat', 'tableelement') == 'tableelement' ? 'name' : 'id';
				$onlytablefields = (int)$node->attributes( 'onlytablefields', 1);
				$incraw = $node->attributes('incraw', 0) == 1 ? true : false;
				$res = $formModel->getElementOptions(false, $valfield, $onlytablefields, $incraw, $pluginFilters);
				break;
			case 'form':
				$id = $this->_parent->get('id');
				$id = JRequest::getVar('cid', array(0));
				if (is_array($id)) {
					$id = $id[0];
				}
				$formModel =& $session->get('com_fabrik.admin.form.edit.model');
				$valfield = $node->attributes('valueformat', 'tableelement') == 'tableelement' ? 'name' : 'id';
				$onlytablefields = (int)$node->attributes( 'onlytablefields', 1);
				$incraw = $node->attributes('incraw', 0) == 1 ? true : false;
				$res = $formModel->getElementOptions(false, $valfield, $onlytablefields, $incraw, $pluginFilters);
				break;
			default:
				return JText::_('THE TABLEFIELDS ELEMENT IS ONLY USABLE BY TABLES AND ELEMENTS');
				break;
		}

		$return = '';
		if (is_array($res)) {
			if ($controller == 'element') {
				foreach ($res as $o) {
					$s = new stdClass();
					//element already contains correct key
					if ($controller != 'element') {
						$s->value= $node->attributes('valueformat', 'tableelement') == 'tableelement' ? $o->table_name.'.'.$o->text : $o->value;
					} else {
						$s->value = $o->value;
					}
					$s->text = FabrikString::getShortDdLabel($o->text);
					$aEls[] = $s;
				}
			} else {
				foreach ($res as &$o) {
					$o->text = FabrikString::getShortDdLabel($o->text);
				}
				$aEls = $res;
			}
			$id 			= ElementHelper::getId($this, $control_name, $name);
			$aEls[] = JHTML::_('select.option', '', '-');
			$return = JHTML::_('select.genericlist',  $aEls, $fullName, 'class="inputbox" size="1" ', 'value', 'text', $value, $id);
			$return .= "<img style='margin-left:10px;display:none' id='".$id."_loader' src='components/com_fabrik/images/ajax-loader.gif' alt='" . JText::_('LOADING'). "' />";
		}
		return $return;
	}
}