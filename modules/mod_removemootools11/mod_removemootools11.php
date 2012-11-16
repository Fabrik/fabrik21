<?php
/**
* @version
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.filesystem.file');
$defines = JFile::exists(JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php') ? JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'user_defines.php' : JPATH_SITE.DS.'components'.DS.'com_fabrik'.DS.'defines.php';
require_once( $defines );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'parent.php' );
require_once( COM_FABRIK_FRONTEND.DS.'helpers'.DS.'html.php' );
FabrikHelperHTML::cleanMootools();
?>