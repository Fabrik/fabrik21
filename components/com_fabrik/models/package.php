<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

class FabrikModelPackage extends JModel
{

	/** @var array of table objects */
	var $_tables 			= array();
	
	/** @var object importer */
	var $_importer			= null;
	
	/** @var string the last action that was performed server side */
	var $_lastTask 			= null;
	
	/** @var string reference to block that called the server e.g. table_1 or form_27 */
	var $_senderBlock = null;
	
	/** @var string status of lastTask i.e. "success" or "fail" */
	var $_lastTaskStatus = null;
	
	/** @var string any data created by the lasttask - e.g. data to create a new table row with */
	var $_lastTaskData = null;
	
	/** @var string format output can be raw or html default = html */
	var $_format = 'html';
	
	/** @var array blocks to update */
	var $_updateBlocks		= null;
	
	/** @var object table package */
	var $_package = null;
	
	/** @var int id */
	var $_id = null;
	
	
	
	/**
	 * Constructor
	 *
	 * @since 1.5
	 */

	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Method to set the  id
	 *
	 * @access	public
	 * @param	int	ID number
	 */

	function setId($id )
	{
		// Set new package ID 
		$this->_id		= $id;
	}

	
	/**
	 * get a package table object
	 * 
	 * @return object connection tables
	 */

	function &getPackage()
	{
		if (!isset($this->_package)) {
			$this->_package =& JTable::getInstance('package', 'Table');
			$this->_package->load($this->_id);
			//forms can currently only be set from form module
			$this->_package->forms = '';
		}
		return $this->_package;
	}
	
	/**
	 * render the package in the front end
	 */

	function render()
	{
		$db =& JFactory::getDBO();
		$config		=& JFactory::getConfig();
	 	$document =& JFactory::getDocument();
		//test stuff needs to be assigned in admin
		$this->_blocks = array();
		return;
		// @TODO: loading of visualizations
	}
	
	function statusBar()
	{
		return "<div class='fbPackageStatus'><span>loading...</span></div>";
	}
	
	/**
	 * javascript code:
	 * main oPackage object
	 */
	
	function getManagementJS()
	{
		FabrikHelperHTML::script('package.js', 'media/com_fabrik/js/', true);
	}
	
	/**
	 * load the importer class
	 */

	function loadImporter()
	{
		$this->_importer = new fabrikImport( $db);
	}
	
	/**
	 * load in the tables associated with the package
	 */

	function loadTables()
	{
		$db =& JFactory::getDBO();
		if ($this->_package->tables != '') {
			$aIds 	= explode(',', $this->_package->tables);
			foreach ($aIds as $id) {
				$tableModel = &JModel::getInstance('table', 'FabrikModel');
				$tableModel->setId($id);
				$this->_tables[] = $tableModel->getTable();
				$formModel =& $tableModel->getForm();
				$this->_forms[] = $formModel->getForm();
			}
		}
		return $this->_tables;
	}
	
	/**
	 * (un)publish the package & all its tables
	 */

	function publish( $state )
	{
		foreach ($this->_tables as $oTable) {
			$oTable->publish( $oTable->id, $state);
		}
		parent::publish( $this->id, $state);
	}
 
}

class fabrikPackageMenu extends JModel{
	
	/**
	 * Constructor
	 *
	 * @since 1.5
	 */

	function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Method to set the  id
	 *
	 * @access	public
	 * @param	int	ID number
	 */

	function setId($id)
	{
		// Set new form ID 
		$this->_id		= $id;
	}
	
	function render() {
		return "menu items to go here";
	}
}

?>