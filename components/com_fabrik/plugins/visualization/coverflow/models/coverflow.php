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

class fabrikModelCoverflow extends FabrikModelVisualization { //JModel

	/**
	 * constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
 	 * write out the admin form for customising the plugin
 	 *
 	 * @param object $row
 	 */

 	function renderAdminSettings()
	{
		$pluginParams =& $this->getPluginParams();
		?>
		<div id="page-<?php echo $this->_name;?>" class="pluginSettings" style="display:none">
		<?php
		$c = count($pluginParams->get('timeline_table'));
		$pluginParams->_duplicate = true;
		echo $pluginParams->render('params', 'connection');
		for ($x=0; $x<$c; $x++) {
			echo $pluginParams->render('params', '_default', true, $x);
		}
		?>
		</div><?php
 	}

	/**
 	 * internally render the plugin, and add required script declarations
 	 * to the document
 	 */

 	function render()
 	{
 		require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');
 		$app =& JFactory::getApplication();
 		$params	=& $this->getParams();
 		$config	=& JFactory::getConfig();
		$document =& JFactory::getDocument();
		$w = new FabrikWorker();

 		$document->addScript("http://api.simile-widgets.org/runway/1.0/runway-api.js");
		FabrikHelperHTML::script('coverflow.js', $this->srcBase.'coverflow/', true);
		$c = 0;
		$images = $params->get('coverflow_image', array(), '_default', 'array');
		$titles = $params->get('coverflow_title', array(), '_default', 'array');
		$subtitles = $params->get('coverflow_subtitle', array(), '_default', 'array');

		$config =& JFactory::getConfig();

		$aTables = $params->get('coverflow_table', array(), '_default', 'array');
		foreach ($aTables as $tableid) {

 			$tableModel =& JModel::getInstance('Table', 'FabrikModel');
	 		$tableModel->setId($tableid);
	 		$table =& $tableModel->getTable();
			$nav =& $tableModel->getPagination(0, 0, 0);

			$image =  $images[$c];
			$title =  $titles[$c];
			$subtitle = $subtitles[$c];

			$data = $tableModel->getData();
			$eventdata = array();
			if ($tableModel->canView() || $tableModel->canEdit()) {

				$elements =& $tableModel->getElements();
				$imageElement = JArrayHelper::getValue($elements, FabrikString::safeColName($image));
				$action = $app->isAdmin() ? "task" : "view";
				$nextview = $tableModel->canEdit() ? "form" : "details";

				foreach ($data as $group) {
					if (is_array($group)) {
					foreach ($group as $row) {
						$event = new stdClass();
						if (!method_exists($imageElement, 'getStorage')) {
							//JError::raiseError(500, 'Looks like you selected a element other than a fileupload element for the coverflows image element');
							switch (get_class($imageElement)) {
								case 'FabrikModelFabrikImage':
									$rootFolder = $imageElement->getParams()->get('selectImage_root_folder');
									$rootFolder = ltrim($rootFolder, '/');
									$rootFolder = rtrim($rootFolder, '/');
									$event->image = COM_FABRIK_LIVESITE . 'images/stories/'.$rootFolder.'/'.$row->{$image."_raw"};
									break;
								default:
									$event->image = isset($row->{$image."_raw"}) ? $row->{$image."_raw"} : '';
									break;
							}

						} else {
							$event->image = $imageElement->getStorage()->pathToURL($row->{$image."_raw"});
						}
						$event->title = (string)strip_tags($row->$title);
						$event->subtitle = (string)strip_tags($row->$subtitle);
						$eventdata[] = $event;
					}
					}
				}
			}
			$c ++;
		}
		$json = json_encode($eventdata);
		$str = "var coverflow = new fbVisCoverflow($json);";
		FabrikHelperHTML::addScriptDeclaration($str);
 	}

	function setTableIds()
	{
		if (!isset($this->tableids)) {
			$params =& $this->getParams();
			$this->tableids = $params->get('coverflow_table', array(), '_default', 'array');
		}
	}
}
?>