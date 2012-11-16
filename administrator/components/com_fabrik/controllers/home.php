<?php
/**
* @version
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/
// no direct access
defined('_JEXEC') or die('Restricted access');

/**
 * @package		Joomla
 * @subpackage	Fabrik
 */

class FabrikControllerHome extends JController
{

	/**
	 * Constructor
	 */

	function __construct($config = array())
	{
		parent::__construct($config);
	}

	/**
	 * display the admin home page
	 */

	function display()
	{
		$db =& JFactory::getDBO();
		require_once(JPATH_COMPONENT.DS.'views'.DS.'home.php');
		$db->setQuery("SELECT * FROM #__fabrik_log WHERE	message_type != '' ORDER BY timedate_created DESC limit 0, 10");
		$logs = $db->loadObjectList();
		$feed = $this->getRSSFeed();
		FabrikHelperHTML::tips();
		FabrikViewHome::show($feed, $logs);
	}

	/**
	 * delete all data from fabrik
	 */

	function reset()
	{
		$db =& JFactory::getDBO();
		$sql = "SELECT * FROM #__fabrik_tables";
		$db->setQuery($sql);
		$rows = $db->loadObjectList();
		$tableModel =& JModel::getInstance('Table', 'FabrikModel');

		/*$clearData = JRequest::getVar('cleardata', 0, 'get');
		$dropTables = JRequest::getVar('drop', 0, 'get');
		foreach($rows as $row) {
			$tableModel->setId($row->id);
			$tableModel->getTable();
			if( $clearData) {
				$tableModel->dropData();
			}
			$tableModel->deleteTable();
			if( $dropTables) {
				$tableModel->drop();
			}
		}*/

			$prefix = '#__fabrik_';
		$tables = array('calendar_events', 'elements',
		'formgroup', 'forms', 'groups', 'joins',
		'jsactions', 'packages', 'tables', 'validations',
		'visualizations');

		foreach ($tables as $table) {
			$db->setQuery("TRUNCATE TABLE " . $prefix.$table);
			if (!$db->query()) {
				return JError::raiseError(500, $db->getErrorMsg() . ": " . $db->getQuery());
			}
		}

		$this->setRedirect('index.php?option=com_fabrik&c=home', JText::_('FABRIK RESET'));
	}

	/**
 * install sample form
 */

