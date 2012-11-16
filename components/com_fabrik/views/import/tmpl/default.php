<?php

/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');
$url = JRoute::_('index.php');
?>
<form enctype="multipart/form-data" action="<?php echo $url;?>" method="post" name="csv">
<table class="adminform">
	<tr>
		<th colspan="2"><?php echo JText::_('IMPORT CSV FILE') . ": " . $this->table->label;?></th>
	</tr>
	<tr>
		<td align="left"><label for="userfile"><?php echo JText::_('CSV FILE');?></label>
		</td>
		<td><input class="text_area" name="userfile" id="userfile" type="file" size="40" /></td>
	</tr>

	<tr>
		<td align="left"><label for="drop_data"><?php echo JText::_('DROP EXISTING DATA');?></label>
		</td>
		<td><input type="checkbox" name="drop_data" id="drop_data" value="1" />
		</td>
	</tr>
	<tr>
		<td align="left"><label for="overwrite"><?php echo JText::_('OVERWRITE MATCHING RECORDS');?></label>
		</td>
		<td><input type="checkbox" name="overwrite" id="overwrite" value="1" />
		</td>
	</tr>

	<tr>
		<td align="left"><label for="field_delimiter"><?php echo JText::_('FIELD DELIMITER');?></label>
		</td>
		<td>
		<input size="2" class="input" id="field_delimiter" name="field_delimiter" value="," />

		<label><input type="checkbox" name="tabdelimited" value="1" /><?php echo JText::_('COM_FABRIK_OR_TAB_DELIMITED')?></label>
		</td>
	</tr>
	<tr>
		<td align="left"><label for="text_delimiter"><?php echo JText::_('TEXT DELIMITER');?></label>
		</td>
		<td>
		<input size="2" class="input" name="text_delimiter" id="text_delimiter" value='&quot;' />
		</td>
	</tr>
	<tr>
		<td align="left"><?php echo JText::_('COM_FABRIK_CSV_IMPORT_FORMAT_LABEL');?></label>
		</td>
		<td>
			<label>
			<input type="radio" class="input" name="inPutFormat" checked="checked" value="csv" />
			CSV
			</label><br />
			<label>
			<input type="radio" class="input" name="inPutFormat" value="fabrikexcel" />
			Excel <small><?php echo JText::_('COM_FABRIK_CSV_IMPORT_FORMAT_FABRIK')?></small>
			</label><br />
				<input type="radio" class="input" name="inPutFormat" value="excel" />
			Excel <small><?php echo JText::_('COM_FABRIK_CSV_IMPORT_FORMAT_EXCEL')?></small>
			</label>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="left"><input class="button" type="submit"
			value="<?php echo JText::_('IMPORT CSV');?>" /></td>
	</tr>
</table>
<input type="hidden" name="option" value="com_fabrik" />
<input type="hidden" name="controller" value="import" />
<input type="hidden" name="view" value="import" />
<input type="hidden" name="task" value="doimport" />
<input type="hidden" name="Itemid" value="<?php echo JRequest::getInt('Itemid')?>" />
<input type="hidden" name="tableid" value="<?php echo $this->tableid;?>" />
</form>


