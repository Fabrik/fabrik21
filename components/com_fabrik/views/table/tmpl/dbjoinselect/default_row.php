<?php
defined('_JEXEC') or die('Restricted access');
?>

<tr id="<?php echo $this->_row->id;?>" class="<?php echo $this->_row->class;?>">
	<?php foreach ($this->headings as $heading=>$label) {	?>
		<td <?php echo $this->cellClass[$heading]?>><?php echo @$this->_row->data->$heading;?></td>
	<?php }?>
</tr>

