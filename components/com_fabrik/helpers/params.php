<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

/* MOS Intruder Alerts */
defined('_JEXEC') or die();
/*
 * Created on 10-Nov-2005
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
jimport('joomla.html.parameter');


class fabrikParams extends JParameter
{

	/** @var bol duplicatable param (if true add []" to end of element name)*/
	var $_duplicate = false;

	var $_splitter_2 = '$$..*..$$';

	/** used by form plugins - to set id in name of radio buttons **/
	var $_counter_override = null;
	/**
	 * constructor
	 */

	function __construct($data, $path = '')
	{
		$this->_identifier = str_replace("\\", "-", str_replace(".xml", "", str_replace(JPATH_SITE, '', $path)));
		$this->_identifier = str_replace('/', '-', $this->_identifier);
		parent::__construct($data, $path);
	}

	/**
	 * when objs are saved we convert the params data into the attribs field
	 * @param array parameter settings
	 */

	function updateAttribsFromParams($params)
	{
		if (is_array($params)) {
			$txt = array();
			foreach ($params as $k => $v) {
				$deep = 0;
				if (is_array($v)) {
					$str = '';
					foreach ($v as $key2=>$val2) {
						if (is_array($val2)) {
							foreach ($val2 as $key3 => $val3) {
								$deep = 1;
								$str .= $val3 . $this->_splitter_2;
							}

							$str =  substr($str, 0, - strlen($this->_splitter_2)) . GROUPSPLITTER;
						} else {


							$str .= "$val2" . GROUPSPLITTER;
						}
					}
					$v =  substr($str, 0, - strlen(GROUPSPLITTER));
				}
				if ($deep) {
					$v =  $v . GROUPSPLITTER;
				}
				$v = str_replace("\n", "", $v);
				// $$$ hugh - this is already done in J!'s getVar().  If we do it again here
				// we screw up things like regex rules!
				/*
				if (get_magic_quotes_gpc()) {
					$v = stripslashes($v);
				}
				*/
				//backslash any "|"'s in the data (otherwise the ini.php format thinks you are storing an array
				$v = preg_replace('#(?<!\\\)\|#', '\|', $v);
				$txt[] = "$k=$v";
			}
			$this->attribs = implode("\n", $txt);
		}
		return $this->attribs;
	}


	/**
	 * Get the names of all the parameters in the object
	 * @access private
	 * @return array parameter names
	 */

	function _getParamNames()
	{
		$p = array();
		$default = (object)$this->_xml['_default'];
		if (empty($default)) {
			return $p;
		}
		if (!method_exists($default, 'children')) {
			return $p;
		}
		foreach ($default->children() as $node)  {
			$p[] = $node->attributes('name');
		}
		return $p;
	}

	/**
	 * overwrite core get function so we can decode our encoded ","'s
	 * @param string key
	 * @param string default
	 * @param string group
	 * @param string output format (string or array)
	 * @param int counter - not used i think
	 * @return mixed - string or array
	 */

	function get($key, $default='', $group = '_default', $outputFormat = 'string', $counter = null)
	{
		$group = isset($this->_fb_group) ? $this->_fb_group : '_default';
		$value = $this->getValue($group.'.'.$key);
		$result = (empty($value) && ($value !== 0) && ($value !== '0')) ? $default : $value;
		if ($outputFormat == 'array' && is_array($result) && empty($result)) {
			return array('');//test to ensure new calendar repeat params are rendered
		}

		if (is_string($result) && strstr($result, GROUPSPLITTER)) {

			$aReturn = array();
			$bothsplitters =  strstr($result, GROUPSPLITTER) && strstr($result, $this->_splitter_2);
			$result = explode(GROUPSPLITTER, $result);

			//	if in "|..*..|17|//*//|167|..*..|" format (e.g. chart_elements)
			//then remove first and last record from array
			if ($bothsplitters && $result[0] == '') {
				unset($result[0]);
			}
			if (@$result[count($result)-1] == '') {
				unset($result[count($result)-1]);
			}

			foreach ($result as $res) {
				if(strstr($res, $this->_splitter_2)) {
					$aReturn[] = explode($this->_splitter_2, $res);
				} else {
					$aReturn[] = $res;
				}
			}
			return $aReturn;
		} else {
			if ($outputFormat == 'array') {
				if ($result == '') {
					return array();
				} else {
					//return array($result);
					//test!
					return array((int)$counter => $result);
				}
			}
			return $result;
		}

		///////////////////////////////////

		$value = $this->getValue($group.'.'.$key);
		$result = (empty($value) && ($value !== 0) && ($value !== '0')) ? $default : $value;
		return $result;
	}
	/**
	 * (non-PHPdoc)
	 * @see libraries/joomla/html/JParameter#getParams($name, $group)
	 */

	function getParams($name = 'params', $group = '_default', $ouputformat = 'string', $counter = null)
	{
		if (!isset($this->_xml[$group])) {
			return false;
		}

		$results = array();
		foreach ($this->_xml[$group]->children() as $param)  {
			$results[] = $this->getParam( $param, $name, $group, $ouputformat, $counter);
		}
		return $results;
	}

	/**
	 * get a groups parameters names
	 * @param unknown_type $name
	 * @param unknown_type $group
	 * @return string|multitype:
	 */
	function getParamsNames($name = 'params', $group = '_default')
	{
		if (!isset($this->_xml[$group])) {
			return false;
		}
		$results = array();
		foreach ($this->_xml[$group]->children() as $node)  {
			$results[] = $node->attributes('name');
		}
		return $results;
	}

	/**
	 * Render a parameter type
	 *
	 * @param	object A param tag node
	 * @param	string The control name
	 * @param string parameter group
	 * @param string output format
	 * @param mixed repeat group counter??? /how about repeating plugins is this the same??
	 * @return	array Any array of the label, the form element and the tooltip
	 * @since	1.5
	 */

	function getParam(&$node, $control_name = 'params', $group = '_default', $outPutFormat ='string', $counter = null)
	{
		//get the type of the parameter
		$type = $node->attributes('type');

		//remove any occurance of a mos_ prefix
		$type = str_replace('mos_', '', $type);

		$element =& $this->loadElement($type);

		// error happened
		if ($element === false)
		{
			$result = array();
			$result[0] = $node->attributes('name');
			$result[1] = JText::_('ELEMENT NOT DEFINED FOR TYPE').' = '.$type;
			$result[5] = $result[0];
			return $result;
		}

		//get value


		if ($outPutFormat == 'array' && !is_null($counter)) {
			$nodeName = str_replace("[]", "",$node->attributes('name'));
		} else {
			$nodeName = $node->attributes('name');
		}
		//end test

		$value = $this->get($nodeName, $node->attributes('default'), $group, $outPutFormat, $counter);

		if ($outPutFormat == 'array' && !is_null($counter)) {
			$value = JArrayHelper::getValue($value, $counter, '');
		}
		//value must be a string
		$element->_array_counter = $counter;

		$result = $element->render($node, $value, $control_name);

		$reqParamName = $result[5];

		if ($this->_duplicate) { //_duplicate property set in view pages
			if ($type == 'radio') {

				//otherwise only a single entry is recorded no matter how many duplicates we make
				if ($counter == 0 && isset($this->_counter_override)) {
					$counter = $this->_counter_override;
				}
				$replacewith = "[$reqParamName][$counter][]";
			} else {
				$replacewith = "[$reqParamName][]";
			}
			$result[1] = str_replace("[$reqParamName]", $replacewith, $result[1]);
		}

		return $result;
	}

	/**
	 * Render
	 *
	 * @access	public
	 * @param	string	The name of the control, or the default text area if a setup file is not found
	 * @param string group
	 * @param bol write out or return
	 * @param int if set and group is repeat only return int row from rendered params
	 * used for form plugin admin pages.
	 * @return	string	HTML
	 *
	 * NOTE when rendering admin settings I *think* the repeat group is set with $this->_counter_override

	 * @since	1.5
	 */
	function render($name = 'params', $group = '_default', $write = true, $repeatSingleVal = null)
	{
		$return = '';
		$this->_group = $group;
		//$$$rob experimental again
		//problem - when rendering plugin params - e.g. calendar vis - params like the table drop down
		// are repeated n times. I think the best way to deal with this is to get the data recorded for
		// the viz and udpate this objects _xml array duplicate the relavent JSimpleXMLElement Objects
		// for the required number of table drop downs
		//echo " $name : $group <br>";

		$repeat = false;
		$repeatControls = true;
		$repeatMin = 0;
		if (is_array($this->_xml)) {
			if (array_key_exists($group, $this->_xml)) {
				$repeat = $this->_xml[$group]->attributes('repeat');
				$repeatMin = (int)$this->_xml[$group]->attributes('repeatmin');
				$repeatControls = $this->_xml[$group]->attributes('repeatcontrols');
			}
		}
		if ($repeat) {
			//get the name of the first element in the group
			$children = $this->_xml[$group]->children();
			if (empty($children)) {
				$firstElName = '';
				$allParamData = '';
				$value = '';
			} else {
				$firstElName = str_replace("[]", "", $children[0]->attributes('name'));

				$allParamData = $this->_registry['_default']['data'];

				$value = $this->get($firstElName, array(), $group, 'array');
			}


			$c = 0;

			//limit the number of groups of repeated params written out
			if (!is_null($repeatSingleVal) && is_int($repeatSingleVal)) {
				$total = $repeatSingleVal + 1;
				$start = $repeatSingleVal;
			} else {
				$total = count($value);
				$start = 0;
			}

			$identifier = $this->_identifier . '-'.$group;
			$return .= '<div id="container'.$identifier.'">';
				//add in the 'add' button to duplicate the group
			//only show for first added group
			if ($repeatControls && $repeatSingleVal == 0) {
				$return .= "<a href='#' class='addButton'>" . JText::_('ADD') . "</a>";
			}
			for ($x=$start; $x<$total; $x++) {
				//call render for the number of time the group is repeated
				//echo parent::render($name, $group);

				$return .= '<div class="repeatGroup" id="'.$identifier . 'group-'.$x.'">';
				////new
				//$this->_counter_override = $x;
				$params =& $this->getParams($name, $group, 'array', $x);

				$html = array();
				$html[] = '<table width="100%" class="paramlist admintable" cellspacing="1">';

				if ($description = $this->_xml[$group]->attributes('description')) {
					// add the params description to the display
					$desc	= JText::_($description);
					$html[]	= '<tr><td class="paramlist_description" colspan="2">'.$desc.'</td></tr>';
				}
				foreach ($params as $param)
				{
					$html[] = '<tr>';

					if ($param[0]) {
						$html[] = '<td width="40%" class="paramlist_key"><span class="editlinktip">'.$param[0].'</span></td>';
						$html[] = '<td class="paramlist_value">'.$param[1].'</td>';
					} else {
						$html[] = '<td class="paramlist_value" colspan="2">'.$param[1].'</td>';
					}

					$html[] = '</tr>';
				}

				if (count($params ) < 1) {
					$html[] = "<tr><td colspan=\"2\"><i>".JText::_('THERE ARE NO PARAMETERS FOR THIS ITEM')."</i></td></tr>";
				}

				$html[] = '</table>';
				if ($repeatControls) {
					$html[]= "<a href='#' class=\"removeButton delete\">" . JText::_('DELETE') . "</a>";
				}
				$return .= implode("\n", $html);

				///end new
				$c ++;
				$return .= "</div>";
			}
			$return .= "</div>";
		} else {
			$return .= parent::render($name, $group);
		}

		if ($repeat && $repeatControls && ($repeatSingleVal == null || $repeatSingleVal == 0)) {
			FabrikHelperHTML::script('params.js', 'components/com_fabrik/libs/');
			// watch add and remove buttons
			$document =& JFactory::getDocument();
			$script = "window.addEvent('domready', function() {
			 new RepeatParams('container{$identifier}', {repeatMin:$repeatMin});
	});";
			FabrikHelperHTML::addScriptDeclaration($script);
		}
		if ($write) {
			echo $return;
		} else {
			return $return;
		}
	}

}
?>