	function installSampleData()
	{
		$db =& JFactory::getDBO();
		$config =& JFactory::getConfig();
		require_once ( "../components/com_fabrik/models/group.php");
		require_once ( "../components/com_fabrik/models/element.php");
		require_once ( "../components/com_fabrik/models/table.php");
		require_once ( "../components/com_fabrik/models/form.php");
		$dbTableName = $config->getValue('dbprefix') . "_fb_contact_sample";
		echo "<div style='text-align:left;border:1px dotted #cccccc;padding:10px;'>" .
		"<h3>Installing data...</h3><ol>";

		$group =& JTable::getInstance('Group', 'FabrikTable');
		$group->name = "Contact Details";
		$group->label = "Contact Details";
		$group->state = 1;

		if (!$group->store()) {
			return JError::raiseWarning(500, $group->getError());
		}
		$groupId = $db->insertid();

		$sql = "DROP TABLE IF EXISTS $dbTableName;";
		$db->setQuery($sql);
		$db->query();

		echo "<li>Group 'Contact Details' created</li>";

		$element =& JTable::getInstance('element', 'Table');
		$element->label = "First Name";
		$element->name = "first_name";
		$element->plugin = "fabrikfield";
		$element->show_in_table_summary = 1;
		$element->link_to_detail = 1;
		$element->width = 30;
		$element->group_id = $groupId;
		$element->state = 1;
		$element->ordering = 1;
		if (!$element->store()) {
			return JError::raiseWarning(500, $element->getError());
		}

		echo "<li>Element 'First Name' added to group 'Contact Details'</li>";

		$element =& JTable::getInstance('element', 'Table');
		$element->label = "Last Name";
		$element->name = "last_name";
		$element->plugin = "fabrikfield";
		$element->show_in_table_summary = 1;
		$element->width = 30;
		$element->link_to_detail = 1;
		$element->group_id = $groupId;
		$element->state = 1;
		$element->ordering = 2;
		if (!$element->store()) {
			return JError::raiseWarning(500, $element->getError());
		}
		echo "<li>Element 'Last Name' added to group 'Contact Details'</li>";

		$element =& JTable::getInstance('element', 'Table');
		$element->label = "Email";
		$element->show_in_table_summary = 1;
		$element->name = "email";
		$element->plugin = "fabrikfield";
		$element->width = 30;
		$element->group_id = $groupId;
		$element->state = 1;
		$element->ordering = 3;
		if (!$element->store()) {
			return JError::raiseWarning(500, $element->getError());
		}
		echo "<li>Element 'Email' added to group 'Contact Details'</li>";

		$group =& JTable::getInstance('Group', 'FabrikTable');
		$group->name = "Your Enquiry";
		$group->label = "Your Enquiry";
		$group->state = 1;

		if (!$group->store()) {
			return JError::raiseWarning(500, $group->getError());
		}
		$group2Id = $db->insertid();
		echo "<li>Group 'Your Enquiry' created</li>";

		$element =& JTable::getInstance('element', 'Table');
		$element->label = "Message";
		$element->name = "message";
		$element->plugin = "fabriktextarea";
		$element->width = 30;
		$element->height = 10;
		$element->state = 1;
		$element->ordering = 1;
		$element->group_id = $group2Id;
		if (!$element->store()) {
				return JError::raiseWarning(500, $element->getError());
		}
		echo "<li>Element 'Message' added to group 'Your Enquiry'</li>";

		$form =& JTable::getInstance('form', 'Table');
		$form->label = "Contact Us";
		$form->record_in_database = 1;
		$form->intro = "This is a sample contact us form, that is stored in a database table";

		$form->submit_button_label = "Submit";
		$form->state = 1;

		$form->form_template = "default";
		$form->view_only_template = "default";

		if (!$form->store()) {
			return JError::raiseWarning(500, $form->getError());
		}
		echo "<li>Form 'Contact Us' created</li>";
		$formId = $db->insertid();

		$query = "INSERT INTO #__fabrik_formgroup (`form_id`,`group_id`,`ordering`) " .
				"VALUES ('$formId', '$groupId', '0')";
		$db->setQuery($query);
		$db->query();
		echo $db->getErrorMsg();

		$query = "INSERT INTO #__fabrik_formgroup (`form_id`,`group_id`,`ordering`) " .
				"VALUES ('$formId', '$group2Id', '1')";
		$db->setQuery($query);
		$db->query();
		echo $db->getErrorMsg();
		echo "<li>Groups added to 'Contact Us' form</li>";
		$tableModel =& JModel::getInstance('Table', 'FabrikModel');
		$table = JTable::getInstance('table', 'Table');
		$table->label = "Contact Us Data";
		$table->introduction = "This table stores the data submitted in the contact us for";
		$table->form_id = $formId;
		$table->connection_id = 1;
		$table->db_table_name = $dbTableName;
		$table->db_primary_key = "`".$dbTableName. '`.`id`';
		$table->auto_inc = 1;
		$table->state = 1;
		$table->attribs = $tableModel->getDefaultAttribs();
		$table->template = 'default';

		if (!$table->store()) {
			return JError::raiseWarning(500, $table->getError());
		}
		echo "<li>Table for 'Contact Us' created</li></div>";
		if (!$form->store()) {
			echo "<script> alert('".$form->getError()."');  </script>\n";
			exit ();
		}
		$formModel =& JModel::getInstance('Form', 'FabrikModel');
		$formModel->setId($form->id);
		$formModel->_form = $form;

		$tableModel->setId($table->id);
		$tableModel->getTable();
		$tableModel->createDBTable($formModel, $table->db_table_name, $db);
	}

	/**
	 * ajax function to update any drop down that contains records relating to the selected table
	 * called each time the selected table is changed
	 */

