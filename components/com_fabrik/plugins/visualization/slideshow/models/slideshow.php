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

require_once(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'models'.DS.'visualization.php');

class fabrikModelSlideshow extends FabrikModelVisualization {

	/** @var string google charts api url **/

	var $_url = '';

	function getSlideshow()
	{
		$id = 'foo_for_now_fix_this';
		$return = "
			<div id=\"$id\" class=\"slideshow\">
				<div class=\"slideshow-images\">
					<a><img /></a>
					<div class=\"slideshow-loader\"></div>
				</div>
				<div class=\"slideshow-captions\"></div>
				<div class=\"slideshow-controller\"></div>
				<div class=\"slideshow-thumbnails\"></div>
			</div>
		";
		return $return;
	}

	function getPlaylist() {
		$params =& $this->getParams();

		$mediaElement 	= $params->get('media_media_elementList');
		$mediaElement .= '_raw';
		$titleElement 	= $params->get('media_title_elementList', '');
		$imageElement 	= $params->get('media_image_elementList', '');
		if (!empty($imageElement)) {
			$imageElement .= '_raw';
		}
		$infoElement 	= $params->get('media_info_elementList', '');
		$noteElement 	= $params->get('media_note_elementList', '');

		$tableid 		= $params->get('media_table');

		$tableModel =& JModel::getInstance('Table', 'FabrikModel');
		$tableModel->setId($tableid);
		$table =& $tableModel->getTable();
		$form =& $tableModel->getForm();
		//remove filters?
		// $$$ hugh - remove pagination BEFORE calling render().  Otherwise render() applies
		// session state/defaults when it calls getPagination, which is then returned as a cached
		// object if we call getPagination after render().  So call it first, then render() will
		// get our cached pagination, rather than vice versa.
		$nav			=& $tableModel->getPagination(0, 0, 0);
		$tableModel->render();
		$alldata = $tableModel->getData();
		$document = JFactory::getDocument();
		$retstr	= "<?xml version=\"1.0\" encoding=\"".$document->_charset."\"?>\n";
		$retstr .= "<playlist version=\"1\" xmlns = \"http://xspf.org/ns/0/\">\n";
		$retstr .= "	<title>" . $table->label . "</title>\n";
		$retstr .= "	<trackList>\n";
		foreach ($alldata as $data) {
			foreach ($data as $row) {
				if (!isset($row->$mediaElement)) {
					continue;
				}
				$location = $row->$mediaElement;
				if (empty($location)) {
					continue;
				}
				$location = str_replace('\\','/',$location);
				$location = ltrim($location, '/');
				$location = COM_FABRIK_LIVESITE . $location;
				//$location = urlencode($location);
				$retstr .= "		<track>\n";
				$retstr .= "			<location>" . $location . "</location>\n";
				if (!empty($titleElement)) {
					$title = $row->$titleElement;
					$retstr .= "			<title>" . $title . "</title>\n";
				}
				if (!empty($imageElement)) {
					$image = $row->$imageElement;
					if (!empty($image)) {
						$image = str_replace('\\','/',$image);
						$image = ltrim($image, '/');
						$image = COM_FABRIK_LIVESITE . $image;
						$retstr .= "			<image>" . $image . "</image>\n";
					}
				}
				if (!empty($noteElement)) {
					$note = $row->$noteElement;
					$retstr .= "			<annotation>" . $note . "</annotation>\n";
				}
				if (!empty($infoElement)) {
					$link = $row->$titleElement;
					$retstr .= "			<info>" . $link . "</info>\n";
				}
				else {
					$link = JRoute::_('index.php?option=com_fabrik&view=form&fabrik=' . $form->getId() . '&rowid=' . $row->__pk_val);
					$retstr .= "			<info>" . $link . "</info>\n";
				}
				$retstr .= "		</track>\n";
			}
		}
		$retstr .= "	</trackList>\n";
		$retstr .= "</playlist>\n";
		return $retstr;
	}

