<?php
/**
 * Form email plugin
 * @package Joomla
 * @subpackage Fabrik
 * @author Hugh Messenger
 * @copyright (C) Hugh Messenger
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-form.php');

class FabrikModelfabrikftp extends FabrikModelFormPlugin {

	/**
	 * @var array of files to attach to email
	 */
	var $_counter = null;

	var $_aAttachments = array();

	var $_dontEmailKeys = null;
	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * MOVED TO PLUGIN.PHP SHOULDPROCESS()
	 * determines if a condition has been set and decides if condition is matched
	 *
	 * @param object $params
	 * @return bol true if you sould send the email, false stops sending of eaml
	 */

	/*function shouldSend(&$params)
	{
	}*/

	/**
	 * process the plugin, called when form is submitted
	 *
	 * @param object $params
	 * @param object form model
	 * @returns bol
	 */

	function onAfterProcess($params, &$formModel )
	{
		jimport('joomla.mail.helper');

		$user						= &JFactory::getUser();
		$config					=& JFactory::getConfig();
		$db 						=& JFactory::getDBO();

		$this->formModel =& $formModel;
		$formParams			= $formModel->getParams();
		$ftpTemplate	= JPath::clean(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'plugins'.DS.'form'.DS.'fabrikftp'.DS.'tmpl'.DS . $params->get('ftp_template', ''));

		$this->data 		= array_merge($formModel->_formData, $this->getEmailData());

		if (!$this->shouldProcess('ftp_conditon')) {
			return;
		}

		$contentTemplate = $params->get('ftp_template_content');
		if ($contentTemplate != '') {
			$content = $this->_getConentTemplate($contentTemplate);
		} else {
			$content = '';
		}

		if (JFile::exists($ftpTemplate)) {
			if (JFile::getExt($ftpTemplate) == 'php') {
				$message = $this->_getPHPTemplateFtp($ftpTemplate);
			} else {
				$message = $this->_getTemplateFtp($ftpTemplate);
			}
			$message = str_replace('{content}', $content, $message);
		} else {
			if ($contentTemplate != '') {
				$message = $content;
			} else {
				$message = $this->_getTextFtp();
			}
		}


		$cc 		= null;
		$bcc 		= null;
		$w = new FabrikWorker();
		// $$$ hugh - test stripslashes(), should be safe enough.
		$message 	= stripslashes($message);

		$editURL = COM_FABRIK_LIVESITE . "index.php?option=com_fabrik&amp;view=form&amp;fabrik=".$formModel->get('id')."&amp;rowid=".JRequest::getVar('rowid');
		$viewURL = COM_FABRIK_LIVESITE . "index.php?option=com_fabrik&amp;view=details&amp;fabrik=".$formModel->get('id')."&amp;rowid=".JRequest::getVar('rowid');
		$editlink = "<a href=\"$editURL\">" . JText::_('EDIT') . "</a>";
		$viewlink = "<a href=\"$viewURL\">" . JText::_('VIEW') . "</a>";
		$message = str_replace('{fabrik_editlink}', $editlink, $message);
		$message = str_replace('{fabrik_viewlink}', $viewlink, $message);
		$message = str_replace('{fabrik_editurl}', $editURL, $message);
		$message = str_replace('{fabrik_viewurl}', $viewURL, $message);


		$ftp_filename = $params->get('ftp_filename', '');
		$ftp_filename = $w->parseMessageForPlaceholder($ftp_filename, $this->data, false);
		$ftp_eval_filename = (int)$params->get('ftp_eval_filename', '0');
		if ($ftp_eval_filename) {
			$ftp_filename = @eval($ftp_filename);
			FabrikWorker::logEval($email_to_eval, 'Caught exception on eval in ftp filename eval : %s');
		}
		if (empty($ftp_filename)) {
			JError::raiseNotice(500, JText::sprintf('PLG_FTP_NO_FILENAME', $email));
		}

		$ftp_host = $w->parseMessageForPlaceholder($params->get('ftp_host', ''), $this->data, false);
		$ftp_port = $w->parseMessageForPlaceholder($params->get('ftp_port', '21'), $this->data, false);
		$ftp_chdir = $w->parseMessageForPlaceholder($params->get('ftp_chdir', ''), $this->data, false);
		$ftp_user = $w->parseMessageForPlaceholder($params->get('ftp_user', ''), $this->data, false);
		$ftp_password = $w->parseMessageForPlaceholder($params->get('ftp_password', ''), $this->data, false);

		$config = & JFactory::getConfig();
		$tmp_dir = rtrim($config->getValue('config.tmp_path'), DS);
		if (empty($tmp_dir) || !JFolder::exists($tmp_dir)) {
			JError::raiseError(500, 'PLG_FORM_FTP_NO_JOOMLA_TEMP_DIR');
			return false;
		}
		$tmp_file = $tmp_dir . DS . 'fabrik_ftp_' . md5( uniqid() );
		$message = $w->parseMessageForPlaceholder($message, $this->data, true, false);
		if (JFile::write($tmp_file, $message)) {
			$conn_id = ftp_connect($ftp_host, $ftp_port);
			if ($conn_id) {
				if (@ftp_login($conn_id, $ftp_user, $ftp_password)) {
					if (!empty($ftp_chdir)) {
						if (!ftp_chdir($conn_id, $ftp_chdir)) {
							JError::raiseNotice(500, JText::_('PLG_FORM_FTP_COULD_NOT_CHDIR'));
							JFile::delete($tmp_file);
							return false;
						}
					}
					if (!ftp_put($conn_id, $ftp_filename, $tmp_file, FTP_ASCII)) {
						JError::raiseNotice(500, JText::_('PLG_FORM_FTP_COULD_NOT_SEND_FILE'));
						JFile::delete($tmp_file);
						return false;
					}
				}
				else {
					JError::raiseNotice(500, JText::_('PLG_FORM_FTP_COULD_NOT_LOGIN'));
					JFile::delete($tmp_file);
					return false;
				}
			}
			else {
				JError::raiseError(500, 'PLG_FORM_FTP_COULD_NOT_CONNECT');
				JFile::delete($tmp_file);
				return false;
			}
		}
		else {
			JError::raiseError(500, 'PLG_FORM_FTP_COULD_NOT_WRITE_TEMP_FILE');
			JFile::delete($tmp_file);
			return false;
		}

		JFile::delete($tmp_file);
		return true;
	}

	/**
	 * use a php template for advanced email templates, partularly for forms with repeat group data
	 *
	 * @param bol if file uploads have been found
	 * @param string path to template
	 * @return string email message
	 */

	function _getPHPTemplateFtp($tmpl)
	{
		// start capturing output into a buffer
		ob_start();
		require($tmpl);
		$message = ob_get_contents();
		ob_end_clean();
		return $message;
	}


	/**
	 * get an array of keys we dont want to email to the user
	 *
	 * @return array
	 */

	function getDontEmailKeys()
	{
		if (is_null($this->_dontEmailKeys)) {
			$this->_dontEmailKeys = array();
			foreach ($_FILES as $key => $file) {
				$this->_dontEmailKeys[] = $key;
			}
		}
		return $this->_dontEmailKeys;
	}

	/**
	 * template email handling routine, called if email template specified
	 * @param string path to template
	 * @return string email message
	 */

	function _getTemplateFtp($ftpTemplate)
	{
		jimport('joomla.filesystem.file');
		return JFile::read($ftpTemplate);
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
	 * default template handling routine, called if no template specified
	 * @return string email message
	 */

	function _getTextFtp()
	{
		$data =& $this->getEmailData();
		$config =& JFactory::getConfig();
		$ignore = $this->getDontEmailKeys();
		$message = "";
		$pluginManager =& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$groupModels =& $this->formModel->getGroupsHiarachy();
		foreach ($groupModels as &$groupModel) {
			$elementModels = $groupModel->getPublishedElements();
			foreach ($elementModels as &$elementModel) {
				$element = $elementModel->getElement();
				// @TODO - how about adding a 'renderEmail()' method to element model, so specific element types
				// can render themselves?
				$key = (!array_key_exists($element->name, $data)) ? $elementModel->getFullName(false, true, false ) : $element->name;
				if (!in_array($key, $ignore)) {
					$val = '';
					if (is_array($data[$key])) {
						//repeat group data
						foreach ($data[$key] as $k => $v) {
							if (is_array($v)) {
								$val = implode(", ", $v);
							}
							$val .= count($data[$key]) == 1 ? ": $v<br />" : $k++ .": $v<br />";
						}
					} else {
						$val = $data[$key];
					}
					$val = FabrikString::rtrimword( $val, "<br />");
					$val = stripslashes($val);


					// set $val to default value if empty
					if($val == '')
					$val = " - ";

					// don't add a second ":"
					$label = trim(strip_tags($element->label));
					$message .= $label;
					if (strlen($label) != 0 && JString::strpos($label, ':', JString::strlen($label)-1) === false) {
						$message .=":";
					}
					$message .= "<br />" . $val . "<br /><br />";
				}
			}
		}
		$message = JText::_('Email from') . ' ' . $config->getValue('sitename') . "<br />".JText::_('Message').":"
		."<br />===================================<br />".
		"<br />" . stripslashes($message);
		return $message;
	}

}
?>