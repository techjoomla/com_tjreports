<?php
/**
 * @package     Joomla.Site
 * @subpackage  Layout
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

/** @var JPaginationObject $item */
$item = $displayData['data'];

if (!empty($displayData['pagOptions']))
{
	$options = new Joomla\Registry\Registry($displayData['pagOptions']);
	$liClass = $options->get('liClass', '');
	$addText = $options->get('addText', '');
}
else
{
	$liClass = $addText = '';
}

$display = $item->text;

switch ((string) $item->text)
{
	// Check for "Start" item
	case Text::_('JLIB_HTML_START') :
		$icon = 'icon-backward icon-first fa fa-step-backward';
		$aria = Text::sprintf('JLIB_HTML_GOTO_POSITION', strtolower($item->text));
		break;

	// Check for "Prev" item
	case Text::_('JPREV') :
		$item->text = Text::_('JPREVIOUS');
		$icon = 'icon-step-backward icon-previous fa fa-backward';
		$aria = Text::sprintf('JLIB_HTML_GOTO_POSITION', strtolower($item->text));
		break;

	// Check for "Next" item
	case Text::_('JNEXT') :
		$icon = 'icon-step-forward icon-next fa fa-forward';
		$aria = Text::sprintf('JLIB_HTML_GOTO_POSITION', strtolower($item->text));
		break;

	// Check for "End" item
	case Text::_('JLIB_HTML_END') :
		$icon = 'icon-forward icon-last fa fa-step-forward';
		$aria = Text::sprintf('JLIB_HTML_GOTO_POSITION', strtolower($item->text));
		break;

	default:
		$icon = null;
		$aria = Text::sprintf('JLIB_HTML_GOTO_PAGE', $item->text);
		break;
}

$item->text .= $addText ?: '';

if ($icon !== null)
{
	$display = '<span class="' . $icon . '" aria-hidden="true"></span>';
}

if ($displayData['active'])
{
	if ($item->base > 0)
	{
		$limit = 'limitstart.value=' . $item->base;
	}
	else
	{
		$limit = 'limitstart.value=0';
	}

	$cssClasses = array();

	$title = '';

	if (!is_numeric($item->text))
	{
		HTMLHelper::_('bootstrap.tooltip');
		$cssClasses[] = 'hasTooltip';
		$title = ' title="' . $item->text . '" ';
	}

	$onClick = 'document.adminForm.' . $item->prefix . 'limitstart.value=' . ($item->base > 0 ? $item->base : '0') . '; Joomla.submitform();return false;';
}
else
{
	$class = (property_exists($item, 'active') && $item->active) ? 'active' : 'disabled';
	if ($class != 'active')
	{
		$class .= $liClass ? ($class ? ' ' : '') . $liClass : '';
	}
}
?>
<?php if ($displayData['active']) : ?>
	<li<?php echo $liClass ? ' class="' . $liClass . '"' : ''; ?>>
		<a aria-label="<?php echo $aria; ?>" <?php echo $cssClasses ? 'class="' . implode(' ', $cssClasses) . '"' : ''; ?> <?php echo $title; ?> href="#" onclick="<?php echo $onClick; ?>">
			<?php echo $display; ?>
		</a>
	</li>
<?php else : ?>
	<li class="<?php echo $class; ?>">
	<span <?php echo $class == 'active' ? 'aria-current="true" aria-label="' . Text::sprintf('JLIB_HTML_PAGE_CURRENT', $item->text) . '"' : ''; ?>>
		<?php echo $display; ?>
	</span>
	</li>
<?php endif;
