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

require_once(COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'menu.php');
require_once(COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'adminhtml.php');
require_once(COM_FABRIK_BASE.DS.'administrator'.DS.'components'.DS.'com_fabrik'.DS.'helpers'.DS.'fabrik.php');

/**
 * @package		Joomla
 * @subpackage	Fabrik
 */

class FabrikControllerVisualization extends JController
{

	/**
	 * Constructor
	 */
	function __construct($config = array())
	{
		parent::__construct($config);
		// Register Extra tasks
		$this->registerTask('add',			'edit');
		$this->registerTask('apply',		'save');
		$this->registerTask('unpublish',	'publish');
		$this->registerTask('menulinkVisualization', 'save');
		$this->registerTask('go2menu', 'save');
		$this->registerTask('go2menuitem', 'save');
	}

	/**
	 * Edit a connection
	 */

	function edit()
	{
		$user	  = &JFactory::getUser();
		$db 		=& JFactory::getDBO();
		$row 		=& JTable::getInstance('visualization', 'Table');
		if ($this->_task == 'edit') {
			$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
			$cid	= array((int) $cid[0]);
		} else {
			$cid	= array(0);
		}

		$row->load($cid[0]);


		if ($cid) {
			$row->checkout( $user->get('id'));
		}

		// get params definitions
		$params = new fabrikParams($row->attribs, JPATH_COMPONENT.DS.'xml'.DS.'visualization.xml');
		require_once(JPATH_COMPONENT.DS.'views'.DS.'visualization.php');

		//build list of visualization plugins
		$pluginManager	 	=& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$pluginManager->getPlugInGroup('visualization');

		$lists['plugins'] = $pluginManager->getElementTypeDd($row->plugin, 'plugin', 'class="inputbox"');

		//build list of menus
		$lists['menuselect'] = FabrikHelperMenu::MenuSelect();
		if ($row->id != '') {
			//only existing tables can have a menu linked to them
			$and = "\n AND link LIKE 'index.php?option=com_fabrik&view=visualization%'";
			$and .= " AND params LIKE '%visualizationid=".$row->id."%'";
			$menus = FabrikHelperMenu::Links2Menu('component', $and);
		} else {
			$menus = null;
		}

		//get table and connection drop downs
		$db->setQuery("SELECT id AS value, label AS text FROM #__fabrik_tables");
		$rows = $db->loadObjectList();
		$default = '';
		$lists['tables'] = JHTML::_('select.genericlist', $rows, 'table[]', "class=\"inputbox\"  size=\"1\" ", 'value', 'text', $default);

		// Create the form
		$form = new JParameter('', JPATH_COMPONENT.DS.'models'.DS.'visualization.xml');
		$form->bind($row);
		$form->set('created', JHTML::_('date', $row->created, '%Y-%m-%d %H:%M:%S'));
		$form->set('publish_up', JHTML::_('date', $row->publish_up, '%Y-%m-%d %H:%M:%S'));

		if ($cid[0] == 0 || $form->get('publish_down') == '' || $form->get('publish_down') ==  $db->getNullDate()) {
			$form->set('publish_down', JText::_('NEVER'));
		} else {
			$form->set('publish_down', JHTML::_('date', $row->publish_down, '%Y-%m-%d %H:%M:%S'));
		}

		FabrikViewVisualization::edit($row, $params, $lists, $menus, $pluginManager, $form);
	}

  /**
   * cancel editing
   */

  function cancel()
  {
    JRequest::checkToken() or die('Invalid Token');
  	$row 		=& JTable::getInstance('visualization', 'Table');
  	$id 		= JRequest::getInt('id', 0, 'post');
  	$row->load($id);
  	$row->checkin();
  	$this->setRedirect('index.php?option=com_fabrik&c=visualization');
  }

	/**
	 * Save a visualization
	 */

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$task = JRequest::getCmd('task');
		$pluginManager	=& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$className 			= JRequest::getVar('plugin', 'calendar', 'post');
		$pluginModel 		=& $pluginManager->getPlugIn($className, 'visualization');
		$id 						= JRequest::getInt('id', 0, 'post');
		$pluginModel->setId($id);

		$row =& JTable::getInstance('visualization', 'Table');

		$post	= JRequest::get('post');

		if (!$row->bind($post)) {
			return JError::raiseWarning(500, $row->getError());
		}

		//$filter	= new JFilterInput(null, null, 1, 1);
		list($dofilter, $filter) = FabrikWorker::getContentFilter();
		$intro_text = JRequest::getVar('intro_text', '', 'post', 'string', JREQUEST_ALLOWRAW);
		$row->intro_text = $dofilter ? $filter->clean($intro_text) : $intro_text;

		$details = JRequest::getVar('details', array(), 'post', 'array');
		$row->bind($details);

		// 	save params
		$pluginModel->attribs =& $row->attribs;
		$params = $pluginModel->getParams();
		$row->attribs = $params->updateAttribsFromParams(JRequest::getVar('params', array(), 'post', 'array'));

