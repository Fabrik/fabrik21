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


class FabrikModelConnection extends JModel {

	/** none table vars */
	/** @var array a list of all the database tables */
	var $_connectionTables = null;

	/** @var object table **/
	var $_connection = null;

	/** @var object default connection table **/
	var $_defaultConnection = null;

	/** @var array containing db connections */
	var $_dbs = array();

	/** @var int connection id */
	var $_id = null;

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Method to set the element id
	 *
	 * @access	public
	 * @param	int	element ID number
	 */

	function setId($id)
	{
		// Set new element ID
		$this->_id = $id;
	}

	/**
	 * is the conenction table the default connection
	 * @return bol
	 */

	function isDefault()
	{
		return $this->getConnection()->default;
	}

	/**
	 * creates a html dropdown box for the current connection
	 * @param string javascript to add to select box
	 * @param string name of dropdown box
	 * @param string the selected element in the list
	 * @param string class name
	 */

	function getTableDdForThisConnection($javascript = '', $name = 'table_join', $selected = '', $class='inputbox')
	{
		$tableOptions = array();
		$cn = $this->getConnection();
		if ($cn->host and $cn->state == '1') {
			if (@mysql_connect($cn->host, $cn->user, $cn->password)) {
				//ensure db files are included
				jimport('joomla.database.database');
				$options = $this->getConnectionOptions($cn);
				//$fabrikDb = new JDatabaseMySQL($options);
				$fabrikDb =& JDatabase::getInstance($options);
				$tables = $fabrikDb->getTableList();
				$tableOptions[] = JHTML::_('select.option', '', '-');
				if (is_array($tables)) {
					foreach ($tables as $table) {
						$tableOptions[] = JHTML::_('select.option', $table, $table);
					}
				}
			} else {
				$tableOptions[] = JHTML::_('select.option','couldnt connect');
			}
		} else {
			$tableOptions[] = JHTML::_('select.option','host not set');
		}
		return JHTML::_('select.genericlist', $tableOptions, $name, 'class="' . $class . '" size="1" id="' . $name  . '" '.$javascript, 'value', 'text', $selected);
	}

	/**
	 * get a connection table object
	 *
	 * @return object connection tables
	 */

	function &getConnection()
	{
		if (!is_object($this->_connection)) {
			if (JRequest::getVar('fabrikdebug') !== 1) {
				$session =& JFactory::getSession();
				if ($session->has('fabrik.connection.'.$this->_id)) {
					$connProperties = unserialize($session->get('fabrik.connection.'.$this->_id));
					if (is_a($this->_connection, '__PHP_Incomplete_Class')) {
						$session->clear('fabrik.connection.'.$this->_id);
					} else {
						$this->_connection =& JTable::getInstance('connection', 'Table');
						$this->_connection->bind($connProperties);
						return $this->_connection;
					}
				}
			}
			if ($this->_id == -1) {
				$this->_connection = $this->loadDefaultConnection();
			} else {
				$this->_connection =& JTable::getInstance('connection', 'Table');
				$this->_connection->load($this->_id);

			}
			// $$$ rob store the connection for later use as it may be required by modules/plugins
			$session->set('fabrik.connection.'.$this->_id, serialize($this->_connection->getProperties()));
		}
		return $this->_connection;
	}

	/**
	 * load the connection associated with the table
	 * @return object database object using connection details false if connection error
	 */

