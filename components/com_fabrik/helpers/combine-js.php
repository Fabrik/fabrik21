<?php
// Written by Ed Eliot (www.ejeliot.com) - provided as-is, use at your own risk
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

class combineJS {

	var $aFiles = array();

	function __construct($editor = 'none')
	{
		/****************** start of config ******************/
		define('CACHE_LENGTH', 31356000); // length of time to cache output file, default approx 1 year
		define('ARCHIVE_FOLDER', 'js'.DS.'archive'); // location to store archive, don't add starting or trailing slashes
	}

	function addFile($f)
	{
		// $$$ hugh - defensive coding
		// http://fabrikar.com/forums/showthread.php?p=53599#post53599
		if (!empty($f) && !in_array($f, $this->aFiles)) {
			$this->aFiles[] = $f;
		}
	}

	function getCacheFile()
	{
		$cachename = '';
		foreach ($this->aFiles as $f) {
			$f = basename($f);
			$f2 = substr($f, 1, 1);
			$f2 .= substr($f, 3, 1);
			$f2 .= substr($f, -1, 1);
			$cachename .= str_replace('.js', '', $f2);
		}
		$cachename .= '.cache';
		return $cachename;
	}

	function outputFolder()
	{
		return COM_FABRIK_FRONTEND.DS.ARCHIVE_FOLDER;
	}

	function output()
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		$app =& JFactory::getApplication();
		//make a unique key out of the files added:
		$cachename = $this->getCacheFile();
		/****************** end of config ********************/

		$folder = $this->outputFolder();
		// create a directory for storing current and archive versions
		if (!JFolder::exists($folder)) {
			JFolder::create($folder);
		}
		// get code from archive folder if it exists, otherwise grab latest files, merge and save in archive folder
		//if in admin always refresh the script as the file name is the same for more than one page
		if (JFile::exists($folder.DS.$cachename) && $app->getName() != 'administrator') {
			//if the cache is out of date
			if (filemtime( $folder.DS.$cachename) < time() - CACHE_LENGTH) {
				$this->write($cachename, $folder);
			}
		} else {
			//cache file not found so write it out
			$this->write($cachename, $folder);
		}
		//test
		//$this->write($cachename, $folder);
	}

	/**
	 * write the combined file into the archive folder
	 * @param string cachefilename
	 * @param string cache foldername
	 */

	function write($cachename, $folder)
	{
		// get and merge code
		$sCode = '';

		foreach ($this->aFiles as $sFile) {
			// $$$ hugh - check it exists first, to avoid PHP notices
			if (JFile::exists(COM_FABRIK_BASE.DS.$sFile)) {
				$sCode .= file_get_contents(COM_FABRIK_BASE.DS.$sFile);
			}
		}
		//$sCode = preg_replace("/\/\*(.*?)\/\*/", '', $sCode);
		//try to remove block comments
		//$sCode = preg_replace("/\/\*(.*?)\*\//", '', $sCode);
		//remove tabs
		//$sCode = preg_replace("/\t/", '', $sCode);
		//try to get rid of one line conmments but doesnt work:
		//$sCode = preg_replace("/\/\/(.*?)\n/", ' ', $sCode);
		$fbConfig =& JComponentHelper::getParams('com_fabrik');
		$lib = $fbConfig->get('compress_js', 'none');

		if ($lib == 'none') {
			JFile::write($folder.DS.$cachename, $sCode);
		} else {

			///compress it! - test php5 only


			$src = $folder.DS.$cachename;
			$out = $folder.DS.$cachename;

			//$t1 = microtime(true);
			switch ($lib) {
				case "jsmin":
					$lib = 'jsmin'.DS.'jsmin-1.1.1.php';
					require_once(COM_FABRIK_FRONTEND.DS.'libs'.DS.'compression'.DS.'jsmin'.DS.'jsmin-1.1.1.php');
					file_put_contents($out, JSMin::minify($sCode));
					break;
				case "packer":
				default:
					require_once(COM_FABRIK_FRONTEND.DS.'libs'.DS.'compression'.DS.'packer1.1'.DS.'class.JavaScriptPacker.php');
					$encoding = 'Normal'; //0,10,62,95
					$encoding = 0;
					$packer = new JavaScriptPacker($sCode, $encoding, true, false);
					$packed = $packer->pack();
					file_put_contents($out, $packed);
					break;
			}
			//$t2 = microtime(true);
			//$time = sprintf('%.4f', ($t2 - $t1));
			//echo 'script ', $src, ' packed in ' , $out, ', in ', $time, ' s.', "\n";
		}
	}
}
?>