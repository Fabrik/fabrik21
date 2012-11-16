<?php
/**
 * Plugin element to render rating widget
 * @package fabrikar
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'element.php');

class FabrikModelFabrikrating extends FabrikModelElement {

	var $_pluginName = 'rating';

	/** @var array average ratings */
	var $avgs = null;

	/** @bool can the rating element be used by the current user*/
	var $canRate = null;

	/** @var array creator id */
	var $creatorIds = null;

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
		$user =& JFactory::getUser();
		$params =& $this->getParams();
		$ext = $params->get('rating-pngorgif', '.png');
		$imagepath = COM_FABRIK_LIVESITE.'components/com_fabrik/plugins/element/fabrikrating/images/';
		$data = explode(GROUPSPLITTER, $data);
		$url = COM_FABRIK_LIVESITE.'index.php?option=com_fabrik&amp;format=raw&amp;controller=plugin&amp;task=pluginAjax&amp;g=element&amp;plugin=fabrikrating&amp;method=ajax_rate&amp;element_id='.$this->getElement()->id;

		$url .= '&amp;row_id='.$oAllRowsData->__pk_val;
		$url .= '&amp;elementname='.$this->getElement()->id;
		$url .= '&amp;userid='.$user->get('id');
		$url .= '&amp;nonajax=1';
		$row_id = isset($oAllRowsData->__pk_val) ? $oAllRowsData->__pk_val : $oAllRowsData->id;
		$ids = JArrayHelper::getColumn($this->getTableModel()->getData(), '__pk_val');
		$canRate = $this->canRate($row_id, $ids);
		for ($i=0; $i <count($data); $i++) {
			$avg = $this->_renderTableData($data[$i], $oAllRowsData);
			if (!$canRate) {
				$atpl = '';
				$a2 = '';
			} else {
				$atpl = "<a href=\"{$url}&rating={r}\">";
				$a2 = "</a>";
			}
			$str = '<div style="width:100px">';
			for ($s=0; $s<$avg; $s++) {
				$r = $s+1;
				$a = str_replace('{r}', $r, $atpl);
				$str .= "$a<img src=\"$imagepath"."star_in$ext\" style=\"padding-left:1px;\" alt=\"$r\" class=\"starRating rate_$r\"/>$a2";
			}
			for ($s=$avg; $s<5; $s++) {
				$r = $s+1;
				$a = str_replace('{r}', $r, $atpl);
				$str .= "$a<img src=\"$imagepath"."star_out$ext\" style=\"padding-left:1px;\" alt=\"$r\" class=\"starRating rate_$r\"/>$a2";
			}
			if ($params->get('rating-mode') != 'creator-rating') {
				$str .= "<div class=\"ratingMessage\">$avg</div>";
			}
			$str .= '</div>';
			$data[$i] = $str;
		}
		$data = implode(GROUPSPLITTER, $data);


		return parent::renderTableData($data, $oAllRowsData);
	}

	private function _renderTableData($data, $oAllRowsData)
	{
		$params =& $this->getParams();
		if ($params->get('rating-mode') == 'creator-rating') {
			return $data;
		} else {
			$list =& $this->getTableModel()->getTable();
			$listid = $list->id;
			$formid = $list->form_id;
			$ids = JArrayHelper::getColumn($this->getTableModel()->getData(), '__pk_val');
			$row_id = isset($oAllRowsData->__pk_val) ? $oAllRowsData->__pk_val : $oAllRowsData->id;
			list($avg, $total) = $this->getRatingAverage($data, $listid, $formid, $row_id, $ids);
			return $avg;
		}
	}

	/**
	 * @param $data string/int
	 * @param $tableid int table id
	 * @param $formid int form id
	 * @param $row_id int row id
	 * @param $ids array all row ids
	 * @return array(int average rating, int total)
	 */

	function getRatingAverage($data, $listid, $formid, $row_id, $ids = array())
	{
		if (empty($ids)) {
			$ids[] = $row_id;
		}
		if (!isset($this->avgs)) {
			JArrayHelper::toInteger($ids);
			$db =& FabrikWorker::getDbo();
			$elementid = $this->getElement()->id;
			// do this  query so that table view only needs one query to load up all ratings
			$query = "SELECT row_id, AVG(rating) AS r, COUNT(rating) AS total FROM #__fabrik_ratings WHERE rating <> -1 AND tableid = ".(int)$listid." AND formid = ".(int)$formid." AND element_id = ".(int)$elementid;
			$query .= " AND row_id IN (".implode(',', $ids) .") GROUP BY row_id";
			$db->setQuery($query);
			$this->avgs = $db->loadObjectList('row_id');
		}
		$params = $this->getParams();
		$r = array_key_exists($row_id, $this->avgs) ? $this->avgs[$row_id]->r : 0;
		$t = array_key_exists($row_id, $this->avgs) ? $this->avgs[$row_id]->total : 0;
		$float = (int)$params->get('rating_float', 0);
		$this->avg = number_format($r, $float);
		return array(round($r), $t);
	}

	/**
	 * Enter description here ...
	 * @param unknown_type $tableid
	 * @param unknown_type $formid
	 * @param unknown_type $row_id
	 * @param unknown_type $ids
	 */

	protected function getCreatorId($listid, $formid, $row_id, $ids = array())
	{
		if (!isset($this->creatorIds)) {
			if (empty($ids)) {
				$ids[] = $row_id;
			}
			JArrayHelper::toInteger($ids);
			$db = FabrikWorker::getDbo();
			$elementid = $this->getElement()->id;
			// do this  query so that table view only needs one query to load up all ratings
			$query = "SELECT row_id, user_id FROM #__fabrik_ratings WHERE rating <> -1 AND tableid = ".(int)$listid." AND formid = ".(int)$formid." AND element_id = ".(int)$elementid;
			$query .= " AND row_id IN (".implode(',', $ids) .") GROUP BY row_id";
			$db->setQuery($query);
			$this->creatorIds = $db->loadObjectList('row_id');
		}
		return array_key_exists($row_id, $this->creatorIds) ? $this->creatorIds[$row_id]->user_id : 0;
	}

	/**
	 * determines if the element can contain data used in sending receipts, e.g. fabrikfield returns true
	 */

	function isReceiptElement()
	{
		return true;
	}

	protected function canRate($row_id = null, $ids = array())
	{
		//if (!isset($this->canRate)) {
			$params =& $this->getParams();
			if ($params->get('rating-mode') == 'user-rating') {
				$this->canRate = true;
				return true;
			}
			if (is_null($row_id)) {
				$row_id = JRequest::getInt('rowid');
			}
			$list = $this->getTableModel()->getTable();
			$listid = $list->id;
			$formid = $list->form_id;
			$creatorid = $this->getCreatorId($listid, $formid, $row_id, $ids);
			$userid = $this->getStoreUserId($listid, $row_id);
			$this->canRate = $creatorid == $userid;
		//}
		return $this->canRate;
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
		$id				= $this->getHTMLId($repeatCounter);
		$params 	=& $this->getParams();
		if (JRequest::getVar('view') == 'form' && $params->get('rating-rate-in-form', true) == 0) {
			return JText::_('ONLY_ACCESSIBLE_IN_DETALS_VIEW');
		}
		$ext = $params->get('rating-pngorgif', '.png');
		$element =& $this->getElement();
		$css = $this->canRate() ? 'cursor:pointer;' : '';
		$value = $this->getValue($data, $repeatCounter);
		$imagepath = JUri::root().'/components/com_fabrik/plugins/element/fabrikrating/images/';

		$str = "<div id=\"$id"."_div\" class=\"fabrikSubElementContainer\">";
		if ($params->get('rating-nonefirst') && $this->canRate()) {
			$str .= "<img src=\"$imagepath"."clear_rating_out$ext\" style=\"".$css."padding:3px;\" alt=\"clear\" class=\"rate_-1\" />";
		}
		$listid = $this->getTableModel()->getTable()->id;
		$formid = JRequest::getInt('fabrik');
		$row_id = JRequest::getInt('rowid');
		if ($params->get('rating-mode') == 'creator-rating') {
			$avg = $value;
			$this->avg = $value;
		} else {
			list($avg, $total) = $this->getRatingAverage($value, $listid, $formid, $row_id);
		}
		for ($s = 0; $s<$avg; $s++) {
			$r = $s+1;
			$str .= "<img src=\"$imagepath"."star_in$ext\" style=\"".$css."padding:3px;\" alt=\"$r\" class=\"starRating rate_$r\" />";
		}
		for ($s = $avg; $s<5; $s++) {
			$r = $s+1;
			$str .= "<img src=\"$imagepath"."star_out$ext\" style=\"".$css."padding:3px;\" alt=\"$r\" class=\"starRating rate_$r\" />";
		}

		if (!$params->get('rating-nonefirst')) {
			$str .= "<img src=\"$imagepath"."clear_rating_out$ext\" style=\"".$css."padding:3px;\" alt=\"clear\" class=\"rate_-1\" />";
		}
		$str .= "<span class=\"ratingScore\">$this->avg</span>";
		$str .= "<div class=\"ratingMessage\">";
		//$str .= $this->canRate() ? JText::_('NO RATING') : '';
		$str .= "</div>";
		$str .= "<input type=\"hidden\" name=\"$name\" id=\"$id\" value=\"$value\" />\n";
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
		$listid = JRequest::getInt('tableid');
		$formid = JRequest::getInt('fabrik');
		$row_id = JRequest::getInt('rowid');
		if ($params->get('rating-mode') == 'user-rating') {
			list($val, $total) = $this->getRatingAverage($val, $listid, $formid, $row_id);
		}
		return $val;
	}

	/**
	 * called via widget ajax, stores the selected rating and returns the average
	 */

	function ajax_rate()
	{
		$tableModel =& $this->getTableModel();
		$table = $tableModel->getTable();
		$listid = $table->id;
		$formid = $table->form_id;
		$row_id = JRequest::getVar('row_id');
		$rating = JRequest::getInt('rating');
		$this->doRating($listid, $formid, $row_id, $rating);

		if (JRequest::getVar('mode') == 'creator-rating') {
			// @todo FIX for joins as well
			//store in elements table as well
			$db =& $tableModel->getDb();
			$element =& $this->getElement();
			$db->setQuery("UPDATE $table->db_table_name SET $element->name = $rating WHERE $table->db_primary_key = " . $db->Quote($row_id));
			$db->query();
		}
		$this->getRatingAverage('', $listid, $formid, $row_id);
		echo $this->avg;
	}

	private function getCookieName($listid, $row_id)
	{
		$cookieName =  "rating-table_{$listid}_row_{$row_id}".$_SERVER['REMOTE_ADDR'];
		jimport('joomla.utilities.utility');
		return JUtility::getHash($cookieName);
	}

	/**
	 * main method to store a rating
	 * @param $listid
	 * @param $formid
	 * @param $row_id
	 * @param $rating
	 */

	private function doRating($listid, $formid, $row_id, $rating)
	{
		$db =& FabrikWorker::getDbo();
		$config =& JFactory::getConfig();
		$tzoffset = $config->getValue('config.offset');
		$date =& JFactory::getDate('now', $tzoffset);
		$strDate = $db->Quote($date->toMySQL());
		$userid = $db->Quote($this->getStoreUserId($listid, $row_id));
		$elementid = $this->getElement()->id;
		$db->setQuery("INSERT INTO #__fabrik_ratings (user_id, tableid, formid, row_id, rating, date_created, element_id)
		values ($userid, $listid, $formid, $row_id, $rating, $strDate, $elementid)
			ON DUPLICATE KEY UPDATE date_created = $strDate, rating = $rating");
		$db->query();
	}

	private function getStoreUserId($listid, $row_id)
	{
		$user =& JFactory::getUser();
		$userid = (int)$user->get('id');
		if ($userid === 0) {
			$hash = $this->getCookieName($listid, $row_id);
			//set cookie
			$lifetime = time() + 365*24*60*60;
			setcookie($hash, '1', $lifetime, '/');
			$userid = $hash;
		}
		return $userid;
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
		if (JRequest::getVar('view') == 'form' && $params->get('rating-rate-in-form', true) == 0) {
			return;
		}
		$id 			= $this->getHTMLId($repeatCounter);
		$element 	=& $this->getElement();
		$data 		=& $this->_form->_data;
		$listid = $this->getTableModel()->getTable()->id;
		$formid = JRequest::getInt('fabrik');
		$row_id = JRequest::getInt('rowid');
		$value = $this->getValue($data, $repeatCounter);
		if ($params->get('rating-mode') != 'creator-rating') {
			list($value, $total) = $this->getRatingAverage($value, $listid, $formid, $row_id);
		}
		$opts = new stdClass();
		$opts->ext 				= $params->get('rating-pngorgif', '.png');
		$opts->liveSite 	= COM_FABRIK_LIVESITE;
		$opts->row_id 		= JRequest::getInt('rowid');
		$opts->elid 			= $this->getElement()->id;
		$opts->userid 		= (int)$user->get('id');
		$opts->canRate 		= (int)$this->canRate();
		$opts->mode				= $params->get('rating-mode');
		$opts->view				= JRequest::getCmd('view');
		$opts 						= json_encode($opts);

		$lang 					= new stdClass();
		$lang->norating = JText::_('NO RATING');
		$lang 					= json_encode($lang);
		$str = "new fbRating('$id', $opts, '$value', $lang)";
		return $str;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikrating/', true);
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
		FabrikHelperHTML::script('table-fabrikrating.js', 'components/com_fabrik/plugins/element/fabrikrating/', false);
	}

	/**
	 * get js to ini js object that manages the behaviour of the rating element (non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelElement#elementTableJavascript()
	 */

	function elementTableJavascript()
	{
		$user =& JFactory::getUser();
		$params =& $this->getParams();
		$user =& JFactory::getUser();
		$id = $this->getHTMLId();
		$list =& $this->getTableModel()->getTable();
		$opts = new stdClass();

		$opts->tableid = $list->id;
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts->imagepath = COM_FABRIK_LIVESITE.'components/com_fabrik/plugins/element/fabrikrating/images/';
		$opts->elid = $this->getElement()->id;
		$opts->ext = $params->get('rating-pngorgif', '.png');
		$opts->userid = (int)$user->get('id');
		$opts->mode = $params->get('rating-mode');
		$opts = json_encode($opts);
		return "new FbRatingTable('$id', $opts);\n";
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
			return $this->filterValueList_All($normal, $tableName, $label, $id, $incjoin);
		} else {
			return $this->filterValueList_Exact($normal, $tableName, $label, $id, $incjoin);
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