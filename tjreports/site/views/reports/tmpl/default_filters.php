<?php
/**
 * @version     1.0.0
 * @package     com_tjreports
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @author      TechJoomla <extensions@techjoomla.com> - http://www.techjoomla.com
 */

// no direct access
defined('_JEXEC') or die;
use Joomla\CMS\HTML\HTMLHelper;

$displayFilters = $this->filters;
$filters = $this->filterValues;
$classForShowHide = '';

if ($this->filterLevel == 2)
{
	$classForShowHide = 'col-filter-header';
}

foreach ($displayFilters as $searchKey => $filter)
{
	$searchType = $filter['search_type'];
	$searchValue = isset($filters[$searchKey]) ? $filters[$searchKey] : '';
	$filterHtml = '';
	$filterHide = $searchValue === '' ? 'filter-hide' : 'filter-show';
	$bsGroupClass  = (JVERSION < '4.0.0') ? ' input-group ' : ' form-group ';

	if ($searchType == 'text')
	{
		$filterHtml = '<div class="' . $bsGroupClass . '">
							<input type="text" name="filters[' . $searchKey . ']"
									class="input input-mini filter-input ' . $filterHide . '" ' .
									'onkeydown="tjrContentUI.report.submitOnEnter(event);"
									onblur="tjrContentUI.report.submitTJRData();"
									value="' . htmlspecialchars($searchValue) . '"
								/>
							<span class="input-group-btn">
								<button class="btn btn-secondary close-icon" type="button" title="Cancel Search">
									<i class="fa fa-remove"></i>
								</button>
							</span>
						</div>';

		if (isset($this->colKey))
		{
			$filterHtml .= HTMLHelper::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
		}
	}
	elseif ($searchType == 'select' && isset($filter['select_options']))
	{
		$svalue = isset($filter['select_value']) ? $filter['select_value'] : "value";
		$stext  = isset($filter['select_text']) ? $filter['select_text'] : "text";

		$filterHtml = '<div class="' . $bsGroupClass . '">';

		$selectClass  = (JVERSION < '4.0.0') ? ' filter-input form-select-sm ' : ' form-select-sm ';

		$filterHtml .= HTMLHelper::_('select.genericlist', $filter['select_options'], 'filters[' . $searchKey . ']',
			'class="' . $selectClass . $filterHide . '" size="1" onchange="tjrContentUI.report.submitTJRData();"',
			$svalue, $stext, $searchValue
		);

		if ($this->filterLevel == 1)
		{
			$filterHtml	.= '</div>';
		}
		else
		{
			$filterHtml .= '<span class="input-group-btn">
								<button class="btn report-btn btn-secondary close-icon" type="button" title="Cancel Search">
									<i class="fa fa-remove"></i>
								</button>
							</span></div>';
		}

		if (isset($this->colKey))
		{
			$filterHtml .= HTMLHelper::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
		}
	}
	elseif ($searchType == 'date.range' || $searchType == 'calendar')
	{
		$j = ($searchType == 'date.range') ? 2 : 1;

		for ($i=1; $i<=$j; $i++)
		{
			$altfieldKey = '';

			if ($searchType == 'date.range')
			{
				$fieldKey    =  ($i == 1) ?  ($searchKey . '_from') : ($searchKey . '_to');
				$altfieldKey =  ($i == 1) ?  ($searchKey . '_to') : ($searchKey . '_from');
			}
			else
			{
				$fieldKey =  $searchKey;
			}

			$searchValue = isset($filters[$fieldKey]) ? $filters[$fieldKey] : '';
			$dateFormat  = isset($filters['dateFormat']) ? $filters['dateFormat'] : '%Y-%m-%d';

			if ($j == 2)
			{
				$altSearchValue = isset($filters[$altfieldKey]) ? $filters[$altfieldKey] : '';
				$filterHide = ($searchValue === '' && $altSearchValue === '') ? 'filter-hide' : 'filter-show';
			}
			else
			{
				$filterHide = $searchValue === '' ? 'filter-hide' : 'filter-show';
			}

			$attrib		 = array('class' => 'tjrsmall-input dash-calendar validate-ymd-date ' . $filterHide);

			if (isset($filter[$fieldKey]['attrib']))
			{
				$fieldAttr = array_merge($filter[$fieldKey]['attrib'], $attrib);
			}
			elseif (isset($filter['attrib']))
			{
				$fieldAttr = array_merge($filter['attrib'], $attrib);
			}
			else
			{
				$fieldAttr = $attrib;
			}

			$filterHtml  .= '<div class="filter-search controls custom-group ' . $bsGroupClass . '">'
				. HTMLHelper::_('calendar', htmlspecialchars($searchValue), 'filters[' . $fieldKey . ']', 'filters_' . $fieldKey , $dateFormat, $fieldAttr);

			if ($this->filterLevel == 1)
			{
				$filterHtml	.= '</div>';
			}
			elseif ($this->filterLevel != 1 && $i != 1 || $searchType == 'calendar' )
			{
				$filterHtml	.= '<span class="input-group-btn custom-group-btn">
								<button class="btn btn-secondary close-icon" type="button" title="Cancel Search">
									<i class="fa fa-remove"></i>
								</button>
							</span></div>';
			}

			if (isset($this->colKey))
			{
				$filterHtml .= HTMLHelper::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
			}
		}
	}
	elseif ($searchType == 'html')
	{
		$filterHtml = $filter['html'];
	}
	?>
		<div class="filter-search controls pull-left <?php echo $classForShowHide; ?>">
			<?php echo $filterHtml;?>
		</div>
	<?php
}
