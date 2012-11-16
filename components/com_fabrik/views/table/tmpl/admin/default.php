<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php if ($this->params->get('show_page_title', 1)) { ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_title')); ?></div>
<?php } ?>
<?php if ($this->params->get('show-title', 1)) {?>
	<h1><?php echo $this->table->label;?></h1>
<?php }?>
<?php echo $this->table->intro;?>
<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikTable">

<?php if ($this->showAdd) {?>
	<span class="pagenav ">
		<a class="addbutton" href="<?php echo $this->addRecordLink;?>"><?php echo $this->addRecordLabel;?></a>
	</span>
<?php }?>

<?php if ($this->showCSV) {?>
	<span class="csvExportButton">
		<a href="#"><?php echo JText::_('EXPORT TO CSV');?></a>
	</span>
<?php }?>

<?php if($this->showCSVImport) {?>
	<span class="csvImportButton">
		<a href="<?php echo $this->csvImportLink;?>"><?php echo JText::_('IMPORT FROM CSV');?></a>
	</span>
<?php }?>

<?php if ($this->showRSS) {?>
	<span class="pagenav">
		<a href="<?php echo $this->rssLink;?>"><?php echo JText::_('SUBSCRIBE RSS');?></a>
	</span>
<?php }?>

<?php if ($this->showFilters) {?>
	<table class="filtertable">
		<tr>
			<th style="text-align:left"><?php echo JText::_('SEARCH');?>:</th>
			<th style="text-align:right"><?php echo $this->clearFliterLink;?></th>
		</tr>
		<?php
		$c = 0;
		foreach ($this->filters as $filter) {
			$required = $filter->required == 1 ? ' class="notempty"' : '';?>
			<tr class="fabrik_row oddRow<?php echo ($c % 2);?>">
				<td<?php echo $required ?>><?php echo $filter->label;?></td>
				<td style="text-align:right;"><?php echo $filter->element;?></td>
			</tr>
		<?php $c++;
		} ?>
		<?php if($this->filter_action != 'onchange') {?>
		<tr>
			<td colspan="2" style="text-align:right;"><input type="button"
				class="fabrik_filter_submit button" value="<?php echo JText::_('GO');?>"
				name="filter" /></td>
		</tr>
		<?php }?>
	</table>
<?php } // end show filters ?>
<br style="clear:right" />
<?php $fbConfig =& JComponentHelper::getParams('com_fabrik');
		if ($fbConfig->get('use_fabrikdebug', false) == 1) {?>
<label>
<?php $checked = JRequest::getVar('fabrikdebug', 0) == 1 ? 'checked="checked"' : '';?>
	<input type="checkbox" name="fabrikdebug" value="1" <?php echo $checked?> onclick="document.fabrikTable.submit()" />
	<?php echo JText::_('debug')?>
</label>
<?php }?>

<div class="tablespacer"></div>

<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>"><?php echo $this->emptyDataMessage; ?></div>
<div class="fabrikDataContainer" style="<?php echo $this->tableStyle?>">
<?php
echo $this->loadTemplate('group');
?>
<table class="adminlist fabrikTable">
	<tfoot>
		<tr>
		<td colspan="<?php echo count($this->headings) ?>">
		<?php echo $this->nav;?>
		<div style="text-align:right">
		<?php
		echo $this->deleteButton;
		echo "&nbsp;" . $this->emptyButton;
		foreach ($this->pluginButtons as $pluginbutton) {
			echo "&nbsp;" . $pluginbutton;
		}?>
		</div>
	</td>
	</tr>
</tfoot>
</table>

<?php print_r( $this->hiddenFields);?>
</div>
</form>


