<?php

/**
 * A cron task to email records to a give set of users
 * @package Joomla
 * @subpackage Fabrik
 * @author Hugh Messenger
 * @copyright (C) Hugh Messenger
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');
require_once(COM_FABRIK_FRONTEND.DS.'plugins'.DS.'cron'.DS.'geocode'.DS.'libs'.DS.'gmaps.php');


class FabrikModelGeocode extends fabrikModelPlugin {

	var $_counter = null;

	var $log = array();

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function requiresTableData() {
		/* we don't need cron to load $data for us */
		return false;
	}

	function canUse()
	{
		return true;
	}

	/**
	 * do the plugin action
	 *
	 */
	function process(&$data, &$tableModel)
	{
		$params =& $this->getParams();

		// grab the table model and find table name and PK
		$table =& $tableModel->getTable();
		$table_name 		= $table->db_table_name;
		$primary_key = $tableModel->_shortKey();
		$primary_key_element = str_replace("`","",$primary_key);

		// for now, we have to read the table ourselves.  We can't rely on the $data passed to us
		// because it can be arbitrarily filtered according to who happened to hit the page when cron
		// needed to run.

		// FIXME - this won't work if any of the required elements are in joined data,
		// or the table is not on the default site connection!
		// So instead of doing this, we need to add an option to the main cron run code, which can
		// turn off pre-filters.

		$mydata = array();
		$db = $tableModel->getDb();
		$db->setQuery("SELECT * FROM $table_name");
		$mydata[0] = $db->loadObjectList();
		if (empty($mydata[0])) {
			$this->log[] = "No records found in table: $table_name\n";
			return;
		}

		// grab all the params, like GMaps key, field names to use, etc
		$geocode_gmap_key = $params->get('geocode_gmap_key');
		$geocode_is_empty = $params->get('geocode_is_empty');
		$geocode_zoom_level = $params->get('geocode_zoom_level', '4');
		$geocode_map_element_long = $params->get('geocode_map_element');
		$default = $params->get('geocode_default');
		$geocode_map_element = FabrikString::shortColName($geocode_map_element_long);
		$geocode_addr1_element_long = $params->get('geocode_addr1_element');
		$geocode_addr1_element = $geocode_addr1_element_long ? FabrikString::shortColName($geocode_addr1_element_long) : '';
		$geocode_addr2_element_long = $params->get('geocode_addr2_element');
		$geocode_addr2_element = $geocode_addr2_element_long ? FabrikString::shortColName($geocode_addr2_element_long) : '';
		$geocode_city_element_long = $params->get('geocode_city_element');
		$geocode_city_element = $geocode_city_element_long ? FabrikString::shortColName($geocode_city_element_long) : '';
		$geocode_state_element_long = $params->get('geocode_state_element');
		$geocode_state_element = $geocode_state_element_long ? FabrikString::shortColName($geocode_state_element_long) : '';
		$geocode_zip_element_long = $params->get('geocode_zip_element');
		$geocode_zip_element = $geocode_zip_element_long ? FabrikString::shortColName($geocode_zip_element_long) : '';
		$geocode_country_element_long = $params->get('geocode_country_userid_element');
		$geocode_country_element = $geocode_country_element_long ? FabrikString::shortColName($geocode_country_element_long) : '';
		$verbose = (int)$params->get('geocode_verbose_log', 0);

		$app =& JFactory::getApplication();
		// sanity check, make sure required elements have been specified
		if (empty($geocode_gmap_key)) {
			$msg = "no Google Maps API key given!";
			if ($app->isAdmin()) {
				$app->enqueueMessage($msg);
			}
			$this->log[] = $msg . "\n";
			return;
		}
		$gmap = new GMaps($geocode_gmap_key);

		$delay = (int)$params->get('geo_code_delay', 0);
		$max = (int)$params->get('geo_code_batch', 0);
		$counter = 0;
		// run through our table data
		$total_encoded = 0;
		$total_unfound = 0;
		$total_emtypy = 0;
		$total_not_empty = 0;
		$gkeys = array_keys($mydata);
		foreach ($gkeys as $x) {
			$group = $mydata[$x];
			if (is_array($group)) {
				$rkeys = array_keys($group);
				foreach ($rkeys as $y) {
					if ($max != 0 && $counter > $max) {
						break 2;
					}
					$row = $group[$y];
					$map_value = "($default):$geocode_zoom_level";
					// see if the map element is considered empty
					if (empty($row->$geocode_map_element) || $row->$geocode_map_element == $geocode_is_empty) {
						// it's empty, so lets try and geocode.
						// first, construct the address
						// we'll build an array of address components, which we'll explode into a string later
						$a_full_addr = array();
						// for each address component element, see if one is specific in the params,
						// if so, see if it has a value in this row
						// if so, add it to the address array.
						if ($geocode_addr1_element) {
							if ($row->$geocode_addr1_element) {
								$a_full_addr[] = $row->$geocode_addr1_element;
							}
						}
						if ($geocode_addr2_element) {
							if ($row->$geocode_addr2_element) {
								$a_full_addr[] = $row->$geocode_addr2_element;
							}
						}
						if ($geocode_city_element) {
							if ($row->$geocode_city_element) {
								$a_full_addr[] = $row->$geocode_city_element;
							}
						}
						if ($geocode_state_element) {
							if ($row->$geocode_state_element) {
								$a_full_addr[] = $row->$geocode_state_element;
							}
						}
						if ($geocode_zip_element) {
							if ($row->$geocode_zip_element) {
								$a_full_addr[] = $row->$geocode_zip_element;
							}
						}
						if ($geocode_country_element) {
							if ($row->$geocode_country_element) {
								$a_full_addr[] = $row->$geocode_zip_element;
							}
						}
						// now explode the address into a string
						$full_addr = implode(',',$a_full_addr);
						// Did we actually get an address?
						if (!empty($full_addr)) {
							// OK!  Lets try and geocode it ...
							if ($gmap->getInfoLocation($full_addr)) {
								$lat = $gmap->getLatitude();
								$long = $gmap->getLongitude();
								if (!empty($lat) && !empty($long)) {
									$map_value = "($lat,$long):$geocode_zoom_level";
									$total_encoded++;
								} else {
									$total_unfound ++;
									$this->log[] = "record unfound: " . $row->$primary_key_element . "\n";
									continue;
								}
							} else {
								$total_unfound ++;
								$this->log[] = "record unfound: " . $row->$primary_key_element . "\n";
								continue;
							}
							if ($gmap->errcode == '620') {
								//sent too fast
								$delay += 100000;
								$this->log[] = "Google API error 620, increasing delay\n";
							}
							if (!empty($gmap->err)) {
								$this->log[] = "Google API error: " . $gmap->err . "\n";
								continue;
							}
						} else {
							$total_emtypy ++;
							$this->log[] = "No address data for row: " . $row->$primary_key_element . "\n";
							continue;
						}
						$db->setQuery("
										UPDATE $table_name
										SET $geocode_map_element = ".$db->Quote($map_value)."
										WHERE $primary_key = ".$db->Quote($row->$primary_key_element));
						$db->query();
						if ($verbose) {
							$this->log[] = "Updated row {$row->$primary_key_element}: $map_value\n";
						}
					}
					else {
						$total_not_empty++;
						if ($verbose) {
							$this->log[] = "Map element not considered empty for row {$row->$primary_key_element}: {$row->$geocode_map_element}\n";
						}
					}
					//
					$counter ++;
					usleep($delay);
				}
			}
		}

		$this->log[] = $msg = "encoded: $total_encoded; unfound: $total_unfound; empty addresses: $total_emtypy; not empty map element: $total_not_empty";
		if ($app->isAdmin()) {
			$app->enqueueMessage($msg);
		}
		return $total_encoded + $total_unfound + $total_emtypy;
	}

	/**
	 * show a new for entering the form actions options
	 */

	function renderAdminSettings()
	{
		//JHTML::stylesheet('fabrikadmin.css', 'administrator/components/com_fabrik/views/');
		$this->getRow();
		$pluginParams =& $this->getParams();

		$document =& JFactory::getDocument();
		?>
<div id="page-<?php echo $this->_name;?>" class="pluginSettings"
	style="display: none"><b>NOTES</b>
<ul>
	<li>You can either run this as a scheduled task, or use it as a one-off
	import script for new data (by simply not selecting a scheduled run
	time, and using the Run button by hand)</li>
	<li>You don't need to specify all the geocoding elements (addr1, addr2,
	city, etc), but whatever you do select should build a valid address,
	when concatenated (in order) into one comma separated string. The
	simplest case would be a single element which has the entire address
	already comma separated. Or you can match some or all of the address
	components to you form elements.</li>
	<li>The 'Empty Value' can be used where (for example) you have Fabrik
	map elements which have been submitted without the marker being placed,
	so they will have the default lat/long and zoom level, like
	"53.2224,-4.2007:4". Setting the "Empty Value" to this will cause this
	script to treat both empty map elements AND ones which have that
	default string as being in need of encoding.</li>
</ul>
		<?php
		// @TODO - work out why the language diddly doesn't work here, so we can make the above notes translateable?
		//echo JText::_('GCALNOTES');
		echo $pluginParams->render('params');
		echo $pluginParams->render('params', 'fields');
		?></div>
		<?php
		return;
	}

	/**
	 * used in cron plugins
	 */

	function getLog()
	{
		return implode("\n", $this->log);
	}

}
?>