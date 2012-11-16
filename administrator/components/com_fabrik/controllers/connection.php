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

class FabrikControllerConnection extends JController
{

	/**
	 * Constructor
	 */

	function __construct($config = array())
	{
		parent::__construct($config);
		// Register Extra tasks
		$this->registerTask('add', 'edit');
		$this->registerTask('unpublish',	'publish');
		$this->registerTask('apply',		'save');
	}

	/**
	 * trys to connection to the database
	 * @return string connection message
	 */

	function test( )
	{
		JRequest::checkToken() or die('Invalid Token');
		$db =& JFactory::getDBO();
		$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
		$cid	= array((int) $cid[0]);
		$link = 'index.php?option=com_fabrik&c=connection&view=display';

		foreach ($cid as $id) {
			$model = JModel::getInstance('Connection', 'FabrikModel');
			$model->setId($id);
			if ($model->testConnection() == false) {
				JError::raiseWarning(500,  JText::_('UABEL TO CONNECT'));
				$this->setRedirect($link);
				return;
			}
		}
		$this->setRedirect($link, JText::_('CONNECTION SUCESSFUL'));
	}

	/**
	 * set the default connection
	 */

	function setdefault()
	{
		JRequest::checkToken() or die('Invalid Token');
		$db =& JFactory::getDBO();
		$cid	= JRequest::getVar('cid', 0, 'method', 'array');
		$cid	= ((int) $cid[0]);
		$model = JModel::getInstance('Connection', 'FabrikModel');
		$link = 'index.php?option=com_fabrik&c=connection&view=display';
		$model->setDefault($cid);
		$this->setRedirect($link, JText::_('DEFAULT CONNECTION UPDATED'));
	}

	/**
	 * cancel editing a connection
	 */