	function &getDb()
	{
		static $dbs;
		if (!isset($dbs)) {
			$dbs = array();
		}
		$cn =& $this->getConnection();
		if (!array_key_exists($cn->id, $dbs)) {
			$session =& JFactory::getSession();
			//$$$rob lets see if we have an exact config match with J db if so just return that
			$conf =& JFactory::getConfig();
			$host 		= $conf->getValue('config.host');
			$user 		= $conf->getValue('config.user');
			$password = $conf->getValue('config.password');
			$database	= $conf->getValue('config.db');
			$prefix 	= $conf->getValue('config.dbprefix');
			$driver 	= $conf->getValue('config.dbtype');
			$debug 		= $conf->getValue('config.debug');

			$deafult_options = array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix);
			$options = $this->getConnectionOptions($cn);

			if ($this->_compareConnectionOpts($deafult_options, $options)) {
				$dbs[$cn->id] =& JFactory::getDBO();
			} else {
				$dbs[$cn->id] =& JDatabase::getInstance($options);
			}

			if (JError::isError($dbs[$cn->id])) {
				if (JDEBUG) {
					JError::raiseNotice(E_WARNING, 'couldnt connect to Fabrik connection');
				}
				//$$$Rob - not sure why this is happening on badmintonrochelais.com (mySQL 4.0.24) but it seems like
				//you can only use one connection on the site? As JDatabase::getInstance() forces a new connection if its options
				//signature is not found, then fabrik's default connection won't be created, hence defaulting to that one
				if ($cn->default == 1) {
					$dbs[$cn->id] =& JFactory::getDBO();
					// $$$rob remove the error from the error stack
					// if we dont do this the form is not rendered
					JError::getError(true);

				} else {
					$app =& JFactory::getApplication();
					if (!$app->isAdmin()) {
						JError::raiseError(E_ERROR, 'Could not connection to database', $deafult_options);
						jexit('Could not connection to database - possibly a menu item which doesn\'t link to a fabrik table');
					} else {
						// $$$ rob - unset the connection as caching it will mean that changes we make to the incorrect connection in admin, will not result
						// in the test connection link informing the user that the changed connection properties are now correct
						if (JRequest::getCmd('task') == 'test') {
							$session->clear('fabrik.connection.'.$cn->id);
							$this->_connection = null;
						}
						$dbs[$cn->id] = JError::raiseError(E_ERROR, 'Could not connection to database', $deafult_options);
					}
				}
			}
		}
		return $dbs[$cn->id];
	}

	/**
	 * compare two arrays of connection details. Ignore prefix as this may be set to '' if using koowna
	 * @param array $opts1
	 * @param array $opts2
	 * @return boolean
	 */

	private function _compareConnectionOpts($opts1, $opts2)
	{
		return ($opts1['driver'] == $opts2['driver']
		&& $opts1['host'] == $opts2['host']
		&& $opts1['user'] == $opts2['user']
		&& $opts1['password'] == $opts2['password']
		&& $opts1['database'] == $opts2['database']
		);
	}

	/**
	 * get the options to connect to a db
	 *
	 * @param object $connection table
	 * @return array connection options
	 */

	function getConnectionOptions(&$cn)
	{
		$conf				=& JFactory::getConfig();
		$host 			= $cn->host;
		$user 			= $cn->user;
		$password 	= $cn->password;
		$database		= $cn->database;
		if (defined('KOOWA')) {
			$prefix = '';
		} else {
			$prefix 		= $conf->getValue('config.dbprefix');
		}
		$driver 		= $conf->getValue('config.dbtype');
		$debug 			= $conf->getValue('config.debug');
		$options		= array('driver' => $driver, 'host' => $host, 'user' => $user, 'password' => $password, 'database' => $database, 'prefix' => $prefix);
		return $options;
	}

	/**
	 * gets object of connections
	 * @param javascript to run on change
	 */

	function getConnections()
	{
		$db	=& JFactory::getDBO();
		$db->setQuery("SELECT *, id AS value, description AS text FROM #__fabrik_connections WHERE state = '1'");
		$realCnns	= $db->loadObjectList();
		echo $db->getErrorMsg();
		return $realCnns;
	}

	/**
	 * gets dropdown list of published connections
	 * @param object connections stored in database
	 * @param string javascript to run on change
	 * @param string name of connection drop down
	 * @param int default value
	 * @param string element id
	 */

	function getConnectionsDd($realCnns, $javascript, $name = 'connection_id', $selected, $id = '', $attribs= 'class="inputbox" size="1" ')
	{
		if ($id == '') {
			$id = $name;
		}
		$cnns[] = JHTML::_('select.option', '-1', JText::_('COM_FABRIK_PLEASE_SELECT'));
		$cnns = array_merge($cnns, $realCnns);
		$attribs .= $javascript;
		return JHTML::_('select.genericlist', $cnns, $name, $attribs, 'value', 'text', $selected, $id);
	}

	/**
	 * queries all published connections and returns an multidimensional array
	 * of tables and fields for each connection
	 * WARNING: this is likely to
	 * exceed php script execution time if querying a larger remote database
	 *
	 * @param object all available connections
	 */

	function getConnectionTableFields($realCnns)
	{
		$connectionTableFields = array();
		$connectionTableFields[-1] = array();
		$connectionTableFields[-1][] = JHTML::_('select.option', '-1', JText::_('COM_FABRIK_PLEASE_SELECT'));
		foreach ($realCnns as $cn) {
			$connectionTableFields[$cn->value] = array();
			if ($cn->host and $cn->state == '1') {

				$options = $this->getConnectionOptions($cn);
				$fabrikDb =& JDatabase::getInstance($options);

				if (JError::isError($fabrikDb)) {
					$connectionTableFields[$cn->value][$key] = "unable to connect to $cn->text<br />";
				} else {
					if ($fabrikDb->getErrorNum() == 0) {
						$tables = $fabrikDb->getTableList();
						$fields = $fabrikDb->getTableFields($tables);
						$connectionTableFields[$cn->value][$key] = $fields;
					} else {
						$connectionTableFields[$cn->value][$key] = "unable to connect to $cn->text<br />";
					}
				}
			}
		}
		return $connectionTableFields;
	}

	/**
	 * queries all published connections and returns an multidimensional array
	 * of tables for each connection
	 * @param object all available connections
	 */

	function getConnectionTables($realCnns)
	{
		$connectionTables = array();
		$connectionTables[-1] = array();
		$db =& JFactory::getDBO();
		$connectionTables[-1][] = JHTML::_('select.option', '-1', JText::_('COM_FABRIK_PLEASE_SELECT'));
		foreach ($realCnns as $cn) {
			$connectionTables[$cn->value] = array();
			if ($cn->host and $cn->state == '1') {

				$this->_connection = null;
				$this->_id = $cn->id;
				$fabrikDb =& $this->getDb();

				if (JError::isError($fabrikDb)) {
					$connectionTables[$cn->value][] = JHTML::_('select.option','', "unable to connect to $cn->description");
				} else {
					if ($fabrikDb->getErrorNum() == 0) {
						$tables = $fabrikDb->getTableList();
						$connectionTables[$cn->value][] = JHTML::_('select.option', '', '- Please select -');
						if (is_array($tables)) {
							foreach ($tables as $table) {
								$connectionTables[$cn->value][] = JHTML::_('select.option',$table, $table);
							}
						}
					} else {
						$connectionTables[$cn->value][] = JHTML::_('select.option','', "unable to connect to $cn->description");
					}
				}
			}
		}
		return $connectionTables;
	}

	/**
	 * get the tables names in the loaded connection
	 * @param blo add an empty record to the beginning of the list
	 * @return array tables
	 */

	function getThisTables($addBlank = false)
	{
		$cn =& $this->getConnection();
		$fabrikDb =& $this->getDb();
		$tables = $fabrikDb->getTableList();
		if (is_array($tables)) {
			if ($addBlank) {
				$tables = array_merge(array(""), $tables);
			}
			return $tables;
		} else {
			return array();
		}
	}

	/**
	 * tests if you can connect to the connection
	 * @return bol true if connection made otherwise false
	 */

	function testConnection()
	{
		$db =& $this->getDb();
		return JError::isError($db) ? false : true;
	}

	/**
	 * load the default connection
	 */

	function &loadDefaultConnection()
	{
		if (!$this->_defaultConnection) {
			$db	=& JFactory::getDBO();
			$row =& JTable::getInstance('Connection', 'Table');
			$row->_tbl_key = '`default`';
			$row->load(1);
			$row->_tbl_key = 'id';
			$this->_defaultConnection = $row;
		}
		$this->_connection =& $this->_defaultConnection;
		return $this->_defaultConnection;
	}
}

?>