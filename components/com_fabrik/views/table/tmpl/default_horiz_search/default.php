<?php
/**
 * puts the filters underneath the table columns
 * wont filter if you are using the table group by feature
 */
?>
<?php if ($this->params->get('show_page_title', 1)) { ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_title')); ?></div>
<?php } ?>
<?php if ($this->tablePicker != '') { ?>
	<div style="text-align:right"><?php echo JText::_('TABLE') ?>: <?php echo $this->tablePicker; ?></div>
<?php } ?>
<?php if ($this->params->get('show-title', 1)) {?>
	<h1><?php echo $this->table->label;?></h1>
<?php }?>

<?php echo $this->table->intro;?>
<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikTable">

<?php echo $this->loadTemplate('buttons');

//for some really ODD reason loading the headings template inside the group
//template causes an error as $this->_path['template'] doesnt cotain the correct
// path to this template - go figure!
$this->headingstmpl =  $this->loadTemplate('headings');
if ($this->showFilters) {
	echo $this->loadTemplate('filter');
}?>
<br style="clear:right" />
<div class="tablespacer"></div>
<div class="fabrikDataContainer">
<?php if ($this->nodata) {?>

	<table class="fabrikTable" id="table_<?php echo $this->table->id;?>" >
	<thead>
		<?php
			echo $this->headingstmpl;
			?>
		</thead>
</table>
<div class="emptyDataMessage"><?php echo $this->emptyDataMessage; ?></div>
<?php } else {
	foreach ($this->rows as $groupedby => $group) {
		if( $this->isGrouped) {
			echo $this->grouptemplates[$groupedby];
		}
		?>
	<table class="fabrikTable" id="table_<?php echo $this->table->id;?>" >
		<thead>
		<?php
			echo $this->headingstmpl;
			?>
		</thead>
		<tfoot>
			<tr class="fabrik_calculations">
			<?php
			foreach ($this->calculations as $el => $cal) {
				echo "<td class=\"fabrik_row___".$el."\">";
				echo array_key_exists($groupedby, $cal->grouped) ? $cal->grouped[$groupedby] : $cal->calc;
				echo  "</td>";
			}
			?>
			</tr>
		</tfoot>
		<tbody>
		<?php
			foreach ($group as $this->_row) {
				echo $this->loadTemplate('row');
		 	}
		 	?>
		 </tbody>
	</table>
<?php }
		?>
	<div class="fabrikButtons">
		<?php
		if ($this->canDelete) {
		 echo $this->deleteButton;
		}
		echo "&nbsp;" . $this->emptyButton;
		foreach ($this->pluginButtons as $pluginbutton) {
			echo "&nbsp;" . $pluginbutton;
		}
		?>
	</div>
	<?php
	echo $this->nav;
	//end not empty
}
print_r($this->hiddenFields);
?>
</div>
<div style="display:none">
<?php
//add in any table filter that isnt going to be shown next to a column
//needed otherwise the key for filters is incorrect and latter filters may be ignored

if ($this->showFilters) {
	foreach($this->filters as $key => $filter) {
		if (!in_array($key, $this->found_filters)) {
			print_r($filter->element);
		}
	}
}
?>
</div>
</form>