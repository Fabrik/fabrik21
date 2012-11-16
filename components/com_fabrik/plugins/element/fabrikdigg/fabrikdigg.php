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

class FabrikModelFabrikdigg extends FabrikModelElement {

	var $_pluginName = 'digg';

	var $commentDigg = false;

	var $commentId = null;

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function isHidden()
	{
		return false;
	}

	/**
	 * shows the data formatted for the table view
	 * @param string data
	 * @param object all the data in the tables current row
	 * @return string formatted value
	 */

	function renderTableData($data, $oAllRowsData)
	{
		$data = explode(GROUPSPLITTER, $data);
		for ($i=0; $i <count($data); $i++) {
			$data[$i] = $this->_renderTableData($data[$i], $oAllRowsData);
		}
		$data = implode(GROUPSPLITTER, $data);
		return parent::renderTableData($data, $oAllRowsData);
	}

	function _renderTableData($data, $oAllRowsData)
	{
		$user =& JFactory::getUser();
		$table =& $this->getTableModel()->getTable();
		$tableid = $table->id;
		$formid = $table->form_id;
		$row_id = isset($oAllRowsData->__pk_val) ? $oAllRowsData->__pk_val : $oAllRowsData->id;
		$db =& JFactory::getDBO();
		$commentId = JRequest::getInt('commentId');
		$data = $this->getVoteCount( $tableid, $formid, $row_id, $commentId);
		$vtext = $data == 1 ? JText::_('VOTE') :  JText::_('VOTES');
		$str = "<div class=\"digg-votes\"><span class=\"digg-votenum\">" . $data. '</span> '. $vtext . "</div>";

		$url = COM_FABRIK_LIVESITE.'index.php?option=com_fabrik&format=raw&controller=plugin&task=pluginAjax&g=element&plugin=fabrikdigg&method=ajax_vote&element_id='.$this->getElement()->id;
		$url .= '&row_id='.$row_id;
		$url .= '&elementname='.$this->getElement()->id;
		$url .= '&voteType=record';
		$url .= '&commentId=0';
		$url .= '&tableid='.$this->getTableModel()->getTable()->id;
		$url .= '&formid='.$this->getForm()->getForm()->id;
		$url .= '&nonajax=1';
		$url = str_replace('&', '&amp;', $url);
		$userid = $this->getUserId($tableid, $row_id, $commentId);
		$db->setQuery("SELECT COUNT(user_id) FROM #__fabrik_digg WHERE tableid = $tableid AND formid = $formid AND row_id = $row_id AND user_id = $userid");
		$voted = $db->loadResult();

		// $$$rob if rendering J article in PDF format __pk_val not in pdf table view
		if ($voted == 1) {
			$img = '<a href="'.$url.'&amp;vote=0"><img src="'.COM_FABRIK_LIVESITE . 'components/com_fabrik/plugins/element/fabrikdigg/images/heart.png" alt="'.JText::_('vote') .'"/></a>';
		} else {
			$img = '<a href="'.$url.'&amp;vote=1"><img src="'.COM_FABRIK_LIVESITE . 'components/com_fabrik/plugins/element/fabrikdigg/images/heart-off.png" alt="'.JText::_('vote') .'"/></a>';

		}
		$str .= $img;
		return $str;
	}


	function getVoteCount($tableid, $formid, $row_id, $commentId)
	{
		$db =& JFactory::getDBO();
		if ($this->commentDigg) {
			$db->setQuery("SELECT COUNT(user_id) FROM #__fabrik_digg WHERE comment_id = ".(int)$commentId);
		}else{
			$db->setQuery("SELECT COUNT(user_id) FROM #__fabrik_digg WHERE tableid = ".(int)$tableid." AND formid = ".(int)$formid." AND row_id = ".(int)$row_id);
		}
		return $db->loadResult();
	}

	/**
	 * draws the form element
	 * @param int repeat group counter
	 * @return string returns element html
	 */

