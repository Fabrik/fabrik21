<h1><?php echo $this->heading?></h1>
<?php $headings = array_shift($this->data)?>
<table class="adminlist fabrikTable">
	<tbody>
		<?php
		$c = 0;
		 foreach ($this->data as $row) {?>
		<tr class="fabrik_row oddRow<?php echo $c?> row<?php echo $c?>">
			<?php foreach ($row as $d) {?>
				<td><?php echo $d?></td>
			<?php }?>
			</tr>
		<?php
		 $c = 1-$c;
		 }?>

	</tbody>
	<thead>
		<tr class="fabrik___heading">
	<?php foreach ($headings as $h) {?>
		<th><?php echo $h?></th>
	<?php }?>
		</tr>
	</thead>
</table>
