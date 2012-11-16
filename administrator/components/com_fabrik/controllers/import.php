<?php
/**
 * @version
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

/**
 * @package		Joomla
 * @subpackage	Fabrik
 * @license		GNU/GPL, see LICENSE.php
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

require_once(COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'menu.php');
require_once(COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'adminhtml.php');
require_once(COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'fabrik.php');

require_once(JPATH_COMPONENT.DS.'views'.DS.'import.php');
/**
 * @package		Joomla
 * @subpackage	Fabrik
 */

class FabrikControllerImport extends JController
{

	/**
	 * Constructor
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
	}

	/**
	 * if new elements found in the CSV file and user decided to
	 * add them to the table then do it here
	 * @param object import model
	 * @param array existing headings
	 * @return unknown_type
	 */

	protected function addElements($model, $headings)
	{
		$user 			=& JFactory::getUser();
		$c = 0;
		$tableModel	=& $this->getModel( 'Table');
		$tableModel->setId(JRequest::getInt('fabrik_table'));
		$tableModel->getTable();
		$formModel 	=& $tableModel->getForm();
		$groupId 		= current(array_keys($formModel->getGroupsHiarachy()));
		$plugins 			= JRequest::getVar('plugin');
		$elementModel =& $this->getModel('element');
		$element =& $elementModel->getElement();
		$elementsCreated = 0;
		$newElements 	= JRequest::getVar('createElements', array());
		$dataRemoved = false;
		foreach ($newElements as $elname => $add) {
			if ($add) {
				$element->id = 0;
				$element->name = JFilterInput::clean($elname);
				$element->label 	= strtolower($elname);
				$element->plugin = $plugins[$c];
				$element->group_id 			= $groupId;
				$element->eval 					= 0;
				$element->state 				= 1;
				$element->width 				= 255;
				$element->created 			= date( 'Y-m-d');
				$element->created_by 		= $user->get('id');
				$element->created_by_alias 	= $user->get('username');
				$element->checked_out 	= 0;
				$element->show_in_table_summary = 1;
				$element->ordering 			= 0;
				$headings[] = $element->name;

				$element->store();
				$where = " group_id = '" . $element->group_id . "'";
				$element->move( 1, $where);
				$elementModel->addToDBTable();
				$elementsCreated ++;
			}else{
				//need to remove none selected element's (that dont already appear in the table structure
				// data from the csv data
				$session 		=& JFactory::getSession();
				$allHeadings = $session->get('com_fabrik.csvheadings');
				$index = array_search( $elname, $allHeadings);
				if ($index !== false) {
					$dataRemoved = true;
					foreach ($model->data as &$d) {
						unset($d[$index]);
					}
				}
			}

			$c ++;
		}
		if($dataRemoved) {
			//reindex data array
			foreach ($model->data as $k => $d) {
				$model->data[$k] = array_reverse(array_reverse($d));
			}
		}
		return $headings;
	}

	/**
	 * cancel import
	 * @return null
	 */

	function cancel()
	{
		$this->setRedirect('index.php?option=com_fabrik&c=table');
	}

	/**
	 * make or update the table from the CSV file
	 * @return null
	 */

	function makeTableFromCSV()
	{
		//called when you are adding in new elements to an existing table
		$session =& JFactory::getSession();
		$model =& $this->getModel('Importcsv');
		$model->data = $session->get('com_fabrik.csvdata');
		$headings = $session->get('com_fabrik.matchedHeadings');
		$model->matchedHeadings = $this->addElements($model, $headings);
		JRequest::setVar('tableid', JRequest::getInt('fabrik_table'));
		$msg = $model->makeTableFromCSV();
		$this->setRedirect('index.php?option=com_fabrik&c=table', $msg);
	}

	/**
	 * perform the file upload and set the session state
	 * Unlike front end import if there are unmatched heading we take the user to
	 * a form asking if they want to import those new headings (creating new elements for them)
	 * @return null
	 */

	function doimport()
	{
		$model = &$this->getModel('Importcsv');
		@set_time_limit(800);

		$tmp_file = $model->checkUpload();
		if ($tmp_file === false) {
			$this->display();
		}
		//$userfile = JRequest::getVar('userfile', null, 'files');
		$model->readCSV($tmp_file);

		$model->findExistingElements();
    $tableModel =& $model->getTableModel();
    $this->table =& $tableModel->getTable();
    $this->newHeadings 			=& $model->newHeadings;
		$this->headings 				=& $model->headings;
		$this->data 						= $model->data;
		$this->matchedHeadings 	=& $model->matchedHeadings;
		$session =& JFactory::getSession();
		$session->set('com_fabrik.csvheadings', $this->headings);
		$session->set('com_fabrik.csvdata', $this->data);
		$session->set('com_fabrik.matchedHeadings', $this->matchedHeadings);

		if (!empty($model->newHeadings))
		{
			$pluginManager =& $this->getModel('pluginmanager');
			$elementTypes = $pluginManager->getElementTypeDd('fabrikfield', 'plugin[]');
			FabrikViewImport::csvChooseElementTypes($elementTypes);
		} else {
			JRequest::setVar('fabrik_table', $this->table->id);
			$msg = $model->makeTableFromCSV();
			$this->setRedirect('index.php?option=com_fabrik&c=table', $msg);
		}
	}

	/**
	 * display the import CSV file form
	 */

	function display()
	{
		$this->tableid = JRequest::getVar('tableid', 0);
		$tableModel =& JModel::getInstance('Table', 'FabrikModel');
		$tableModel->setId($this->tableid);
		$this->table =& $tableModel->getTable();
		FabrikViewImport::import();
	}
}
?>