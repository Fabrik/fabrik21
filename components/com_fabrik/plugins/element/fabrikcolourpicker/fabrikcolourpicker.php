<?php
/**
* Plugin element to render colour picker
* @package fabrikar
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikColourPicker  extends FabrikModelElement {

	var $_pluginName = 'colourpicker';

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
		if (strstr($data, GROUPSPLITTER)) {
			$data = explode(GROUPSPLITTER, $data);
			$str = '';
			foreach ($data as $d) {
				$str .= "<div style='width:15px;height:15px;background-color:rgb($d)'></div>";
			}
			return $str;
		}
		return "<div style='width:15px;height:15px;background-color:rgb($data)'></div>";
	}

	/**
	 * formats the posted data for insertion into the database
	 * @param mixed thie elements posted form data
	 * @param array posted form data
	 */

	function storeDatabaseFormat($val, $data)
	{
		$val =  parent::storeDatabaseFormat($val, $data);
		return $val;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('colour-picker.js', 'components/com_fabrik/plugins/element/fabrikcolourpicker/', true);
	}

	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @param int group repeat counter
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		if (!$this->_editable) {
			return;
		}
		$params = $this->getParams();
		$element =& $this->getElement();
		$id = $this->getHTMLId($repeatCounter);
		$data =& $this->_form->_data;
		$value = $this->getValue($data, $repeatCounter);
		$vars = explode(",", $value);
		$vars = array_pad( $vars, 3, 0);
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$c = new stdClass();
		// 14/06/2011 changed over to color param object from ind colour settings
		$c->red = (int)$vars[0];
		$c->green = (int)$vars[1];
		$c->blue = (int)$vars[2];
		$opts->colour = $c;
		$swatch = $params->get('colourpicker-swatch', 'default.js');
		$swatchFile = COM_FABRIK_FRONTEND.DS.'plugins'.DS.'element'.DS.'fabrikcolourpicker'.DS.'swatches'.DS.$swatch;
		$opts->swatch = json_decode(JFile::read($swatchFile));
		$opts = json_encode($opts);
		return "new ColourPicker('$id', $opts)";
	}

	/**
	 * draws the form element
	 * @param array row data
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name = $this->getHTMLName($repeatCounter);
		$id 	= $this->getHTMLId($repeatCounter);
		$trackImage  = COM_FABRIK_LIVESITE . 'components/com_fabrik/plugins/element/fabrikcolourpicker/images/track.gif';
		$handleImage = COM_FABRIK_LIVESITE . 'components/com_fabrik/plugins/element/fabrikcolourpicker/images/handle.gif';
		$value = $this->getValue($data, $repeatCounter);
		$str 	= "<div class=\"fabrikSubElementContainer\">";
		$str .= '<input type="hidden" name="' .$name . '" id="'.$id.'" /><div class="colourpicker_bgoutput" style="float:left;width:20px;height:20px;border:1px solid #333333;background-color:rgb('.$value.')"></div>';
		if ($this->_editable) {
			$str .= '<div class="colourPickerBackground colourpicker-widget" style="color:#000;z-index:99999;left:200px;background-color:#EEEEEE;border:1px solid #333333;width:390px;padding:0 0 5px 0;"></div>';
		}
		$str .= "</div>";
		return $str;
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
		return "VARCHAR(30)";
	}



}
?>
