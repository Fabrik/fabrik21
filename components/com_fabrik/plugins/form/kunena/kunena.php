<?php

/**
 * Creates a thread in kunena forum
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();


//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-form.php');

class FabrikModelkunena extends FabrikModelFormPlugin {

	/**
	 * Constructor
	 */
	var $_counter = null;

	var $vb_forum_field = '';
	var $vb_path = '';
	var $vb_globals = '';

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * process the plugin, called when form is submitted
	 *
	 * @param object $params
	 * @param object form
	 */

	function onBeforeStore(&$params, &$formModel)
	{
		jimport('joomla.filesystem.file');
		$files[] = COM_FABRIK_BASE.DS.'components'.DS.'com_kunena'.DS.'class.kunena.php';
		$define = COM_FABRIK_BASE.DS.'components'.DS.'com_kunena'.DS.'lib'.DS.'kunena.defines.php';
		$files[]  = COM_FABRIK_BASE.DS.'components'.DS.'com_kunena'.DS.'lib'.DS.'kunena.defines.php';
		$files[]  = COM_FABRIK_BASE.DS.'components'.DS.'com_kunena'.DS.'lib'.DS.'kunena.link.class.php';
		$files[]  = COM_FABRIK_BASE.DS.'components'.DS.'com_kunena'.DS.'lib'.DS.'kunena.link.class.php';
		if (!JFile::exists($define)) {
			JError::raiseError(500, 'could not find the Kunena component');
			return false;
		}
		require_once($define);
		foreach ($files as $file) {
			if (!JFile::exists($file)) {
				JError::raiseError(500, 'could not find the Kunena file: '.$file);
				return false;
			}
			require_once($file);
		}
		$postfile = KUNENA_PATH_FUNCS . DS . 'post.php';

		$w = new FabrikWorker();
		if (!JFile::exists($postfile)) {
			JError::raiseError(500,  "cant find post file");
			return false;
		}

		//new
		include_once (COM_FABRIK_BASE.DS.'components'.DS.'com_kunena' . DS . 'lib' . DS . 'kunena.timeformat.class.php');

		if (file_exists ( KUNENA_ABSTMPLTPATH . '/initialize.php' )) {
			require_once ( KUNENA_ABSTMPLTPATH . '/initialize.php' );
		} else {
			require_once (KPATH_SITE . '/template/default/initialize.php');
		}
		// Kunena Current Template Icons Pack
		if (file_exists ( KUNENA_ABSTMPLTPATH . '/icons.php' )) {
			include (KUNENA_ABSTMPLTPATH . '/icons.php');
		} else {
			include (KUNENA_PATH_TEMPLATE_DEFAULT . DS . 'icons.php');
		}
		$kunena_session = KunenaFactory::getSession(true);

		//end new
		$catid = $params->get('kunena_category', 0);
		$msg = $w->parseMessageForPlaceHolder($params->get('kunena_content'), $formModel->_fullFormData);
		$subject = $params->get('kunena_title');
		$subject = $w->parseMessageForPlaceHolder($subject, $formModel->_fullFormData);
		include ($postfile);
		$page = new CKunenaPost();
		require_once (KUNENA_PATH_LIB . DS . 'kunena.posting.class.php');
		$message = new CKunenaPosting();

		$user =& JFactory::getUser();
		$fields['name'] = $user->guest ? 'Annonymous' : $user->get('name');
		$fields['email'] = $user->guest ? 'noreply@annonymous.com' : $user->get('email');
		$fields['subject'] = $subject;
		$fields['message'] = $msg;
		$fields['topic_emoticon'] = JRequest::getInt('topic_emoticon', null);

		$options ['attachments'] = 1;
		$options ['anonymous'] = 0;
		$lang =& JFactory::getLanguage();
		$lang->load('com_kunena');

		$success = $message->post($catid, $fields, $options);
		// Handle errors
		if (!$success) {
			$errors = $message->getErrors ();
			foreach ($errors as $field => $error) {
				JError::raiseNotice(500, "$field: $error");
				return false;
			}
		} else {
			$success = $message->save();
			// Handle errors
			if (! $success) {
				$errors = $message->getErrors();
				foreach ($errors as $field => $error) {
					JError::raiseNotice(500, "$field: $error");
				return false;
				}
			}
		}
	}

}
?>