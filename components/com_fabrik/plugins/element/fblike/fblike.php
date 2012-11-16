<?php
/**
 * Plugin element to render facebook open graph like button
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFblike extends FabrikModelElement {

	var $_pluginName = 'fblike';

	var $hasLabel = false;
	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		$params =& $this->getParams();
		$meta = array();
		$config = JFactory::getConfig();
		$ex = $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://';
		// $$$ rob no need to get other meta data as we are linking to the details which contains full meta info on what it is
		// you are liking
		$meta['og:url'] = $ex.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$meta['og:site_name'] = $config->getValue('sitename');
		$meta['fb:admins'] = $params->get('fblike_opengraph_applicationid');
		$str = FabrikHelperHTML::facebookGraphAPI($params->get('opengraph_applicationid'), $params->get('fblike_locale', 'en_US'), $meta);
		//in table view we like the detailed record not the table view itself
		$url = $this->getTableModel()->linkHref($this, $oAllRowsData);
		return $str.$this->_render($url);
		return parent::renderTableData($data, $oAllRowsData);
	}

	/**
	 * draws the form element
	 * @param array data to pre-populate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$params =& $this->getParams();
		$meta = array();
		$formModel = $this->getForm();
		$config = JFactory::getConfig();
		$ex = $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://';
		$map = array(
			'og:title' => 'fblike_title',
			'og:type' => 'fblike_type',
			'og:image' => 'fblike_image',
			'og:description' => 'fblike_description',
			'og:street-address' => 'fblike_street_address',
			'og:locality' => 'fblike_locality',
			'og:region' => 'fblike_region',
			'og:postal-code' => 'fblike_postal_code',
			'og:country-name' => 'fblike_country',
			'og:email' => 'fblike_email',
			'og:phone_number' => 'fblike_phone_number',
			'og:fax_number' => 'fblike_fax_number'
		);

		foreach ($map as $k => $v) {
			$elid = $params->get($v);
			if ($elid != '') {
				$el = $formModel->getElement($elid, true);
				if (is_object($el)) {
					$name = $el->getFullName(false, true, false);
					$v = JArrayHelper::getValue($data, $name);
					if ($k == 'og:image') { $v = $ex.$_SERVER['SERVER_NAME'].$v; }
					if ($v !== '') {
						$meta[$k] = $v;
					}
				}
			}
		}

		$locEl = $formModel->getElement($params->get('fblike_location'), true);
		if ($locEl != '') {
			$loc = JArrayHelper::getValue($data, $locEl->getFullName(false, true, false));
			$loc = array_shift(explode(':', $loc));
			$loc = explode(",", $loc);
			if (count($loc) == 2) {
				$meta['og:latitude'] = $loc[0];
				$meta['og:longitude'] = $loc[1];
			}
		}
		$meta['og:url'] = $ex.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		$meta['og:site_name'] = $config->getValue('sitename');
		$meta['fb:app_id'] = $params->get('fblike_opengraph_applicationid');
		$str = FabrikHelperHTML::facebookGraphAPI($params->get('fblike_opengraph_applicationid'), $params->get('fblike_locale', 'en_US'), $meta);
		$url = $params->get('fblike_url');
		//$$$tom placeholder option for URL params
		$w = new FabrikWorker();
		$url = $w->parseMessageForPlaceHolder($url, $data);
		return $str.$this->_render($url);
	}

	protected function _render($url)
	{
		$params =& $this->getParams();
		if ($url !== '') {
			if (!strstr($url, COM_FABRIK_LIVESITE)) {
				// $$$ rob doesnt work with sef urls as $url already contains site folder.
				//$url = COM_FABRIK_LIVESITE.$url;
				$ex = $_SERVER['SERVER_PORT'] == 80 ? 'http://' : 'https://';
				$url = $ex.$_SERVER['SERVER_NAME'].$url;
			}
			$href = "href=\"$url\"";
		} else {
			$href = '';
		}

		$layout= $params->get('fblike_layout', 'standard');
		$showfaces = $params->get('fblike_showfaces', 0) == 1 ? 'true' : 'false';
		$width = $params->get('fblike_width', 300);
		$action = $params->get('fblike_action', 'like');
		$font = $params->get('fblike_font', 'arial');
		$colorscheme = $params->get('fblike_colorscheme', 'light');
		$str = "<fb:like $href layout=\"$layout\" show_faces=\"$showfaces\" width=\"$width\" action=\"$action\" font=\"$font\" colorscheme=\"$colorscheme\" />";
		return $str;
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$id = $this->getHTMLId($repeatCounter);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts = json_encode($opts);
		return "new fbLike('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fblike/', false);
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
		return "INT(1)";
	}

	function renderAdminSettings(&$lists)
	{
		$element =& $this->getElement();
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings" style="display:none">
<?php
echo $pluginParams->render('params', 'extra');
?>
<fieldset><legend><?php echo JText::_('Required Open Graph Metadata') ?></legend>
<?php echo $pluginParams->render('params', 'opengraph-required'); ?></fieldset>
<fieldset><legend><?php echo JText::_('Contact Open Graph Metadata') ?></legend>
<?php echo $pluginParams->render('params', 'opengraph-contact'); ?></fieldset>
<fieldset><legend><?php echo JText::_('Location Open Graph Metadata') ?></legend>
<?php echo $pluginParams->render('params', 'opengraph-location'); ?></fieldset>
<?php
?></div>
<?php
	}

}
?>