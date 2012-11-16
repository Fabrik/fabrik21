<?php
/**
 * Plugin element to render fields
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikGooglemap extends FabrikModelElement {

	var $_pluginName = 'googlemap';

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
		$str = '';

		$params =& $this->getParams();
		$w = $params->get('fb_gm_table_mapwidth');
		$h = $params->get('fb_gm_table_mapheight');
		$z = $params->get('fb_gm_table_zoomlevel');
		if (strstr($data, GROUPSPLITTER)) {
			$data = explode(GROUPSPLITTER, $data);
			foreach ($data as $d) {
				if ($params->get('fb_gm_staticmap_tableview')) {
					$str .= $this->_staticMap( $d, $w, $h);
				}
				// $$$tom : added DMS option
				/*else {
					$str .= $this->_microformat($d);
					}*/
				else if ($params->get('fb_gm_staticmap_tableview_type_coords', 'num') == 'dms') {
					$str .= $this->_dmsformat($d);
				} else {
					$str .= $this->_microformat($d);
				}

			}
		} else {
			if ($params->get('fb_gm_staticmap_tableview')) {
				$str .= $this->_staticMap($data, $w, $h, $z, 0, true, JArrayHelper::fromObject($oAllRowsData));
			}
			// $$$tom : added DMS option
			/*else {
				$str .= $this->_microformat($data);
				}*/
			else if ($params->get('fb_gm_staticmap_tableview_type_coords', 'num') == 'dms') {
				$str .= $this->_dmsformat($data);
			} else {
				$str .= $this->_microformat($data);
			}
		}
		return $str;
	}

	function renderTableData_feed($data, $oAllRowsData)
	{
		$str = '';
		if (strstr($data, GROUPSPLITTER)) {
			$data = explode(GROUPSPLITTER, $data);
			foreach ($data as $d) {
				$str .= $this->_georss($d);
			}
		} else {
			$str .= $this->_georss($data);
		}
		return $str;
	}

	/**
	 * format the data as a georss
	 *
	 * @param string $data
	 * @return string html microformat markup
	 */

	function _georss($data)
	{
		if (strstr($data, '<georss:point>')) {
			return $data;
		}
		$o = $this->_strToCoords($data, 0);
		if($data != '') {
			$lon = trim($o->coords[1]);
			$lat = trim($o->coords[0]);
			$data = "<georss:point>{$lat},{$lon}</georss:point>";
		}
		return $data;
	}

	/**
	 * format the data as a microformat
	 *
	 * @param string $data
	 * @return string html microformat markup
	 */

	function _microformat($data)
	{
		$o = $this->_strToCoords($data, 0);
		if($data != '') {
			$data = "<div class=\"geo\">
			<span class=\"latitude\">{$o->coords[0]}</span>,
			<span class=\"longitude\">{$o->coords[1]}</span>
			</div>
			";
		}
		return $data;
	}

	/**
	 * $$$tom format the data as DMS
	 * [N,S,E,O] Degrees, Minutes, Seconds
	 *
	 * @param string $data
	 * @return string html DMS markup
	 */

	function _dmsformat($data)
	{
		$dms = $this->_strToDMS($data);
		if($data != '') {
			$data = "<div class=\"geo\">
			<span class=\"latitude\">{$dms->coords[0]}</span>,
			<span class=\"longitude\">{$dms->coords[1]}</span>
			</div>
			";
		}
		return $data;
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		if (is_array($val)) {
			$val = implode(GROUPSPLITTER, $val);
		}
		return $val;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		$document =& JFactory::getDocument();
		$params =& $this->getParams();
		//$src = "http://maps.google.com/maps?file=api&amp;v=2&amp;key=" . $params->get('fb_gm_key');
		$uri = JURI::getInstance();
		$src = $uri->getScheme() . "://maps.google.com/maps/api/js?sensor=".$params->get('fb_gm_sensor', 'false');
		$document->addScript($src);
		if ((int)$params->get('fb_gm_radius', '0')) {
			FabrikHelperHTML::script('distancewidget.js', 'components/com_fabrik/libs/googlemaps/', true);
		}
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikgooglemap/', true);
	}

	/**
	 * as different map instances may or may not load geo.js we shouldnt put it in
	 * formJavascriptClass() but call this code from elementJavascript() instead.
	 * The files are still only loaded when needed and only once
	 */

	protected function geoJs()
	{
		static $geoJs;
		if (!isset($geoJs)) {
			$document =& JFactory::getDocument();
			$params =& $this->getParams();
			if ($params->get('fb_gm_defaultloc')) {
				$uri = JURI::getInstance();
				$document->addScript($uri->getScheme() . "://code.google.com/apis/gears/gears_init.js");
				FabrikHelperHTML::script('geo.js', 'components/com_fabrik/libs/geo-location/');
				$geoJs = true;
			}
		}
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @param int repeat group counter
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$params 	=& $this->getParams();
		$id 		= $this->getHTMLId($repeatCounter);
		$element 	=& $this->getElement();
		$data 		=& $this->_form->_data;
		$v 		= $this->getValue($data, $repeatCounter);
		$zoomlevel      = $params->get('fb_gm_zoomlevel');
		$o              = $this->_strToCoords($v, $zoomlevel);
		$dms            = $this->_strToDMS($v);
		$opts           =& $this->getElementJSOptions($repeatCounter);
		$this->geoJs();
		$opts->lat 		= (float)$o->coords[0];
		$opts->lon 		= (float)$o->coords[1];
		$opts->lat_dms 		= (float)$dms->coords[0];
		$opts->lon_dms 		= (float)$dms->coords[1];
		$opts->zoomlevel 	= (int)$o->zoomlevel;
		$opts->threeD 		= $params->get('fb_gm_3d');
		$opts->control 		= $params->get('fb_gm_mapcontrol');
		$opts->scalecontrol 	= $params->get('fb_gm_scalecontrol');
		$opts->maptypecontrol 	= $params->get('fb_gm_maptypecontrol');
		$opts->overviewcontrol 	= $params->get('fb_gm_overviewcontrol');
		$opts->drag             = ($this->_form->_editable) ? true:false;
		$opts->staticmap        = $this->_useStaticMap() ? true: false;
		$opts->maptype          = $params->get('fb_gm_maptype');
		$opts->key              = $params->get('fb_gm_key');
		$opts->scrollwheel      = $params->get('fb_gm_scroll_wheel');
		$opts->latlng           = $this->_editable ? $params->get('fb_gm_latlng', false ) : false;
		$opts->latlng_dms       = $this->_editable ? $params->get('fb_gm_latlng_dms', false ) : false;
		$opts->geocode          = $params->get('fb_gm_geocode', false);
		$opts->geocode_event 	= $params->get('fb_gm_geocode_event', 'button');
		$opts->geocode_fields	= array();
		$opts->auto_center		= $params->get('fb_gm_auto_center', '0') == '0' ? false : true;
		if ($opts->geocode == '2') {
			foreach (array('addr1','addr2','city','state','zip','country') as $which_field) {
				$field_id = '';
				if ($field_id = $this->_getGeocodeFieldId($which_field, $repeatCounter)) {
					$opts->geocode_fields[] = $field_id;
				}
			}
		}
		$opts->reverse_geocode = $params->get('fb_gm_reverse_geocode', '0') == '0' ? false : true;
		if ($opts->reverse_geocode) {
			foreach (array('route' => 'addr1','neighborhood' => 'addr2','locality' => 'city','administrative_area_level_1' => 'state','postal_code' => 'zip','country' => 'country') as $google_field => $which_field) {
				$field_id = '';
				if ($field_id = $this->_getGeocodeFieldId($which_field, $repeatCounter)) {
					$opts->reverse_geocode_fields[$google_field] = $field_id;
				}
			}
		}
		$opts->center = $params->get('fb_gm_defaultloc', 0);

		$opts->use_radius = $params->get('fb_gm_radius','0') == '0' ? false : true;
		$opts->radius_fitmap = $params->get('fb_gm_radius_fitmap','0') == '0' ? false : true;
		$opts->radius_write_element = $opts->use_radius ? $this->_getFieldId('fb_gm_radius_write_element', $repeatCounter) : false;
		$opts->radius_read_element = $opts->use_radius ? $this->_getFieldId('fb_gm_radius_read_element', $repeatCounter) : false;
		$opts->radius_ro_value = $opts->use_radius ? $this->_getFieldValue('fb_gm_radius_read_element', $data, $repeatCounter) : false;
		$opts->radius_default = $params->get('fb_gm_radius_default', '50');
		if ($opts->radius_ro_value === false)
		{
			$opts->radius_ro_value = $opts->radius_default;
		}
		$opts->radius_unit = $params->get('fb_gm_radius_unit', 'm');
		$opts->radius_resize_icon = COM_FABRIK_LIVESITE . 'media/com_fabrik/images/radius_resize.png';
		$opts->radius_resize_off_icon = COM_FABRIK_LIVESITE . 'media/com_fabrik/images/radius_resize.png';

		$opts = json_encode($opts);

		return "new fbGoogleMap('$id', $opts)";
	}

	function _getFieldValue($which_field, $data, $repeatCounter = 0) {
		$params =& $this->getParams();
		$field = $params->get($which_field, false);
		if ($field) {
			$elementModel =& JModel::getInstance('element', 'FabrikModel');
			$elementModel->setId($field);
			if (!$this->_form->_editable) {
				$elementModel->_inDetailedView = true;
			}
			return $elementModel->getValue($data, $repeatCounter);
		}
		return false;		
	}
	
	function _getFieldId($which_field, $repeatCounter = 0) {
		$params =& $this->getParams();
		$field = $params->get($which_field, false);
		if ($field) {
			$elementModel =& JModel::getInstance('element', 'FabrikModel');
			$elementModel->setId($field);
			if (!$this->_form->_editable) {
				$elementModel->_inDetailedView = true;
			}
			return $elementModel->getHTMLId($repeatCounter);
		}
		return false;
	}

	function _getGeocodeFieldId($which_field, $repeatCounter = 0)
	{
		$tableModel =& $this->getTableModel();
		return $this->_getFieldId('fb_gm_geocode_' . $which_field, $repeatCounter);
	}

	/**
	 * determine if we use a google static ma
	 * Option has to be turned on and element un-editable
	 *
	 * @return bol
	 */

	function _useStaticMap()
	{
		static $usestatic;
		if (!isset($usestatic)) {
			$params =& $this->getParams();
			//requires you to have installed the pda plugin
			//http://joomup.com/blog/2007/10/20/pdaplugin-joomla-15/
			if (array_key_exists('ispda', $GLOBALS) && $GLOBALS['ispda'] == 1) {
				$usestatic = true;
			} else {
				$usestatic = ($params->get('fb_gm_staticmap') == '1' && !$this->_editable);
			}
		}
		return $usestatic;
	}

	/**
	 * util function to turn the saved string into coordinate array
	 *@param string coordinates
	 * @param int default zoom level
	 * @return object coords array and zoomlevel int
	 */

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

	/**
	 * $$$tom : util function to turn the saved string into DMS coordinate array
	 * @param string coordinates
	 * @param int default zoom level
	 * @return object coords array and zoomlevel int
	 */

	function _strToDMS($v)
	{
		$dms = new stdClass();
		$dms->coords = array('', '');
		if (strstr($v, ",")) {
			$ar = explode(":", $v);
			$v = FabrikString::ltrimword($ar[0], "(");
			$v = rtrim($v, ")");
			$dms->coords = explode(",", $v);

			// Latitude
			if (strstr($dms->coords[0], '-')) {
				$dms_lat_dir = 'S';
			} else {
				$dms_lat_dir = 'N';
			}
			$dms_lat_deg = abs((int)$dms->coords[0]);
			$dms_lat_min_float = 60 * (abs($dms->coords[0]) - $dms_lat_deg);
			$dms_lat_min = (int)$dms_lat_min_float;
			$dms_lat_sec_float = 60 * ($dms_lat_min_float - $dms_lat_min);

			//Round the secs
			$dms_lat_sec = round($dms_lat_sec_float, 0);
			//$dms_lat_sec = $dms_lat_sec_float;
			if ($dms_lat_sec == 60) {
				$dms_lat_min += 1;
				$dms_lat_sec = 0;
			}
			if ($dms_lat_min == 60) {
				$dms_lat_deg += 1;
				$dms_lat_min = 0;
			}

			//@TODO $$$tom Maybe add the possibility to "construct" our own format:
			// W87Â°43'41"
			// W 87Â° 43' 41" (with the spacing)
			// 87Â°43'41" W (Direction at the end)
			// etc.
			//
			// Also: for the seconds: use 1 quote (") or 2 single quotes ('') ? Right now: 1 quote

			// Currently W87Â°43'41"

			$dms->coords[0] = $dms_lat_dir.$dms_lat_deg.'&deg;'.$dms_lat_min.'&rsquo;'.$dms_lat_sec.'&quot;';

			// Longitude
			if (strstr($dms->coords[1], '-')) {
				$dms_long_dir = 'W';
			} else {
				$dms_long_dir = 'E';
			}
			$dms_long_deg = abs((int)$dms->coords[1]);
			$dms_long_min_float = 60 * (abs($dms->coords[1]) - $dms_long_deg);
			$dms_long_min = (int)$dms_long_min_float;
			$dms_long_sec_float = 60 * ($dms_long_min_float - $dms_long_min);

			//Round the secs
			$dms_long_sec = round($dms_long_sec_float, 0);
			//$dms_long_sec = $dms_long_sec_float;
			if ($dms_long_sec == 60) {
				$dms_long_min += 1;
				$dms_long_sec = 0;
			}
			if ($dms_long_min == 60) {
				$dms_long_deg += 1;
				$dms_long_min = 0;
			}

			$dms->coords[1] = $dms_long_dir.$dms_long_deg.'&deg;'.$dms_long_min.'&rsquo;'.$dms_long_sec.'&quot;';


		} else {
			$dms->coords = array(0, 0);
		}
		return $dms;
	}


	/**
	 * @access private
	 * get a static map
	 *
	 * @param string coordinates
	 * @param int width
	 * @param int height
	 * @param int zoom level
	 * @param int $repeatCounter
	 * @param bool is the static map in the table view
	 * @param array row / form data, needed if we need to get radius widget data
	 * @return string static map html
	 */

	function _staticMap($v, $w=null, $h=null, $z=null, $repeatCounter = 0, $tableView = false, $data = array())
	{
		$id		= $this->getHTMLId($repeatCounter);
		$params 	=& $this->getParams();
		if (is_null($w)) {
			$w = JRequest::getVar('fb_gm_mapwidth', $params->get('fb_gm_mapwidth'));
		}
		if (is_null($h)) {
			$h = JRequest::getVar('fb_gm_mapheight', $params->get('fb_gm_mapheight'));
		}
		if (is_null($z)) {
			$z = JRequest::getVar('fb_gm_zoomlevel', $params->get('fb_gm_zoomlevel'));
		}
		$k = $params->get('fb_gm_key');
		$icon = urlencode($params->get('fb_gm_staticmap_icon'));
		$o = $this->_strToCoords($v, $z);
		$lat = trim($o->coords[0]);
		$lon = trim($o->coords[1]);
		$z = $o->zoomlevel;

		switch ($params->get('fb_gm_maptype')) {
			case "G_SATELLITE_MAP":
				$type = 'satellite';
				break;
			case "G_HYBRID_MAP":
				$type = 'hybrid';
				break;
			case "TERRAIN":
				$type = 'terrain';
				break;
			case "G_NORMAL_MAP":
			default:
				$type = 'roadmap';
				break;
		}

		//$src = "http://maps.google.com/staticmap?center=$lat,$lon&zoom={$z}&size={$w}x{$h}&maptype=mobile&markers=$lat,$lon,&key={$k}";
		// new api3 url:
		$markers = '';
		if ($icon !== '') {
			$markers .="icon:$icon|";
		}
		$markers .= "$lat,$lon";
		$uri = JURI::getInstance();
		$src = $uri->getScheme() . "://maps.google.com/maps/api/staticmap?center=$lat,$lon&amp;zoom={$z}&amp;size={$w}x{$h}&amp;maptype=$type&amp;mobile=true&amp;markers=$markers&amp;sensor=false";
		
		if ((int)$params->get('fb_gm_radius', '0') == 1) {
			//$data =& $this->_form->_data;
			$radius = $this->_getFieldValue('fb_gm_radius_read_element', $data, $repeatCounter);
			if ($radius === false || !isset($radius))
			{
				$radius = $params->get('fb_gm_radius_default', '50');;
			}
			$enc_str = $this->GMapCircle($lat,$lon,$radius);
			$src .= "&amp;path=weight:2%7Ccolor:black%7Cfillcolor:0x5599bb%7Cenc:" . $enc_str;
		}
		
		$id = $tableView ? '' : "id=\"{$id}\"";
		$str =  "<div $id class=\"gmStaticMap\"><img src=\"$src\" alt=\"static map\" />";
		/*		$str .= "<div style='text-align:right;width:{$w}px'><a href='#' class='gmStaticMapZoomOut'><img style='padding:5px 0 0 0;' src='".COM_FABRIK_LIVESITE."components/com_fabrik/plugins/element/fabrikgooglemap/images/zoomout.png' alt='[-]'/></a>".
		 "<a href='#' class='gmStaticMapZoomIn'><img style='padding:5px 0 0 5px;' src='".COM_FABRIK_LIVESITE."components/com_fabrik/plugins/element/fabrikgooglemap/images/zoomin.png' alt='[+]'/></a>".
		 "</div>";*/
		$str .= "</div>";
		return $str;
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		require_once(COM_FABRIK_FRONTEND.DS.'libs'.DS.'mobileuseragent'.DS.'mobileuseragent.php');
		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'string.php');
		$ua 				= new MobileUserAgent();
		$id					= $this->getHTMLId($repeatCounter);
		$name 			= $this->getHTMLName($repeatCounter);
		$groupModel = $this->_group;
		$element 		=& $this->getElement();
		$val 				= $this->getValue($data, $repeatCounter);

		$params 		=& $this->getParams();
		$w 					= JRequest::getVar('fb_gm_mapwidth', $params->get('fb_gm_mapwidth'));
		$h 					= JRequest::getVar('fb_gm_mapheight', $params->get('fb_gm_mapheight'));

		if ($this->_useStaticMap()) {
			return $this->_staticMap($val, null, null, null, $repeatCounter, false, $data);
		} else {
			$val = JArrayHelper::getValue($data, $name, $val);//(array_key_exists($name, $data) && !empty($data[$name])) ? $data[$name] : $val;
			if ($element->hidden == '1') {
				return $this->getHiddenField($name, $data[$name], $id);
			}
			$str = "<div class=\"fabrikSubElementContainer\" id=\"$id\">";
			//if its not editable and theres no val don't show the map
			if ((!$this->_editable && $val !='') || $this->_editable) {
				if ($this->_editable && $params->get('fb_gm_geocode') == '1') {
					$str .= "<div style=\"margin-bottom:5px\"><input class=\"geocode_input inputbox\" style=\"margin-right:5px\"/>";
				}
				else if ($this->_editable && $params->get('fb_gm_geocode') == '2') {
					$str .= "<div>";
				}
				if ($params->get('fb_gm_geocode') != '0' && $params->get('fb_gm_geocode_event', 'button') == 'button' && $this->_editable) {
					$str .= "<input class=\"button geocode\" type=\"button\" value=\"" . JText::_('PLG_ELEMENT_GOOGLEMAP_GEOCODE') . "\" />";
				}
				if ($this->_editable && $params->get('fb_gm_geocode') != '0') {
					$str .= '</div>';
				}
				$str .= "<div class=\"map\" style=\"width:{$w}px; height:{$h}px\"></div>";
				$str .= "<input type=\"hidden\" class=\"fabrikinput\" name=\"$name\" value=\"".htmlspecialchars($val, ENT_QUOTES)."\" />";
				if (($this->_editable || $params->get('fb_gm_staticmap') == '2') && $params->get('fb_gm_latlng') == '1') {
					$arrloc = explode(',', $val);
					$arrloc[0] = str_replace("(", "", $arrloc[0]);
					$arrloc[1] = array_key_exists(1, $arrloc ) ? str_replace(")", "", array_shift(explode(":", $arrloc[1]))) : '';
					$edit = $this->_editable ? '' : 'disabled="true"';
					$str .= "<div class=\"coord\" style=\"margin-top:5px;\">
					<input $edit size=\"23\" value=\"$arrloc[0] ° N\" style=\"margin-right:5px\" class=\"inputbox lat\"/>
					<input $edit size=\"23\" value=\"$arrloc[1] ° E\"  class=\"inputbox lng\"/></div>";
				}
				if (($this->_editable || $params->get('fb_gm_staticmap') == '2') && $params->get('fb_gm_latlng_dms') == '1') {
					$dms = $this->_strToDMS($val);
					$edit = $this->_editable ? '' : 'disabled="true"';
					$str .= "<div class=\"coord_dms\" style=\"margin-top:5px;\">
					<input $edit size=\"23\" value=\"".$dms->coords[0]."\" style=\"margin-right:5px\" class=\"latdms\"/>
					<input $edit size=\"23\" value=\"".$dms->coords[1]."\"  class=\"lngdms\"/></div>";
				}
				$str .= "</div>";
			} else {
				$str .= JText::_('PLG_ELEMENT_GOOGLEMAP_NO_LOCATION_SELECTED');
			}
			$str .= $this->_microformat($val);
			return $str;
		}
	}

	/**
	 * get field description
	 * @return string field description
	 */

	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		return "VARCHAR(255)";
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php
	echo $pluginParams->render();
	?>