	function ajax_updateColumDropDowns()
	{
		$cnnId = JRequest::getInt('cid', 1);
		$tbl	= JRequest::getVar('table', '');
		$model = JModel::getInstance('Table', 'FabrikModel');
		$fieldDropDown 	= $model->getFieldsDropDown( $cnnId, $tbl, '-', false, 'order_by');
		$fieldDropDown2 = $model->getFieldsDropDown( $cnnId, $tbl, '-', false, 'group_by');
		$fieldDropDown3 = $model->getFieldsDropDown( $cnnId, $tbl, '-', false, 'params[group_by_order]');
		echo "$('orderByTd').innerHTML = '$fieldDropDown';";
		echo "if($('groupByTd')) {
			$('groupByTd').innerHTML = '$fieldDropDown2';
		}";
		echo "if($('groupByOrderTd')) {
			$('groupByOrderTd').innerHTML = '$fieldDropDown3';
		}";
	}

	function getRSSFeed()
	{
		//  get RSS parsed object
		$options = array();
		$options['rssUrl']		= 'http://feeds.feedburner.com/fabrik';
		$options['cache_time']	= 86400;

		$rssDoc =& JFactory::getXMLparser('RSS', $options);
		if ($rssDoc == false) {
			$output = JText::_('Error: Feed not retrieved');
		} else {
			// channel header and link
			$title 	= $rssDoc->get_title();
			$link	= $rssDoc->get_link();

			$output = '<table class="adminlist">';
			$output .= '<tr><th colspan="3"><a href="'.$link.'" target="_blank">'.JText::_($title) .'</th></tr>';

			$items = array_slice($rssDoc->get_items(), 0, 3);
			$numItems = count($items);
			if ($numItems == 0) {
				$output .= '<tr><th>' .JText::_('No news items found'). '</th></tr>';
			} else {
				$k = 0;
				for ($j = 0; $j < $numItems; $j++) {
					$item = $items[$j];
					$output .= '<tr><td class="row' .$k. '">';
					$output .= '<a href="' .$item->get_link(). '" target="_blank">' .$item->get_title(). '</a>';
					$output .= '<br />'.$item->get_date('Y-m-d');
					if($item->get_description()) {
						$description = $this->_truncateText($item->get_description(), 50);
						$output .= '<br />' .$description;
					}
					$output .= '</td></tr>';
				}
			}
			$k = 1 - $k;

			$output .= '</table>';
		}
		return $output;
	}

	function _truncateText($txt, $length)
	{
		$bits = explode(' ', $txt);
		if (count($bits) < $length) {
			return $txt;
		} else {
			$bits = implode(' ', array_slice($bits, 0, $length));
			return $bits;
		}
	}

	/**
	 * called from installation/install.fabrik.php
	 * @return unknown_type
	 */

	function endinstallation()
	{
		@ini_set('max_execution_time', 380);
	  jimport('joomla.filesystem.file');
	  jimport('joomla.filesystem.archive');
	  $app =& JFactory::getApplication();

 	  $files = array(
	    "plugins" => COM_FABRIK_FRONTEND.DS.'plugins.zip',
	    "libs" => COM_FABRIK_FRONTEND.DS.'libs.zip',
	    "views" => COM_FABRIK_FRONTEND.DS.'views.zip');

	  foreach ($files as $folder => $file) {
	  	@ini_set('max_execution_time', 180);
		  if (JFile::exists($file)) {
		  	if (JArchive::extract( $file, COM_FABRIK_FRONTEND.DS.$folder)) {
		  		echo "<span style='color:green'>" . JText::sprintf('INSTALLATION_UNCOMPRESS_SUCCESS', $file) . "</span></br />";
		  		JFile::delete($file);
		  	} else {
		  		echo "<strong style='color:red'>$file: " . JText::sprintf( 'INSTALLATION_UNCOMPRESS_FAIL', $file, $file) . "</strong><br />";
		  	}
		  }else{
		    echo "<strong style='color:red'>" . JText::sprintf( 'INSTALLATION_UNCOMPRESS_FAIL', $file, $file) . "</strong><br />";
		  }
	  }
	  echo "<p>moved ".COM_FABRIK_FRONTEND.DS.'fabrikfeed'. ' to '.JPATH_SITE.DS.'libraries'.DS.'joomla'.DS.'document</p>';
	  $dest = JPATH_SITE.DS.'libraries'.DS.'joomla'.DS.'document'.DS.'fabrikfeed';
	  JFolder::delete($dest);
	  $ret = JFolder::move(COM_FABRIK_FRONTEND.DS.'fabrikfeed', $dest);
	  echo "<p>Installation finished</p>";
	  echo '<p><a target="_top" href="index.php?option=com_fabrik&amp;task=installSampleData">Click
here to install sample data</a></p>
	  ';
	  $wierd = COM_FABRIK_FRONTEND.DS.'views'.DS."<span style='color:red'>";
	  if (JFolder::exists($wierd)) {
	  	//echo "deleting $wierd";
	  	JFolder::delete($wierd);
	  }else{
	  	//echo "$wierd not found";
	  }
	}
}
?>