	function cancel()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$this->setRedirect('index.php?option=com_fabrik&c=connection');
		// Initialize variables
		$db		=& JFactory::getDBO();
		$post	= JRequest::get('post');
		$row	=& JTable::getInstance('connection', 'Table');
		$row->bind($post);
		$row->checkin();
	}


	/**
	 * Edit a connection
	 */

	function edit()
	{

		$user =& JFactory::getUser();
		$db =& JFactory::getDBO();
		$row =& JTable::getInstance('connection', 'Table');
		if ($this->_task == 'edit') {
			$cid	= JRequest::getVar('cid', array(0), 'method', 'array');
			$cid	= array((int) $cid[0]);
		} else {
			$cid	= array(0);
		}
		$row->load($cid[0]);
		// fail if checked out not by 'me'
		if ($row->checked_out && $row->checked_out != $user->get('id')) {
			$this->setRedirect('index.php?option=com_fabrik&c=connection');
			return JError::raiseWarning(500, 'The connection '. $row->description .' is currently being edited by another administrator');
		}
		if ($cid) {
			$row->checkout($user->get('id'));
		}
		require_once(JPATH_COMPONENT.DS.'views'.DS.'connection.php');
		FabrikViewConenction::edit($row);
	}

	/**
	 * Save a connection
	 */

	function save()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$db =& JFactory::getDBO();
		$row =& JTable::getInstance('connection', 'Table');
		if (JRequest::getVar('passwordConf', '', 'post') !=  JRequest::getVar('password', '', 'post')) {
			return JError::raiseWarning(500, JText::_('FBK_PASSWORDS_DO_NOT_MATCH'));
		}

		$post	= JRequest::get('post');

		if (JRequest::getVar('id', '', 'post') != '0') {
			/* if we're editing an existing connection and no new password has been added we want to delete the post password data*/
			if (JRequest::getVar('password', '', 'post') == '') {
				unset($post['password']);
			}
		}
		if (!$row->bind($post)) {
			return JError::raiseWarning(500, $row->getError());
		}

		if (!$row->store()) {
			return JError::raiseWarning(500, $row->getError());
		}

		// remove all sessions except the logged in admin user
		$db =& JFactory::getDBO();
		$user =& JFactory::getUser();
		$db->setQuery("DELETE FROM #__session WHERE userid != ".(int)$user->get('id'));
		$db->query();

		// clear current logged in users session connection
		$session =& JFactory::getSession();
		$skey = 'fabrik.connection.'.$row->id;
		if ($session->has($skey)) {
			$session->clear($skey);
		}

		$row->checkin();
		$task = JRequest::getCmd('task');
		switch ($task)
		{
			case 'apply':
				$link = 'index.php?option=com_fabrik&c=connection&task=edit&cid[]='. $row->id;
				break;

			case 'save':
			default:
				$link = 'index.php?option=com_fabrik&c=connection&view=display';
				break;
		}
		$this->setRedirect($link, JText::_('CONNECTION SAVED'));
	}

	/**
	 * Publish a connection
	 */

	function publish()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');

		$this->setRedirect('index.php?option=com_fabrik&c=connection');

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

		$query = 'UPDATE #__fabrik_connections'
		. ' SET state = ' . (int) $publish
		. ' WHERE id IN ( '. $cids.'  )'
		. ' AND ( checked_out = 0 OR ( checked_out = ' .(int) $user->get('id'). ') )'
		;
		$db->setQuery($query);
		if (!$db->query()) {
			return JError::raiseWarning(500, $row->getError());
		}
		$this->setMessage( JText::sprintf( $publish ? 'Items published' : 'Items unpublished', $n));
	}

	/**
	 * Display the list of connections
	 */

	function display()
	{
		$app =& JFactory::getApplication();
		$db =& JFactory::getDBO();
		// get the total number of records
		$db->setQuery("SELECT count(*) FROM #__fabrik_connections");
		$total = $db->loadResult();
		$context			= 'com_fabrik.connection.list.';
		$limit						= $app->getUserStateFromRequest( $context.'limit', 'limit', $app->getCfg('list_limit'), 'int');
		$limitstart 			= $app->getUserStateFromRequest( $context.'limitstart', 'limitstart', 0, 'int');
		$sql = "SELECT *, u.name AS editor, #__fabrik_connections.id AS id FROM #__fabrik_connections " .
				"\n LEFT JOIN #__users AS u ON #__fabrik_connections.checked_out = u.id";
		$db->setQuery($sql, $limitstart, $limit);
		jimport('joomla.html.pagination');
		$pageNav = new JPagination($total, $limitstart, $limit);
		$rows = $db->loadObjectList();
		require_once(JPATH_COMPONENT.DS.'views'.DS.'connection.php');
		FabrikViewConenction::show($rows, $pageNav);
	}

	/**
	 * copy a connection
	 * @param int connection id
	 */

	function copy()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$this->setRedirect('index.php?option=com_fabrik&c=connection');
		$cid		= JRequest::getVar('cid', null, 'post', 'array');
		$db			=& JFactory::getDBO();
		$connection	=& JTable::getInstance('connection', 'Table');
		$user		= &JFactory::getUser();
		$n			= count($cid);

		if ($n > 0)
		{
			foreach ($cid as $id)
			{
				if ($connection->load((int)$id))
				{
					$connection->id				= 0;
					$connection->description	= 'Copy of ' . $connection->description;
					$connection->default			= 0;

					if (!$connection->store()) {
						return JError::raiseWarning($connection->getError());
					}
				}
				else {
					return JError::raiseWarning(500, $connection->getError());
				}
			}
		}
		else {
			return JError::raiseWarning(500, JText::_('NO ITEMS SELECTED'));
		}
		$this->setMessage( JText::sprintf( 'ITEMS COPIED', $n));
	}


	/**
	 * delete connection
	 */

	function remove()
	{
		// Check for request forgeries
		JRequest::checkToken() or die('Invalid Token');
		$this->setRedirect('index.php?option=com_fabrik&c=connection');
		// Initialize variables
		$db		=& JFactory::getDBO();
		$cid	= JRequest::getVar('cid', array(), 'post', 'array');
		$n		= count($cid);
		JArrayHelper::toInteger($cid);
		if (in_array(1, $cid)) {
			return JError::raiseWarning(500, JText::_('CAN NOT DELETE FIRST CONNECTION'));
		}
		if ($n)
		{
			$query = 'DELETE FROM #__fabrik_connections'
			. ' WHERE id = ' . implode(' OR id = ', $cid )
			;
			$db->setQuery($query);
			if (!$db->query()) {
				JError::raiseWarning(500, $db->getError());
			}
		}

		$this->setMessage( JText::sprintf( 'Items removed', $n));
	}
}
?>