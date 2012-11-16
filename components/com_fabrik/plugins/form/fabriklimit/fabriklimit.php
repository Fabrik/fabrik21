<?php
/**
 * Form limit submissions plugin
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

class FabrikModelfabriklimit extends FabrikModelFormPlugin {

	/**
	 * @var array of files to attach to email
	 */
	var $_counter = null;

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * process the plugin, called when form is loaded
	 *
	 * @param object $params
	 * @param object form model
	 * @returns bol
	 */

	function onLoad($params, &$formModel)
	{
		FabrikHelperHTML::script('namespace.js', 'administrator/components/com_fabrik/views/', true);
		return $this->_process($params, $formModel);
	}

	private function _process(&$params, &$formModel)
	{
		$user =& JFactory::getUser();
		$db =& JFactory::getDBO();
		if ($params->get('limit_allow_anonymous')) {
			return true;
		}
		if (JRequest::getCmd('view') === 'details' || JRequest::getInt('rowid', 0) > 0) {
			return true;
		}

		$tableid = (int)$params->get('limit_table');
		if ($tableid === 0) {
			//use the limit setting supplied in the admin params
			$limit = (int)$params->get('limit_length');
		} else {
			//look up the limit from the table spec'd in the admin params
			$table =& JModel::getInstance('Table', 'FabrikModel');
			$table->setId($tableid);
			$max = $db->nameQuote(FabrikString::shortColName($params->get('limit_max')));
			$userfield = $db->nameQuote(FabrikString::shortColName($params->get('limit_user')));
			$db->setQuery("SELECT $max FROM " . $table->getTable()->db_table_name . " WHERE $userfield = " . (int)$user->get('id'));
			$limit = (int)$db->loadResult();

		}
		$field = $params->get('limit_userfield');
		$tableModel =& $formModel->getTableModel();
		$table =& $tableModel->getTable();
		$db =& $tableModel->getDb();
		$db->setQuery("SELECT COUNT($field) FROM $table->db_table_name WHERE $field = " . (int)$user->get('id'));

		$c = $db->loadResult();
		if ($c >= $limit) {
			$msg = $params->get('limit_reached_message');
			$msg = str_replace('{limit}', $limit, $msg);
			JError::raiseNotice(1, $msg);
			return false;
		} else {
			$app =& JFactory::getApplication();
			$app->enqueueMessage(JText::sprintf('ENTRIES_LEFT_MESSAGE', $limit - $c, $limit));
		}
		return true;
	}

	/**
	 * get JS to manage the plugins html
	 * @see components/com_fabrik/models/FabrikModelPlugin#getAdminJs($form, $lists)
	 */

	function getAdminJs( $form, $lists )
	{
		FabrikHelperHTML::script('admin.js', 'components/com_fabrik/plugins/form/fabriklimit/', true);
		$params =& $this->getParams();

		$children = $params->_xml['_default']->children();

		$opts = $this->getAdminJsOpts($form, $lists);
		foreach ($children as $node) {
			$type = $node->attributes('type');
			//remove any occurance of a mos_ prefix
			$type = str_replace('mos_', '', $type);
			$element =& $params->loadElement($type);
			$repeat 	= $element->getRepeat();
			$c = $element->getRepeatCounter();
			if ($type == 'fabriktables') {
				$connection = $node->attributes('observe');
				$opts->connection_id = $connection;
			}
			if ($type == 'element') {
				$name = $node->attributes('name');
				$opts->$name = new stdClass();
				$opts->$name->published = (int)$node->attributes('published', 0);
				$opts->$name->include_calculations = (int)$node->attributes('include_calculations', 0);
				$opts->$name->showintable = (int)$node->attributes('showintable', 0);
				$opts->$name->table_id = $node->attributes('table');
			}
		}
		$opts = json_encode($opts);
		$script = "new fabrikAdminLimit('{$this->row->name}', '$this->_pluginLabel', $opts)";
		return $script;
	}

}
?>