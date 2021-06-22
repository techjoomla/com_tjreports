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

$displayFilters = $this->filters;
$filters = $this->filterValues;
$classForShowHide = '';

if ($this->filterLevel == 2)
{
	$classForShowHide = 'col-filter-header';
}

if ($this->addMorefilter && $this->allowToCreateResultSets)
{
	$removeFilter = '';

	if (isset($this->removeFilter) && $this->removeFilter != '' && $this->removeFilter != 0)
	{
		$removeFilter = (int) $this->removeFilter;

		unset($filters['options-' . $removeFilter]);

		$filterInc  = 0;
		$tempFilter = array();

		foreach ($filters as $f_key => $f_value)
		{
			$tempFilter['options-' . $filterInc] = $f_value;
			$filterInc++;
		}

		$filters = $tempFilter;
	}

	for ((int) $i = 0; $i < $this->addMorefilter; $i++)
	{
		if ($i >= 1)
		{
			echo '<hr/>';
		}
		?>
		<div class="tjreport-filter-repeatable clearfix">

		<?php
		foreach ($displayFilters as $searchKey => $filter)
		{
			$searchType  = $filter['search_type'];
			$searchValue = isset($filters['options-' . $i][$searchKey]) ? $filters['options-' . $i][$searchKey] : '';
			$filterHtml  = '';
			$filterHide  = $searchValue === '' ? 'filter-hide' : 'filter-show';

			if ($searchType == 'text')
			{
				$filterHtml = '<div class="input-group">
									<input type="text" name="filters[options-' . $i . '][' . $searchKey . ']"
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

				if(isset($this->colKey))
				{
					$filterHtml .= JHtml::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
				}
			}
			elseif($searchType == 'select' && isset($filter['select_options']))
			{
				$svalue      = isset($filter['select_value']) ? $filter['select_value'] : "value";
				$stext       = isset($filter['select_text']) ? $filter['select_text'] : "text";
				$multiple    = isset($filter['multiple']) && $filter['multiple'] ? "multiple='true'" : "";
				$arrayFilter = isset($filter['multiple']) && $filter['multiple'] ? "[]" : "";
				$class       = isset($filter['class']) ? $filter['class'] : "";

				$filterHtml = '<div class="input-group">';

				$filterHtml .= JHtml::_('select.genericlist', $filter['select_options'], 'filters[options-' . $i . '][' . $searchKey . ']' . $arrayFilter,
							'class="filter-input ' . $filterHide . ' ' . $class . ' " size="1" onchange="tjrContentUI.report.submitTJRData();"' . $multiple,
							$svalue, $stext, $searchValue);

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

				if(isset($this->colKey))
				{
					$filterHtml .= JHtml::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
				}
			}
			elseif($searchType == 'date.range' || $searchType == 'calendar')
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

					$searchValue = isset($filters['options-' . $i][$fieldKey]) ? $filters['options-' . $i][$fieldKey] : '';
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

					$filterHtml  .= '<div class="filter-search controls custom-group input-group">'
						. JHtml::_('calendar', htmlspecialchars($searchValue), 'filters[options-' . $i . ']['. $fieldKey . ']', 'filters_' . $fieldKey , $dateFormat, $fieldAttr);

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

					if(isset($this->colKey))
					{
						$filterHtml .= JHtml::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
					}
				}
			}
			elseif($searchType == 'html')
			{
				$filterHtml = $filter['html'];
			}
			?>
				<div class="filter-search controls pull-left <?php echo $classForShowHide; ?>">
					<?php echo $filterHtml;?>
				</div>
			<?php
		}
		?>
		<?php
		if ($this->filterLevel == 1)
		{
		?>
		<div class="btn-group filter-btn-block control-group">
			<button type="button" onclick="tjrContentUI.report.submitTJRData('default', 1);" class="btn hasTooltip btn-success" title="Add">
				<i class="fa fa-plus-circle" aria-hidden="true"></i>
			</button>
			<?php
			if ($i != 0)
			{
				?>
				<button type="button" onclick="tjrContentUI.report.submitTJRData('default', 0, <?php echo $i; ?>);" class="btn hasTooltip btn-danger" title="Remove">
					<i class="fa fa-minus-circle" aria-hidden="true"></i>
				</button>
				<?php
			}
			?>
		</div>
		<?php
		}
		?>
	</div>
	<?php
	}
}
else
{
	foreach($displayFilters as $searchKey => $filter)
	{
		$searchType = $filter['search_type'];
		$searchValue = isset($filters[$searchKey]) ? $filters[$searchKey] : '';
		$filterHtml = '';
		$filterHide = $searchValue === '' ? 'filter-hide' : 'filter-show';

		if ($searchType == 'text')
		{
			$filterHtml = '<div class="input-group">
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

			if(isset($this->colKey))
			{
				$filterHtml .= JHtml::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
			}
		}
		elseif($searchType == 'select' && isset($filter['select_options']))
		{
			$svalue      = isset($filter['select_value']) ? $filter['select_value'] : "value";
			$stext       = isset($filter['select_text']) ? $filter['select_text'] : "text";
			$multiple    = isset($filter['multiple']) && $filter['multiple'] ? "multiple='true'" : "";
			$arrayFilter = isset($filter['multiple']) && $filter['multiple'] ? "[]" : "";
			$class       = isset($filter['class']) ? $filter['class'] : "";

			$filterHtml = '<div class="input-group">';

			$filterHtml .= JHtml::_('select.genericlist', $filter['select_options'], 'filters[' . $searchKey . ']' . $arrayFilter,
						'class="filter-input ' . $filterHide . ' ' . $class . ' " size="1" onchange="tjrContentUI.report.submitTJRData();"' . $multiple,
						$svalue, $stext, $searchValue);

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

			if(isset($this->colKey))
			{
				$filterHtml .= JHtml::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
			}
		}
		elseif($searchType == 'date.range' || $searchType == 'calendar')
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

				$filterHtml  .= '<div class="filter-search controls custom-group input-group">'
					. JHtml::_('calendar', htmlspecialchars($searchValue), 'filters['. $fieldKey . ']', 'filters_' . $fieldKey , $dateFormat, $fieldAttr);

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

				if(isset($this->colKey))
				{
					$filterHtml .= JHtml::_('grid.sort', '', $this->colKey, $this->listDirn, $this->listOrder);
				}
			}
		}
		elseif($searchType == 'html')
		{
			$filterHtml = $filter['html'];
		}
		?>
			<div class="filter-search controls pull-left <?php echo $classForShowHide; ?>">
				<?php echo $filterHtml;?>
			</div>
		<?php
	}
}
