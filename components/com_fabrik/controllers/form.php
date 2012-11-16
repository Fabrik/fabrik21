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
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');
//just until joomla uses mootools 1.2
jimport('joomla.html.editor');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'editor.php');
//end mootools 1.2

/**
 * Fabrik From Controller
 *
 * @static
 * @package		Joomla
 * @subpackage	Fabrik
 * @since 1.5
 */

class FabrikControllerForm extends JController
{

	var $_isMambot = false;

	/* @var int  id used from content plugin when caching turned on to ensure correct element rendered)*/
	var $cacheId = 0;

	/**
	 * Display the view
	 */

	function display()
	{
		//menu links use fabriklayout parameters rather than layout
		$flayout = JRequest::getVar('fabriklayout');
		if ($flayout != '') {
			JRequest::setVar('layout', $flayout);
		}
		$document =& JFactory::getDocument();

		$viewName	= JRequest::getVar('view', 'form', 'default', 'cmd');
		$modelName = $viewName;
		if ($viewName == 'emailform') {
			$modelName = 'form';
		}

		if ($viewName == 'details') {
			$viewName = 'form';
			$modelName = 'form';
		}

		$viewType	= $document->getType();

		// Set the default view name from the Request
		$view = &$this->getView($viewName, $viewType);

		// Push a model into the view
		$model	= &$this->getModel($modelName);
		//if errors made when submitting from a J plugin they are stored in the session
		//lets get them back and insert them into the form model
		if (empty($model->_arErrors)) {
			if (array_key_exists('fabrik', $_SESSION) && array_key_exists('mambot_errors', $_SESSION['fabrik'])) {
				$model->_arErrors = JArrayHelper::getValue($_SESSION['fabrik']['mambot_errors'], $view->getId(), array());
				unset($_SESSION['fabrik']['mambot_errors'][$view->getId()]);
			} else {
				$model->_arErrors =  array();
			}
		}
		if (!JError::isError($model) && is_object($model)) {
			$view->setModel($model, true);
		}
		$view->_isMambot = $this->_isMambot;
		// Display the view
		$view->assign('error', $this->getError());
		$user =& JFactory::getUser();

		// $$$ hugh - added disable caching option, and no caching if not logged in (unless we can come up with a unique cacheid for guests)
		// NOTE - can't use IP of client, as could be two users behind same NAT'ing proxy / firewall.
		if ($viewName !== 'table') {
			$listModel = $model->getListModel();
			$listParams = $listModel->getParams();
		}
		else {
			$listParams = $model->getParams();
		}
		if ($user->get('id') != 0 && $listParams->get('list_disable_caching', '0') !== '1') {
			$post = JRequest::get('post');
			$cacheid = serialize(array(JRequest::getURI(), $post, $user->get('id'), get_class($view), 'display', $this->cacheId));
			$cache =& JFactory::getCache('com_fabrik', 'view');

			// Workaround for token caching
			ob_start();
			$cache->get($view, 'display', $cacheid);
			$contents = ob_get_contents();
			ob_end_clean();
			$token			= JUtility::getToken();
			$search 		= '#<input type="hidden" name="[0-9a-f]{32}" value="1" />#';
			$replacement 	= '<input type="hidden" name="'.$token.'" value="1" />';
			echo preg_replace($search, $replacement, $contents);
		}
		else {
			$view->display();
		}
	}

	/**
	 * process the form
	 */

