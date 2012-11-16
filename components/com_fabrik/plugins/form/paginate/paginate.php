<?php

/**
 * Adds first/previous/next/last buttons to form and details view
 * allowing for scrolling through records
 *
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-form.php');

class FabrikModelPaginate extends FabrikModelFormPlugin {

	var $_counter = null;

	var $_data = '';

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * get the html to insert at the bottom of the form(non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelFormPlugin#getBottomContent_result()
	 */

	function getBottomContent_result($c)
	{
		return $this->_data;
	}

	/**
	 * store the html to insert at the bottom of the form(non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelFormPlugin#getBottomContent()
	 */

	function getBottomContent(&$params, &$formModel)
	{
		if (!$this->show($params, $formModel)) {
			return;
		}
		$app =& JFactory::getApplication();

		$formId = $formModel->getForm()->id;
		$mode = strtolower(JRequest::getCmd('view'));
		global $_PROFILER;
		JDEBUG ? $_PROFILER->mark('PAGINATE START') : null;
		$this->ids = $this->getNavIds($formModel);
		JDEBUG ? $_PROFILER->mark('PAGINATE SINGLE QUERY DONE') : null;
		$linkStartPrev = $this->ids->index == 0 ? ' disabled' : '';
		$linkNextEnd = $this->ids->index == $this->ids->lastKey ? ' disabled' : '';
		if ($app->isAdmin()) {
			$url = 'index.php?option=com_fabrik&c=form&task=form&view='.$mode.'&fabrik='.$formId.'&rowid=';
		} else {
			$url = 'index.php?option=com_fabrik&c=form&view='.$mode.'&fabrik='.$formId.'&rowid=';
		}
		$ajax = (bool)$params->get('paginate_ajax', true);
		$firstLink = ($ajax && $linkStartPrev) ? JText::_('START') : '<a href="'.JRoute::_($url.$this->ids->first).'" class="pagenav paginateFirst '.$linkStartPrev.'">'.JText::_('START').'</a>';
		$prevLink = ($ajax && $linkStartPrev) ? JText::_('PREV') : '<a href="'.JRoute::_($url.$this->ids->prev).'" class="pagenav paginatePrevious '.$linkStartPrev.'">'.JText::_('PREV').'</a>';

		$nextLink = ($ajax && $linkNextEnd) ? JText::_('NEXT') : '<a href="'.JRoute::_($url.$this->ids->next).'" class="pagenav paginateNext'.$linkNextEnd.'">'.JText::_('NEXT').'</a>';
		$endLink = ($ajax && $linkNextEnd) ? JText::_('END') : '<a href="'.JRoute::_($url.$this->ids->last).'" class="pagenav paginateLast'.$linkNextEnd.'">'.JText::_('END').'</a>';
		$this->_data = '<ul class="pagination">
				<li>&lt;&lt;</li>
				<li>'.$firstLink.'</li>
				<li>&lt; </li>
				<li>'.$prevLink.'</li>
				<li>'.$nextLink.'</li>
				<li> &gt;</li>
				<li>'.$endLink.'</li>
				<li>&gt;&gt;</li>
		</ul>';

		return true;
	}

	/**
	 * get the first last, prev and next record ids
	 * @param object $formModel
	 */
	protected function getNavIds($formModel)
	{
		$tableModel =& $formModel->getTableModel();
		$table = $tableModel->getTable();
		$db = $tableModel->getDb();

		$join = $tableModel->_buildQueryJoin();
		$where = $tableModel->_buildQueryWhere();
		$order = $tableModel->_buildQueryOrder();

		// @ rob as we are selecting on primary key we can select all rows - 3000 records load in 0.014 seconds
		$query = "SELECT $table->db_primary_key FROM $table->db_table_name $join $where $order";

		$db->setQuery($query);
		$rows = $db->loadResultArray();
		$keys = array_flip($rows);
		$o = new stdClass();
		$o->index = JArrayHelper::getValue($keys, $formModel->_rowId, 0);

		$o->first = $rows[0];
		$o->lastKey = count($rows)-1;
		$o->last = $rows[$o->lastKey];
		$o->next = $o->index + 1 > $o->lastKey ? $o->lastKey : $rows[$o->index + 1];
		$o->prev = $o->index - 1 < 0 ? 0 : $rows[$o->index - 1];
		return $o;
	}

	protected function show($params, $formModel)
	{
		$where = $params->get('paginate_where');
		switch($where) {
			case 'both':
				return true;
				break;
			case 'form':
				return (int)$formModel->_editable == 1;
				break;
			case 'details':
				return (int)$formModel->_editable == 0;
				break;
		}
	}

	/**
	 * process the plugin, called when form is submitted
	 *
	 * @param object $params
	 * @param object form
	 */

	function onAfterJSLoad(&$params, &$formModel)
	{
		if (!$this->show($params, $formModel)) {
			return;
		}
		if ($params->get('paginate_ajax') == 0) {
			return;
		}
		$opts = new stdClass();
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts->view = JRequest::getCmd('view');
		$opts->ids = $this->ids;
		$opts->pkey = FabrikString::safeColNameToArrayKey($formModel->getTableModel()->getTable()->db_primary_key);
		$opts = json_encode($opts);
		$form =& $formModel->getForm();
		$container = $formModel->_editable ? 'form' : 'details';
		$container .= "_".$form->id;
		if (JRequest::getVar('tmpl') != 'component') {
			FabrikHelperHTML::script('scroller.js', 'components/com_fabrik/plugins/form/paginate/');
			FabrikHelperHTML::script('encoder.js', 'media/com_fabrik/js/');
			FabrikHelperHTML::addScriptDeclaration("
			window.addEvent('load', function() {
			$container.addPlugin(new FabRecordSet($container, $opts));
	 		});");
		} else {
			// included scripts in the head don't work in mocha window
			// read in the class and insert it into the body as an inline script
			$class = JFile::read(JPATH_BASE."/components/com_fabrik/plugins/form/paginate/scroller.js");
			FabrikHelperHTML::addScriptDeclaration($class);
			$class = JFile::read(JPATH_BASE."/media/com_fabrik/js/encoder.js");
			FabrikHelperHTML::addScriptDeclaration($class);
			//there is no load event in a mocha window - use domready instead
			FabrikHelperHTML::addScriptDeclaration("
				window.addEvent('domready', function() {
				$container.addPlugin(new FabRecordSet($container, $opts));
	 		});");
		}
	}

	/**
	 * called from plugins ajax call
	 */

	function xRecord()
	{
		$formid = JRequest::getInt('formid');
		$rowid = JRequest::getVar('rowid');
		$mode = JRequest::getVar('mode', 'details');
		$model =& JModel::getInstance('Form', 'FabrikModel');
		$model->setId($formid);
		$model->_rowId = $rowid;
		$ids = $this->getNavIds($model);
		$url = COM_FABRIK_LIVESITE.'index.php?option=com_fabrik&format=raw&controller=plugin&g=form&task=pluginAjax&plugin=paginate&method=xRecord&formid='.$formid.'&rowid='.$rowid;
		$url = COM_FABRIK_LIVESITE.'index.php?option=com_fabrik&c=form&view='.$mode.'&fabrik='.$formid.'&rowid='.$rowid.'&format=raw';
		$ch = curl_init();
 	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
 	  curl_close($ch);
 	  //apend the ids to the json array
 	  $data = json_decode($data);
 	  //$data['ids'] = $ids;
 	  $data->ids = $ids;
 	  echo json_encode($data);

	}
}
?>