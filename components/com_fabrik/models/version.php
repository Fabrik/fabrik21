<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* $LastChangedDate: 2011-03-08 01:34:42 +0100 (Tue, 08 Mar 2011) $
* $Rev: 4852 $
* $Author: rob $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * Version information
 * @package Joomla
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');

class FabrikModelVersion extends JModel {
	/** @var string Product */
	var $PRODUCT 	= 'Fabrik';
	/** @var int Main Release Level */
	var $RELEASE 	= '2.0';
	/** @var string Development Status */
	var $DEV_STATUS = '';
	/** @var int Sub Release Level */
	var $DEV_LEVEL 	= '2';
	/** @var int build Number */
	var $BUILD	 	= '$Revision: 4852 $';
	/** @var string Date */
	var $RELDATE 	= '1 Sept 2010';
	/** @var string Time */
	var $RELTIME 	= '20:00';
	/** @var string Timezone */
	var $RELTZ 		= 'UTC';
	/** @var string Copyright Text */
	var $COPYRIGHT 	= "Copyright (C) 2005 - 2010 Fabrikar. All rights reserved.";
	/** @var string URL */
	var $URL 		= '<a href="http://www.fabrikar.com">Farbik</a> is Free Software released under the GNU/GPL License.';
	/** last svn revision number before release */
	var $REV = 4028;

	/** last svn commit downloaded by the user
	NOTE to devs you have to manually update this file on each commit
	*/

	var $SVNREV = '$Revision: 4852 $';
	/**
	 * codename ***/
  var $CODENAME = 'Inversions';

  /** enable experimental feautres **/
  var $ADVANCED = true;

	var $headers = array(
		'acc'  => 'Accept: %s',
		'cont' => 'Content-Type: %s'
	);

	function getPostMethod()
	{

	}


	function construct_headers($request)
	{
		return array(
			sprintf($this->headers['acc'],  $this->config['response_type']),
			sprintf($this->headers['cont'], $this->config['response_type'])
		);
	}
	/**
	 * calls the unfuddle api to get the latest svn revision and compares it to the rev
	 * listed in this class
	 */

	function checkRevision()
	{
		$this->_error = '';
		$this->_msg = '';

		if (!function_exists('curl_init')) {
			$this->_error = JText::_("SORRY YOU NEED CURL INSTALLED TO CHECK REVISION");
			return false;
		}

		$this->config = array(
			'port'             => 80,
			'version'          => 1,
			'account'          => 'account_identifier',
			'response_type'    => 'application/xml',
			'username'         => 'anonymous',
			'password'         => 'anonymous',
			'project'			=> 17220,
			'default_assignee' => 8527,
			'default_milestone' => 0
		);

		$headers = array(
			'Content-Type: application/xml',
			'Accept: application/xml'
		);
		$headers = $this->construct_headers('');

		$url = "http://fabrik.unfuddle.com/api/v1/projects/17220/changesets/latest";

		$this->connection = curl_init($url);

		$xml_string = '';
		$curl_options = array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_VERBOSE => 1,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_USERPWD        => 'anonymous:anonymous'
		);

		foreach ($curl_options as $key => $value)
		{
			curl_setopt($this->connection, $key, $value);
		}

		$xml = $this->handle_response(curl_exec($this->connection));
		require_once(COM_FABRIK_BASE . DS."includes".DS."domit".DS."xml_domit_lite_parser.php");
		$xmlDoc = new DOMIT_Lite_Document();

		$ok = $xmlDoc->parseXML($xml);
		if ($ok) {
		 	$this->_msg .= "<p style='color:green'>Version info obtained from SNV server</p>";
		  $rev = $xmlDoc->getElementsByTagName('revision');
		  $rev = $rev->item(0);
		  $lastestRev = $rev->getText();

		  if( $this->SVNREV != $lastestRev) {
		    $this->_msg .= "An update is available from the SVN<br>";
		    $msg = $xmlDoc->getElementsByTagName('message');
		    $msg = $msg->item(0);
		    $date = $xmlDoc->getElementsByTagName('created-at');
		    $date = $date->item(0);
		    $this->_msg .= "<br />message:" . $msg->getText() . "<br>date:" . $date->getText() . "<br><br>";
		  }else{
		  	$this->_msg .= "<p>YOU ARE UP TO DATE!</p>";
		  }
		  $this->_msg .= "This release versions revision = '$this->REV' <br>
		  This installations current SVN revision = '$this->SVNREV' <br>
		    Latest available SVN rev = '$lastestRev'<br />";
		} else {
		  $this->_error = JText::_("UNABLE TO PARSE RESPONSE");
		}
	}

	function handle_response($response, $post = false)
	{
		if (false)
		{
			header('Content-Type: text/xml');
			die($response);
		}

		if (curl_errno($this->connection))
		{
			die("ERROR: " . curl_error($this->connection));
		}
		curl_close($this->connection);

		if ($post)
		{
			return true;
		}
		return json_decode($response,true);
	}

	/**
	 * @return string Long format version
	 */
	function getLongVersion()
	{
		return $this->PRODUCT .' '. $this->RELEASE .'.'. $this->DEV_LEVEL .' '
			. $this->DEV_STATUS
			.' [ '.$this->CODENAME .' ] '. $this->RELDATE .' '
			. $this->RELTIME .' '. $this->RELTZ;
	}

	/**
	 * @return string Short version format
	 */
	function getShortVersion()
	{
		return $this->RELEASE .'.'. $this->DEV_LEVEL;
	}

	/**
	 * @return string Version suffix for help files
	 */
	function getHelpVersion()
	{
		if ($this->RELEASE > '1.0') {
			return '.' . str_replace('.', '', $this->RELEASE);
		} else {
			return '';
		}
	}
}
?>