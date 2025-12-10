<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_tjreports
 *
 * @copyright   Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
$input = Factory::getApplication()->input;

$showParent = true;

if ($this->form->getValue('id'))
{
	if (!$this->form->getValue('parent'))
	{
		$showParent = false;
	}

	$this->form->setFieldAttribute('client', 'readonly', 'readonly');
	$this->form->setFieldAttribute('parent', 'readonly', 'readonly');
}
else
{
	$this->form->setFieldAttribute('parent', 'required', 'required');
}

Factory::getDocument()->addScriptDeclaration('
	Joomla.submitbutton = function(task)
	{
		if (task == "tjreport.cancel" || document.formvalidator.isValid(document.getElementById("adminForm")))
		{
			if (task != "tjreport.cancel" && jQuery("#jform_param").val())
			{
				try{
					var params = JSON.stringify(JSON.parse(jQuery("#jform_param").val()));
					jQuery("#jform_param").val(params)
				}catch(e){
					alert(Joomla.Text._("COM_TJREPORTS_INVALID_JSON_VALUE"));
					return false;
				}
			}
			if (!jQuery("#jform_id").val())
			{
				jQuery("#jform_parent").val(0);
			}
			jQuery("#permissions-sliders select").attr("disabled", "disabled");
			Joomla.submitform(task, document.getElementById("adminForm"));
		}
	};
');
?>
<form action="<?php echo Route::_('index.php?option=com_tjreports&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" name="adminForm" id="adminForm" class="form-validate tjreportForm">

	<div class="form-horizontal" id="tjreportContainer">
		<fieldset class="adminform">
			<?php echo HTMLHelper::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>

			<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'general', Text::_('COM_TJREPORTS_FEILDSET_DETAILS')); ?>
				<div class="control-group" style="display:none">
					<div class="control-label"><?php echo $this->form->getLabel('id'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('id'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('userid'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('userid'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('title'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('title'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('alias'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('alias'); ?></div>
				</div>

				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('client'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('client'); ?></div>
				</div>
				<?php if($showParent) {?>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('parent'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('parent'); ?></div>
				</div>
				<?php } ?>
				<div class="control-group">
					<div class="control-label"><?php echo $this->form->getLabel('param'); ?></div>
					<div class="controls"><?php echo $this->form->getInput('param'); ?></div>
				</div>

				<div class="control-group <?php echo $this->form->getValue('id') ? '' :'hide'?>">
					<div class="control-label">&nbsp;</div>
					<div class="controls">
						<button onclick="tjrContentUI.tjreport.getParams(true); return false;" class="btn">
							<?php echo Text::_('COM_TJREPORTS_LOAD_DEFAULT_PARAMS') ?>
						</button>
					</div>
				</div>

				<input type="hidden" name="jform[default]" value="1" />

				<?php
				if($this->item->id)
				{	?>
					<div class="control-group">
						<div class="control-label"><?php echo $this->form->getLabel('plugin'); ?></div>
						<div class="controls"><?php echo $this->form->getInput('plugin'); ?></div>
					</div>
		<?php	}
				else
				{	?>
					<input type="hidden" name="jform[plugin]" id="jform_plugin" value=""/>
		<?php	}	?>

			<?php echo HTMLHelper::_('bootstrap.endTab'); ?>

			<?php if ($this->canDo->get('core.admin')) : ?>
				<?php echo HTMLHelper::_('bootstrap.addTab', 'myTab', 'rules', Text::_('COM_CONTENT_FIELDSET_RULES')); ?>
					<div class="control-group">
						<div class="controls"><?php echo $this->form->getInput('rules'); ?></div>
					</div>
				<?php echo HTMLHelper::_('bootstrap.endTab'); ?>
			<?php endif; ?>

			<?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
		</fieldset>

	</div>
    <input type="hidden" name="extension" value="<?php echo $input->get('extension') ?>" id="jform_parent" />
    <input type="hidden" id="task" name="task" value="tjreport.edit" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>
