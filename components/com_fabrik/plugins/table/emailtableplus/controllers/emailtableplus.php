<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'params.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php');

/**
 * Contact Component Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Contact
 * @since 1.5
 */
class FabrikControllerTableemailtableplus extends JController
{
	/*
	 * Fix up broken layout search path.
	 */

	function &getView($name = '', $type = '', $prefix = '', $config = array())
	{
		$config['base_path'] = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'table'.DS.'emailtableplus';
		return parent::getView($name,$type,$prefix,$config);
	}

	/*
	 * Fix up view and model names to normal values.
	 */
	function display()
	{
		$document =& JFactory::getDocument();
		$viewName = 'emailtableplus';
		$viewType = $document->getType();

		$view = &$this->getView($viewName, $viewType);

		$model	= &$this->getModel('emailtableplus');
		if (!JError::isError($model)) {
			$view->setModel($model, true);
		}
		$view->assign('error', $this->getError());

		return $view->display();
	}

	function doemail()
	{
		jimport('joomla.mail.helper');
		jimport('joomla.filesystem.file');
		jimport('joomla.client.helper');

		global $mainframe;

		JClientHelper::setCredentialsFromRequest('ftp');
		$config =& JFactory::getConfig();
		$folder = '';
		$filepaths = array();
		$attached = 0;
		$notattached = 0;

		foreach (JRequest::get('FILES') as $elname=>$file) {
			if($file['name'] != '') {
				if ($folder == '') {
					$folder = $config->getValue('config.tmp_path').DS.uniqid('com_fabrik.plg.table.emailtableplus.');
					if (!JFolder::create($folder)) {
						JError::raiseWarning(E_NOTICE, JText::_('Could not upload files'));
						break;
					}
				}
				$filepath = $folder.DS.JFile::makeSafe($file['name']);
				if (JFile::upload($file['tmp_name'], $filepath)) {
					$filepaths[count($filepaths)]=$filepath;
					$attached++;
				} else {
					JError::raiseWarning(E_NOTICE, JText::sprintf('Could not upload file %s', $file['name']));
				}
			}
		}

		$renderOrder = JRequest::getInt('renderOrder', 0);
		$subject = JMailHelper::cleanSubject(JRequest::getVar('subject'));
		$message = JMailHelper::cleanBody( JRequest::getVar('message'));
		$recordids = explode(',', JRequest::getVar('recordids'));
		$tableModel =& $this->getModel('Table');
		$tableModel->setId(JRequest::getVar('id', 0));
		$formModel =& $tableModel->getForm();
		$this->formModel =& $formModel;

		$params =& $tableModel->getParams();
		$elementModel =& JModel::getInstance('element', 'FabrikModel');
		$field_name = $params->get('emailtableplus_field_name');
		if (is_array($field_name)) {
			$field_name = $field_name[$renderOrder];
		}
		$elementModel->setId($field_name);
		$element =& $elementModel->getElement(true);
		$tonamefield = $elementModel->getFullName(false, true, false);
		$field_email = $params->get('emailtableplus_field_email');
		if (is_array($field_email)) {
			$field_email = $field_email[$renderOrder];
		}
		$elementModel->setId($field_email);
		$element =& $elementModel->getElement(true);
		$tofield = $elementModel->getFullName(false, true, false);
		$fromUser = $params->get('emailtableplus_from_user');
		if (is_array($fromUser)) {
			$fromUser = $fromUser[$renderOrder];
		}

		if ($fromUser[0]) {
			$my =& JFactory::getUser();
			$from = $my->get('email');
			$fromname = $my->get('name');
		} else {
			$config =& JFactory::getConfig();
			$from = $config->getValue('mailfrom');
			$fromname = $config->getValue('fromname');
		}

		$ubcc = $params->get('emailtableplus_use_BCC');
		if (is_array($ubcc)) {
			$ubcc = $ubcc[$renderOrder];
		}
		$useBCC = $ubcc && count($recordids) > 0 && !preg_match('/{[^}]*}/', $subject) && !preg_match('/{[^}]*}/', $message);

		/*
		$include_rowdata = $params->get('emailtableplus_include_rowdata');
		if (is_array($include_rowdata)) {
			$include_rowdata = $include_rowdata[$renderOrder];
		}
		*/

		$sent = 0;
		$notsent = 0;

		if ($useBCC) {
			$bcc = array();
			foreach ($recordids as $id) {
 				$row = $tableModel->getRow($id);

				//$message .= $this->_getTextEmail( JArrayHelper::fromObject($row));

				$to = $row->$tofield;
				$toname = $row->$tonamefield;

				if (JMailHelper::cleanAddress($to) && JMailHelper::isEmailAddress($to)) {
					$tofull = '"' . JMailHelper::cleanLine($toname) . '" <' . $to . '>';
					$bcc[$sent] = $tofull;
					$sent ++;
				} else {
					$notsent ++;
				}
			}
			// $$$ hugh - working round bug in the SMTP mailer method:
			// http://forum.joomla.org/viewtopic.php?f=199&t=530189&p=2190233#p2190233
			// ... which basically means if using the SMTP method, we MUST specify a To addrees,
			// so if mailer is smtp, we'll set the To address to the same as From address
			if ($config->getValue('mailer') == 'smtp') {
				$res = JUtility::sendMail( $from, $fromname, $from, $subject, $message, 0, null, $bcc, $filepaths);
			}
			else {
				$res = JUtility::sendMail( $from, $fromname, null, $subject, $message, 0, null, $bcc, $filepaths);
			}
			if (!$res) {
				$notsent += $sent;
				$sent = 0;
			}
		} else {
			$w = new FabrikWorker();
			foreach ($recordids as $id) {
				$row = $tableModel->getRow($id);
				$to = $row->$tofield;
				$toname = $row->$tonamefield;

				if (JMailHelper::cleanAddress($to) && JMailHelper::isEmailAddress($to)) {
					$tofull = '"' . JMailHelper::cleanLine($toname) . '" <' . $to . '>';
					$thissubject = $w->parseMessageForPlaceholder($subject, $row);
					$thismessage = $w->parseMessageForPlaceholder($message, $row);
					$res = JUtility::sendMail( $from, $fromname, $tofull, $thissubject, $thismessage, 0, null, null, $filepaths);
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

		if ($folder != '') {
			JFolder::delete($folder);
		}

		if ($attached > 0) {
			$mainframe->enqueueMessage( JText::sprintf('%s files attached', $attached));
		}
		$mainframe->enqueueMessage( JText::sprintf('%s emails sent', $sent));
		if ($notsent != 0) {
			JError::raiseWarning(E_NOTICE, JText::sprintf('%s emails not sent', $notsent));
		}
	}

	function _getTextEmail( $data )
	{
		//$data = $this->formModel->_formData;
		$config =& JFactory::getConfig();
		//$arDontEmailThesKeys = $this->getDontEmailKeys();
		$message = "";
		$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$groupModels =& $this->formModel->getGroupsHiarachy();
		foreach ($groupModels as $groupModel) {
			$elementModels =& $groupModel->getPublishedElements();
			foreach ($elementModels as $elementModel) {
				$element = $elementModel->getElement();
				//$element->label = strip_tags($element->label);
				if (!array_key_exists($element->name, $data)) {
					$elName = $elementModel->getFullName();
				} else {
					$elName = $element->name;
				}
				if (!array_key_exists($elName, $data)) {
					continue;
				}
				$key = $elName;
				$elementModel->defaults = array();
				//if (!in_array($key, $arDontEmailThesKeys)) {
					//$val = $elementModel->getDefaultValue($data);
					$val = $data[$key];
					$val = $elementModel->getEmailValue($val, $data, 0);
					//$val = $data[$key];
					if (is_array($val)) {
						$val = implode("<br />", $val);
					}
					//$val = FabrikString::rtrimword($val, "<br />");
					//$val = stripslashes($val);
					$message .= $element->label . ": " . $val . "\r\n";
				//}
			}
		}
		$message =  stripslashes($message);
		return $message;
	}
}
?>