<?php

/**
* Add an action button to the table to download files
* @package Joomla
* @subpackage Fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-table.php');
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');

class FabrikModelDownload extends FabrikModelTablePlugin {

	var $_counter = null;

	var $_buttonPrefix = 'download';

	/**
	* Constructor
	*/

	function __construct()
	{
		parent::__construct();
	}

	function button()
	{
		return "download files";
	}

	function button_result($c)
	{
		$params =& $this->getParams();
		if ($this->canUse()) {
			$name = $this->_getButtonName();
			return "<input type=\"button\" name=\"$name\" value=\"". $params->get('download_button_label') . "\" class=\"button tableplugin\"/>";
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelTablePlugin::getAclParam()
	 */

	function getAclParam()
	{
		return 'download_access';
	}

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 *
	 * @return bol
	 */

	function canSelectRows()
	{
		return $this->canUse();
	}

	/**
	 * do the plugin action
	 * @param object parameters
	 * @param object table model
	 */
	function process(&$params, &$model)
	{
		$ids	= JRequest::getVar('ids', array(), 'method', 'array');
		//$params =& $model->getParams();
		$download_table = $params->get('download_table');
		$download_fk = $params->get('download_fk');
		$download_file = $params->get('download_file');
		$download_width = $params->get('download_width');
		$download_height = $params->get('download_height');
		$download_resize = ($download_width || $download_height) ? true : false;
		$table =& $model->getTable();
		$filelist = array();
		$zip_err = '';

		if (empty($download_fk) && empty($download_file) && empty($download_table)) {
			return;
		}
		else if (empty($download_fk) && empty($download_table) && !empty($download_file)) {
			foreach ($ids AS $id) {
				$row = $model->getRow($id);
				if (isset($row->$download_file)) {
					$this_file = JPATH_SITE.DS.$row->$download_file;
					if (is_file($this_file)) {
						$filelist[] = $this_file;
					}
				}
			}
		}
		else {
			$db = JFactory::getDBO();
			$ids_string = implode(',',$ids);
			$query = "SELECT $download_file FROM $download_table WHERE $download_fk IN ($ids_string)";
			$db->setQuery($query);
			$results = $db->loadObjectList();
			foreach ($results AS $result) {
				$this_file = JPATH_SITE.DS.$result->$download_file;
				if (is_file($this_file)) {
					$filelist[] = $this_file;
				}
			}
		}
		if (!empty($filelist)) {
			if ($download_resize) {
				ini_set('max_execution_time', 300);
				require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'image.php');
				$storage =& $this->getStorage();
				$download_image_library = $params->get('download_image_library');
				$oImage 		= imageHelper::loadLib( $download_image_library);
				$oImage->setStorage($storage);
			}
			$zipfile = tempnam(sys_get_temp_dir(), "zip");
			$zipfile_basename = basename($zipfile);
			$zip = new ZipArchive;
			$zipres = $zip->open($zipfile, ZipArchive::OVERWRITE);
			if ($zipres === true) {
				$ziptot = 0;
				$tmp_files = array();
				foreach ($filelist AS $this_file) {
					$this_basename = basename($this_file);
					if ($download_resize && $oImage->GetImgType($this_file)) {
						$tmp_file = '/tmp/' . $this_basename;
						$oImage->resize($download_width, $download_height, $this_file, $tmp_file);
						$this_file = $tmp_file;
						$tmp_files[] = $tmp_file;
					}
					$zipadd = $zip->addFile($this_file, $this_basename);
					if ($zipadd === true) {
						$ziptot++;
					}
					else {
						$zip_err .= JText::_('ZipArchive add error: ' . $zipadd);
					}
				}
				if (!$zip->close()) {
					$zip_err = JText::_('ZipArchive close error') . ($zip->status);
				}

				if ($download_resize) {
					foreach ($tmp_files as $tmp_file) {
						$storage->delete($tmp_file);
					}
				}
				if ($ziptot > 0) {
					// Stream the file to the client
					$filesize = filesize($zipfile);
					if ($filesize > 0) {
						header("Content-Type: application/zip");
						header("Content-Length: " . filesize($zipfile));
						header("Content-Disposition: attachment; filename=\"$zipfile_basename.zip\"");
						echo JFile::read($zipfile);
						JFile::delete($zipfile);
						exit;
					}
					else {
						$zip_err .= JText::_('ZIP is empty');
					}
				}
			}
			else {
				$zip_err = JText::_('ZipArchive open error: ' . $zipres);
			}

		}
		else {
			$zip_err = "No files to ZIP!";
		}
		return $zip_err;
	}

	/**
	 * load the javascript class that manages interaction with the form element
	 * should only be called once
	 * @return string javascript class file
	 */

	function loadJavascriptClass()
	{
		FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/plugins/table/download/', false);
	}

	/**
	 * return the javascript to create an instance of the class defined in formJavascriptClass
	 * @param object parameters
	 * @param object table model
	 * @param array [0] => string table's form id to contain plugin
	 * @return bool
	 */

	function loadJavascriptInstance($params, $model, $args )
	{
		$form_id = $args[0];
		$opts = new stdClass();
		$opts->name = $this->_getButtonName();
		$opts = json_encode($opts);
		$lang = $this->_getLang();
		$lang = json_encode($lang);
		$this->jsInstance = "new fbTableDownload('$form_id', $opts, $lang)";
		return true;
	}

	/**
	 * show a new for entering the form actions options
	 */

 	function renderAdminSettings($elementId, &$row, &$params, $lists, $c)
 	{
 		$params->_counter_override = $this->_counter;
 		$display =  ($this->_adminVisible) ? "display:block" : "display:none";
 		$return = '<div class="page-' . $elementId . ' elementSettings" style="' . $display . '">
 		' . $params->render('params', '_default', false, $c) .
 		'</div>
 		';
    $return = str_replace("\r", "", $return);
	  return $return;
	  //dont do here as if we json enocde it as we do in admin form view things go wrong
		//return  addslashes(str_replace("\n", "", $return));
 	}

 	/**
 	 * get the filesystem storage type - currently always uses the filesystem storage adaoptor
 	 * @return object storage
 	 */

	function getStorage()
	{
		if (!isset($this->storage)) {
			$params =& $this->getParams();
			//$storageType = $params->get('fileupload_storage_type', 'filesystemstorage');
			$storageType = 'filesystemstorage';
			require_once(COM_FABRIK_FRONTEND.DS.'plugins'.DS.'element'.DS.'fabrikfileupload'.DS.'adaptors'.DS.$storageType.'.php');
			$this->storage = new $storageType($params);
		}
		return $this->storage;
	}

}
?>