	function processForm()
	{
		@set_time_limit(300);
		$document =& JFactory::getDocument();
		$viewName	= JRequest::getVar('view', 'form', 'default', 'cmd');
		$viewType	= $document->getType();
		$view 		= &$this->getView($viewName, $viewType);
		$model		= &$this->getModel('form');
		$session =& JFactory::getSession();
		$registry	=& $session->get('registry');
		if (!JError::isError($model)) {
			$view->setModel($model, true);
		}

		$model->setId(JRequest::getInt('form_id', 0));
		$model->getPostMethod();

		$this->_isMambot = JRequest::getVar('_isMambot', 0);
		$model->getForm();
		//$model->_rowId = JRequest::getVar('rowid', '');
		$model->getRowId();
		// Check for request forgeries
		$fbConfig =& JComponentHelper::getParams('com_fabrik');
		if ($model->getParams()->get('spoof_check', $fbConfig->get('spoofcheck_on_formsubmission', true)) == true) {
			JRequest::checkToken() or die('Invalid Token');
		}

		if (JRequest::getVar('fabrik_ignorevalidation', 0) != 1) { //put in when saving page of form
			if (!$model->validate()) {
				//if its in a module with ajax or in a package
				if (JRequest::getInt('_packageId') !== 0) {
					$data = array('modified' => $model->_modifiedValidationData);
					//validating entire group when navigating form pages
					$data['errors'] = $model->_arErrors;
					echo json_encode($data);
					return;
				}
				if ($this->_isMambot) {
					//store errors in session
					$_SESSION['fabrik']['mambot_errors'][$model->_id] = $model->_arErrors;
					//JRequest::setVar('fabrik_referrer', JArrayHelper::getValue($_SERVER, 'HTTP_REFERER', ''), 'post');
					// $$$ hugh - testing way of preserving form values after validation fails with form plugin
					// might as well use the 'savepage' mechanism, as it's already there!
					$session->set('com_fabrik.form.'.$model->_id.'.session.on', true);
					$this->savepage();
					$this->makeRedirect('', $model);
				} else {
					// $$$ rob - http://fabrikar.com/forums/showthread.php?t=17962
					// couldn't determine the exact set up that triggered this, but we need to reset the rowid to -1
					// if reshowing the form, otherwise it may not be editable, but rather show as a detailed view
					if (JRequest::getCmd('usekey') !== '') {
						JRequest::setVar('rowid', -1);
					}
					$view->display();
				}
				return;
			}
		}

		//reset errors as validate() now returns ok validations as empty arrays
		$model->_arErrors = array();

		$defaultAction = $model->process();
		//check if any plugin has created a new validation error
		if (!empty($model->_arErrors)) {
			$pluginManager 	=& $model->getPluginManager();
			$pluginManager->runPlugins('onError', $model);
			$view->display();
			return;
		}
		// $$$ rob 31/01/2011
		// Now redirect always occurs even with redirect thx message, $this->setRedirect
		// will look up any redirect url specified in the session by a plugin and use that or
		// fall back to the url defined in $this->makeRedirect()

		//one of the plugins returned false stopping the default redirect
		// action from taking place
		/*if ($defaultAction !== true) {
			//see if a plugin set a custom re
			$url =
			return;
			}*/
		$tableModel	=& $model->getTableModel();
		$tableModel->_table = null;

		//$$$ rob 30/03/2011 if using as a search form don't show record added message
		// $$$ hugh - had a couple of reports where $registry doesn't exist, so put in a sanity test
		if (!is_object($registry) || $registry->getValue('com_fabrik.searchform.fromForm') != $model->_id) {
			$msg = $model->getParams()->get('submit-success-msg', JText::_('RECORD ADDED/UPDATED'));
		} else {
			$msg = '';
		}
		if (JRequest::getInt('_packageId') !== 0) {
			echo json_encode(array('msg' => $msg));
			return;
		}
		if (JRequest::getVar('format') == 'raw') {
			JRequest::setVar('view', 'table');
			$this->display();
			return;
		} else {
			$this->makeRedirect($msg, $model);
		}
	}

	/**
	 * (non-PHPdoc) adds redirect url and message to session
	 * @see JController::setRedirect()
	 */

	function setRedirect($url, $msg = null, $type = 'message')
	{
		$session =& JFactory::getSession();
		$formdata = $session->get('com_fabrik.form.data');
		$context = 'com_fabrik.form.'.$formdata['fabrik'].'.redirect.';
		//if the redirect plug-in has set a url use that in preference to the default url
		$surl = $session->get($context.'url', array($url));
		if (!is_array($surl)) {
			$surl = array($surl);
		}
		if (empty($surl)) {
			$surl[] = $url;
		}
		$smsg = $session->get($context.'msg', array($msg));
		if (!is_array($smsg)) {
			$smsg = array($smsg);
		}
		if (empty($smsg)) {
			$smsg[] = $msg;
		}

		// $$$ hugh - hmmm, array_shift re-orders array keys, which will screw up plugin ordering?
		$url = array_shift($surl);
		// $$$ hugh - something changed in the way the redirect plugin works, so we can't remove
		// the msg any more.

		// $$$ rob Was using array_shift to set $msg, not to really remove it from $smsg
		// without the array_shift the custom message is never attached to the redirect page.
		// use case 'redirct plugin with jump page pointing to a J page and thanks message selected.
		$custommsg = JArrayHelper::getValue($smsg, array_shift(array_keys($smsg)));
		if ($custommsg != '') {
			$msg = $custommsg;
		}
		$app =& JFactory::getApplication();
		$q = $app->getMessageQueue();
		$found = false;
		foreach ($q as $m) {
			//custom message already queued - unset default msg
			if ($m['type'] == 'message' && trim($m['message']) !== '') {
				$found= true;
				break;
			}
		}
		if ($found) {
			$msg = null;
		}
		$session->set($context.'url', $surl);
		$session->set($context.'msg', $smsg);
		$showmsg = array_shift($session->get($context.'showsystemmsg', array(true)));
		$msg = $showmsg == 1 ? $msg : null;
		parent::setRedirect($url, $msg, $type);
	}

	/**
	 * generic function to redirect
	 * @param string redirection message to show
	 */

