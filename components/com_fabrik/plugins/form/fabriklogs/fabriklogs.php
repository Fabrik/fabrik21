<?php
/**
 * Form email plugin
 * @package Joomla
 * @subpackage Fabrik
 * @author peamak
 * @copyright (C) fabrikar.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

//require the abstract plugin class
require_once(COM_FABRIK_FRONTEND.DS.'models'.DS.'plugin-form.php');

class FabrikModelfabriklogs extends FabrikModelFormPlugin {

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

	function onLoad(&$params, &$formModel)
	{
		if ((JRequest::getVar('view') == 'details') && ($params->get('log_details') != '0')) {
			$this->log($params, $formModel, 'form.load.details');
		}
		elseif ((JRequest::getVar('view') == 'form') && ($params->get('log_form_load') != '0')) {
			$this->log($params, $formModel, 'form.load.form');
		}
		return true;
	}

	protected function getMessageType($rowid)
	{
		if (JRequest::getVar('view') == 'details') {
			return 'form.details';
		}
		if (($rowid == "=") || ($rowid == '&') || ($rowid == '') || $rowid == 0) {
			return 'form.add';
		} else {
			return 'form.edit';
		}

	}
	/**
	 * process the plugin, called when form is submitted
	 *
	 * @param object $params
	 * @param object form model
	 * @returns bol
	 */

	function onAfterProcess(&$params, &$formModel)
	{
		$type = empty($formModel->_origRowId) ? 'form.submit.add' : 'form.submit.edit';
		return $this->log($params, $formModel, $type);
	}

	function getNewData($formModel)
	{
		$listModel =& $formModel->getListModel();
		$fabrikDb =& $listModel->getDb();
		$sql = $formModel->_buildQuery();
		$fabrikDb->setQuery($sql);
		return $fabrikDb->loadObjectList();
	}

	/**
	 * perform log
	 *
	 * @param	object	$params
	 * @param	object	form model
	 * @param	string	message type
	 * @returns	bool
	 */

	protected function log($params, $formModel, $messageType)
	{
		$this->formModel =& $formModel;
		$app =& JFactory::getApplication();
		$db =& JFactory::getDBO();
		$rowid = JRequest::getVar('rowid', '');
		$loading = strstr($messageType, 'form.load' );
		$http_referrer = JRequest::getVar('HTTP_REFERER', 'no HTTP_REFERER', 'SERVER');
		$user =& JFactory::getUser();
		$userid = $user->get('id');
		$username = $user->get('username');

		// Generate random filename
		if ($params->get('logs_random_filename') == 1) {
			function generate_filename($length) {
				$key = "";
				$possible = "0123456789bcdfghjkmnpqrstvwxyzBCDFGHJKLMNPQRTVWXYZ";
				$i = 0;
				while ($i < $length) {
					$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
					$key .= $char;
					$i++;
				}
				return $key;
			}
			$random_filename = '_'.generate_filename($params->get('logs_random_filename_length'));
		} else {
			$random_filename = '';
		}

		$w = new FabrikWorker();
		$logs_path = $w->parseMessageForPlaceHolder($params->get('logs_path'));
		if (strpos($logs_path,DS) !== 0) {
			$logs_path = JPATH_ROOT.DS.$logs_path;
		}
		$logs_path = rtrim($logs_path, DS);
		if (!JFolder::exists($logs_path)) {
			if (!JFolder::create($logs_path)) {
				return;
			}
		}
		$ext = $params->get('logs_file_format');
		$sep = $params->get('logs_separator');
		// Making complete path + filename + extension
		$w = new FabrikWorker();
		$logs_file = $logs_path.DS.$w->parseMessageForPlaceHolder($params->get('logs_file')).$random_filename.'.'.$ext;
		$logs_mode = $params->get('logs_append_or_overwrite');
		$date_element = $params->get('logs_date_field');
		$date_now = $params->get('logs_date_now');

		// COMPARE DATA
		$result_compare = '';
		if ($params->get('compare_data')) {
			if ($ext == 'csv') {
				$sep_compare = '';
				$sep_2compare = '/ ';
			} else if ($ext == 'txt') {
				$sep_compare = "\n";
				$sep_2compare = "\n";
			} else if ($ext == 'htm') {
				$sep_compare = '<br/>';
				$sep_2compare = '<br/>';
			}
			if ($loading) {
				$result_compare = JText::_('COMPARE_DATA_LOADING') . $sep_2compare;
			}
			else {
				$data = $this->getEmailData();
				$newData = $this->getNewData($formModel);
				if (!empty($data)) {

					$post = JRequest::get('post');
					$elementModel =& JModel::getInstance('element','FabrikModel');
					$element =& $elementModel->getElement(true);
					$tableModel =& $formModel->getTable();

					$origDataCount = count(array_keys(JArrayHelper::fromObject($formModel->_origData[0])));
					if ($origDataCount > 0) {
						$c = 0;
						$origData = $formModel->_origData;
						/*
						foreach ($data as $key => $val) {
							if (($val != $formModel->_origData[0]->$key) && (isset($formModel->_origData[0]->$key)) && (isset($val)) && (substr($key, -4, 4) != '_raw')) {
								$id_elementModel =& JModel::getInstance('element','FabrikModel');
								$id_elementModel->setId($key);
								$id_element =& $id_elementModel->getElement(true);
								$formModel->_formData[$id_element->name] = $formModel->_fullFormData['rowid'];
								$formModel->_formData[$id_element->name . '_raw'] = $formModel->_fullFormData['rowid'];
								$result_compare .= JText::_('COMPARE_DATA_CHANGE_ON').' '.$key.' '.$sep_compare.JText::_('COMPARE_DATA_FROM').' '.$formModel->_origData[0]->$key.' '.$sep_compare.JText::_('COMPARE_DATA_TO').' '.$val.' '.$sep_2compare;
							}
						}
						*/
						$log_elements = explode(',', str_replace(' ', '', $params->get('logs_element_list', '')));
						$groups = $formModel->getGroupsHiarachy();
						foreach ($groups as $groupModel) {
							$group =& $groupModel->getGroup();
							$elementModels =& $groupModel->getPublishedElements();
							foreach ($elementModels as $elementModel) {
								$element = $elementModel->getElement();
								$fullName = $elementModel->getFullName(false, true, false);
								if (empty($log_elements) || in_array($fullName, $log_elements)) {
									if ($newData[$c]->$fullName != $origData[$c]->$fullName) {
										$result_compare .= JText::_('COMPARE_DATA_CHANGE_ON').' '.$element->label.' '.$sep_compare.JText::_('COMPARE_DATA_FROM').' '.$origData[0]->$fullName.' '.$sep_compare.JText::_('COMPARE_DATA_TO').' '.$newData[$c]->$fullName.' '.$sep_2compare;
									}
								}
							}
						}
						if (empty($result_compare)) {
							$result_compare = JText::_('COMPARE_DATA_NO_DIFFERENCES');
						}
					} else {
						$result_compare .= "New record:".$sep_2compare;
						foreach ($data as $key => $val) {
							if (isset($val) && (substr($key, -4, 4) != '_raw')) {
								$result_compare .= "$key : $val".$sep_2compare;
							}
						}
					}
				}
				else {
					$result_compare = "No data to compare!";
				}
			}
		}

		// Defining the date to use - Not used anymore as logs should really only record the current time_date
		if ($date_now != '') {
			$date = date("$date_now");
		} else {
			$date = date("Y-m-d H:i:s");
		}

		// Custom Message
		if ($params->get('custom_msg') != '') {

			$rep_add_edit = $messageType == 'form.add' ? JText::_('REP_ADD') : ($messageType == 'form.edit' ? JText::_('REP_EDIT') : JText::_('DETAILS'));

			$custom_msg = $params->get('custom_msg');
			$custom_msg = preg_replace('/{Add\/Edit}/', $rep_add_edit, $custom_msg);
			$custom_msg = preg_replace('/{DATE}/', $date, $custom_msg);
			$excl_clabels = preg_replace('/([-{2}| |"][0-9a-zA-Z.:$_>]*)/', '', $custom_msg);
			$split_clabels = preg_split('/[+]{1,}/', $excl_clabels);
			$clabels = preg_replace('/[={2}]+[a-zA-Z0-9_-]*/', '', $split_clabels);
			$ctypes = preg_replace('/[a-zA-Z0-9_-]*[={2}]/', '', $split_clabels);
			$labtyp = array_combine($clabels, $ctypes);

			$w = new FabrikWorker();
			$custom_msg = $w->parseMessageForPlaceHolder($custom_msg);
			$excl_cdata = preg_replace('/((?!("[^"]*))([ |\w|+|.])+(?=[^"]*"\b)|(?!\b"[^"]*)( +)+(?=([^"]*)$)|(?=\b"[^"]*)( +)+(?=[^"]*"\b))/', '', $custom_msg);
			$cdata = preg_split('/["]{1,}/', $excl_cdata);
			// Labels for CSV & for DB
			$clabels_csv_imp = implode("\",\"", $clabels);
			$clabels_csv_p1 = preg_replace('/^(",)/', '', $clabels_csv_imp);
			$clabels_csv = '';
			//if ($datefield) {
			//	$clabels_csv .= '"'.$labeldate.'",';
			//}
			$clabels_csv .= preg_replace('/(,")$/', '', $clabels_csv_p1);
			if( $params->get('compare_data') == 1) {
				$clabels_csv .= ', "'.JText::_('COMPARE_DATA_LABEL_CSV').'"';
			}
			$clabels_createdb_imp = '';
			foreach ($labtyp as $klb => $vlb) {
				$klb = $db->nameQuote($klb);
				if ($vlb == 'varchar') {
					$clabels_createdb_imp .= $klb.' '.$vlb.'(255) NOT NULL, ';
				} else if ($vlb == 'int') {
					$clabels_createdb_imp .= $klb.' '.$vlb.'(11) NOT NULL, ';
				} else if ($vlb == 'datetime') {
					$clabels_createdb_imp .= $klb.' '.$vlb.' NOT NULL, ';
				}
			}

			$clabels_createdb = substr_replace($clabels_createdb_imp, '', -2);

			if( $params->get('compare_data') == 1) {
				$clabels_createdb .= ', '.$db->nameQuote(JText::_('COMPARE_DATA_LABEL_DB')).' text NOT NULL';
			}

			// @todo - what if we use differnt db driver which doesnt name quote with `??
			$clabels_db_imp = implode("`,`", $clabels);
			$clabels_db_p1 = preg_replace('/^(`,)/', '', $clabels_db_imp);
			$clabels_db = '';

			$clabels_db .= preg_replace('/(,`)$/', '', $clabels_db_p1);


			if( $params->get('compare_data') == 1) {
				$clabels_db .= ', ' . $db->nameQuote(JText::_('COMPARE_DATA_LABEL_DB'));
			}
			// data for CSV & for DB
			$cdata_csv_imp = implode("\",\"", $cdata);
			$cdata_csv_p1 = preg_replace('/^(",)/', '', $cdata_csv_imp);
			$cdata_csv = preg_replace('/(,")$/', '', $cdata_csv_p1);
			$cdata_csv = preg_replace('/={1,}",/', '', $cdata_csv);
			$cdata_csv = preg_replace('/""/', '"', $cdata_csv);

			if( $params->get('compare_data') == 1) {
				$cdata_csv .= ', "'.$result_compare.'"';
			}
			$cdata_db_imp = implode("','", $cdata);
			$cdata_db_p1 = preg_replace("/^(',)/", '', $cdata_db_imp);
			$cdata_db = preg_replace("/(,')$/", '', $cdata_db_p1);
			$cdata_db = preg_replace("/={1,}',/", '', $cdata_db);
			$cdata_db = preg_replace("/''/", "'", $cdata_db);

			if( $params->get('compare_data') == 1 && !$loading) {
				$result_compare = preg_replace('/<br\/>/', '- ', $result_compare);
				$result_compare = preg_replace('/\\n/', '- ', $result_compare);
				$cdata_db .= ", '".$result_compare."'";
			}
			$custom_msg = preg_replace('/([++][0-9a-zA-Z.:_]*)/', '', $custom_msg);
			$custom_msg = preg_replace('/^[ ]/', '', $custom_msg);
			$custom_msg = preg_replace('/  /', ' ', $custom_msg);
			$custom_msg = preg_replace('/"/', '', $custom_msg);
			if( $params->get('compare_data') == 1 && !$loading) {
				$custom_msg .= '<br />'.$result_compare;
			}

		} else {
			$clabelsCreateDb = array();
			$clabelsDb = array();
			$cdataDb = array();

			$clabelsCreateDb[] = $db->nameQuote('date')." datetime NOT NULL";
			$clabelsDb[] = $db->nameQuote('date');
			$cdataDb[] = "NOW()";

			$clabelsCreateDb[] = $db->nameQuote('ip')." varchar(32) NOT NULL";
			$clabelsDb[] = $db->nameQuote('ip');
			$cdataDb[] = $params->get('logs_record_ip') == '1' ? $db->Quote($_SERVER['REMOTE_ADDR']) : $db->Quote('');

			$clabelsCreateDb[] = $db->nameQuote('referer')." varchar(255) NOT NULL";
			$clabelsDb[] = $db->nameQuote('referer');
			$cdataDb[] = $params->get('logs_record_referer') == '1' ? $db->Quote($http_referrer) : $db->Quote('');

			$clabelsCreateDb[] = $db->nameQuote('user_agent')." varchar(255) NOT NULL";
			$clabelsDb[] = $db->nameQuote('user_agent');
			$cdataDb[] = $params->get('logs_record_useragent') == '1' ? $db->Quote($_SERVER['HTTP_USER_AGENT']) : $db->Quote('');

			$clabelsCreateDb[] =$db->nameQuote( 'data_comparison' )." TEXT NOT NULL";
			$clabelsDb[] = $db->nameQuote( 'data_comparison' );
			$cdataDb[] = $params->get('compare_data') == '1' ? $db->Quote($result_compare) : $db->Quote('');

			$clabelsCreateDb[] =$db->nameQuote('rowid')." INT(11) NOT NULL";
			$clabelsDb[] = $db->nameQuote('rowid');
			$cdataDb[] = $db->Quote((int)$rowid);

			$clabelsCreateDb[] =$db->nameQuote('userid')." INT(11) NOT NULL";
			$clabelsDb[] = $db->nameQuote('userid');
			$cdataDb[] = $db->Quote((int)$userid);

			$clabelsCreateDb[] =$db->nameQuote('tableid')." INT(11) NOT NULL";
			$clabelsDb[] = $db->nameQuote('tableid');
			$cdataDb[] = $db->Quote( $formModel->getTableModel()->getId() );

			$clabelsCreateDb[] =$db->nameQuote('formid')." INT(11) NOT NULL";
			$clabelsDb[] = $db->nameQuote('formid');
			$cdataDb[] = $db->Quote( $formModel->getId() );

			$clabels_createdb = implode(", ", $clabelsCreateDb);
			$clabels_db = implode(", ", $clabelsDb);
			$cdata_db = implode(", ", $cdataDb);
		}
		/* For CSV files
		 * If 'Append' method is used, you don't want to repeat the labels (Date, IP, ...)
		 * each time you add a line in the file */
		$labels = (!JFile::exists($logs_file) || $logs_mode == 'w') ? 1 : 0;

		$buffer = ($logs_mode == 'a' && JFile::exists($logs_file)) ? JFile::read($logs_file) : '';

		$send_email = $params->get('log_send_email') == '1';
		$make_file = $params->get('make_file') == '1';
		if ($send_email && !$make_file) {
			$ext = 'txt';
		}
		$email_msg = '';
		//@TODO redo all this with JFile API and only writing a string once - needless overhead doing fwrite all the time
		if ($make_file || $send_email) {
			// Opening or creating the file
			if ($params->get('custom_msg') != '') {
				if ($send_email) {
					$email_msg = $custom_msg;
				}
				if ($make_file) {
					$custMsg = $buffer;
					if ($ext != 'csv') {
						JFile::write($logs_file, $buffer.$custom_msg."\n".$sep."\n");
					} else {
						// Making the CSV file
						// If the file already exists, do not add the 'label line'
						if ($labels == 1) {
							$custMsg .= $clabels_csv;
						}
						// Inserting data in CSV with actual line break as row separator
						$custMsg .= "\n".$cdata_csv."";
						JFile::write($logs_file, $custMsg);
					}
				}
			} else {
				// Making HTM File
				if ($ext == 'htm') {
					$htmlMsg = "<b>Date:</b> " . $date . "<br/>";
					if ($params->get('logs_record_ip') == 1) {
						$htmlMsg .= "<b>IP Address:</b> " . $_SERVER['REMOTE_ADDR'] . "<br/>";
					}
					if ($params->get('logs_record_referer') == 1) {
						$htmlMsg .= "<b>Referer:</b> ". $http_referrer . "<br/>";
					}
					if ($params->get('logs_record_useragent') == 1) {
						$htmlMsg .= "<b>UserAgent: </b>". $_SERVER['HTTP_USER_AGENT']. "<br/>";
					}
					$htmlMsg .= $result_compare.$sep."<br/>";
					if ($send_email) {
						$email_msg = $htmlMsg;
					}
					if ($make_file) {
						$htmlMsg = $buffer . $htmlMsg;
						$res = JFile::write($logs_file, $htmlMsg);
						if (!$res) {
							JError::raiseNotice(E_NOTICE, "error writing html to log file: " . $logs_file);
						}
					}
				}
				// Making the TXT file
				else if ($ext == 'txt') {
					$txtMsg = "Date: " . $date . "\n";
					$txtMsg .= "Form ID: " . $formModel->getId() . "\n";
					$txtMsg .= "Table ID: " . $formModel->getListModel()->getId() . "\n";
					$txtMsg .= "Row ID: " . (int)$rowid . "\n";
					$txtMsg .= "User ID: $userid ($username)\n";
					if ($params->get('logs_record_ip') == 1) {
						$txtMsg .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
					}
					if ($params->get('logs_record_referer') == 1) {
						$txtMsg .= "Referer: ". $http_referrer . "\n";
					}
					if ($params->get('logs_record_useragent') == 1) {
						$txtMsg .= "UserAgent: ". $_SERVER['HTTP_USER_AGENT']. "\n";
					}
					$txtMsg .= $result_compare.$sep."\n";
					if ($send_email) {
						$email_msg = $txtMsg;
					}
					if ($make_file) {
						$txtMsg = $buffer . $txtMsg;
						JFile::write($logs_file, $txtMsg);
					}
				} else // Making the CSV file
				if ($ext == 'csv') {
					$csvMsg = array();
					// If the file already exists, do not add the 'label line'
					if ($labels == 1) {

						$csvMsg[] = "Date";
						if ($params->get('logs_record_ip') == 1) {
							// Putting some "" around the label to avoid two different fields
							$csvMsg[] = "\"IP Address\"";
						}
						if ($params->get('logs_record_referer') == 1) {
							$csvMsg[] = "Referer";
						}
						if ($params->get('logs_record_useragent') == 1) {
							$csvMsg[] = "UserAgent";
						}
						if ($params->get('compare_data') == 1) {
							$csvMsg[] = "\"".JText::_('COMPARE_DATA_LABEL_CSV')."\"";
						}
					}
					// Inserting data in CSV with actual line break as row separator
					$csvMsg[] = "\n\"".$date."\"";

					if ($params->get('logs_record_ip') == 1) {
						$csvMsg[] = "\"".$_SERVER['REMOTE_ADDR'] . "\"";
					}
					if ($params->get('logs_record_referer') == 1) {
						$csvMsg[] = "\"".$http_referrer . "\"";
					}
					if ($params->get('logs_record_useragent') == 1) {
						$csvMsg[] = "\"".$_SERVER['HTTP_USER_AGENT']. "\"";
					}
					if ($params->get('compare_data') == 1) {
						$csvMsg[] = "\"".$result_compare. "\"";
					}
					$csvMsg = implode(",", $csvMsg);
					if ($send_email) {
						$email_msg = $csvMsg;
					}
					if ($make_file) {
						if ($buffer !== '') {
							$csvMsg = $buffer . $csvMsg;
						}
						JFile::write($logs_file, $csvMsg);
					}
				}
			}
		}

		if ($params->get('logs_record_in_db') == 1) {
			// In which table?
			if ($params->get('record_in') == '') {
				$rdb = '#__fabrik_log';
			} else {
				$db_suff = $params->get('record_in');

				$form =& $formModel->getForm();
				$fid = $form->id;
				$db->setQuery("SELECT ".$db->nameQuote('db_table_name')." FROM ".$db->nameQuote('#__fabrik_tables')." WHERE ".$db->nameQuote('form_id')." = ". (int)$fid);
				$tname = $db->loadResult();

				$rdb = $db->nameQuote($tname.$db_suff);
			}

			// Making the message to record
			if ($params->get('custom_msg') != '') {
				$message = preg_replace('/<br\/>/', ' ', $custom_msg);
			} else {
				$message = $this->makeStandardMessage($params, $result_compare);
			}

			// $$$ hugh - FIXME - not sure about the option driven $create_custom_table stuff, as this won't work
			// if they add an option to an existing log table.  We should probably just create all the optional columns
			// regardless.
			if ($params->get('record_in') == '') {
				$in_db = "INSERT INTO $rdb (".$db->nameQuote('referring_url').", ".$db->nameQuote('message_type').", ".$db->nameQuote('message').") VALUES (".$db->Quote($http_referrer).", ".$db->Quote($messageType).", ".$db->Quote($message).");";
				$db->setQuery($in_db);
				$db->query();
			} else {
				$create_custom_table = "CREATE TABLE IF NOT EXISTS $rdb (".$db->nameQuote('id')." int(11) NOT NULL auto_increment PRIMARY KEY, $clabels_createdb);";
				$db->setQuery($create_custom_table);
				$db->query();

				$in_db = "INSERT INTO $rdb ($clabels_db) VALUES ($cdata_db);";

				$db->setQuery($in_db);
				if (!$db->query()) {
					// $$$ changed to always use db fields even if not selected
					// so logs already created may need optional fields added.
					// try adding every field we should have, don't care if query fails.
					foreach ($clabelsCreateDb as $insert) {
						$db->setQuery("ALTER TABLE ADD $insert AFTER `id`");
						$db->query();
					}
					// ... and try the insert query again
					$db->setQuery($in_db);
					$db->query();
				}
			}

		}

		if ($send_email) {
			jimport('joomla.mail.helper');
			$config =& JFactory::getConfig();
			$email_from = $config->getValue('mailfrom');
			$email_to = explode(',', $w->parseMessageForPlaceholder( $params->get('log_send_email_to', '') ));
			$subject = strip_tags($w->parseMessageForPlaceholder( $params->get('log_send_email_subject', 'log event') ));
			foreach ($email_to as $email) {
				$email = trim($email);
				if (empty($email)) {
					continue;
				}
				if (JMailHelper::isEmailAddress($email)) {
					$res = JUtility::sendMail($email_from, $email_from, $email, $subject, $email_msg, true);
				} else {
					JError::raiseNotice(500, JText::sprintf('DID_NOT_SEND_EMAIL_INVALID_ADDRESS', $email));
				}
			}
		}
		return true;
	}

	protected function makeStandardMessage($params, $result_compare)
	{
		$message = '';
		if ($params->get('logs_record_ip') == 1)
		{
			$message .= 'IP: ' . $_SERVER['REMOTE_ADDR'] . GROUPSPLITTER2;
		}
		if ($params->get('logs_record_useragent') == 1)
		{
			$message .= 'UserAgent: ' . $_SERVER['HTTP_USER_AGENT'] . GROUPSPLITTER2;
		}
		if ($params->get('compare_data') == 1)
		{
			$result_compare = preg_replace('/<br\/>/', '- ', $result_compare);
			$message .= preg_replace('/\\n/', '- ', $result_compare);
		}
		return $message;
	}

}
?>