	function render($data, $repeatCounter = 0)
	{
		$db =& JFactory::getDBO();
		$params 	=& $this->getParams();
		$tableModel =& $this->getTableModel();
		$table =& $tableModel->getTable();
		$formid = $tableModel->getForm()->getForm()->id;
		$tableid = $table->id;
		$formid = JRequest::getInt('fabrik', $formid);
		$row_id = JRequest::getInt('rowid');
		$commentId = JRequest::getInt('commentId');
		$userid = $this->getUserId($tableid, $row_id, $commentId);

		if ($this->commentDigg) {
			$db->setQuery("SELECT COUNT(user_id) FROM #__fabrik_digg WHERE user_id = $userid AND comment_id =" .(int)$this->commentId);
			$name ="comment_digg[]";
			$id = "comment_digg_".$this->commentId;
		} else {

			if (JRequest::getVar('view') == 'form' && $params->get('rating-rate-in-form') == 0) {
				return JText::_('ONLY_ACCESSIBLE_IN_DETALS_VIEW');
			}
			$db->setQuery("SELECT COUNT(user_id) FROM #__fabrik_digg WHERE user_id = $userid AND tableid = $tableid AND formid = $formid AND row_id = $row_id");
			$name 			= $this->getHTMLName($repeatCounter);
			$id 				= $this->getHTMLId($repeatCounter);

		}
		//$total = $this->getValue($data, $repeatCounter);
		$value = $db->loadResult();
		if ($value == 0) {
			$img = '<img src="'.COM_FABRIK_LIVESITE . 'components/com_fabrik/plugins/element/fabrikdigg/images/heart-off.png" alt="'.JText::_('DIGG_THIS').'" title="'.JText::_('DIGG_THIS').'"/>';
		} else {
			$img = '<img src="'.COM_FABRIK_LIVESITE . 'components/com_fabrik/plugins/element/fabrikdigg/images/heart.png"  alt="'.JText::_('UNDIGG_THID').'" title="'.JText::_('UNDIGG_THIS').'"/>';
		}
		$total = $this->getVoteCount($tableid, $formid, $row_id, $this->commentId);

		if ($this->_editable) {
			$str = '<a href="#">'.$img.'</a>';
			//put stored total in here - so that if the element is saved the count doesnt revert to 0/1
			$str .= '<input type="hidden" name="'.$name.'" id="'.$id.'" value="'.$total.'" />';
		} else {
			$str = $img;
		}

		$vtext = $total == 1 ? JText::_('VOTE') :  JText::_('VOTES');
		$str .= "<span class=\"digg-votenum\">" . $total. '</span> '. $vtext;
		return $str;
	}

	/**
	 * if logged in returns user id
	 * if not logged in returns cookie hash
	 * one hash per user per digg
	 * @return unknown_type
	 */