		FabrikHelper::prepareSaveDate($row->publish_down);
		FabrikHelper::prepareSaveDate($row->created);
		FabrikHelper::prepareSaveDate($row->publish_up);

		$user =& JFactory::getUser();
		if ($row->id != 0) {
			$datenow =& JFactory::getDate();
			$row->modified 		= $datenow->toMySQL();
			$row->modified_by 	= $user->get('id');
		}
		if (!$row->store()) {
			return JError::raiseWarning(500, $row->getError());
		}
		$row->checkin();

		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_fabrik&c=visualization&task=edit&cid[]='. $row->id;
				$msg = JText::_('VISUALIZATION SAVED');
				break;

			case 'save':
			default:
				$link = 'index.php?option=com_fabrik&c=visualization';
				$msg = JText::_('VISUALIZATION SAVED');
				break;
		}
		$this->setRedirect($link, $msg);
	}

	/**
	 * Publish a visualization
	 */

	function publish()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=visualization');

		// Initialize variables
		$db			=& JFactory::getDBO();
		$user		=& JFactory::getUser();
		$cid		= JRequest::getVar('cid', array(), 'post', 'array');
		$task		= JRequest::getCmd('task');
		$publish	= ($task == 'publish');
		$n			= count($cid);

		if (empty($cid)) {
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}

		JArrayHelper::toInteger($cid);
		$cids = implode(',', $cid);

		$query = 'UPDATE #__fabrik_visualizations'
		. ' SET state = ' . (int) $publish
		. ' WHERE id IN ( '. $cids.'  )'
		. ' AND ( checked_out = 0 OR ( checked_out = ' .(int) $user->get('id'). ') )'
		;
		$db->setQuery($query);
		if (!$db->query()) {
			return JError::raiseWarning(500, $row->getError());
		}
		$this->setMessage( JText::sprintf( $publish ? 'ITEMS PUBLISHED' : 'ITEMS UPUBLISHED', $n));
	}

	/**
	 * Display the list of Visualizations
	 */

	function display()
	{
		$app =& JFactory::getApplication();
		$db =& JFactory::getDBO();
		$context					= 'com_fabrik.vizualization.list.';
		$filter_plugin	= $app->getUserStateFromRequest( $context."filter_plugin", 'filter_plugin', '');
		//get active vizulalization plugins
		$pluginManager	 	=& JModel::getInstance('Pluginmanager', 'FabrikModel');
		$pluginManager->_group = 'visualization';
		$pluginManager->loadPlugInGroup('visualization');
		$lists['vizualizations'] = $pluginManager->getElementTypeDd($filter_plugin, 'filter_plugin', 'class="inputbox"  onchange="document.adminForm.submit();"', '- ' . JText::_('SELECT PLUGIN TYPE') . ' -');

		$where = ($filter_plugin == '') ? '' : ' WHERE plugin = "'.$filter_plugin.'"';
		// get the total number of records
		$db->setQuery("SELECT count(*) FROM #__fabrik_visualizations $where");
		$total = $db->loadResult();
		echo $db->getErrorMsg();

		$limit			= $app->getUserStateFromRequest( $context.'limit', 'limit', $app->getCfg('list_limit'), 'int');
		$limitstart = $app->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int');

		$sql = "SELECT * FROM #__fabrik_visualizations $where";
		$db->setQuery($sql, $limitstart, $limit);
		jimport('joomla.html.pagination');
		$pageNav = new JPagination($total, $limitstart, $limit);
		$rows = $db->loadObjectList();
		require_once(JPATH_COMPONENT.DS.'views'.DS.'visualization.php');
		FabrikViewVisualization::show($rows, $pageNav, $lists );
	}

	/**
	 * copy a connection
	 */

	function copy()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=visualization');

		$cid		= JRequest::getVar('cid', null, 'post', 'array');
		$db			=& JFactory::getDBO();
		$rule		=& JTable::getInstance('visualization', 'Table');
		$user		= &JFactory::getUser();
		$n			= count($cid);

		if ($n > 0)
		{
			foreach ($cid as $id)
			{
				if ($rule->load((int)$id))
				{
					$rule->id				= 0;
					if (!$rule->store()) {
						return JError::raiseWarning($rule->getError());
					}
				}
				else {
					return JError::raiseWarning(500, $rule->getError());
				}
			}
		}else {
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}
		$this->setMessage( JText::sprintf( 'ITEMS COPIED', $n));
	}

	/**
	 * remove visualization
	 */

	function remove( )
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=visualization');

		// Initialize variables
		$db		=& JFactory::getDBO();
		$cid	= JRequest::getVar('cid', array(), 'post', 'array');
		$n		= count($cid);
		JArrayHelper::toInteger($cid);

		if ($n)
		{
			$query = 'DELETE FROM #__fabrik_visualizations'
			. ' WHERE id = ' . implode(' OR id = ', $cid )
			;
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500, $db->getError());
			}
		}
		$this->setMessage( JText::sprintf( 'ITEMS REMOVED', $n));

	}
}
?>