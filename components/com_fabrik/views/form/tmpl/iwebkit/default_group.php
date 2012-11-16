<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php foreach ( $this->elements as $element) {
	?>
	<div <?php echo @$element->column;?> class="<?php echo $element->containerClass;?>">
<?php echo $element->label;?>
		<div class="fabrikElement">
			<?php echo $element->element;?>

		</div>
		<div class="fabrikErrorMessage">
				<?php echo $element->error;?>
			</div>
	</div>
	<?php }?>
