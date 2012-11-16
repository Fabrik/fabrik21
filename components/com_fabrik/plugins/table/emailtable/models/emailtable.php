<?php

/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'plugin-table.php');

class fabrikModelEmailtable extends FabrikModelTablePlugin {

	var $useMocha = true;

	var $_buttonPrefix = 'emailtable';

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * show a new for entering the form actions options
	 */

	function renderAdminSettings($elementId, &$row, &$params, $lists, $c)
	{
		$params->_counter_override = $this->_counter;
		$display =  ($this->_adminVisible) ? "display:block" : "display:none";
		$return = '<div class="page-' . $elementId . ' elementSettings" style="' . $display . '">
 		' . $params->render('params', '_default', false, $c) .
 		'</div>
 		';
		$return = str_replace("\r", "", $return);
		return $return;
	}


	function getAclParam()
	{
		return 'emailtable_access';
	}

	function button()
	{
		return "email records";
	}

	function button_result($c)
	{
		if ($this->canUse()) {
			$params =& $this->getParams();
			$loc = $params->get('emailtable_button_location', 'bottom');
			if ($loc == 'bottom' || $loc == 'both') {
				return $this->getButton();
			} else {
				return '';
			}
		}
	}

	protected function getButton()
	{
		$params =& $this->getParams();
		$name = $this->_getButtonName();
		if ($this->canUse()) {
			$label = $params->get('email_button_label', JText::_('EMAIL'));
			return "<input type=\"button\" name=\"$name\" value=\"". $label . "\" class=\"button tableplugin\"/>";
		}
		return '';
	}

	/**
	 * get the position for the button
	 */

	protected function getRenderLocation()
	{
		return $this->getParams()->get('emailtable_button_location', 'bottom');
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		FabrikHelperHTML::mocha();
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/table/emailtable/', false);
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @param object parameters
	 * @param object table model
	 * @param array [0] => string table's form id to contain plugin
	 * @return bool
	 */

	function loadJavascriptInstance($params, $model, $args)
	{
		$form_id = $args[0];
		$opts 						= new stdClass();
		$opts->liveSite 	= COM_FABRIK_LIVESITE;
		$opts->name = $this->_getButtonName();
		$opts->mooversion = (FabrikWorker::getMooVersion() == 1) ? 1.2 : 1.1;
		$opts->renderOrder = $this->renderOrder;
		$opts 						= json_encode($opts);
		$lang 						= $this->_getLang();
		$lang 						= json_encode($lang);
		$this->jsInstance = "new fbTableEmail('$form_id', $opts, $lang)";
		return true;
	}

	function getToField()
	{
		$this->_type = 'table';
		$this->_id = JRequest::getInt('id');
		$params =& $this->getParams();
		$renderOrder = JRequest::getInt('renderOrder');
		$toType = $params->get('emailtable_to_type');
		$toType = is_array($toType) ? JArrayHelper::getValue($toType, $renderOrder, 'list') : $toType;
		if ($toType == 'field') {
			$to = $params->get('emailtable_to');
			$to = is_array($to) ? JArrayHelper::getValue($to, $renderOrder) : $to;
			return "<input name=\"email_to\" id=\"email_to\" value=\"".$to."\" readonly=\"true\" />";
		}
		else if ($toType == 'list') {
			return $this->formModel->getElementList('email_to');
		}
		else if ($toType == 'table' || $toType == 'table_picklist') {
			$emailtable_to_table_table = $params->get('emailtable_to_table_table');
			if (is_array($emailtable_to_table_table)) {
				$emailtable_to_table_table = $emailtable_to_table_table[$renderOrder];
			}
			$emailtable_to_table_email = $params->get('emailtable_to_table_email');
			if (is_array($emailtable_to_table_email)) {
				$emailtable_to_table_email = $emailtable_to_table_email[$renderOrder];
			}
			$emailtable_to_table_name = $params->get('emailtable_to_table_name');
			if (is_array($emailtable_to_table_name)) {
				$emailtable_to_table_name = $emailtable_to_table_name[$renderOrder];
			}
			if (empty($emailtable_to_table_name)) {
				$emailtable_to_table_name = $emailtable_to_table_email;
			}

			$toTableModel =& JModel::getInstance('table', 'FabrikModel');
			$toTableModel->setId($emailtable_to_table_table);
			//$toFormModel =& $toTableModel->getFormModel();
			$toDb = $toTableModel->getDb();

			$emailtable_to_table_name = FabrikString::safeColName($emailtable_to_table_name);
			$emailtable_to_table_email = FabrikString::safeColName($emailtable_to_table_email);
			$emailtable_to_table = $toDb->nameQuote($toTableModel->getTable()->db_table_name);

			$toDb->setQuery("SELECT $emailtable_to_table_email AS email, $emailtable_to_table_name AS name FROM $emailtable_to_table ORDER BY name ASC");
			$results = $toDb->loadObjectList();
			$empty = new stdClass();

			if ($toType == 'table_picklist') {
				// $$$ hugh - yeah yeah, I'll move these into assets when I get a spare minute or three.
				$html = '
<style type="text/css">
	.fabrik_email_holder	{ width:200px; float:left; }
	#email_add,#email_remove	{ display:block; width:150px; text-align:center; border:1px solid #ccc; background:#eee; }
	.fabrik_email_holder select	{ margin:0 0 10px 0; width:150px; padding:5px; height:200px; }
</style>
<script type="text/javascript">
	window.addEvent(\'domready\', function() {
		$(\'email_add\').addEvent(\'click\', function() {
			$(\'email_to_selectfrom\').getSelected().each(function(el) {
				el.inject($(\'email_to\'));
			});
		});
		$(\'email_remove\').addEvent(\'click\', function() {
			$(\'email_to\').getSelected().each(function(el) {
				el.inject($(\'email_to_selectfrom\'));
			});
		});
	});
</script>
';
				$html .= '<div class="fabrik_email_holder">';
				$html .= JHTML::_('select.genericlist', $results, 'email_to_selectfrom[]', 'class="fabrikinput inputbox" multiple="multiple" size="5"', 'email', 'name', '', 'email_to_selectfrom');
				$html .= '<a href="javascript:;" id="email_add">add &gt;&gt;</a>';
				$html .= '</div>';
				$html .= '<div class="fabrik_email_holder">';
				$html .= JHTML::_('select.genericlist', $empty, 'email_to[]', 'class="fabrikinput inputbox" multiple="multiple" size="5"', 'email', 'name', '', 'email_to');
				$html .= '<a href="javascript:;" id="email_remove">&lt;&lt; remove</a>';
				$html .= '</div>';
				$html .= '<div style="clear:both;"></div>';
			}
			else {
				$html = JHTML::_('select.genericlist', $results, 'email_to[]', 'class="fabrikinput inputbox" multiple="multiple" size="5"', 'email', 'name', '', 'email_to');
			}

			return $html;
		}
	}

