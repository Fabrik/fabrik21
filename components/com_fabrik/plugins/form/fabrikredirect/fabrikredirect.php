<?php

/**
* Redirect the user when the form is submitted
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

class FabrikModelFabrikRedirect extends FabrikModelFormPlugin {

	/**
	* Constructor
	*/

	var $_result = true;

	var $_counter = null;

	function __construct()
	{
		parent::__construct();
	}

 	/**
 	 * process the plugin, called afer form is submitted
 	 *
 	 * !!!! NOTE this is the ONLY form plugin that should use onLastProcess
 	 *
 	 * @param object $params (with the current active plugin values in them)
 	 * @param object form
 	 */

 	function onLastProcess($params, &$formModel)
	{
		$session =& JFactory::getSession();
		$context = 'com_fabrik.form.'.$formModel->_id.'.redirect.';

		//get existing session params
		$surl = (array)$session->get($context.'url', array());
 		$stitle = (array)$session->get($context.'title', array());
 		$smsg = (array)$session->get($context.'msg', array());
 		$sshowsystemmsg = (array)$session->get($context.'showsystemmsg', array());

 		$app =& JFactory::getApplication();
 		$this->formModel =& $formModel;
 		$w = new FabrikWorker();
 		$this->_data = new stdClass();

 		$this->_data->append_jump_url = $params->get('append_jump_url');
 		$this->_data->save_in_session = $params->get('save_insession');
 		$form =& $formModel->getForm();

 		// $$$ hugh - swapped args round, otherwise _formData takes precedence,
 		// which makes getEmailData() pretty much redundant.
 		// NOTE - this may cause some issues with existing usage for things like checkboxes
 		//$this->data 		= array_merge($this->getEmailData(), $formModel->_formData);
 		$this->data 		= array_merge($formModel->_formData, $this->getEmailData());
 		$this->_data->jump_page 			= $w->parseMessageForPlaceHolder($params->get('jump_page'), $this->data);
  		// $$$ hugh - added helper func to pull a URL apart and urlencode any QS args.
 		// Fixes issue where people use &foo={table___element} in their jump URL's and the data
 		// has special characters in it.
 		$this->_data->jump_page = FabrikString::encodeurl($this->_data->jump_page);
 		$this->_data->thanks_message 	= $w->parseMessageForPlaceHolder($params->get('thanks_message'), $this->data);
 		if (!$this->shouldRedirect($params)) {
 			//clear any sessoin redirects
 			unset($surl[$this->renderOrder]);
 			unset($stitle[$this->renderOrder]);
 			unset($smsg[$this->renderOrder]);
			unset($sshowsystemmsg[$this->renderOrder]);

 			$session->set($context.'url', $surl);
 			$session->set($context.'title', $stitle);
 			$session->set($context.'msg', $smsg);
 			$session->set($context.'showsystemmsg', $sshowsystemmsg);
			return true;
		}
 		$this->_storeInSession($formModel);
		$sshowsystemmsg[$this->renderOrder] = $this->_data->thanks_message == '' ? false : true;
 		$session->set($context.'showsystemmsg', $sshowsystemmsg);
 		if ($this->_data->jump_page != '') {
 			$this->_data->jump_page = $this->_buildJumpPage($formModel);
			if (JRequest::getVar('fabrik_postMethod', '') != 'ajax' || $params->get('redirect_inajax') == 1) {
				//dont redirect if form is in part of a package (or a module with 'use ajax' option turned on)

				$surl[$this->renderOrder] = $this->_data->jump_page;
				$session->set($context.'url', $surl);
			}
		} else {
			$sshowsystemmsg[$this->renderOrder] = false;
			$session->set($context.'showsystemmsg', $sshowsystemmsg);

			$stitle[$this->renderOrder] = $form->label;
			$session->set($context.'title', $stitle);

			$surl[$this->renderOrder] = 'index.php?option=com_fabrik&controller=plugin&g=form&plugin=fabrikredirect&method=displayThanks&task=pluginAjax';
			$session->set($context.'url', $surl);
		}

		$smsg[$this->renderOrder] = $this->_data->thanks_message;
		$session->set($context.'msg', $smsg);
		return true;
	}

	/**
	 * once the form has been sucessfully completed, and if no jump page is
	 * specified then show the thanks message
	 * @param string thanks message title @depreicated - set in session in onLastProcess
	 * @param string thanks message string @depreicated - set in session in onLastProcess
	 */

	function displayThanks($title = '', $message = '')
	{
		$session =& JFactory::getSession();
		$formdata = $session->get('com_fabrik.form.data');
		$context = 'com_fabrik.form.'.$formdata['fabrik'].'.redirect.';
		$title = (array)$session->get($context.'title', $title);
		$title = array_shift($title);
		$message = $session->get($context.'msg', $message);
		//require_once(JPATH_SITE . "/includes/HTML_toolbar.php");
		// $$$ hugh - if using AJAX, echo thanks in JSON, handled in form.js doSubmit()
		// FIXME - prolly need to look at form.js and check for $message being an array
		if (JRequest::getVar('fabrik_postMethod', '') == 'ajax') {
				$data['thanks']['title'] = $title;
				$data['thanks']['message'] = $message;
			    echo json_encode($data);
		} else {
			// $$$ hugh - it's an array, need to bust it up.
			if (is_array($message)) {
				$message = implode('<br />', $message);
			}
		?>
		<div class="componentheading"><?php echo $title ?></div>
		<p><?php echo $message ?></p>
		<?php
		}
	}

	/**
	 * alter the returned plugin manager's result
	 *
	 * @param string $method
	 * @param object form model
	 * @return bol
	 */


	function customProcessResult($method, &$formModel)
	{
		// if we are applying the form don't run redirect
		if (is_array($formModel->_formData) && array_key_exists('apply', $formModel->_formData)) {
			return true;
		}
		if ($method != 'onLastProcess') {
			return true;
		}
		if (JRequest::getVar('fabrik_postMethod', '') != 'ajax') {
			//return false to stop the default redirect occurring
			return false;
		} else {
			if (!empty($this->_data->jump_page)) {
				//ajax form submit load redirect page in mocha window
				if (strstr($this->_data->jump_page, "?")) {
					$this->_data->jump_page .= "&tmpl=component";
				} else {
					$this->_data->jump_page .= "?tmpl=component";
				}
				echo "oPackage.openRedirectInMocha('".$this->_data->jump_page."')";
				return false;
			}
			else {
				return true;
			}
		}
	}

	/**
	 * takes the forms data and merges it with the jump page
	 * @param object form
	 * @return new jump page
	 */

	function _buildJumpPage(&$formModel)
	{
		///$$$rob - I've tested the issue reported in rev 1268
		//where Hugh added a force call to getTable() in elementModel->getFullName() to stop the wrong table name
		//being appended to the element name. But I can't reproduce the issue (Testing locally php 5.2.6 on my Gigs table)
		// if there is still an issue it would make a lot more sense to manually set the element's table model rather than calling
		//force in the getFullName() code - as doing so increases the table query count by a magnitude of 2
		$jumpPage = $this->_data->jump_page;
		$reserved = array('format','view','layout','task');
		$queryvars = array();
		if ($this->_data->append_jump_url == '1') {
			$groups =& $formModel->getGroupsHiarachy();
			foreach ($groups as $group) {
				$elements =& $group->getPublishedElements();
				if ($group->isJoin()) {
					$tmpData = $formModel->_fullFormData['join'][$group->getGroup()->join_id];
				} else {
					$tmpData = $formModel->_fullFormData;
				}
				foreach ($elements as $elementModel) {

					$name = $elementModel->getFullName(false, true, false);
					if (array_key_exists($name, $tmpData)) {
						$this->_appendQS($queryvars, $name, $tmpData[$name]);
					} else {
						$element =& $elementModel->getElement();
						if (array_key_exists($element->name, $tmpData)) {
							$this->_appendQS($queryvars, $element->name, $tmpData[$element->name]);
						}
					}
				}
			}
		}

		// $$$ rob removed url comparison as this stopped form js vars being appeneded to none J site urls (e.g. http://google.com)
		//if ((!strstr($jumpPage, COM_FABRIK_LIVESITE) && strstr($jumpPage, 'http')) || empty($queryvars)) {
		if (empty($queryvars)) {
			return $jumpPage;
		}
		if (!strstr($jumpPage, "?")) {
			$jumpPage .= "?";
		}
		else {
			$jumpPage .= "&";
		}
		$jumpPage .= implode('&', $queryvars);
		return $jumpPage;
	}

	function _appendQS(&$queryvars, $key, $val)
	{
		if (is_array($val)) {
			foreach ($val as $v) {
				$this->_appendQS($queryvars, "{$key}[value]", $v);
			}
		} else {
			$val = urlencode(stripslashes($val));
			$queryvars[] = "$key=$val";
		}
	}

	/**
	 * date is stored in session com_fabrik.searchform.form'.$formModel->_id.'.filters
	 * tablefilters looks up the com_fabrik.searchform.fromForm session var to then be able to pick up
	 * the search form data
	 * once its got it it unsets com_fabrik.searchform.fromForm so that the search values are not reused
	 * (they are however stored in the session so behave like normal filters afterwards)
	 * If the tablefilter does find the com_fabrik.searchform.fromForm var it won't use any session filters
	 *
	 * @param $formModel
	 * @return unknown_type
	 */
	function _storeInSession(&$formModel)
	{
		$app 			=& JFactory::getApplication();
		$store 		= array();
		if ($this->_data->save_in_session == '1') {
			//@TODO - rob, you need to look at this, I really only put this in as a band-aid.
			// $$$ hugh - we need to guesstimate the 'type', otherwise when the session data is processed
			// on table load as filters, everything will default to 'field', which borks up if (say) it's
			// really a dropdown
			/*
			foreach ($formModel->_formData as $key => $value) {
				if ($formModel->hasElement($key)) {
					//$value = urlencode(stripslashes($value));
					$store[$formModel->_id]["$key"] = array('type'=>'', 'value'=>$value, 'match'=>false);
				}
			}
			*/

			$groups =& $formModel->getGroupsHiarachy();
			foreach ($groups as $group) {
				$elements =& $group->getPublishedElements();
				foreach ($elements as $element) {

					if ($group->isJoin()) {
						$tmpData = $formModel->_fullFormData['join'][$group->getGroup()->join_id];
					} else {
						$tmpData = $formModel->_fullFormData;
					}
					if ($element->getElement()->name == 'fabrik_table_filter_all') {
						continue;
					}
					$name =  $element->getFullName(false);
					if (array_key_exists($name, $tmpData)) {
						$value = $tmpData[$name];

						$match = $element->getElement()->filter_exact_match;
						if (!is_array($value)) {
							$value = array($value);
						}

							$c = 0;
						foreach ($value as $v) {
						if (count($value) == 1 || $c == 0) {
								$join = 'AND';
								$grouped = false;
							}else{
								$join = 'OR';
								$grouped = true;
							}
							if ($v != '') {
								$store['join'][] = $join;
								$store['key'][] = FabrikString::safeColName($name);
								$store['condition'][] = '=';
								$store['search_type'][] =  'search';
								$store['access'][] = 0;
								$store['grouped_to_previous'][] = $grouped;
								$store['eval'][] = FABRIKFILTER_TEXT;
								$store['required'][] = false;
								$store['value'][] = $v;
								$store['full_words_only'][] = false;
								$store['match'][] = $match;
								$store['hidden'][] = 0;
								$store['elementid'][] = $element->getElement()->id;
							}

							$c ++;
						}
					}
				}
			}

			$session =& JFactory::getSession();
			$registry	=& $session->get('registry');

			//clear registry search form entries
			$key = 'com_fabrik.searchform';
			//test for this err - http://fabrikar.com/forums/showthread.php?t=14149&page=3
			if (is_a($registry, 'JRegistry')) {
				$registry->setValue($key, null);
			}
			//$session->destroy();

			$tableModel =& $formModel->getTableModel();
			//check for special fabrik_table_filter_all element!
			$searchAll = JRequest::getVar($tableModel->getTable()->db_table_name .'___fabrik_table_filter_all');

			$registry->setValue('com_fabrik.searchform.form'.$formModel->_id.'.searchall', $searchAll);

			$registry->setValue('com_fabrik.searchform.form'.$formModel->_id.'.filters', $store);

			$registry->setValue('com_fabrik.searchform.fromForm', $formModel->_id);

		}
	}

	/**
	 * determines if a condition has been set and decides if condition is matched
	 *
	 * @param object $params
	 * @return bol true if you should redirect, false ignores redirect
	 */

	function shouldRedirect(&$params)
	{
		// if we are applying the form dont run redirect
		if (array_key_exists('apply', $this->formModel->_formData)) {
			return false;
		}
		return $this->shouldProcess('redirect_conditon');
	}
}
?>