<fieldset><legend><?php echo JText::_('PLG_ELEMENT_GOOGLEMAP_DEFAULT_LOCATION') ?></legend>
	<?php echo $pluginParams->render('params', 'defaultlocation'); ?></fieldset>
<fieldset><legend><?php echo JText::_('Geocoding') ?></legend> <?php echo $pluginParams->render('params', 'geocoding'); ?>
</fieldset>
<fieldset><legend> <?php echo JText::_('Table settings');?> </legend> <?php echo $pluginParams->render('params', 'tablesettings');?>
</fieldset>
<fieldset><legend> <?php echo JText::_('Radius Widget');?> </legend> <?php echo $pluginParams->render('params', 'radiuswidget');?>
</fieldset>
</div>
	<?php
	}

	/**
	 * can be overwritten in the plugin class - see database join element for example
	 * @param array
	 * @param array
	 * @param string table name (depreciated)
	 */

	function getAsField_html(&$aFields, &$aAsFields, $dbtable = '')
	{
		$dbtable = $this->actualTableName();
		$db =& JFactory::getDBO();
		$tableModel =& $this->getTableModel();
		$table 		=& $tableModel->getTable();

		$fullElName = "$dbtable" . "___" . $this->_element->name;
		$dbtable = $db->nameQuote($dbtable);
		$str 				= $dbtable.".".$db->nameQuote($this->_element->name)." AS ".$db->nameQuote($fullElName);
		if ($table->db_primary_key == $fullElName) {
			array_unshift($aFields, $fullElName);
			array_unshift($aAsFields, $fullElName);
		} else {
			$aFields[] 	= $str;
			$aAsFields[] =  $db->nameQuote($fullElName);
			$rawName = "$fullElName". "_raw";
			$aFields[]				= $dbtable.".".$db->nameQuote($this->_element->name)." AS ".$db->nameQuote($rawName);
			$aAsFields[]			= $db->nameQuote($rawName);
		}
	}

	/**
	 * this really does get just the default value (as defined in the element's settings)
	 * @return unknown_type
	 */

	function getDefaultValue($data = array())
	{
		if (!isset($this->_default)) {
			$params =& $this->getParams();
			$which_default = $params->get('fb_gm_defaultloc', '1');
			if ($which_default == '0') {
				// $$$ hugh - added parens around lat,long for consistancy!
				$this->_default = '(' . JRequest::getVar('fb_gm_lat', $params->get('fb_gm_lat')) . ',' . JRequest::getVar('fb_gm_long', $params->get('fb_gm_long')) . ')' . ':' . JRequest::getVar('fb_gm_zoomlevel', $params->get('fb_gm_zoomlevel'));
			}
			else if ($which_default = '2') {
				$this->_default = $params->get('fb_gm_eval_default');
				$w = new FabrikWorker();
				$this->_default = $w->parseMessageForPlaceHolder($this->_default, $data, true);
				$this->_default = @eval(stripslashes($this->_default));
				FabrikWorker::logEval($this->_default, 'Caught exception on eval in '.$this->_element->name.'::getDefaultValue() : %s');
			}
		}
		return $this->_default;
	}


	function getValue($data, $repeatCounter = 0, $opts = array())
	{
		if (is_null($this->defaults)) {
			$this->defaults = array();
		}
		if (!array_key_exists($repeatCounter, $this->defaults)) {
			$groupModel =& $this->getGroup();
			$formModel 	=& $this->getForm();
			$element		=& $this->getElement();
			$tableModel =& $this->getTableModel();
			$params 		=& $this->getParams();
			$name = $this->getFullName(false, true, false);

			// $$$rob - if no search form data submitted for the search element then the default
			// selection was being applied instead
			if (array_key_exists('use_default', $opts) && $opts['use_default'] == false) {
				$value = '';
			} else {
				$value = $this->getDefaultValue($data);
			}

			$table 		=& $tableModel->getTable();
			if ($groupModel->canRepeat() == '1') {
				$fullName = $table->db_table_name . $formModel->_joinTableElementStep . $element->name;
				if (isset($data[$fullName])) {
					if (is_array($data[$fullName])) {
						$value = $data[$fullName][0];
					} else {
						$value = $data[$fullName];
					}
					$value = explode(GROUPSPLITTER, $value);

					if (is_array($value) && array_key_exists($repeatCounter, $value)) {
						$value = $value[$repeatCounter];
						if (is_array($value)) {
							$value = implode(',', $value);
						}
						return $value;
					}
				}
			}
			if ($groupModel->isJoin()) {
				$fullName = $this->getFullName(false, true, false);
				$joinid = $groupModel->getGroup()->join_id;
				if (isset($data['join'][$joinid][$fullName])) {
					$value = $data['join'][$joinid][$fullName];
					if (is_array($value) && array_key_exists($repeatCounter, $value)) {
						$value = $value[$repeatCounter];
					}
				} else {
					// $$$ rob - prob not used but im leaving in just in case
					if (isset($data[$fullName])) {
						$value = $data[$fullName];
						if (is_array($value) && array_key_exists($repeatCounter, $value)) {
							$value = $value[$repeatCounter];
						}
					}
				}
			} else {
				$fullName = $table->db_table_name . $formModel->_joinTableElementStep . $element->name;
				if (isset($data[$fullName])) {
					/* drop down  */
					if (is_array($data[$fullName])) {

						if (isset($data[$fullName ][0])) {
							/* if not its a file upload el */
							$value = $data[$fullName ][0];
						}
					} else {
						$value = $data[$fullName];
					}
				}
			}
			if ($value === '') { //query string for joined data
				$value = JArrayHelper::getValue($data, $name);
			}
			//stops this getting called from form validation code as it messes up repeated/join group validations
			if (array_key_exists('runplugins', $opts) && $opts['runplugins'] == 1) {
				$formModel->getPluginManager()->runPlugins('onGetElementDefault', $formModel, 'form', $this);
			}
			/** ensure that the data is a string **/
			if (is_array($value)) {
				$value  = implode(',', $value);
			}
			$this->defaults[$repeatCounter] = $value;
		}
		return $this->defaults[$repeatCounter];
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelElement::_getQRValue()
	 */
	protected function _getQRValue($value, $data = array(), $repeatCounter = 0, $view = 'table')
	{
		// QR encoding will just want "lat,lon", not "(lat,lon):zoom"
		$o = $this->_strToCoords($value);
		return implode(',', $o->coords);
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelElement::_getEmailValue()
	 */
	protected function _getEmailValue($value, $data = array(), $repeatCounter = 0)
	{
		return $this->_staticMap($value, null, null, null, 0, true, $data);
	}
	
	/**
	 * Code to build a polyline circle, stolen from:
	 * http://stackoverflow.com/questions/7316963/drawing-a-circle-google-static-maps
	 * Needed for drawing radius circle on static maps
	 * 
	 * @param unknown_type $Lat
	 * @param unknown_type $Lng
	 * @param unknown_type $Rad
	 * @param unknown_type $Detail
	 */
	protected function GMapCircle($Lat,$Lng,$Rad,$Detail=8){
		$R    = 6371;
	
		$pi   = pi();
	
		$Lat  = ($Lat * $pi) / 180;
		$Lng  = ($Lng * $pi) / 180;
		$d    = $Rad / $R;
	
		$points = array();
		$i = 0;
	
		for ($i = 0; $i <= 360; $i+=$Detail)
		{
			$brng = $i * $pi / 180;
		
			$pLat = asin(sin($Lat)*cos($d) + cos($Lat)*sin($d)*cos($brng));
			$pLng = (($Lng + atan2(sin($brng)*sin($d)*cos($Lat), cos($d)-sin($Lat)*sin($pLat))) * 180) / $pi;
			$pLat = ($pLat * 180) /$pi;
		
			$points[] = array($pLat,$pLng);
		}
	
		require_once(COM_FABRIK_FRONTEND.DS.'libs'.DS.'googlemaps'.DS.'polyline_encoder'.DS.'class.polylineEncoder.php');
		$PolyEnc   = new PolylineEncoder();
		$EncString = $PolyEnc->encode($points);
	
		return $EncString->points;
	}
}
?>