	public function getAllowAttachment()
	{
		$renderOrder = JRequest::getInt('renderOrder');
		$params =& $this->getParams();
		$allow = $params->get('emailtable_allow_attachment');
		return is_array($allow) ? JArrayHelper::getValue($allow, $renderOrder, false) : $allow;
	}

	public function getShowSubject()
	{
		$renderOrder = JRequest::getInt('renderOrder');
		$params =& $this->getParams();
		$var = $params->get('emailtable_hide_subject');
		return (is_array($var) ? JArrayHelper::getValue($var, $renderOrder, '') : $var) == '0';
	}

	public function getSubject()
	{
		$renderOrder = JRequest::getInt('renderOrder');
		$params =& $this->getParams();
		$var = $params->get('email_subject');
		return is_array($var) ? JArrayHelper::getValue($var, $renderOrder, '') : $var;
	}

	public function getMessage()
	{
		$renderOrder = JRequest::getInt('renderOrder');
		$params =& $this->getParams();
		$var = $params->get('email_message');
		return is_array($var) ? JArrayHelper::getValue($var, $renderOrder. '') : $var;
	}

	public function getRecords($key = 'ids', $allData = false)
	{
		$ids = (array)JRequest::getVar($key, array());
		$renderOrder = JRequest::getInt('renderOrder');
		$params =& $this->getParams();
		$model = JModel::getInstance('table', 'FabrikModel');
		$model->setId(JRequest::getInt('id'));
		$pk = $model->getTable()->db_primary_key;
		$pk2 = FabrikString::safeColNameToArrayKey($pk).'_raw';
		$whereClause = "($pk IN (" . implode(",", $ids). "))";
		$cond = $params->get('emailtable_condition');
		$cond = is_array($cond) ? JArrayHelper::getValue($cond, $renderOrder, '') : $cond;
		if (trim($cond) !== '') {
			$whereClause .= " AND ($cond)";
		}
		$model->setPluginQueryWhere($this->_buttonPrefix, $whereClause);
		$model->formatAll(true);
		$data = $model->getData();
		if ($allData) {
			return $data;
		}
		$return = array();
		foreach ($data as $gdata) {
			foreach($gdata as $row) {
				$return[] = $row->$pk2;
			}
		}
		return $return;
	}

