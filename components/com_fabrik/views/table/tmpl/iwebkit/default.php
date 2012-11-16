<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php
  	$document =& JFactory::getDocument();
  	$document->setMetaData("apple-mobile-web-app-capable", "yes");
  	$document->setMetaData("viewport", "minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no");
  	$document->addStyleSheet('components/com_fabrik/views/table/tmpl/iwebkit/css/style.css');
  	$document->addScript('components/com_fabrik/views/table/tmpl/iwebkit/javascript/functions.js');
		$document->addHeadLink('components/com_fabrik/views/table/tmpl/iwebkit/homescreen.png', 'apple-touch-icon');
		$document->addHeadLink('components/com_fabrik/views/table/tmpl/iwebkit/startup.png', 'apple-touch-startup-image');

		$script = "document.addEvent('domready', function() {
			document.getElement('body').addClass('list');
		});";
		FabrikHelperHTML::addScriptDeclaration($script);

		?>
		<div id="topbar">
  <div id="title">

		<?php
if ($this->params->get('show_page_title', 1)) { ?>
	<div class="componentheading<?php echo $this->params->get('pageclass_sfx')?>"><?php echo $this->escape($this->params->get('page_title')); ?></div>
<?php } ?>

<?php if ($this->params->get('show-title', 1)) {?>
	<?php echo $this->table->label;?>
<?php }?>
</div>
</div>
<?php echo $this->table->intro;?>
<form class="fabrikForm" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikTable">

<?php echo $this->loadTemplate('buttons');

if ($this->showFilters) {
//	echo $this->loadTemplate('filter');
}?>
<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>"><?php echo $this->emptyDataMessage; ?></div>
<div class="fabrikDataContainer content" style="<?php echo $this->tableStyle?>">
<ul class="fabrikTable pageitem" id="table_<?php echo $this->table->id;?>" >
<?php
	foreach ($this->rows as $groupedby => $group) {
		if( $this->isGrouped) {
			echo "<li class=\"title\">".$this->grouptemplates[$groupedby]."</li>";
		}

			foreach ($group as $this->_row) {
				echo $this->loadTemplate('row');
		 	}
		  }
		?>
		</ul>
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
	// echo "Total records: " . $this->navigation->total . "<br />\n";
	//end not empty

print_r( $this->hiddenFields);
?>
</div>
</form>
