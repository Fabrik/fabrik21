<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php
  	$document =& JFactory::getDocument();
  	$document->setMetaData("apple-mobile-web-app-capable", "yes");
  	$document->setMetaData("viewport", "minimum-scale=1.0, width=device-width, maximum-scale=0.6667, user-scalable=no");
  	$document->addStyleSheet('components/com_fabrik/views/form/tmpl/iwebkit/css/style.css');
  	$document->addScript('components/com_fabrik/views/form/tmpl/iwebkit/javascript/functions.js');
		$document->addHeadLink('components/com_fabrik/views/form/tmpl/iwebkit/homescreen.png', 'apple-touch-icon');
		$document->addHeadLink('components/com_fabrik/views/form/tmpl/iwebkit/startup.png', 'apple-touch-startup-image');
?>
<script>
document.addEvent('domready', function() {

	document.getElements('.fabrikLabel').each(function(l) {
		var s = new Element('span', {id:l.id, 'class':l.className}).adopt(l.getChildren());

		s.replaces(l);
	});

	document.getElements('.fabrikfield, .fabrikdate').each(function(f) {
		var l = f.getElement('label');
		var li = new Element('li', {'class':'smallfield'});
		var span = new Element('span', {'class':'name'});
		if($type(f.getElement('input')) !== false) {
			f.getElement('input').setProperty('placeholder', 'Enter text');
			span.setText(l.getText());
			l.remove();
			new Element('ul',{'class':'pageitem'}).adopt(li.adopt([span, f.getElement('input')])).injectInside(f);
		}else{
			new Element('ul',{'class':'pageitem'}).adopt(li.adopt(span)).injectInside(f);
		}
	});

	document.getElements('.fabrikradiobutton').each(function(f) {
		var lis = [];
		var li;
		f.getElement('.fabrikLabel').addClass('graytitle');
		f.getElements('.fabrik_subelement').each(function(s) {
			li = new Element('li', {'class':'radiobutton'}).adopt(s.getChildren().clone());
			li.getElement('span').addClass('name');
			lis.push(li);
			s.remove();
		});
		new Element('ul',{'class':'pageitem'}).adopt(lis).injectInside(f);
	});

	document.getElements('.fabrikcheckbox').each(function(f) {
		var lis = [];
		var li;
		f.getElement('.fabrikLabel').addClass('graytitle');
		f.getElements('.fabrik_subelement').each(function(s) {
			li = new Element('li', {'class':'checkbox'}).adopt([new Element('span', {'class':'name'}).setText(s.getElement('span').getText()), s.getElement('input')]);
			lis.push(li);
			s.remove();
		});
		new Element('ul',{'class':'pageitem'}).adopt(lis
		).injectInside(f);
	});

	document.getElements('.fabrikcascadingdropdown, .fabrikdropdown').each(function(f) {
		f.getElement('.fabrikLabel').addClass('graytitle');
		var li = new Element('li', {'class':'select'}).adopt([f.getElement('select'), new Element('span', {'class':'arrow'})]);
		f.getChildren().remove();
		new Element('ul',{'class':'pageitem'}).adopt(li).injectInside(f);
	});

	document.getElements('.fabriktextarea').each(function(f) {
		f.getElement('.fabrikLabel').addClass('graytitle');
		var li = new Element('li', {'class':'textbox'}).adopt(f.getElement('textarea'));
		f.getChildren().remove();
		new Element('ul',{'class':'pageitem'}).adopt(li).injectInside(f);
	});
});

</script>


<?php $form = $this->form;
echo $form->startTag;
if ($this->params->get('show-title', 1)) {?>
<div id="topbar">
  <div id="title"><?php echo $form->label;?></div>
</div>
<div style="padding:5px 10px;">
<?php }
echo $form->intro;
echo $this->plugintop;
$active = ($form->error != '') ? '' : ' fabrikHide';
echo "<div class=\"fabrikMainError fabrikError$active\">$form->error</div>";?>
	<?php
	if ($this->showEmail) {
		echo $this->emailLink;
	}
	if ($this->showPDF) {
		echo $this->pdfLink;
	}
	if ($this->showPrint) {
		echo $this->printLink;
	}
	echo $this->loadTemplate('relateddata');
	foreach ( $this->groups as $group) {
		?>
		<fieldset class="fabrikGroup" id="group<?php echo $group->id;?>" style="<?php echo $group->css;?>">
		<legend><?php echo $group->title;?></legend>
		<?php if ($group->canRepeat) {
			foreach ($group->subgroups as $subgroup) {
			?>
				<div class="fabrikSubGroup">
					<div class="fabrikSubGroupElements">
						<?php
						$this->elements = $subgroup;
						echo $this->loadTemplate('group');
						?>
					</div>
					<?php if ($group->editable) { ?>
						<div class="fabrikGroupRepeater">
							<a class="addGroup" href="#">
								<img src="<?php echo COM_FABRIK_LIVESITE?>components/com_fabrik/views/form/tmpl/default/images/add.png" alt="<?php echo JText::_('Add group');?>" />
							</a>
							<a class="deleteGroup" href="#">
								<img src="<?php echo COM_FABRIK_LIVESITE?>components/com_fabrik/views/form/tmpl/default/images/del.png" alt="<?php echo JText::_('Delete group');?>" />
							</a>
						</div>
					<?php } ?>
				</div>
				<?php
			}
		} else {
			$this->elements = $group->elements;
			echo $this->loadTemplate('group');
		}?>
	</fieldset>
<?php
	}
	echo $this->hiddenFields;
	?>
	<?php echo $this->pluginbottom; ?>
	<div class="fabrikActions">

	<ul class="pageitem">
	<?php $buttons = array('resetButton', 'submitButton', 'applyButton', 'copyButton', 'deleteButton', 'gobackButton');
	foreach($buttons as $b) {
	if (isset($form->$b) && trim($form->$b) !== '') {
		?>
		<li class="button">
    <?php echo $form->$b;?>
  </li>
		<?php
	}
	}?>
</ul>

	<?php echo $this->message ?>
	</div>

<?php
echo $form->endTag;
echo FabrikHelperHTML::keepalive();
?>
</div>