	/**
	 * upload the attachments to the server
	 * @access private
	 *
	 * @return bol success/fail
	 */

	function _upload()
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.client.helper');
		JClientHelper::setCredentialsFromRequest('ftp');
		$files 	= JRequest::getVar('attachement', array(), 'files');
		$folder = JPATH_ROOT.DS.'images'.DS.'stories';
		$this->filepath = array();
		$c = 0;
		if (array_key_exists('name', $files)) {
			foreach ($files['name'] as $name) {
				if ($name == '') {
					continue;
				}
				$path = $folder.DS.strtolower($name);
				if (!JFile::upload($files['tmp_name'][$c], $path)) {
					JError::raiseWarning(100, JText::_('Error. Unable to upload file'));
					return false;
				} else {
					$this->filepath[] = $path;
				}
				$c ++;
			}
		}
		return true;
	}

	public function doEmail($tableModel)
	{
		$app =& JFactory::getApplication();
		jimport('joomla.mail.helper');
		if (!$this->_upload()) {
			return false;
		}

		$tableModel->setId(JRequest::getInt('id', 0));
		$w = new FabrikWorker();
		$config =& JFactory::getConfig();
		$this->_type = 'table';
		$params =& $tableModel->getParams();
		$to = JRequest::getVar('email_to');
		$renderOrder = JRequest::getInt('renderOrder');

		$merge_emails = $params->get('emailtable_mergemessages', 0);
		if (is_array($merge_emails)) {
			$merge_emails  = (int)JArrayHelper::getValue($merge_emails, $renderOrder, 0);
		}

		$toHow = $params->get('emailtable_to_how', 'single');
		if (is_array($toHow)) {
			$toHow = JArrayHelper::getValue($toHow, $renderOrder, 'single');
		}

		$toType = $params->get('emailtable_to_type', 'list');
		if (is_array($toType)) {
			$toType = JArrayHelper::getValue($toType, $renderOrder, 'list');
		}
		if ($toType == 'list') {
			$to = str_replace('.', '___', $to);
		}
		else {
			if (is_array($to)) {
				// $$$ hugh - if using a table selection type, allow specifying a default in
				// the "emailtable_to" field.
				if ($toType != 'field') {
					$emailtable_to = $params->get('emailtable_to', '');
					if (is_array($emailtable_to)) {
						$emailtable_to  = JArrayHelper::getValue($emailtable_to, $renderOrder, '');
					}
					if (!empty($emailtable_to)) {
						if (!in_array($emailtable_to, $to)) {
							$to[] = $emailtable_to;
						}
					}
				}
				$to = implode(',', $to);
			}
		}

		$fromUser = $params->get('emailtable_from_user');
		if (is_array($fromUser)) {
			$fromUser = JArrayHelper::getValue($fromUser, $renderOrder, '');
		}

		$emailTemplate = $params->get('emailtable_template', '');
		if (is_array($emailTemplate)) {
			$emailTemplate = JArrayHelper::getValue($emailTemplate, $renderOrder, '');
		}
		if (!empty($emailTemplate)) {
			$emailTemplate = JPath::clean(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'plugins'.DS.'table'.DS.'emailtable'.DS.'tmpl'.DS . $emailTemplate);
		}

		$contentTemplate = $params->get('emailtable_template_content', '');
		if (is_array($contentTemplate)) {
			$contentTemplate = JArrayHelper::getValue($contentTemplate, $renderOrder, '');
		}
		if ($contentTemplate != '') {
			$content = $this->_getConentTemplate($contentTemplate);
		} else {
			$content = '';
		}

		$php_msg = false;
		if (JFile::exists($emailTemplate)) {
			if (JFile::getExt($emailTemplate) == 'php') {
				// $message = $this->_getPHPTemplateEmail($emailTemplate);
				$message = '';
				$php_msg = true;
			} else {
				$message = $this->_getTemplateEmail($emailTemplate);
			}
			$message = str_replace('{content}', $content, $message);
		} else {
			if ($contentTemplate != '') {
				$message = $content;
			} else {
				$message = '';
			}
		}

		$subject 			= JRequest::getVar('subject');
		$cover_message 			= JRequest::getVar('message', '', 'post', 'string', 4);
		$old_style = false;
		if (empty($message) && !$php_msg) {
			$old_style = true;
			//$message = $cover_message;
		}
		$recordids 		= explode(',', JRequest::getVar('recordids'));
		$data = $this->getRecords('recordids', true);
		if ($fromUser) {
			$my =& JFactory::getUser();
			$email_from = $my->get('email');
			// $$$ rob - erm $ ema isn't used anywhere - do we need it?????
			$fromname = $my->get('name');
		} else {
			$config =& JFactory::getConfig();
			$email_from = $config->getValue('mailfrom');
			$fromname = $config->getValue('fromname');
		}

		$cc 					= null;
		$bcc 					= null;
		$sent 				= 0;
		$notsent 			= 0;
		$updated = array();
		$merged_msg = '';
		$first_row = array();
		foreach ($data as $group) {
			foreach ($group as $row) {
				if ($merge_emails) {
					if (empty($first_row)) {
						$first_row = $row; // used for placeholders in subject when merging mail
					}
					$thismsg = '';
					if ($old_style) {
						$thismsg = $w->parseMessageForPlaceHolder($cover_message, $row);
					}
					else {
						if ($php_msg) {
							$thismsg = $this->_getPHPTemplateEmail($emailTemplate, $row, $tableModel);
						}
						else {
							$thismsg = $w->parseMessageForPlaceHolder($message, $row);
						}
					}
					$merged_msg .= $thismsg;
					$updated[] = $row->__pk_val;
				}
				else {
					if ($toType == 'list') {
						$process = isset($row->$to);
						$mailto = $row->$to;
					} else {
						$process = true;
						$mailto = $to;
					}
					if ($process) {
						$res = false;
						$mailtos = explode(',', $mailto);
						if ($toHow == 'single') {
							foreach ($mailtos as $tokey => $thisto) {
								$thisto = $w->parseMessageForPlaceholder($thisto, $row);
								if (!JMailHelper::isEmailAddress($thisto)) {
									unset($mailtos[$tokey]);
									$notsent++;
								}
								else {
									$mailtos[$tokey] = $thisto;
								}
							}
							if ($notsent > 0) {
								$mailtos = array_values($mailtos);
							}
							$sent = sizeof($mailtos);
							if ($sent > 0) {
								$thissubject = $w->parseMessageForPlaceholder($subject, $row);
								$thismsg = '';
								$thismsg = $cover_message;
								if (!$old_style) {
									if ($php_msg) {
										$thismsg .= $this->_getPHPTemplateEmail($emailTemplate, $row, $tableModel);
									}
									else {
										$thismsg .= $message;
									}
								}
								$thismsg = $w->parseMessageForPlaceholder($thismsg, $row);
								$res = JUtility::sendMail($email_from, $fromname, $mailtos, $thissubject, $thismsg, 1, $cc, $bcc, $this->filepath);
							}
						}
						else {
							foreach ($mailtos as $mailto) {
								$mailto = $w->parseMessageForPlaceholder($mailto, $row);
								if (JMailHelper::isEmailAddress($mailto)) {
									$thissubject = $w->parseMessageForPlaceholder($subject, $row);
									$thismsg = '';
									$thismsg = $cover_message;
									if (!$old_style) {
										if ($php_msg) {
											$thismsg .= $this->_getPHPTemplateEmail($emailTemplate, $row, $tableModel);
										}
										else {
											$thismsg .= $message;
										}
									}
									$thismsg = $w->parseMessageForPlaceholder($thismsg, $row);
									$res = JUtility::sendMail($email_from, $fromname, $mailto, $thissubject, $thismsg, 1, $cc, $bcc, $this->filepath);
									if ($res) {
										$sent ++;
									} else {
										$notsent ++;
									}
								} else {
									$notsent ++;
								}
							}
						}
						if ($res) {
							$updated[] = $row->__pk_val;
						}
					} else {
						$notsent ++;
					}
				}
			}
		}
		if ($merge_emails) {
			// arbitrarily use first row for placeholders
			if ($toType == 'list') {
				$mailto = $first_row->$to;
			} else {
				$mailto = $to;
			}
			$thistos = explode(',', $w->parseMessageForPlaceHolder($mailto, $first_row));
			$thissubject = $w->parseMessageForPlaceHolder($subject, $first_row);
			$preamble = $params->get('emailtable_message_preamble', '');
			$preamble = is_array($preamble) ? JArrayHelper::getValue($preamble, $renderOrder, '') : $preamble;
			$postamble = $params->get('emailtable_message_postamble', '');
			$postamble = is_array($postamble) ? JArrayHelper::getValue($postamble, $renderOrder, '') : $postamble;
			$merged_msg = $preamble . $merged_msg . $postamble;
			if (!$old_style) {
				$merged_msg = $cover_message . $merged_msg;
			}

			if ($toHow == 'single') {
				foreach ($thistos as $tokey => $thisto) {
					$thisto = $w->parseMessageForPlaceholder($thisto, $first_row);
					if (!JMailHelper::isEmailAddress($thisto)) {
						unset($thistos[$tokey]);
						$notsent++;
					}
					else {
						$mailtos[$tokey] = $thisto;
					}
				}
				if ($notsent > 0) {
					$thistos = array_values($thistos);
				}
				$sent = sizeof($thistos);
				if ($sent > 0) {
					$res = JUTility::sendMail($email_from, $fromname, $thistos, $thissubject, $merged_msg, true, $cc, $bcc, $this->filepath);
				}
			}
			else {
				foreach ($thistos as $thisto) {
					if (JMailHelper::isEmailAddress($thisto)) {
						$res = JUTility::sendMail($email_from, $fromname, $thisto, $thissubject, $merged_msg, true, $cc, $bcc, $this->filepath);
						if ($res) {
							$sent ++;
						} else {
							$notsent ++;
						}
					} else {
						$notsent++;
					}
				}
			}
		}
		$updateField = $params->get('emailtable_update_field');
		$updateField = is_array($updateField) ? JArrayHelper::getValue($updateField, $renderOrder, '') : $updateField;
		if (!empty($updateField) && !empty($updated)) {
			$updateVal = $params->get('emailtable_update_value');
			$updateVal = is_array($updateVal) ? JArrayHelper::getValue($updateVal, $renderOrder, '') : $updateVal;
			$tableModel->updateRows($updated, $updateField, $updateVal);
		}
		// $$$ hugh - added second update field for Bea
		$updateField = $params->get('emailtable_update_field2');
		$updateField = is_array($updateField) ? JArrayHelper::getValue($updateField, $renderOrder, '') : $updateField;
		if (!empty($updateField) && !empty($updated)) {
			$updateVal = $params->get('emailtable_update_value2');
			$updateVal = is_array($updateVal) ? JArrayHelper::getValue($updateVal, $renderOrder, '') : $updateVal;
			$tableModel->updateRows($updated, $updateField, $updateVal);
		}
		$app->enqueueMessage(JText::sprintf('%s emails sent', $sent));
		if ($notsent != 0) {
			JError::raiseWarning(E_NOTICE, JText::sprintf('%s emails not sent', $notsent));
		}
	}

	/**
	* get content item template
	* @param int $contentTemplate
	* @return string content item html (translated with Joomfish if installed)
	*/

	function _getConentTemplate($contentTemplate)
	{
		require_once(COM_FABRIK_BASE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'query.php');
		JModel::addIncludePath(COM_FABRIK_BASE.DS.'components'.DS.'com_content'.DS.'models');
		$articleModel = JModel::getInstance('Article', 'ContentModel');
		$articleModel->setId($contentTemplate);
		// $$$ rob when sending from admin we need to alter $mainframe to be the
		//front end application otherwise com_content errors out trying to create
		//the article
		global $mainframe;
		$origMainframe = $mainframe;
		jimport('joomla.application.application');
		$mainframe = JApplication::getInstance('site', array(), 'J');
		$res = $articleModel->getArticle();
		$mainframe = $origMainframe;
		return $res->introtext . " " . $res->fulltext;
	}

	/**
	* use a php template for advanced email templates, partularly for forms with repeat group data
	*
	* @param string path to template
	* @return string email message
	*/

	function _getPHPTemplateEmail($tmpl, $row, $tableModel)
	{
		// start capturing output into a buffer
		ob_start();
		require($tmpl);
		$message = ob_get_contents();
		ob_end_clean();
		return $message;
	}

	/**
	* template email handling routine, called if email template specified
	* @param string path to template
	* @return string email message
	*/

	function _getTemplateEmail($emailTemplate)
	{
		jimport('joomla.filesystem.file');
		return JFile::read($emailTemplate);
	}

}
?>