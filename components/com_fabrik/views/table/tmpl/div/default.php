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


if ($this->showFilters) {
	echo $this->loadTemplate('filter');
}?>
<br style="clear:right" />
<div class="tablespacer"></div>
<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>"><?php echo $this->emptyDataMessage; ?></div>
<div class="fabrikDataContainer" style="<?php echo $this->tableStyle?>">
<?php
	foreach ($this->rows as $groupedby => $group) {
		if( $this->isGrouped) {
			echo $this->grouptemplates[$groupedby];
		}
		?>
	<div class="fabrikTable" id="table_<?php echo $this->table->id;?>" >

			<ul class="fabrik_calculations">
			<?php
			foreach ($this->calculations as $el => $cal) {
				$c = array_key_exists($groupedby, $cal->grouped) ? $cal->grouped[$groupedby] : $cal->calc;
				if ($c != '') {
					echo "<li class=\"fabrik_row___".$el."\">";
					echo $c;
					echo  "</li>";
				}
			}
			?>
			</ul>
		<?php
			foreach ($group as $this->_row) {
				echo $this->loadTemplate('row');
		 	}
		 	?>
	</div> <!-- to close div class="fabrikTable" -->

<?php }
		?>

	<div class="footer">
	<?php
	echo $this->nav;
	//end not empty?>
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
	</div>
	<?php

print_r( $this->hiddenFields);
?>
</div>
</form>