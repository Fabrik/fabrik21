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
require_once(COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php');


class FabrikModelCronnotification extends fabrikModelPlugin {

	var $_counter = null;

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
<div id="page-<?php echo $this->_name;?>" class="pluginSettings"
	style="display: none"><?php
	echo $pluginParams->render('params');
	?></div>

	<?php
	return;
	}

}
?>