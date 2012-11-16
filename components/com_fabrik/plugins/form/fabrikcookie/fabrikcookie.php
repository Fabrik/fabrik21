<?php
/**
 * Form cookie plugin - stores form data in cookie
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

jimport('joomla.utilities.simplecrypt');
jimport('joomla.utilities.utility');

class FabrikModelfabrikCookie extends FabrikModelFormPlugin {

	/**
	 * @var array of files to attach to email
	 */
	var $_counter = null;

	/** @var object simply crypt **/
	var $_crypt = null;

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}


	function getRowKey($formModel)
	{
		return 'rowid'.$formModel->_id;
	}

	function onSetRowId($params, &$formModel )
	{
		/*if ($formModel->_rowId == 0) {
			$cookierowid = $this->getCookie( $this->getRowKey($formModel));
			if ($cookierowid != '') {
				$formModel->_rowId = $cookierowid;
			}
		}*/
	}

	/**
	 * process the plugin, called when form is submitted
	 *
	 * @param object $params
	 * @param object form model
	 * @returns bol
	 */

	function onAfterProcess( $params, &$formModel )
	{
	  
		$user						= &JFactory::getUser();
		if ($user->get('id') != 0) {
		  return;
		}
		$config					=& JFactory::getConfig();
		$groups =& $formModel->getGroups();
		foreach ($groups as $group) {
			if ($group->getGroup()->id == $params->get('group')) {
				$elements =& $group->getMyElements();
				foreach ($elements as $element) {
					unset($element->defaults[0]);
					$v = $element->getValue($formModel->_formDataWithTableName);
					$name = $element->getFullName(false, true, false);
					if(is_array($v)) {
						$v= $v[0];
					}
					$this->setCookie( $name, $v);
					//HACK for ideenbus
					$fields = array('ide_values___', 'ide_people___', 'ide_questions___', 'ide_idea___');
					foreach ($fields as $field) {
						$this->setCookie( $field . $element->getElement()->name, $v);
					}
				}
			}
		}
		
		$rowid = JRequest::getVar('rowid');
		$this->setCookie( $this->getRowKey($formModel), $rowid);
		return true;
	}

	function setCookie( $cookieKey, $val )
	{
	  $user						= &JFactory::getUser();
		
	  if ($user->get('id') == 63) {
		  echo "$cookieKey, $val <br>";
		 // return;
		}
		
		$crypt =& $this->getCrypt();

		//$rcookie = $crypt->encrypt(serialize($val));
		//$rcookie = (serialize($val));
		$rcookie = (($val));
		$lifetime = time() + 365*24*60*60;
		setcookie( $cookieKey, $rcookie, $lifetime, '/');
	}

	function getCookie( $cookieKey )
	{
		$crypt =& $this->getCrypt();
		$val = trim(JRequest::getVar($cookieKey, '', 'cookie'));
		//return unserialize($crypt->decrypt($val));
		//return unserialize(($val));
		return $val;
	}

	function getCrypt()
	{
		if (!isset($this->_crypt)) {
			//Create the encryption key, apply extra hardening using the user agent string
			$key = JUtility::getHash( @$_SERVER['HTTP_USER_AGENT']);
			$this->_crypt = new JSimpleCrypt( $key);
		}
		return $this->_crypt;
	}

	/**
	 * augments the element getdefault value method
	 *
	 * @param object $params form params
	 * @param object $formModel
	 * @param array $args additional arguements - in this case the first arg is the current element model
	 */
	
	function onGetElementDefault($params, &$formModel, $args )
	{
		
		$element 		=& $args[0];
		$default 		= $element->getElement()->default;
		//hack for joined groups that have the same element
		$name 			= $element->getFullName(false);
		$cookieval 	= JRequest::getVar($name, '', 'cookie');
		
		//if ($cookieval != '' && trim($cookieval) == trim($default)) {
		if ($cookieval != '') {
			
			//$crypt = $this->getCrypt();
			//$default = unserialize(($cookieval));
			$default = $cookieval;
			$element->getElement()->default = $default;
		}
	}

}
?>
