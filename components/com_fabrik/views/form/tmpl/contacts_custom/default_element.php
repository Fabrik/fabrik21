<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php
/*
 This part of the template is what actually renders each individual element.  You will be loading this
 template multiple times (once for each element you want to display) from your default_group.php file.
 
 You probably won't need to edit this file - most changes you want can probably be done
 by editing the custom.css file and overriding classes like .fabrikLabel, .fabrikElement, etc.
 
 If you do edit this file, make sure you use the same parts of the element this example uses,
 i.e. the same class definitions, etc.
*/
?>
	<div <?php echo @$this->element->column;?> class="<?php echo $this->element->containerClass;?>">
		<?php echo $this->element->label;?>
		<div class="fabrikElement">
			<?php echo $this->element->element;?>
			
		</div>
		<div class="fabrikErrorMessage">
				<?php echo $this->element->error;?>
			</div>
		<div style="clear:both"></div>
	</div>

	<?php
	$this->element->rendered = true;
	?>
