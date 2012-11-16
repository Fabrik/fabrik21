<?php
/**
 * Plugin element to captcha
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');
require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'plugins'.DS.'element'.DS.'fabrikcaptcha'.DS.'recaptcha-php-1.11'.DS.'recaptchalib.php');

class FabrikModelFabrikcaptcha extends FabrikModelElement {

	var $_font = 'monofont.ttf';

	var $_pluginName = 'captcha';
	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * can be overwritten in plugin class
	 * determines if the element can contain data used in sending receipts, e.g. fabrikfield returns true
	 */

	function isReceiptElement()
	{
		return true;
	}

	protected function _generateCode($characters)
	{
		/* list all possible characters, similar looking characters and vowels have been removed */
		$possible = '23456789bcdfghjkmnpqrstvwxyz';
		$code = '';
		$i = 0;
		while ($i < $characters) {
			$code .= substr($possible, mt_rand(0, strlen($possible)-1), 1);
			$i++;
		}
		return $code;
	}

	function getLabel($repeatCounter)
	{
		$user =& JFactory::getUser();
		$params =& $this->getParams();
		if ($user->id != 0) {
			if ($params->get('captcha-showloggedin', 0) == 0) {
				return '';
			}
		}
		return parent::getLabel($repeatCounter);
	}

	function isHidden()
	{
		$user =& JFactory::getUser();
		$params =& $this->getParams();
		if ($user->id != 0) {
			if ($params->get('captcha-showloggedin', 0) == 0) {
				return true;
			}
		}
		return parent::isHidden();
	}

	/**
	 * check user can view the read only element & view in table view
	 * If user logged in return false
	 * @return bol can view or not
	 */

	function canView()
	{
		$user =& JFactory::getUser();
		$params =& $this->getParams();
		if ($user->id != 0) {
			if ($params->get('captcha-showloggedin', 0) == 0) {
				return false;
			}
		}
		return parent::canView();
	}

	/**
	 * check user can view the active element
	 * If user logged in return false
	 * @return bol can view or not
	 */

	function canUse()
	{
		$user =& JFactory::getUser();
		$params =& $this->getParams();
		if ($user->id != 0) {
			if ($params->get('captcha-showloggedin', 0) == 0) {
				return false;
			}
		}
		return parent::canUse();
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0) {
		$name 		= $this->getHTMLName($repeatCounter);
		$id				= $this->getHTMLId($repeatCounter);
		$element 	= $this->getElement();
		$params 	= $this->getParams();
		$user =& JFactory::getUser();

		if ($params->get('captcha-method') == 'recaptcha') {

			//$publickey = $params->get('recaptcha_publickey');

			//$$$tom added lang & theme options
			//$theme = $params->get('recaptcha_theme', 'red');
			//$lang = strtolower($params->get('recaptcha_lang', 'en'));
			//$error = null;
			if ($user->id != 0 && $params->get('captcha-showloggedin', 0) == false) {
				return "<input class=\"inputbox text\" type=\"hidden\" name=\"$name\" id=\"$id\" value=\"\" />\n";
			} else {
				//$str = recaptcha_get_html($publickey, $error, false);
				$str = '<div id="'.$id.'"></div>';
				return $str;
			}
		} else {
			$size 		= $element->width;
			$height = $params->get('captcha-height', 40);
			$width = $params->get('captcha-width', 40);
			$characters = $params->get('captcha-chars', 6);
			$code = $this->_generateCode($characters);

			// $$$ hugh - code that generates image now in image.php

			$_SESSION['security_code'] = $code;

			$str = "<div class='fabrikSubElementContainer'>";
			// $$$ hugh - changed from static image path to using simple image.php script, to get round IE caching images
			$str .= "<img src='" .COM_FABRIK_LIVESITE . "components/com_fabrik/plugins/element/fabrikcaptcha/image.php?width=$width&amp;height=$height&amp;font=" . $this->_font . "&amp;foo=" . rand() . "' alt='" . JText::_('security image') . "' />";
			$str .= "<br />";

			$maxlength  = $params->get('maxlength');
			if ($maxlength == "0" or $maxlength == "") {
				$maxlength = $size;
			}

			$value = $this->getValue($data, $repeatCounter);
			$type = ($params->get('password') == "1" ) ? "password" : "text";
			if (isset($this->_elementError) && $this->_elementError != '') {
				$type .= " elementErrorHighlight";
			}
			if ($element->hidden == '1') {
				$type = "hidden";
			}
			$sizeInfo =  " size=\"$size\" maxlength=\"$maxlength\"";
			if (!$this->_editable) {
				if ($element->hidden == '1') {
					return "<!--" . stripslashes($value) . "-->";
				} else {
					return stripslashes($value);
				}
			}
			/* no need to eval here as its done before hand i think ! */
			if ($element->eval == "1" and !isset ( $data[$name])) {
				$str .= "<input class=\"inputbox $type\" type=\"$type\" name=\"$name\" id=\"$id\" $sizeInfo value=\"\" />\n";
			} else {
				$value = stripslashes($value);
				$str .= "<input class=\"inputbox $type\" type=\"$type\" name=\"$name\" $sizeInfo id=\"$id\" value=\"\" />\n";
			}
			$str .= "</div>";
			return $str;
		}
	}

	/**
	 * can be overwritten in adddon class
	 *
	 * checks the posted form data against elements INTERNAL validataion rule - e.g. file upload size / type
	 * @param string elements data
	 * @param int repeat group counter
	 * @return bol true if passes / false if falise validation
	 */

	function validate( $data, $repeatCounter = 0  )
	{
		$params 		=& $this->getParams();
		$user =& JFactory::getUser();
		if ($user->get('id') !== 0) {
			if ($params->get('captcha-showloggedin', 0) == 0) {
				return true;
			}
		}

		if ($params->get('captcha-method') == 'recaptcha') {
			$privatekey = $params->get('recaptcha_privatekey');
			if (JRequest::getVar('recaptcha_response_field')) {
				$resp = recaptcha_check_answer ($privatekey,
				$_SERVER["REMOTE_ADDR"],
				JRequest::getVar('recaptcha_challenge_field'),
				JRequest::getVar('recaptcha_response_field'));
				return ($resp->is_valid) ? true : false;
			}

			return false;
		} else {

			$this->getParams();
			$elName = $this->getFullName( true, true, false);
			if (@$_SESSION['security_code'] != $data) {
				return false;
			}
			return true;
		}
	}

	/**
	 * @return string error message raised from failed validation
	 */

	function getValidationErr()
	{
		return JText::_('CAPTCHA_FAILED');
	}

	function mustValidate()
	{
		$params 		=& $this->getParams();
		if (!$this->canUse() && !$this->canView()) {
			return false;
		}
		return parent::mustValidate();
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @param object element
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$user =& JFactory::getUser();
		$params =& $this->getParams();
		if ($user->id == 0 || (int)$params->get('captcha-showloggedin', 0) == 1) {
			$id = $this->getHTMLId($repeatCounter);
			$opts =& $this->getElementJSOptions($repeatCounter);
			$opts->method = $params->get('captcha-method', 'standard');
			$opts->recaptcha_pubkey = $params->get('recaptcha_publickey', '');
			$opts->recaptcha_theme = $params->get('recaptcha_theme', 'red');
			$opts->recaptcha_lang = strtolower($params->get('recaptcha_lang', 'en'));
			$opts->recaptcha_element_id = $id;
			$opts = json_encode($opts);
			return "new fbCaptcha('$id', $opts)";
		}
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikcaptcha/', true);
		$user =& JFactory::getUser();
		$params =& $this->getParams();
		if ($params->get('captcha-method') == 'recaptcha' && ($user->id == 0 || (int)$params->get('captcha-showloggedin', 0) == 1)) {
			/*
			$pubkey = $params->get('recaptcha_publickey');
			$src = 'http://www.google.com/recaptcha/api//js/recaptcha_ajax.js';
			$document =& JFactory::getDocument();
			$document->addScript($src);
			*/
			FabrikHelperHTML::script('recaptcha_ajax.js', 'http://www.google.com/recaptcha/api/js/', true);
		}
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */
	function getFieldDescription()
	{
		$p =& $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "VARCHAR(255)";
	}

	/**
	 * render the element admin settings
	 */

	function renderAdminSettings()
	{
		if (!function_exists('imagettfbbox')) {
			JError::raiseNotice(500, JText::_('Your version of PHP needs to be compiled with Freetype support to use CAPTCHA'));
			return;
		}
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		?>
	<div id="page-<?php echo $this->_name;?>" class="elementSettings" style="display: none">

	<?php
	echo $pluginParams->render();?>
<fieldset><legend><?php echo JText::_('STANDARDSETTINGS')?></legend> <?php echo $pluginParams->render('params', 'standard');
?></fieldset>
<fieldset><legend><?php echo JText::_('RECAPTCHASETTINGS')?></legend>
<?php echo $pluginParams->render('params', 'recaptcha');?>
</fieldset>
</div>
<?php
	}

	/**
	 * used to format the data when shown in the form's email
	 * @param mixed element's data
	 * @return string formatted value
	 */

	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		return "";
	}
}
?>
