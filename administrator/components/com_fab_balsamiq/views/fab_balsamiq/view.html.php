<?php
/**
 * Joomla! 1.5 component iching
 *
 * @version $Id: view.html.php 2009-04-30 01:05:16 svn $
 * @author Rob Clayburn
 * @package Joomla
 * @subpackage iching
 * @license GNU/GPL
 *
 * inching components
 *
 * This component file was created using the Joomla Component Creator by Not Web Design
 * http://www.notwebdesign.com/joomla_component_creator/
 *
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

// Import Joomla! libraries
jimport( 'joomla.application.component.view');

class fab_balsamiqViewfab_balsamiq extends JView
{

	/**
	 * set up the menu when editing the cron job
	 */

	function setToolbar()
	{
		$task = JRequest::getVar('task', '', 'method', 'string');
		JToolBarHelper::save();
		JToolBarHelper::cancel();
	}

	function display($params) {
		JHTML::_('behavior.tooltip');
		fab_balsamiqViewfab_balsamiq::setToolbar();
		?>
		<p>

		</p>
<form action="index.php" method="post" name="adminForm" enctype="multipart/form-data">
		<div class="col60" style="width:60%;float:left">
			<fieldset class="adminform">
				<legend><?php echo JText::_('COM_FAB_BALSAMIQ_IMPORT'); ?></legend>
				<?php echo $params->render('params', 'details'); ?>
			</fieldset>
		</div>
<div class="col40" style="width:35%;float:left">
<fieldset class="adminform">

</fieldset>
</div>

	<input type="hidden" name="option" value="com_fab_balsamiq" />
	<input type="hidden" name="c" value="cron" />
	<input type="hidden" name="task" />
	<input type="hidden" name="id" value="0" />
	<?php echo JHTML::_('form.token');
	echo JHTML::_('behavior.keepalive'); ?>
		</form>
		<?php
	}
}
?>