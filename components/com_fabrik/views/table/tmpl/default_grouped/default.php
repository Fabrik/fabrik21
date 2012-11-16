<?php
defined('_JEXEC') or die('Restricted access');
?>

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
<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>"><?php echo $this->emptyDataMessage; ?></div>
<div class="fabrikDataContainer" style="<?php echo $this->tableStyle?>">

			<table class="fabrikTable" style="width:100%" id="table_<?php echo $this->table->id;?>" >

			<tr class="fabrikHide">
<?php foreach ($this->headings as $key=>$heading) {?>
	<td></td>
	<?php }?>
</tr>

	<?php foreach ($this->rows as $groupedby => $group) {?>
	<?php
		if( $this->isGrouped) {?>
			<tr class="fabrik_calculations"><td colspan="<?php echo $this->colCount;?>">
				<?php echo $this->grouptemplates[$groupedby]; ?>
			</td></tr>
			<?php }
			echo $this->headingstmpl;

			$this->_c = 0;
			foreach ($group as $this->_row) {
				echo $this->loadTemplate('row');
		 	}
		 	?>
		<?php if($this->hasCalculations) { ?>
				<tr class="fabrik_calculations">
				<?php
				foreach ($this->calculations as $el => $cal) {
					echo "<td class=\"fabrik_row___".$el."\">";
					echo array_key_exists($groupedby, $cal->grouped) ? $cal->grouped[$groupedby] : $cal->calc;
					echo  "</td>";
				}
				?>
				</tr>
<?php }
	}?>
		</table>
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

print_r( $this->hiddenFields);
?>
</div>
</form>