	function getImageJSData()
	{
		$params =& $this->getParams();
		$tableid 		= $params->get('slideshow_viz_table');
		$tableModel =& JModel::getInstance('Table', 'FabrikModel');
		$tableModel->setId($tableid);
		$table =& $tableModel->getTable();
		$form =& $tableModel->getForm();
		$nav			=& $tableModel->getPagination(0, 0, 0);
		$tableModel->render();
		$alldata = $tableModel->getData();

		$slideshow_viz_thumbnails = $params->get('slideshow_viz_thumbnails', false);
		$slideshow_viz_file = $params->get('slideshow_viz_file', '') . '_raw';
		$slideshow_viz_caption = $params->get('slideshow_viz_caption', '');

		$js_opts = array();
		foreach ($alldata as $data) {
			foreach ($data as $pic) {
				if (!isset($pic->$slideshow_viz_file)) {
					JError::raiseNotice(E_NOTICE,  $params->get('slideshow_viz_file', '') . ' not found - is it set to show in the table view?');
					break 2;
				}
				#var_dump($pic); echo "<br />\n";
				$pic->$slideshow_viz_file = str_replace("\\", "/",  $pic->$slideshow_viz_file);
				$pic_opts = array();
				if (isset($pic->$slideshow_viz_caption)) {
					$pic_opts['caption'] = $pic->$slideshow_viz_caption;
				}
				if ($slideshow_viz_thumbnails) {
					$mythumb = dirname($pic->$slideshow_viz_file) . '/thumbs/' . basename($pic->$slideshow_viz_file);
					$pic_opts['thumbnail'] = $mythumb;
				}
				$js_opts[$pic->$slideshow_viz_file] = $pic_opts;
			}
		}
		return $js_opts;
	}

	function getJS()
	{
		$params =& $this->getParams();
		$str = "window.addEvent('domready', function() {\n";
		$viz = $this->getVisualization();

		$use_thumbs = $params->get('slideshow_viz_thumbnails', 0);
		$use_captions = $params->get('slideshow_viz_caption', '') == '' ? false : true;
	    $opts = new stdClass();
		$opts->slideshow_data = $slideshow_data = $this->getImageJSData();
		$opts->id = $viz->id;
		$opts->html_id = 'slideshow_viz';
		$opts->liveSite = COM_FABRIK_LIVESITE;
		$opts->slideshow_type = $params->get('slideshow_viz_type', 1);
		$opts->slideshow_width = (int)$params->get('slideshow_viz_width', 400);
		$opts->slideshow_height = (int)$params->get('slideshow_viz_height', 300);
		$opts->slideshow_delay = (int)$params->get('slideshow_viz_delay', 5000);
		$opts->slideshow_duration = (int)$params->get('slideshow_viz_duration', 2000);
		$opts->slideshow_zoom = (int)$params->get('slideshow_viz_zoom', 50);
		$opts->slideshow_pan = (int)$params->get('slideshow_viz_pan', 20);
		$opts->slideshow_thumbnails = $use_thumbs ? true : false;
		$opts->slideshow_captions = $use_captions ? true : false;
		$opts->container = "slideshow_viz_".$this->getVisualization()->id;
		$opts->show = $this->getRequiredFiltersFound();
		$opts = json_encode($opts);
		$str .= "fabrikSlideshowViz = new fbSlideshowViz('slideshow_viz', $opts)\n";
	    $str .= "});\n";
	    return $str;
	}
	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		$document =& JFactory::getDocument();
		FabrikHelperHTML::script('adminmedia.js', 'components/com_fabrik/plugins/visualization/media/', true);
		?>
<div id="page-<?php echo $this->_name;?>" class="pluginSettings"
	style="display: none">
	<?php
		echo $pluginParams->render('params');
	}

 	/**
 	 * get all table models filters
 	 * @return array table filters
 	 */

 	function getFilters()
 	{
 	  $params 		=& $this->getParams();
 	  $tableids 	= $params->get('slideshow_viz_table', array(), '_default', 'array');
 	  $tableModels =& $this->getTableModels( $tableids);
 	  $filters = array();
 	  foreach ($tableModels as $tableModel) {
 	    $filters[$tableModel->getTable()->label] = $tableModel->getFilters();
 	  }
 	  return $filters;
 	}

	function setTableIds()
	{
		if (!isset($this->tableids)) {
			$params =& $this->getParams();
			$this->tableids = $params->get('slideshow_viz_table', array(), '_default', 'array');
		}
	}

}


?>