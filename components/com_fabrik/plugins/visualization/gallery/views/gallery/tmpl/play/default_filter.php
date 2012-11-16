<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php if ($this->showFilters) {?>
<form method="post" action="">
<?php

foreach ($this->filters as $table => $filters) {
  if (!empty($filters)) {
?>
	  <table class="fabrikTable" style="border:1px solid #9da4a9">

	   <tfoot><tr><td colspan='5' style="text-align:right;">

	  <input type="submit" class="button" value="<?php echo JText::_('GO')?>" /> | <a href="#" class="clearFilters"><?php echo JText::_('CLEAR'); ?></a>
	  </td></tr>
	  </tfoot>
	  <tbody style="margin:5px;">

	    <tr class="fabrik_row">
	    	<td style="width:100px;"><h4><?php echo JText::_('SEARCH');?></h4></td>
	    	<td style="width:150px;"><?php echo $filters['jos_fabble_activity___create_date']->label?> </td>
	    	<?php
	    	$e = explode("<br />", $filters['jos_fabble_activity___create_date']->element);
	    	?>
	    	<td style="width:250px;"><?php echo $e[0]?></td>
				<td colspan="2" style="width:200px;"><?php echo $e[1]?></td>
			</tr>

			<tr class="fabrik_row">
	    	<td></td>
	    	<td><?php echo $filters['jos_fabble_activity___rating']->label?> </td>
	    	<td><?php echo $filters['jos_fabble_activity___rating']->element?></td>
	    	<td><?php echo $filters['jos_fabble_activity___user_id']->label?> </td>
	    	<td><?php echo $filters['jos_fabble_activity___user_id']->element?></td>
			</tr>

	  </tbody>

	 </table>
	  <?php
  }
}
?>
</form>
<?php }?>
