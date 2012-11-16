<?php

/**
 * @package		Joomla
 * @subpackage	Fabik
 * @copyright	Copyright (C) 2005 - 2008 Pollen 8 Design Ltd. All rights reserved.
 * @license		GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class FabrikViewElement extends JView{

	var $_id 				= null;
	var $_isMambot 	= null;

	function setId($id)
	{
		$this->_id = $id;
	}

	/**
	 * display the template
	 *
	 * @param sting $tpl
	 */

	function display($tpl = null)
	{
		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php');
		$model =& $this->getModel();
		$tableModel = JModel::getInstance('Table', 'FabrikModel');
		$tableid = JRequest::getInt('tableid');
		$rowid = JRequest::getVar('rowid');
		$tableModel->setId($tableid);
		$data = JArrayHelper::fromObject($tableModel->getRow($rowid));
		$element = JRequest::getVar('element');
		$elementid = JRequest::getVar('elid');
		$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$plugin =& $pluginManager->getElementPlugin($elementid);
		$plugin->setId($elementid);
		if (!$plugin->canUse()) {
			if (JRequest::getVar('task') != 'save') {
				echo JText::_("NOT_AUTH");
				return;
			}
			$plugin->_editable = false;
		} else {
			$plugin->_editable = $model->_editable;
		}
		$groupModel =& $plugin->getGroup();

		$repeatCounter = 0;
		$html = '';
		$key = $plugin->getFullName();
		//@TODO add acl checks here
		if ($plugin->canToggleValue() && JRequest::getCmd('task') !== 'save'){
			// ok for yes/no elements activating them (double clicking in cell)
			// should simply toggle the stored value and return the new html to show
			$toggleValues = $plugin->getOptionValues();
			unset($toggleValues[$data[$key]]);
			$newvalue = array_pop($toggleValues);
			$data[$key] = $newvalue;
			$shortkey = array_pop(explode("___", $key));
			$tableModel->storeCell($rowid, $shortkey, $newvalue);
			$this->mode = 'readonly';
			$html = $plugin->renderTableData($data[$key], $data);
			echo $html;
			return;
		}
		unset($tableModel->_aRunCalculations);
		$tableModel->doCalculations($key);
		$doCalcs = "\noPackage.blocks['table_".$tableid."'].updateCals(".json_encode($tableModel->getCalculations()).")";

		// so not an element with toggle values, so load up the form widget to enable user
		// to select/enter a new value
		//wrap in fabriKElement div to ensure element js code works
		$html .= "<div class=\"fabrikElementContainer\">";
		$html .= "<div class=\"fabrikElement\">";
		if (JRequest::getCmd('task') !== 'save') {
			//render form element
			$html .= $plugin->_getElement($data, $repeatCounter, $groupModel);
		} else {
			// render table view
			$html .= $plugin->renderTableData($data[$key], $data);
		}
		$htmlid = $plugin->getHTMLId($repeatCounter);
		$html .= "</div></div>";
		if(JRequest::getCmd('task') !== 'save') {
			$html .= "<div class=\"ajax-controls\">";
			if (JRequest::getBool('inlinesave') == true) {
				$html .= "<a href=\"#\" class=\"inline-save\"><img src=\"".COM_FABRIK_LIVESITE."media/com_fabrik/images/action_check.png\" alt=\"".JText::_('SAVE')."\" /></a>";
			}
			if (JRequest::getBool('inlinecancel') == true) {
				$html .= "<a href=\"#\" class=\"inline-cancel\"><img src=\"".COM_FABRIK_LIVESITE."media/com_fabrik/images/del.png\" alt=\"".JText::_('CANCEL')."\" /></a>";
			}
			$html .= "</div>";
			$html .= "<script type=\"text/javasript\">";
			$html .= "
			window.inlineedit_$elementid = ".$plugin->elementJavascript($repeatCounter).";\n";
			$html .="window.inlineedit_$elementid.select();
			window.inlineedit_$elementid.focus();";
			$html .= "</script>\n";
		} else {
			$html .= "\n<script type=\"text/javasript\">";
				$html .= $doCalcs;
			$html .= "</script>\n";
		}
		echo $html;
	}

}
?>