<?php

/**
 * @version    CVS: 1.0.0
 * @package    Com_Tjreports
 * @author     Parth Lawate <contact@techjoomla.com>
 * @copyright  2016 Parth Lawate
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Tjreports helper.
 *
 * @since  1.6
 */
class TjreportsHelpersTjreports
{
	/** Gets a list of the actions that can be performed.
	 *
	 * @return  JObject
	 *
	 * @since    1.6
	 */

	public static function getActions()
	{
		$user   = JFactory::getUser();
		$result = new JObject;

		$assetName = 'com_tjreports';

		$actions = array(
			'core.admin',
			'core.manage',
			'core.create',
			'core.edit',
			'core.edit.own',
			'core.edit.state',
			'core.delete',
			'view.reports',
			'view.coursecategories',
			'view.courses',
			'view.questioncategories',
			'view.questionbank',
			'view.manageenrollment',
			'view.certificatetemplate',
			'view.coupons',
			'view.orders',
			'view.activities',
			'view.coursefields',
			'view.coursefieldsgroups',
			'view.singlecoursereport'
		);

		foreach ($actions as $action)
		{
			$result->set($action, $user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Get all the dates converted to utc
	 *
	 * @param   date  $date  date of lesson
	 *
	 * @return   date in utc format
	 *
	 * @since   1.0
	 */
	public function getDateInUtc($date)
	{
		// Change date in UTC
		$config = JFactory::getConfig();
		$offset = $config->get('offset');

		$lessonDate = JFactory::getDate(strtotime($date), 'UTC', true);
		$date = $lessonDate->toSql(true);

		return $date;
	}

	/**
	 * Get all the dates converted to utc
	 *
	 * @param   date  $date  date of lesson
	 *
	 * @return   date in utc format
	 *
	 * @since   1.0
	 */
	public function getDateInLocal($date)
	{
		// Get some system objects.
		$config = JFactory::getConfig();
		$user   = JFactory::getUser();

		$config = JFactory::getConfig();
		$offset = $config->get('offset');

		$mydate = JFactory::getDate(strtotime($date), $offset);

		return $mydate;
	}

	/**
	 * Get all jtext for javascript
	 *
	 * @return   void
	 *
	 * @since   1.0
	 */
	public static function getLanguageConstant()
	{
		JText::script('COM_TJREPORTS_NO_OF_ATTEMPT_VALIDATION_MSG');
		JText::script('COM_TJREPORTS_MAX_ATTEMPT_VALIDATION_MSG1');
		JText::script('COM_TJREPORTS_MAX_ATTEMPT_VALIDATION_MSG2');
		JText::script('COM_TJREPORTS_EMPTY_TITLE_ISSUE');
		JText::script('COM_TJREPORTS_COURSE_DURATION_VALIDATION');
		JText::script('COM_TJREPORTS_LESSON_UPDATED_SUCCESSFULLY');
		JText::script('COM_TJREPORTS_MODULE_PUBLISHED_SUCCESSFULLY');
		JText::script('COM_TJREPORTS_MODULE_UNPUBLISHED_SUCCESSFULLY');
		JText::script('COM_TJREPORTS_REPORTS_CANNOT_SELECT_NONE');
		JText::script('COM_TJREPORTS_ENTER_NUMERNIC_MARKS');
		JText::script('COM_TJREPORTS_NO_NEGATIVE_NUMBER');
		JText::script('COM_TJREPORTS_UPDATED_MARKS_SUCCESSFULLY');
		JText::script('COM_TJREPORTS_ENTER_MARKS_GRT_TOTALMARKS');
		JText::script('COM_TJREPORTS_END_DATE_CANTBE_GRT_TODAY');
		JText::script('COM_TJREPORTS_SURE_PAID_TO_FREE');
		JText::script('COM_TJREPORTS_SURE_PAID_TO_FREE');
		JText::script('COM_TJREPORTS_DELETE_CONFIRMATION_MESSAGE');

		// For date valiation
		JText::script('COM_TJREPORTS_DATE_VALIDATION_MONTH_INCORRECT');
		JText::script('COM_TJREPORTS_DATE_VALIDATION_DATE_INCORRECT');
		JText::script('COM_TJREPORTS_DATE_VALIDATION');
		JText::script('COM_TJREPORTS_DATE_VALIDATION_DATE_RANGE');
		JText::script('COM_TJREPORTS_DATE_RANGE_VALIDATION');
		JText::script('COM_TJREPORTS_DATE_TIME_VALIDATION');
		JText::script('COM_TJREPORTS_COUPON_DATE_VALIDATION');
		JText::script('COM_TJREPORTS_DASHBOARD_DATE_RANGE_VALIDATION');
		JText::script('COM_TJREPORTS_CLOSE_PREVIEW_LESSON');
	}
}
