<?php

/**
 * Observe other table plugins
 * @package Joomla
 * @subpackage Fabrik
 * @author Rob Clayburn
 * @copyright (C) Rob Clayburn
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-table.php';
require_once COM_FABRIK_FRONTEND . '/helpers/html.php';

/**
 * Decrypt selected fields with DES-ECB encryption method
 * 
 * @package  Fabrik
 * @since    2.1.2
 */

class FabrikModelDecrypt extends FabrikModelTablePlugin
{

	var $_counter = null;

	/**
	 * Constructor
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * (non-PHPdoc)
	 * @see FabrikModelTablePlugin::getAclParam()
	 */

	function getAclParam()
	{
		return 'php_decrypt_access';
	}

	/**
	 * determine if the table plugin is a button and can be activated only when rows are selected
	 * @return bol
	 */

	function canSelectRows()
	{
		return false;
	}

	/**
	 * run when the table loads its data(non-PHPdoc)
	 * @see components/com_fabrik/models/FabrikModelTablePlugin#onLoadData($params, $oRequest)
	 */
	public function onLoadDataBeforeFormat(&$params, &$model)
	{
		$this->key = $params->get('decrypt_key');
		$this->init();
		$fields = explode(',', $params->get('decrypt_fields'));

		foreach ($model->_data as &$row)
		{
			foreach ($fields as $field)
			{
				$field = trim($field);
				if (isset($row->$field))
				{
					//echo $field . ': from  ' . $row->$field;
					$row->$field = $this->decrypt(utf8_decode($row->$field));
					//echo 'to: ' . $row->$field . "<br>";
				}
			}
		}
	}

	protected function init()
	{

		$this->algorithm = MCRYPT_DES;
		$this->mode = MCRYPT_MODE_ECB;
		$this->iv_size = mcrypt_get_iv_size($this->algorithm, $this->mode);
		$this->iv = mcrypt_create_iv($this->iv_size, MCRYPT_RAND);
	}

	protected function encrypt($data)
	{
		$size = mcrypt_get_block_size($this->algorithm, $this->mode);
		$data = $this->pkcs5_pad($data, $size);
		return base64_encode(mcrypt_encrypt($this->algorithm, $this->key, $data, $this->mode, $this->iv));
	}

	protected function decrypt($data)
	{
		return $this->pkcs5_unpad(rtrim(mcrypt_decrypt($this->algorithm, $this->key, base64_decode($data), $this->mode, $this->iv)));
	}

	protected function pkcs5_pad($text, $blocksize)
	{
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}

	protected function pkcs5_unpad($text)
	{
		$pad = ord($text{strlen($text) - 1});
		if ($pad > strlen($text))
			return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
			return false;
		return substr($text, 0, -1 * $pad);
	}

	/**
	 * show a new for entering the form actions options
	 */

	function renderAdminSettings($elementId, &$row, &$params, $lists, $c)
	{
		$params->_counter_override = $this->_counter;
		$display = ($this->_adminVisible) ? "display:block" : "display:none";
		$return = '<div class="page-' . $elementId . ' elementSettings" style="' . $display . '">
 		' . $params->render('params', '_default', false, $c) . '</div>
 		';
		$return = str_replace("\r", "", $return);
		return $return;
	}

}
?>