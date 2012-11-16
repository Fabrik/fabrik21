<?php
/**
 * Plugin element to render two fields to capture a link (url/label)
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikWeather extends FabrikModelElement {

	var $_pluginName = 'fabrikweather';
	var $_night_cache = array();

	/**
	 * Constructor
	 */

	function __construct()
	{
		$this->hasSubElements = false;
		parent::__construct();
	}

	function setIsRecordedInDatabase()
	{
		$this->_recordInDatabase = false;
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		$lang =& JFactory::getLanguage();
		$langfile = 'com_fabrik.plg.element.fabrikweather';
		$lang->load($langfile, JPATH_ADMINISTRATOR, null, true);

		$tableModel =& $this->getTableModel();
		$params =& $this->getParams();
		$data = explode(GROUPSPLITTER, $data);
		for ($i=0; $i < count($data); $i++) {
			$data[$i] = $this->_renderTableData($data[$i], $oAllRowsData, $i);
		}
		$data = implode(GROUPSPLITTER, $data);
		return parent::renderTableData($data, $oAllRowsData);
	}

	/**
	 * @access private
	 *
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function _renderTableData($data, $oAllRowsData, $repeatCounter)
	{
		$data = explode(GROUPSPLITTER2, $data);
		$tableModel =& $this->getTableModel();
		$params =& $this->getParams();

		$watch_element = str_replace('.', '___', $params->get('fabrikweather_watch'));
		$watch_element_raw = $watch_element . '_raw';

		if (array_key_exists($watch_element_raw, $oAllRowsData)) {
			if (!empty($oAllRowsData->$watch_element_raw)) {
				$watch = $oAllRowsData->$watch_element_raw;
				$watch = explode(GROUPSPLITTER, $watch);
				$watch = $watch[$repeatCounter];
			}
		}

		$html = $this->_render($watch, JArrayHelper::fromObject($oAllRowsData), 'table', $repeatCounter);

		return $html;
	}

	function _strToCoords($v, $zoomlevel = 0)
	{
		$o = new stdClass();
		$o->coords = array('', '');
		$o->zoomlevel = (int)$zoomlevel;
		if (strstr($v, ",")) {
			$ar = explode(":", $v);
			$o->zoomlevel = count($ar) == 2 ? array_pop($ar) : 4;
			$v = FabrikString::ltrimword($ar[0], "(");
			$v = rtrim($v, ")");
			$o->coords = explode(",", $v);
		} else {
			$o->coords = array(0,0);
		}
		return $o;
	}

	private function _isNight($lat, $lon) {
		$params = $this->getParams();
		$askgeo_account = $params->get('fabrikweather_askgeo_account', '');
		$askgeo_key = $params->get('fabrikweather_askgeo_key', '');
		if (empty($askgeo_account) || empty($askgeo_key)) {
			return false;
		}
		if (!isset($this->_night_cache["$lat,$lon"])) {
			// http://www.askgeo.com/api/65005/kdh9ire7nnjj7a0u57v66pdun6/timezone.xml?points=37.78%2C-122.42%3B40.71%2C-74.01
			$this->_xml = simplexml_load_file('http://www.askgeo.com/api/' . $askgeo_account . '/' . $askgeo_key . '/timezone.xml?points=' . $lat . '%2C' . $lon);
			$response = $this->_xml->xpath("/response");
			//var_dump($this->_xml,$response);exit;
			if ($response[0]['code'] != '0') {
				return '';
			}

			$data = $this->_xml->xpath("/response/data/result");
			//var_dump($data, $data[0]['currentOffsetMs']);exit;
			$tzoffset = (int)$data[0]['currentOffsetMs'] / 1000 / 60 / 60;
			$zenith = ini_get("date.sunrise_zenith");
			$sunrise = date_sunrise( time(), SUNFUNCS_RET_TIMESTAMP , $lat, $lon, $zenith, $tzoffset );
			$sunset = date_sunset( time(), SUNFUNCS_RET_TIMESTAMP , $lat, $lon, $zenith, $tzoffset );
			$this->_night_cache["$lat,$lon"] = (time() < $sunrise || time() > $sunset);
		}
		return $this->_night_cache["$lat,$lon"];
	}

	private function _render($watch, $data = array(), $format = 'table', $repeatCounter = 0) {
		// http://www.google.com/ig/api?weather=,,,34739300,-86624100
		$id = $this->getHTMLId($repeatCounter);
		$params =& $this->getParams();
		$watch_type = $params->get('fabrikweather_watch_type', 'map');
		$temp_scale = $params->get('fabrikweather_temp_scale', 'f');
		$w = new FabrikWorker();
		$city = $params->get('fabrikweather_city', '');
		$city = $w->parseMessageForPlaceholder($city, $data);
		$language = $params->get('fabrikweather_language', 'en');
		$is_night = false;

		if ($watch_type == 'map') {
			$o = $this->_strToCoords($watch);
			$lat = (float)trim($o->coords[0]);
			$glat = sprintf("%8d", $lat * 1000000);
			$lon = (float)trim($o->coords[1]);
			$glon = sprintf("%8d", $lon * 1000000);
			$weather = "$glat,$glon";
			$is_night = $this->_isNight($lat,$lon);
		}
		else {
			$weather = $city;
		}


		$custom_slug = $params->get('fabrikweather_class_prefix', 'fabrikweather');
		if (!empty($custom_slug) && JFile::exists(COM_FABRIK_FRONTEND.DS.'components/com_fabrik/plugins/element/fabrikweather/assets/'.$custom_slug.'/weather.css')) {
			FabrikHelperHTML::stylesheet('weather.css', 'components/com_fabrik/plugins/element/fabrikweather/assets/'.$custom_slug.'/');
		}
		else {
			FabrikHelperHTML::stylesheet('weather.css', 'components/com_fabrik/plugins/element/fabrikweather/assets/fabrikweather/');
		}
		if (!empty($custom_slug) && JFile::exists(COM_FABRIK_FRONTEND.DS.'plugins/element/fabrikweather/assets/'.$custom_slug.'/render.php')) {
			require_once(COM_FABRIK_FRONTEND.DS.'plugins/element/fabrikweather/assets/'.$custom_slug.'/render.php');
		}
		else {
			require_once(COM_FABRIK_FRONTEND.DS.'plugins/element/fabrikweather/assets/fabrikweather/render.php');
		}

		$render = new fabrikWeatherRender();
		$render->setIsNight($is_night);
		$render->setTempScale($temp_scale);

		if (!empty($custom_slug) && JFolder::exists(COM_FABRIK_FRONTEND.DS.'plugins/element/fabrikweather/assets/'.$custom_slug.'/icons')) {
			$render->setIconPath('plugins/element/fabrikweather/assets/'.$custom_slug.'/icons');
		}
		else {
			$render->setIconPath('plugins/element/fabrikweather/assets/fabrikweather/icons');
		}

		if (!$render->getWeatherXml($weather, $watch_type, $language)) {
			return JText::_(PLG_ELEMENT_FABRIKWEATHER_NO_GOOGLE_DATA);
		}

		if ($format == 'table') {
			$html = $render->getTableHtml($id, $city);
		}
		else {
			$html = $render->getFormHtml($id, $city);
		}
        return $html;
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0) {
		$lang =& JFactory::getLanguage();
		$langfile = 'com_fabrik.plg.element.fabrikweather';
		$lang->load($langfile, JPATH_ADMINISTRATOR, null, true);

		if (JRequest::getVar('view') == 'form') {
			return JText::_('PLG_ELEMENT_THUMBS_ONLY_ACCESSIBLE_IN_DETALS_VIEW');
		}

		$name 			= $this->getHTMLName($repeatCounter);
		$formModel = $this->getForm();
		$id 				= $this->getHTMLId($repeatCounter);
		$params 		=& $this->getParams();
		//$element 		=& $this->getElement();

		$watch_element = str_replace('.', '___', $params->get('fabrikweather_watch'));
		$watch_element_raw = $watch_element . '_raw';

		$watch = '';
		if (is_array($formModel->_data)) {
			if (array_key_exists($watch_element_raw, $formModel->_data)) {
				if (!empty($formModel->_data[$watch_element_raw])) {
					$watch = $formModel->_data[$watch_element_raw];
					$watch = explode(GROUPSPLITTER, $watch);
					$watch = $watch[$repeatCounter];
				}
			}
		}

		$str = "<div class='fabrikSubElementContainer' id='" . $id . "_div'>";
		if (!empty($watch)) {
			$str .= $this->_render($watch, $formModel->_data, 'form', $repeatCounter);
		}
		else {
			$str .= JText::_('PLG_ELEMENT_FABRIKWEATHER_NO_LOCATION_DATA');
		}
		$str .="</div>";
		return $str;
	}

	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "TEXT";
	}

	function renderAdminSettings()
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	echo $pluginParams->render('params', 'extra');
	?>
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

		return $value;
	}

	/**
	 *  manupulates posted form data for insertion into database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		return $val;
	}


	/**
	 * this really does get just the default value (as defined in the element's settings)
	 * @param array data to use as parsemessage for placeholder
	 * @return unknown}_type
	 */

	function getDefaultValue($data = array())
	{
		if (!isset($this->_default)) {
			$w = new FabrikWorker();
			$params =& $this->getParams();
			$element =& $this->getElement();
			$default = $w->parseMessageForPlaceHolder($element->default, $data);
			if ($element->eval == "1") {
				$default = @eval(stripslashes($default));
				FabrikWorker::logEval($default, 'Caught exception on eval in '.$element->name.'::getDefaultValue() : %s');
			}
			$this->_default = $default;
		}
		return $this->_default;
	}

	/**
	 * can be overwritten by plugin class
	 * determines the value for the element in the form view
	 * @TODO: whats the diff between this and getValue() ?????
	 * $$$ROB - TESTING POINTING getValue() to here
	 * @param array data
	 * @param int when repeating joinded groups we need to know what part of the array to access
	 * @param array options
	 * @return string default value
	 */

	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		return '';
	}

}
?>
