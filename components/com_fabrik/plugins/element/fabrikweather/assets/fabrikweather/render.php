<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class fabrikWeatherRender {

	var $_xml = null;
	var $_slug = 'fabrikweather';
	var $_day_night_suffix = '_night';
	var $_is_night = false;
	var $_icon_path = 'plugins/element/fabrikweather/assets/fabrikweather/icons';
	var $_temp_scale = 'f';

	function getWeatherXml ($weather, $watch_type = 'map', $language = 'en') {
		if (empty($weather)) {
			return false;
		}
		if (empty($this->_xml)) {
			if ($watch_type == 'map') {
				$weather = ',,,' . $weather;
			}
			// FIXME - need to use something other than simplexml, to handle non-UTF8 encoding
			// For now fetch file and convert to UTF8
			//$this->_xml = simplexml_load_file('http://www.google.com/ig/api?weather=' . $weather . '&hl=' . $language);
			$this->_xml = simplexml_load_string(utf8_encode(file_get_contents('http://www.google.com/ig/api?weather=' . $weather . '&hl=' . $language)));

		}
		$problem_cause = $this->_xml->xpath("/xml_api_reply/weather/problem_cause");
		if (!empty($problem_cause)) {
			return false;
		}
		return true;
	}

	private function _getIcon($original_icon = '', $size = 'medium') {
		$icon_dir = $this->_icon_path . '/' . $size . '/';
        $icon_info = pathinfo($original_icon);
        $icon_file = $icon_dir . $icon_info['filename'] . '.png';
        if (JFile::exists(COM_FABRIK_FRONTEND.DS.$icon_file)) {
			$icon_url = COM_FABRIK_LIVESITE . 'components/com_fabrik/' . $icon_file;
        	if ($this->_is_night) {
        		$night_icon_file = $icon_dir . $icon_info['filename'] . $this->_day_night_suffix . '.png';
        		if (JFile::exists(COM_FABRIK_FRONTEND.DS.$night_icon_file)) {
        			$icon_url = COM_FABRIK_LIVESITE . 'components/com_fabrik/' . $night_icon_file;
        		}
        	}
        }
        else {
        	//$icon_url = "http://www.google.com" . $original_icon;
        	if (strstr($original_icon,'http://')) {
	        	$icon_url = $original_icon;
        	}
        	else {
        		$icon_url = "http://www.google.com" . $original_icon;
        	}
        }
        return $icon_url;
	}

	private function _getCurrent($city) {
		$information = $this->_xml->xpath("/xml_api_reply/weather/forecast_information");
		$current = $this->_xml->xpath("/xml_api_reply/weather/current_conditions");

		if (empty($city)) {
			$city = $information[0]->city['data'];
		}

		$icon_url = $this->_getIcon($current[0]->icon['data'], 'large');

        $main_html = "	<div class='{$this->_slug}_current'>";
       	$main_html .= "		<div class='{$this->_slug}_main_left'>";
		$main_html .= "			<img alt='" . $current[0]->condition['data'] . "' src='" . $icon_url . "'>";
		if ($this->_temp_scale == 'f') {
			$main_html .= "			<p class='{$this->_slug}_temp'>" . $current[0]->temp_f['data'] . "&deg; F</p>";
		}
		else {
			$main_html .= "			<p class='{$this->_slug}_temp'>" . $current[0]->temp_c['data'] . "&deg; C</p>";
		}
		$main_html .= "		</div>";
		$main_html .= "		<div class='{$this->_slug}_main_right'>";
		$main_html .= "			<p class='{$this->_slug}_city'>" . $city . "</p>";
		$main_html .= "			<p class='{$this->_slug}_condition'>" . $current[0]->condition['data'] . "</p>";
		$main_html .= "			<p class='{$this->_slug}_humidity'>" . $current[0]->humidity['data'] . "</p>";
		$main_html .= "			<p class='{$this->_slug}_wind'>" . $current[0]->wind_condition['data'] . "</p>";
		$main_html .= "		</div>";
		$main_html .= "	</div>";
		return $main_html;
	}

	private function _getForecast() {
		$forecast_list = $this->_xml->xpath("/xml_api_reply/weather/forecast_conditions");
		$forecast_html = "<ul class='{$this->_slug}_next_days'>";
        foreach ($forecast_list as $forecast) {
        	$icon_url = $this->_getIcon($forecast->icon['data'], 'small');
        	$forecast_html .= "<li class='{$this->_slug}_aitems-4'>";
        	$forecast_html .= "	<div class='{$this->_slug}_fday'>";
        	$forecast_html .= "		<span class='{$this->_slug}_day'>" . $forecast->day_of_week['data'] . "</span>";
            $forecast_html .= "		<img src='" . $icon_url . "' alt='" . $forecast->condition['data'] . "'>";
            $forecast_html .= "		<p class='{$this->_slug}_day_temp'>";
            if ($this->_temp_scale == 'f') {
	            $forecast_html .= "			<span class='{$this->_slug}_day_day'>" . $forecast->high['data']. "&deg; F</span>";
	            $forecast_html .= "			<span class='{$this->_slug}_day_night'>" . $forecast->low['data'] . "&deg; F</span>";
            }
            else {
	            $forecast_html .= "			<span class='{$this->_slug}_day_day'>" . $forecast->high['data']. "&deg; C</span>";
	            $forecast_html .= "			<span class='{$this->_slug}_day_night'>" . $forecast->low['data'] . "&deg; C</span>";
            }
            $forecast_html .= "		</p>";
            $forecast_html .= "	</div>";
        	$forecast_html .= "</li>";
        }
        $forecast_html .= "</ul>";
        return $forecast_html;
	}

	/*
	 * These functions are required
	 */

	public function setIsNight($is_night) {
		$this->_is_night = $is_night;
	}

	public function setIconPath($path) {
		$this->_icon_path = $path;
	}

	public function getTableHtml($id, $city) {
		$html = "<div class='{$this->_slug}_main'>";
        $html .= $this->_getCurrent($city);
        $html .= "</div>";
        return $html;
	}

	public function getFormHtml($id, $city) {
		$html = "<div class='{$this->_slug}_main'>";
		$html .= $this->_getCurrent($city);
        $html .= $this->_getForecast();
        $html .= "</div>";
        return $html;
	}

	public function setTempScale($temp_scale = 'f') {
		$this->_temp_scale = $temp_scale;
	}
}
?>