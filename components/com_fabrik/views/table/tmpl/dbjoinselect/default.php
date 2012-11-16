<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php if ($this->params->get('show-title', 1)) {?>
	<h1><?php echo $this->table->label;?></h1>
<?php }?>

<?php echo $this->table->intro;?>
<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikTable">

<?php

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
<?php
	foreach ($this->rows as $groupedby => $group) {
		if( $this->isGrouped) {
			echo $this->grouptemplates[$groupedby];
		}
		?>
	<table class="fabrikTable" id="table_<?php echo $this->table->id;?>" >
		<thead>
			<?php echo $this->headingstmpl; ?>
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
	echo (trim($this->nav) == '') ? "<ul class=\"pagination\"></ul>" : $this->nav;
	//end not empty

print_r( $this->hiddenFields);
?>
</div>
</form>
<?php
FabrikHelperHTML::script('javascript.js', 'components/com_fabrik/views/table/tmpl/dbjoinselect/');
$script = "var trs = new TableRowSelect('" . JRequest::getVar('triggerElement') . "', '" .  JRequest::getVar('winid') . "', " . (int)$this->form->id . ");
oPackage.addBlock('tableRowSet', trs);
";
FabrikHelperHTML::addScriptDeclaration($script);?>