	public function getUserId($tableid, $row_id, $commentId)
	{
		$db =& JFactory::getDBO();
		$user =& JFactory::getUser();
		if ($user->get('id') == 0) {
			$hash = $this->getCookieName($tableid, $row_id, $commentId);
			$cookie = JRequest::getString($hash, 0, 'cookie', JREQUEST_ALLOWRAW | JREQUEST_NOTRIM);
			if ($cookie != '') {
				$userid = $db->Quote($cookie);
			} else {
				$userid = -1;
			}
		} else {
			$userid = (int)$user->get('id');
		}
		return $userid;
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
		return "INT(6) NOT NULL";
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @return string javascript to create instance. Instance name must be 'el'
	 */

	function elementJavascript($repeatCounter)
	{
		$user =& JFactory::getUser();
		$id = $this->getHTMLId($repeatCounter);
		$params =& $this->getParams();
		if (JRequest::getVar('view') == 'form' && $params->get('rating-rate-in-form') == 0) {
			return '';
		}
		$opts =& $this->getElementJSOptions($repeatCounter);
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts->elid = $this->getElement()->id;
		$opts->tableid = $this->getTableModel()->getTable()->id;
		$opts->formid = $this->getTableModel()->getForm()->_id;
		$opts->row_id = JRequest::getInt('rowid');
		$opts->imageover = COM_FABRIK_LIVESITE . 'components/com_fabrik/plugins/element/fabrikdigg/images/heart.png';
		$opts->imageout = COM_FABRIK_LIVESITE . 'components/com_fabrik/plugins/element/fabrikdigg/images/heart-off.png';
		$opts->digthis = JText::_('DIGG_THIS');
		$opts->undigthis = JText::_('UNDIGG_THIS');
		$opts = json_encode($opts);
		return "new fbDigg('$id', $opts)";
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 */

	function formJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/element/fabrikdigg/', false);
	}

	function tableJavascriptClass()
	{
		if ($this->getElement()->show_in_table_summary) {
			FabrikHelperHTML::script('table-fabrikdigg.js', 'components/com_fabrik/plugins/element/fabrikdigg/', false);
		}
	}

	function elementTableJavascript()
	{
		if (!$this->getElement()->show_in_table_summary) {
			return;
		}
		$id = $this->getHTMLId();
		$opts = new stdClass();
		$opts->livesite = COM_FABRIK_LIVESITE;
		$opts->elid = $this->getElement()->id;
		$opts->imageover = COM_FABRIK_LIVESITE . 'components/com_fabrik/plugins/element/fabrikdigg/images/heart.png';
		$opts->imageout = COM_FABRIK_LIVESITE . 'components/com_fabrik/plugins/element/fabrikdigg/images/heart-off.png';
		$formModel =& $this->getForm();
		$opts->formid = $formModel->getForm()->id;
		$opts->tableid = $formModel->getTableModel()->getTable()->id;
		$opts->view = 'table';
		$opts = json_encode($opts);
		return "new FbDiggTable('$id', $opts);\n";
	}

	function ajax_vote()
	{
		$vote = JRequest::getVar('vote');

		$row_id = JRequest::getVar('row_id');

		if (JRequest::getCmd('voteType') == 'comment') {
			$tableid = JRequest::getInt('tableid');
			$formid = JRequest::getInt('formid');
			$this->commentDigg = true;
			$db =& JFactory::getDBO();
			$commentId = JRequest::getInt('commentId');
			if ($vote == 0) {
				$query = "UPDATE `#__fabrik_comments` SET diggs  = diggs  - 1 WHERE id = $commentId";
			} else {
				$query = "UPDATE `#__fabrik_comments` SET diggs  = diggs  + 1 WHERE id = $commentId";
			}
		} else {
			$commentId = 0; // $$$ hugh - avoid 'not defined' notice when we call getVoteCount() below
			$this->commentDigg = false;
			$table =& $this->getTableModel()->getTable();
			$tableid = $table->id;
			$formid = $table->form_id;
			$db =& $this->getTableModel()->getDb();
			$element =& $this->getElement();
			$name = JRequest::getVar('elementname');

			$name = FabrikString::safeColName($name);
			if ($vote == 0) {
				$query = "UPDATE ".$db->nameQuote($table->db_table_name)." SET $name = $name - 1 WHERE $table->db_primary_key = $row_id";
			} else {
				$query = "UPDATE ".$db->nameQuote($table->db_table_name)." SET $name = $name + 1 WHERE $table->db_primary_key = $row_id";
			}

		}
		if ($this->doVote($tableid, $formid, $row_id, $vote)) {
			$db->setQuery($query);
			$db->query();
		}
		echo $this->getVoteCount($tableid, $formid, $row_id, $commentId);
	}


	function onStoreRow(&$tmpdata)
	{
		$tableid = JRequest::getInt('tableid');
		$formid = JRequest::getInt('fabrik');
		$row_id = JRequest::getInt('rowid');
		$k = $this->getElement()->name . "_raw";
		$vote = JArrayHelper::getValue($tmpdata, $k, 0);
		$this->doVote( $tableid, $formid, $row_id, $vote);
	}

	private function getCookieName($tableid, $row_id, $commentId)
	{
		if ($this->commentDigg) {
			$cookieName =  "digg-table_{$tableid}_comment_{$commentId}";
		} else {
			//wasnt working in table view.
			//$cookieName =  "digg-table_{$tableid}_row_{$commentId}";
			$cookieName =  "digg-table_{$tableid}_row_{$row_id}";
		}
		jimport('joomla.utilities.utility');
		return JUtility::getHash($cookieName);
	}

	/**
	 * store voting is jos_fabrik_digg
	 * @param unknown_type $tableid
	 * @param unknown_type $formid
	 * @param unknown_type $row_id
	 * @param unknown_type $vote
	 * @return bool if vote saved/deleted or not
	 */

	private function doVote($tableid, $formid, $row_id, $vote)
	{
		$db =& JFactory::getDBO();
		$formid= (int)$formid;
		$tableid = (int)$tableid;
		$row_id =(int)$row_id;
		$config =& JFactory::getConfig();
		$tzoffset = $config->getValue('config.offset');
		$date =& JFactory::getDate('now', $tzoffset);
		$strDate = $db->Quote($date->toMySQL());
		$commentId = JRequest::getInt('commentId');
		$user =& JFactory::getUser();
		$userid = (int)$user->get('id');
		if ($userid == 0) {

			$hash = $this->getCookieName($tableid, $row_id, $commentId);
			$cookie = JRequest::getString($hash, '', 'cookie', JREQUEST_ALLOWRAW | JREQUEST_NOTRIM);
			if ($vote == 1) {

				if ($cookie != '') {
					return false;
					// if voted and cookie exists return
				} else {
					//if voted and cookie doesnt exist
					//set cookie
					$cookie = 'rand'.rand();
					$lifetime = time() + 365*24*60*60;
					setcookie($hash, $cookie, $lifetime, '/');

					$cookie = $db->Quote($cookie);
					//store in db
					$db->setQuery("INSERT INTO #__fabrik_digg (user_id, tableid, formid, row_id, date_created, comment_id) values ($cookie, $tableid, $formid, $row_id, $strDate, $commentId)
		ON DUPLICATE KEY UPDATE date_created = $strDate");
					$db->query();
				}

			} else {
				//if not voted delete cookie
				setcookie($hash, false, time() - 86400, '/');
				//delete from db
				$db->setQuery("DELETE FROM #__fabrik_digg WHERE tableid = $tableid AND formid = $formid AND row_id = $row_id AND user_id = ".$db->Quote($cookie)." AND comment_id = $commentId");
				$db->query();
			}

		} else {
			if ($vote == 0) {
				//remove any previous vote
				$db->setQuery("DELETE FROM #__fabrik_digg WHERE tableid = $tableid AND formid = $formid AND row_id = $row_id AND user_id = $userid AND comment_id = $commentId");
				$db->query();
			} else {

				$db->setQuery("INSERT INTO #__fabrik_digg (user_id, tableid, formid, row_id, date_created, comment_id) values ($userid, $tableid, $formid, $row_id, $strDate, $commentId)
			ON DUPLICATE KEY UPDATE date_created = $strDate");
				$db->query();
			}
		}
		return true;
	}

	/**
	 * render admin settings
	 */

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		?>
<div id="page-<?php echo $this->_name;?>" class="elementSettings"
	style="display: none"><?php echo $pluginParams->render();?></div>
		<?php
	}

}
?>