	function makeRedirect($msg = null, &$model)
	{
		$app =& JFactory::getApplication();
		if (is_null($msg)) {
			$msg = JText::_('RECORD ADDED/UPDATED');
		}
		if ($app->isAdmin()) {
			if (array_key_exists('apply', $model->_formData)) {

				$url = "index.php?option=com_fabrik&c=form&task=form&fabrik=".JRequest::getInt('fabrik')."&tableid=".JRequest::getInt('tableid')."&rowid=".JRequest::getInt('rowid');
			} else {
				$url = "index.php?option=com_fabrik&c=table&task=viewTable&cid[]=".$model->_table->id;
			}
			$this->setRedirect($url, $msg);
		} else {
			if (array_key_exists('apply', $model->_formData)) {
				$url = "index.php?option=com_fabrik&c=form&view=form&fabrik=".JRequest::getInt('fabrik')."&rowid=".JRequest::getInt('rowid')."&tableid=".JRequest::getInt('tableid').'&Itemid='.JRequest::getInt('Itemid');
				// $$$ hugh - Seems to be causing 404's when SEF'ing.  Temporarily commenting it out.
				//$url .= "&fabrik_referrer=".urlencode(JRequest::getVar('fabrik_referrer'));
			} else {
				if ($this->_isMambot) {
					//return to the same page
					//$$$ hugh - this doesn't seem to work if SEF is NOT enabled, just goes to index.php.
					//$url = JArrayHelper::getValue($_SERVER, 'REQUEST_URI', 'index.php');
					$url = JArrayHelper::getvalue($_SERVER, 'HTTP_REFERER', 'index.php');
				} else {
					//return to the page that called the form
					$url = urldecode(JRequest::getVar('fabrik_referrer', 'index.php', 'post'));
				}
				global $Itemid;
				if ($url == '') {
					$url = "index.php?option=com_fabrik&Itemid=$Itemid";
				}
			}
			$config		=& JFactory::getConfig();
			if ($config->getValue('sef')) {
				$url = JRoute::_($url);
			}
			$this->setRedirect($url, $msg);
		}
	}

	/**
	 * validate via ajax
	 *
	 */

	function ajax_validate()
	{
		$model	= &$this->getModel('form');
		$model->setId(JRequest::getInt('form_id', 0));
		$model->getForm();
		$model->_rowId = JRequest::getVar('rowid', '');
		$model->validate();
		$data = array('modified' => $model->_modifiedValidationData);
		//validating entire group when navigating form pages
		$data['errors'] = $model->_arErrors;
		echo json_encode($data);
	}

	/**
	 * save a form's page to the session table
	 */

	function savepage()
	{
		$model		=& $this->getModel('Formsession');
		$formModel =& $this->getModel('Form');
		$formModel->setId(JRequest::getInt('fabrik'));
		$model->savePage($formModel);
	}

	/**
	 * clear down any temp db records or cookies
	 * containing partially filled in form data
	 */

	function removeSession()
	{
		$sessionModel =& $this->getModel('formsession');
		$sessionModel->setFormId(JRequest::getInt('form_id', 0));
		$sessionModel->setRowId(JRequest::getInt('rowid', 0));
		$sessionModel->remove();
		$this->display();
	}

	/**
	 * called via ajax to page through form records
	 */
	function paginate()
	{
		$model =& $this->getModel('Form');
		$model->setId(JRequest::getInt('fabrik'));
		$model->paginateRowId(JRequest::getVar('dir'));
		$this->display();
	}

	/**
	 * delete a record from a form
	 */

	function delete()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$app 				=& JFactory::getApplication();
		$model	= &$this->getModel('table');
		$ids = array(JRequest::getVar('rowid', 0));

		$tableid = JRequest::getInt('tableid');
		$limitstart = JRequest::getVar('limitstart'. $tableid);
		$length = JRequest::getVar('limit' . $tableid);

		$oldtotal = $model->getTotalRecords();
		$model->deleteRows($ids);

		$total = $oldtotal - count($ids);

		$ref = JRequest::getVar('fabrik_referrer', "index.php?option=com_fabrik&view=table&tableid=$tableid", 'post');
		if ($total >= $limitstart) {
			$newlimitstart = $limitstart - $length;
			if ($newlimitstart < 0) {
				$newlimitstart = 0;
			}
			$ref = str_replace("limitstart$tableid=$limitstart", "limitstart$tableid=$newlimitstart", $ref);
			$app =& JFactory::getApplication();
			$context = 'com_fabrik.list'.$tableid.'.';
			$app->setUserState($context.'limitstart', $newlimitstart);
		}
		if (JRequest::getVar('format') == 'raw') {
			JRequest::setVar('view', 'table');

			$this->display();
		} else {
			//@TODO: test this
			$app->redirect($ref, count($ids) . " " . JText::_('RECORDS DELETED'));
		}
	}
}
?>