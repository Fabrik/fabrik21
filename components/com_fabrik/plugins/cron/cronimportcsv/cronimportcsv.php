<?php

/**
* A cron task to email records to a give set of users
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin.php');
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'importcsv.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');

class FabrikModelCronimportcsv extends FabrikModelPlugin {

	var $_counter = null;
	var $db = null;

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}


	function canUse()
	{
		return true;
	}

	function requiresTableData() {
		/* we don't need cron to load $data for us */
		return false;
	}

	/*
	 * @author Kyle
	 * @param string $tableName   The name of the file to be loaded.  Should only be file name--not a path.
	 * @return int tableid      The id frabrik gives the table that hold information about files named $tablename
	 * returns an empty() type if no table exists with the same name as $tablename.
	 */

	function getTableIdFromFileName($tableName)
	{
		//get site's database
		if (!isset($this->db))
			 $this->db =& JFactory::getDBO();
		$sql = "SELECT id FROM #__fabrik_tables WHERE db_table_name=".$this->db->Quote($tableName);
		$this->db->setQuery($sql);
		$tableid = $this->db->loadResult();

		return $tableid;
	}

	/**
	 * do the plugin action
	 *
	 * @return number of records updated
	 */

	function process(&$data, &$tableModel)
	{
		$app =& JFactory::getApplication();
		$params =& $this->getParams();

		//Get plugin settings and save state of request array vars we might change
		$maxFiles = (int)$params->get('cron_importcsv_maxfiles', 1);
		$deleteFile = $params->get('cron_importcsv_deletefile', true);
		$cronDir = $params->get('cron_importcsv_directory');
		$useTableName = (int)$params->get('cron_importcsv_usetablename', false);
		$dropdata = $params->get('cron_importcsv_dropdata', '0');
		$orig_dropdata = JRequest::getVar('dropdata', -1);
		JRequest::setVar('drop_data', $dropdata);
		$overwrite = $params->get('cron_importcsv_overwrite', '0');
		$orig_overwrite = JRequest::getVar('overwrite', -1);
		JRequest::setVar('overwrite', $overwrite);
		$orig_tableid = JRequest::getInt('tableid', -1);

		//Fabrik use this as the base directory, so we need a new directory under 'media'
		define("FABRIK_CSV_IMPORT_ROOT",JPATH_ROOT.DS.'media');
		$d = FABRIK_CSV_IMPORT_ROOT.DS.$cronDir;

		//TODO: Need to also have a FILTER for CSV files ONLY.
		$filter = "\.CSV$|\.csv$";
		$exclude = array('done','.svn','CVS');
		$arrfiles = JFolder::files($d, $filter, true, true, $exclude);

		$log =& JTable::getInstance('Log', 'Table');

		$xfiles = 0;
		foreach ($arrfiles as $full_csvfile) {
			if (++$xfiles > $maxFiles) {
				break;
			}
			$log->message 			= "Starting import: $full_csvfile:  ";
			$log->id 						= null;
			$log->referring_url = $_SERVER['HTTP_REFERER'];
			$log->message_type='plg.cron.cronimportcsv.information';
			$log->store();

			$clsImportCSV = JModel::getInstance('FabrikModelImportcsv');

			if ($useTableName) {
				$tableid = $this->getTableIdFromFileName(basename($full_csvfile));
			}
			else {
				$table =& $tableModel->getTable();
				$tableid = $table->id;
			}

			if (empty($tableid))
			{
				$log->message .= "Table with name $filename does not exist";
				$log->message_type='plg.cron.cronimportcsv.warning';
				$log->store();
				continue;
			}
			JRequest::setVar('tableid', $tableid);

			// grab the CSV file, need to strip import root off path first
			$csvfile = str_replace(FABRIK_CSV_IMPORT_ROOT, '', $full_csvfile);
			$clsImportCSV->readCSV($csvfile);

			 //get this->matchedHeading
			$clsImportCSV->findExistingElements();

			$msg =  $clsImportCSV->makeTableFromCSV();
			if ($app->isAdmin()) {
				$app->enqueueMessage($msg);
			}

			if ($deleteFile == '1') {
				JFile::delete($full_csvfile);
			}
			else if ($deleteFile == '2') {
				$new_csvfile = $full_csvfile . '.' . time();
				JFile::move($full_csvfile, $new_csvfile);
			}
			else if ($deleteFile == '3') {
				$done_folder = dirname($full_csvfile) . DS . 'done';
				if (JFolder::exists($done_folder)) {
					$new_csvfile = $done_folder . DS . basename($full_csvfile);
					JFile::move($full_csvfile, $new_csvfile);
				}
				else {
					if ($app->isAdmin()) {
						$app->enqueueMessage("Move file requested, but can't find 'done' folder: $done_folder");
					}
				}
			}

			$log->id = null;
			$log->message_type='plg.cron.cronimportcsv.information';
			$log->message = $msg;
			$log->store();

		}

	 	// Leave the request array how we found it
		if (!empty($orig_tableid)) {
			JRequest::setvar('tableid', $orig_tableid);
		}

		if ($orig_dropdata != -1) {
			JRequest::setVar('drop_data', $orig_dropdata);
		}
		if ($orig_overwrite != -1) {
			JRequest::setVar('overwite', $orig_overwrite);
		}

		if ($xfiles > 0) {
			$updates = $clsImportCSV->addedCount + $clsImportCSV->updatedCount;
		}
		else {
			$updates = 0;
		}
	    return $updates;
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
		<div id="page-<?php echo $this->_name;?>" class="pluginSettings" style="display:none">
		<?php
			echo $pluginParams->render('params');
			echo $pluginParams->render('params', 'fields');
			?> </div> <?php
		return;
	}

}
?>