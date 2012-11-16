<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
?>
<form method="post" action="<?php echo $this->action?>">
	<a id="advanced-search-add" class="addbutton" href="#"><?php echo JText::_('ADD')?></a>
	<div id="advancedSearchContainer">
	<table id="advanced-search-table">
		<tbody>
			<?php foreach ($this->rows as $row) {?>
			<tr>
				<td><span><?php echo $row['join'];?></span></td>
				<td><?php echo $row['element'] . $row['type'] . $row['grouped'];?>
				</td>
				<td><?php echo $row['condition'];?></td>
				<td class='filtervalue'><?php echo $row['filter'];?></td>
				<td><a class="advanced-search-remove-row" href="#"><img src='<?php echo COM_FABRIK_LIVESITE?>media/com_fabrik/images/del.png' alt="[-]" /></a></td>
			</tr>
			<?php }?>

		</tbody>
		<thead>
			<tr class="title">
				<th></th>
				<th><?php echo JText::_('ELEMENT')?></th>
				<th><?php echo JText::_('CONDITION')?></th>
				<th><?php echo JText::_('VALUE')?></th>
				<th><?php echo JText::_('DELETE')?></th>
			</tr>
			</thead>
	</table>
	</div>
	<input type="submit"
		value="<?php echo JText::_('APPLY')?>" class="button fabrikFilter" name="applyAdvFabrikFilter"
		id="advanced-search-apply" type="button">
	<input
		id="advancedFilterTable-clearall" value="<?php echo JText::_('CLEAR')?>" class="button"
		type="button">
			<input type="hidden" name="option" value="com_fabrik" />
	<input type="hidden" name="controller" value="<?php echo JRequest::getVar('nextcontroller', 'table')?>" />
	<input type="hidden" name="c" value="<?php echo JRequest::getVar('nextcontroller', 'table')?>" />
	<input type="hidden" name="view" value="<?php echo JRequest::getVar('nextview', 'table')?>" />
	<input type="hidden" name="tableid" value="<?php echo $this->tableid?>" />
	<input type="hidden" name="task" value="<?php echo JRequest::getVar('nexttask', 'viewTable')?>" />
	<input type="hidden" name="advanced-search" value="1" />
<?php //echo $this->fields?>

</form>
