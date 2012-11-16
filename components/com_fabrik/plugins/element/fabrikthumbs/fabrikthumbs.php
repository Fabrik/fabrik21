<?php
/**
 * Plugin element to render thumbs-up/down widget
 * @package fabrikar
 * @author Thomas Spierckel
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikthumbs extends FabrikModelElement {

	var $_pluginName = 'thumbs';

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
		$imagepath = COM_FABRIK_LIVESITE.'components/com_fabrik/plugins/element/fabrikthumbs/images/';
		$data = explode(GROUPSPLITTER, $data);
		$tableid = $this->getTableModel()->getTable()->id;
		$formid = $this->getTableModel()->getTable()->form_id;
		$row_id = $oAllRowsData->__pk_val;
		$str = '';
		for ($i=0; $i <count($data); $i++) {
			JRequest::setVar('rowid', $row_id);
			$myThumb = $this->_getMyThumb($tableid, $formid, $row_id);
			$imagefileup = 'thumb_up_out.gif';
			$imagefiledown = 'thumb_down_out.gif';
			if ($myThumb == 'up') {
				$imagefileup = 'thumb_up_in.gif';
				$imagefiledown = 'thumb_down_out.gif';
			}
			else if ($myThumb == 'down') {
				$imagefileup = 'thumb_up_out.gif';
				$imagefiledown = 'thumb_down_in.gif';
			}
			$count = $this->_renderTableData($data[$i], $oAllRowsData);
			$count = explode(GROUPSPLITTER2, $count);
			$countUp = $count[0];
			$countDown = $count[1];
			$countDiff = $countUp - $countDown;
			$str .= "<span style=\"color:#32d723;\" class=\"counter_up\">$countUp</span><img src=\"$imagepath"."$imagefileup\" style=\"padding:0px 5px 0 1px;\" alt=\"UP\" class=\"thumbup\" id=\"thumbup$row_id\"/>";
			$str .= "<span style=\"color:#f82516;\" class=\"counter_down\">$countDown</span><img src=\"$imagepath"."$imagefiledown\" style=\"padding:0px 5px 0 1px;\" alt=\"DOWN\" class=\"thumbdown\" id=\"thumbdown$row_id\"/>";
			//$str .= "</div>";
			$data[$i] = $str;
		}
		$data = implode(GROUPSPLITTER, $data);


		return parent::renderTableData($data, $oAllRowsData);
	}

	private function _renderTableData($data, $oAllRowsData)
	{
		$params =& $this->getParams();
		$user =& JFactory::getUser();
		$table =& $this->getTableModel()->getTable();
		$tableid = $table->id;
		$formid = $table->form_id;
		$row_id = isset($oAllRowsData->__pk_val) ? $oAllRowsData->__pk_val : $oAllRowsData->id;
		$db =& JFactory::getDBO();
		return $this->getThumbsCount($data, $tableid, $formid, $row_id);
	}

	/**
	 *
	 * @param $tableid int table id
	 * @param $formid int form id
	 * @param $row_id int row id
	 * @return count thumbs-up, count thumbs-down
	 */

	function getThumbsCount($data, $tableid, $formid, $row_id)
	{
		$db =& JFactory::getDBO();
		$elementid = $this->getElement()->id;

		$db->setQuery("SELECT COUNT(thumb) FROM #__fabrik_thumbs WHERE tableid = ".(int)$tableid." AND formid = ".(int)$formid." AND row_id = ".(int)$row_id." AND element_id = ".(int)$elementid." AND thumb = 'up'");
		$resup = $db->loadResult();
		$db->setQuery("SELECT COUNT(thumb) FROM #__fabrik_thumbs WHERE tableid = ".(int)$tableid." AND formid = ".(int)$formid." AND row_id = ".(int)$row_id." AND element_id = ".(int)$elementid." AND thumb = 'down'");
		$resdown = $db->loadResult();
		return $resup.GROUPSPLITTER2.$resdown;
	}

	/**
	 * determines if the element can contain data used in sending receipts, e.g. fabrikfield returns true
	 */

	function isReceiptElement()
	{
		return true;
	}

	/**
	 * draws the form element
	 * @param array data to preopulate element with
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$name 		= $this->getHTMLName($repeatCounter);
		$id             = $this->getHTMLId($repeatCounter);
		$params 	=& $this->getParams();
		if (JRequest::getVar('view') == 'form') {
			return JText::_('PLG_ELEMENT_THUMBS_ONLY_ACCESSIBLE_IN_DETALS_VIEW');
		}
		$element 	=& $this->getElement();

		$value = $this->getValue($data, $repeatCounter);
		$type = ($params->get('password') == "1" ) ?"password" : "text";
		if (isset($this->_elementError) && $this->_elementError != '') {
			$type .= " elementErrorHighlight";
		}
		$imagepath = COM_FABRIK_LIVESITE.'components/com_fabrik/plugins/element/fabrikthumbs/images/';

		$str = "<div id=\"$id"."_div\" class=\"fabrikSubElementContainer\">";
		$tableid = $this->getTableModel()->getTable()->id;
		$formid = JRequest::getInt('fabrik');
		$row_id = JRequest::getInt('rowid');
		if (!isset($oAllRowsData)) {
			$oAllRowsData = new stdClass();
			$oAllRowsData->__pk_val = $row_id;
		}
		$myThumb = $this->_getMyThumb($tableid, $formid, $row_id);
		$imagefileup = 'thumb_up_out.gif';
		$imagefiledown = 'thumb_down_out.gif';
		if ($myThumb == 'up') {
			$imagefileup = 'thumb_up_in.gif';
			$imagefiledown = 'thumb_down_out.gif';
		}
		else if ($myThumb == 'down') {
			$imagefileup = 'thumb_up_out.gif';
			$imagefiledown = 'thumb_down_in.gif';
		}
		$count = $this->_renderTableData($data[$name], $oAllRowsData);
		$count = explode(GROUPSPLITTER2, $count);
		$countUp = $count[0];
		$countDown = $count[1];
		$countDiff = $countUp - $countDown;
		$str .= "<span style='color:#32d723;' id='count_thumbup'>$countUp</span><img src='$imagepath"."$imagefileup' style='padding:0px 5px 0 1px;' alt='UP' id='thumbup'/>";
		$str .= "<span style='color:#f82516;' id='count_thumbdown'>$countDown</span><img src='$imagepath"."$imagefiledown' style='padding:0px 5px 0 1px;' alt='DOWN' id='thumbdown'/>";
		$str .= "<input type=\"hidden\" name=\"$name\" id=\"$id\" value=\"$countDiff\" class=\"$id\" />\n";
		$str .= "</div>";
		return $str;
	}

	/**
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement#storeDatabaseFormat($val, $data)
	 */

	function storeDatabaseFormat($val, $data, $key)
	{
		$params =& $this->getParams();
		$tableid = JRequest::getInt('tableid');
		$formid = JRequest::getInt('fabrik');
		$row_id = JRequest::getInt('rowid');
		if ($params->get('rating-mode') != 'creator-rating') {
			//$val = $this->getRatingAverage($val, $tableid, $formid, $row_id);
		}
		return $val;
	}

	function _getMyThumb($tableid, $formid, $row_id)
	{
		$db =& JFactory::getDBO();
		$elementid = $this->getElement()->id;
		$user =& JFactory::getUser();
		$user_id = $user->get('id');
		if ($user_id == 0) {
			$user_id = $this->getCookieName($tableid, $row_id);
		}
		$db->setQuery("SELECT thumb FROM #__fabrik_thumbs WHERE tableid = ".(int)$tableid." AND formid = ".(int)$formid." AND row_id = ".(int)$row_id." AND element_id = ".(int)$elementid." AND user_id = '$user_id' LIMIT 1");
		$ret = $db->loadResult();

		return $ret;
	}

	/**
	 * called via widget ajax, stores the selected thumb
	 * stores the diff (thumbs-up minus thumbs-down)
	 * return the new count for up and down
	 */

	function ajax_rate()
	{
		$table =& $this->getTableModel()->getTable();
		$tableid = $table->id;
		$formid = $table->form_id;
		$row_id = JRequest::getVar('row_id');
		$thumb = JRequest::getVar('thumb');
		$this->doThumb( $tableid, $formid, $row_id, $thumb);
		echo $this->getThumbsCount('', $tableid, $formid, $row_id);
	}

	private function getCookieName($tableid, $row_id)
	{
		$cookieName =  "thumb-table_{$tableid}_row_{$row_id}_ip_{$_SERVER['REMOTE_ADDR']}";
		jimport('joomla.utilities.utility');
		return JUtility::getHash($cookieName);
	}

	/**
	 * main method to store a rating
	 * @param $tableid
	 * @param $formid
	 * @param $row_id
	 * @param $thumb
	 */

	private function doThumb($tableid, $formid, $row_id, $thumb)
	{
		$db =& JFactory::getDBO();
		$config =& JFactory::getConfig();
		$tzoffset = $config->getValue('config.offset');
		$date =& JFactory::getDate('now', $tzoffset);
		$strDate = $db->Quote($date->toMySQL());

		$user =& JFactory::getUser();
		$userid = (int)$user->get('id');

		if ($userid == 0) {

			$hash = $this->getCookieName($tableid, $row_id);

			//set cookie
			$lifetime = time() + 365*24*60*60;
			setcookie( $hash, '1', $lifetime, '/');
			$userid = $db->Quote($hash);
		}
		$thumb = $db->Quote($thumb);
		$elementid = $this->getElement()->id;
		$db->setQuery("INSERT INTO #__fabrik_thumbs (user_id, tableid, formid, row_id, thumb, date_created, element_id)
		values ($userid, $tableid, $formid, $row_id, $thumb, $strDate, $elementid)
			ON DUPLICATE KEY UPDATE date_created = $strDate, thumb = $thumb");
		$db->query();

		$this->updateDB($tableid, $formid, $row_id, $elementid);

	}

	private function updateDB($tableid, $formid, $row_id, $elementid)
	{
		$db =& JFactory::getDBO();

		$db->setQuery("UPDATE ".$this->getTableModel()->getTable()->db_table_name."
                    SET ".$this->getElement()->name." = ((SELECT COUNT(thumb) FROM #__fabrik_thumbs WHERE tableid = ".(int)$tableid." AND formid = ".(int)$formid." AND row_id = ".(int)$row_id." AND element_id = ".(int)$elementid." AND thumb = 'up') - (SELECT COUNT(thumb) FROM #__fabrik_thumbs WHERE tableid = ".(int)$tableid." AND formid = ".(int)$formid." AND row_id = ".(int)$row_id." AND element_id = ".(int)$elementid." AND thumb = 'down'))
                    WHERE ".$this->getTableModel()->getTable()->db_primary_key." = ".(int)$row_id."
                        LIMIT 1");
		$db->query();

	}


	/**
	 * return tehe javascript to create an instance of the class defined in formJavascriptClass
	 * @param int repeat group counter
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$user =& JFactory::getUser();
		$params =& $this->getParams();
		if (JRequest::getVar('view') == 'form') {
			return;
		}
		$id 			= $this->getHTMLId($repeatCounter);
		$element 	=& $this->getElement();
		$data 		=& $this->_form->_data;
		$tableid = $this->getTableModel()->getTable()->id;
		$formid = JRequest::getInt('fabrik');
		$row_id = JRequest::getInt('rowid');
		$value = $this->getValue($data, $repeatCounter);

		$opts = new stdClass();
		$opts->liveSite 	= COM_FABRIK_LIVESITE;
		$opts->row_id 		= JRequest::getInt('rowid');
		$opts->myThumb 		= $this->_getMyThumb($tableid, $formid, $row_id);
		$opts->elid 		= $this->getElement()->id;
		$opts->userid 		= (int)$user->get('id');
		$opts->mode		= $params->get('rating-mode');
		$opts->view		= JRequest::getCmd('view');
		$opts->splitter2		= GROUPSPLITTER2;
		$opts 			= json_encode($opts);

		$lang 			= new stdClass();
		$lang->norating         = JText::_('NO RATING');
		$lang 			= json_encode($lang);
		$str = "new fbThumbs('$id', $opts, '$value', $lang)";
		return $str;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikthumbs/', true);
	}

	/**
	 * defines the type of database table field that is created to store the element's data
	 */
	function getFieldDescription()
	{
		$p = $this->getParams();
		if ($this->encryptMe()) {
			return 'BLOB';
		}
		switch( $p->get('text_format')) {
			case 'text':
			default:
				$objtype = "VARCHAR(255)";
				break;
			case 'integer':
				$objtype = "INT(" . $p->get('integer_length', 10) . ")";
				break;
			case 'decimal':
				$objtype = "DECIMAL(" . $p->get('integer_length', 10) . "," . $p->get('decimal_length', 2) . ")";
				break;
		}
		return $objtype;
	}

	/**
	 * render the element admin settings
	 * @param object element
	 */

	function renderAdminSettings()
	{
		$params =& $this->getParams();
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php echo $pluginParams->render('params');
	//echo $pluginParams->render('params', 'extra');
	?></div>
	<?php
	}

	function tableJavascriptClass()
	{

		FabrikHelperHTML::script('table-fabrikthumbs.js', 'components/com_fabrik/plugins/element/fabrikthumbs/', false);
	}

	/**
	 * get js to ini js object that manages the behaviour of the thumbs element (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement#elementTableJavascript()
	 */

	function elementTableJavascript()
	{
		$user =& JFactory::getUser();

		$params =& $this->getParams();
		$user =& JFactory::getUser();
		$id = $this->getHTMLId();
		$tableModel =& $this->getTableModel();
		$table =& $tableModel->getTable();
		$formid = $table->form_id;
		$listMyThumbs = array();
		$idFromCookie = NULL;
		$data =& $tableModel->getData();
		$gKeys = array_keys($data);
		foreach ($gKeys as $gKey) {
			$groupedData = $data[$gKey];
			foreach ($groupedData as $rowkey) {
				if (!$idFromCookie && $user->get('id') == 0) {
					$idFromCookie = $this->getCookieName($table->id, $rowkey->__pk_val);
				}
				$listMyThumbs[$rowkey->__pk_val] = $this->_getMyThumb($table->id, $formid, $rowkey->__pk_val);
			}
		}
		if ($user->get('id') == 0) {
			$userid = $idFromCookie;
		}
		else {
			$userid = $user->get('id');
		}
		$opts = new stdClass();

		$opts->tableid      = $table->id;
		$opts->livesite     = COM_FABRIK_LIVESITE;
		$opts->imagepath    = COM_FABRIK_LIVESITE.'components/com_fabrik/plugins/element/fabrikthumbs/images/';
		$opts->elid         = $this->getElement()->id;
		$opts->myThumbs     = $listMyThumbs;
		$opts->userid       = "$userid";
		$opts->splitter2		= GROUPSPLITTER2;
		$opts               = json_encode($opts);
		return "new FbThumbsTable('$id', $opts);\n";
	}

	function includeInSearchAll()
	{
		return false;
	}

	public function filterValueList($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		$usersConfig = &JComponentHelper::getParams('com_fabrik');
		$params =& $this->getParams();
		$filter_build = $params->get('filter_build_method', 0);
		if ($filter_build == 0) {
			$filter_build = $usersConfig->get('filter_build_method');
		}
		if ($filter_build == 2) {
			return $this->filterValueList_All( $normal, $tableName, $label, $id, $incjoin);
		} else {
			return $this->filterValueList_Exact( $normal, $tableName, $label, $id, $incjoin);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement#filterValueList_All($normal, $tableName, $label, $id, $incjoin)
	 */
	protected function filterValueList_All($normal, $tableName = '', $label = '', $id = '', $incjoin = true)
	{
		for ($i=0; $i<6; $i++) {
			$return[] = JHTML::_('select.option', $i);
		}
		return $return;
	}
}
?>