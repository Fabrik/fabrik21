<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php
/* this is the group template used by the html template and by the ajax updating of the table
 if group_by is not used then this in only repeated once.
 */
//@TODO: the id here will be repeated if group_by is on - need to add a group identifier to it

foreach ($this->rows as $groupedby => $this->group) {
	if( $this->isGrouped) {
		echo $this->grouptemplates[$groupedby];
	}
	?>
<table class="adminlist fabrikTable"
	id="table_<?php echo $this->table->id;?>">
	<thead>
		<tr class="fabrik___heading">
		<?php foreach ($this->headings as $key=>$heading) {?>
			<th <?php echo $this->headingClass[$key]?>><?php echo $heading; ?></th>
			<?php }?>
		</tr>
	</thead>

	<tbody>
	<?php echo $this->loadTemplate('row'); ?>
		<tr class="fabrik_calculations">
		<?php
		foreach ($this->calculations as $el => $cal) {
			echo "<td class=\"fabrik_row___".$el."\">";
			echo array_key_exists($groupedby, $cal->grouped) ? $cal->grouped[$groupedby] : $cal->calc;
			echo  "</td>";
		}
		?>
		</tr>
	</tbody>
</table>
		<?php }?>
