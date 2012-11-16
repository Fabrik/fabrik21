<?php
/**
* Plugin element to render fileuploads of file type
* @package fabrik
* @author Rob Clayburn
* @copyright (C) Rob Clayburn
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class fileRender{

	var $output = '';
	/**
	 * @param object element model
	 * @param object element params
	 * @param string row data for this element
	 * @param object all row's data
	 */

	function renderTableData(&$model, &$params, $file, $oAllRowsData)
	{
		$this->render($model, $params, $file);
	}

	/**
	 * @param object element model
	 * @param object element params
	 * @param string row data for this element
	 */

	function render(&$element, &$params, $file)
	{
		jimport('joomla.filesystem.file');
		$filename = basename($file);
		$filename = strip_tags($filename);
		$ext = JFile::getExt($filename);

		if (!strstr($file, 'http://') && !strstr($file, 'https://')) {
			// $$$rob only add in livesite if we dont already have a full url (eg from amazons3)
			$file = ltrim($file, '/\\');
			$file = COM_FABRIK_LIVESITE.$file;
		}
		$file = str_replace("\\", "/", $file);
		$file = $element->storage->preRenderPath($file);
		$thumb_path = COM_FABRIK_BASE.'/media/com_fabrik/images/'.$ext.'.png';
		// $$$ hugh - using 'make_thumbnail' to mean 'use default $ext.png as an icon
		// instead of just putting the filename.
		$this->output .= "<a class=\"download-archive fabrik-filetype-$ext\" title=\"$filename\" href=\"$file\">";
		if ($params->get('make_thumbnail', false) && JFile::exists($thumb_path)) {
			$thumb_file = COM_FABRIK_LIVESITE."media/com_fabrik/images/".$ext.".png";
			$filename = "<img src=\"$thumb_file\" alt=\"$filename\" />";
		}
		$this->output .= $filename."</a>";
	}
}

?>