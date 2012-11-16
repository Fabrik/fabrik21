<?php
/**
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
class TableFormsession extends JTable
{
	/** @var int Primary key */
	var $id = null;
	
	/** @var string identifiying hash */
	var $hash = null;
	
	/** @var int user id */
	var $user_id = null;
	
	/** @var int form id */
	var $form_id = null;
	
	/** @var int row id */
	var $row_id = null;
	
	/** @var int last page */
	var $last_page = null;
	
	/** @var string referring url */
	var $referring_url  = null;
	
	/** @var  string serialize form data */
	var $data  = null;
	
	/** @var string time data **/
	var $time_date  = null; 

 	/*
 	 * 
 	 */

	function __construct(&$_db )
	{
		parent::__construct('#__fabrik_form_sessions', 'id', $_db);
	}

}
?>
