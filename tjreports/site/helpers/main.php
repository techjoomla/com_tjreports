<?php
/**
 * @version    SVN: <svn_id>
 * @package    Plg_System_Tjlms
 * @copyright  Copyright (C) 2005 - 2014. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * Shika is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// No direct access.
defined('_JEXEC') or die;
jimport('joomla.html.html');
jimport('joomla.html.parameter');
jimport('joomla.utilities.date');
jimport('techjoomla.common');
jimport('techjoomla.tjmail.mail');

/**
 * Tjlms Main helper.
 *
 * @since  1.0.0
 */
class ComtjreportsHelper
{
	/**
	 * Method acts as a consturctor
	 *
	 * @since   1.0.0
	 */
	public function __construct()
	{
		$params = JComponentHelper::getParams('com_tjlms');
		$this->tjlmsparams = $params;
		$socialintegration = $params->get('social_integration');

		// Load main file
		jimport('techjoomla.jsocial.jsocial');
		jimport('techjoomla.jsocial.joomla');

		if ($socialintegration != 'none')
		{
			if ($socialintegration == 'jomsocial')
			{
				jimport('techjoomla.jsocial.jomsocial');
			}
			elseif ($socialintegration == 'easysocial')
			{
				jimport('techjoomla.jsocial.easysocial');
			}
		}

		$this->sociallibraryobj = $this->getSocialLibraryObject();
	}

	/**
	 * Function to get component params
	 *
	 * @param   STRING  $component  Component name
	 *
	 * @return  ARRAY  $params
	 *
	 * @since  1.0.0
	 */
	public function getcomponetsParams($component = 'com_tjlms')
	{
		$params = JComponentHelper::getParams($component);

		return $params;
	}

	/**
	 * Function to genrate PDF
	 *
	 * @param   STRING  $html       Html string
	 * @param   STRING  $pdffile    File path
	 * @param   STRING  $course_id  Course ID
	 * @param   STRING  $user_id    User iD
	 * @param   STRING  $download   Allow download
	 *
	 * @return  Pdf file
	 *
	 * @since  1.0.0
	 */
	public function generatepdf($html, $pdffile, $course_id, $user_id, $download = 0)
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');

		// Removed from joomla 3.0 and greater
		if (JVERSION < 3.0)
		{
			if ($funcs = spl_autoload_functions())
			{
				foreach ($funcs as $func)
				{
					spl_autoload_unregister($func);
				}
			}
		}

		require_once JPATH_SITE . "/libraries/techjoomla/dompdf/dompdf_config.inc.php";

		if (JVERSION < '2.5.0')
		{
			$classpath = JPATH_SITE . "/libraries/techjoomla/dompdf";

			foreach (JFolder::files($classpath) as $file)
			{
				JLoader::register(JFile::stripExt($file), $classpath . DS . $file);
			}
		}
		else
		{
			spl_autoload_register('DOMPDF_autoload');

			// This added in as well. ABP: Re-enable spl functions
			if ($funcs)
			{
				foreach ($funcs as $func)
				{
					if (is_callable($func))
					{
						spl_autoload_register($func);
					}
				}
				// Re-register
				// Import the library loader if necessary.
				if (!class_exists('JLoader'))
				{
					require_once JPATH_PLATFORM . '/loader.php';
				}

				class_exists('JLoader') or die('pdf generation failed');

				// Setup the autoloaders.
				JLoader::setup();

				// Import the cms loader if necessary.
				if (version_compare(JVERSION, '2.5.6', 'le'))
				{
					if (!class_exists('JCmsLoader'))
					{
						require_once JPATH_PLATFORM . '/cms/cmsloader.php';
						JCmsLoader::setup();
					}
				}
				else
				{
					if (!class_exists('JLoader'))
					{
						require_once JPATH_PLATFORM . '/cms.php';
						require_once JPATH_PLATFORM . '/loader.php';
						JLoader::setup();
					}
				}
			}

			require_once JPATH_PLATFORM . '/loader.php';
		}

		$html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $html . '</body></html>';

		if (get_magic_quotes_gpc())
		{
			$html = stripslashes($html);
		}

		$dompdf    = new DOMPDF;
		$font      = Font_Metrics::get_font("Minim", "normal");
		$txtHeight = Font_Metrics::get_font_height($font, 8);
		$html      = utf8_decode($html);
		$dompdf->load_html($html);
		$dompdf->render();

		$output = $dompdf->output();

		if ($download == 1)
		{
			$dompdf->stream("Certificate_" . $course_id . "_" . $user_id . ".pdf", array(
																						"Attachment" => 1
																					)
							);
			jexit();
		}

		if (fopen($pdffile, 'w'))
		{
			$fh = fopen($pdffile, 'w');
			fwrite($fh, $output);
			fclose($fh);

			return $pdffile;
		}
	}

	/**
	 * Function used to get enrolled user
	 *
	 * @param   INT    $c_id     Course ID
	 * @param   ARRAY  $options  Optional parameter
	 *
	 * @return  Enrolled users
	 *
	 * @since  1.0.0
	 */
	public function getCourseEnrolledUsers($c_id = 0, $options = array())
	{
		$select = '*';

		if (isset($options['IdOnly']) && $options['IdOnly'] == 1)
		{
			$select = 'user_id';
		}

		$getResultType = "loadobjectlist";

		if (isset($options['getResultType']))
		{
			$getResultType = $options['getResultType'];
		}

		// ADDED BY BLACK PEARL
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($select);
		$query->from('`#__tjlms_enrolled_users` AS a');
		$query->join('inner', '`#__users` AS u ON a.user_id = u.id');
		$query->where('a.course_id = ' . $db->Quote($c_id));
		$query->where('a.state = 1');
		/*$query = "SELECT " . $select . " FROM #__tjlms_enrolled_users as eu
					RIGHT JOIN #__users as u ON eu.user_id = u.id
					WHERE eu.course_id=" . $c_id . " AND eu.state=1";*/
		$db->setQuery($query);
		$enrolled_users = $db->$getResultType();

		return $enrolled_users;
	}

	/**
	 * Function to get certificate date
	 *
	 * @param   INT  $c_id     Course ID
	 * @param   INT  $user_id  User ID
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function getcourse_Certificate_data($c_id, $user_id)
	{
		if ($c_id)
		{
			$tjlmsCoursesHelper   = new TjlmsCoursesHelper;
			$db                   = JFactory::getDBO();
			$details              = $tjlmsCoursesHelper->getcourseInfo($c_id);
			$result['course']     = $details->title;

			$query = $db->getQuery(true);
			$query->select('username, name');
			$query->from('#__users');
			$query->where('id=' . $user_id);
			$db->setQuery($query);
			$studentdetails            = $db->loadObjectlist();

			$result['studentname']     = $studentdetails[0]->name;
			$result['studentusername'] = $studentdetails[0]->username;

			JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');
			$coursetrack = JTable::getInstance('coursetrack', 'TjlmsTable', array('dbo', $db));
			$coursetrack->load(array('course_id' => $c_id, 'user_id' => $user_id));
			$certtrack = JTable::getInstance('certtrack', 'TjlmsTable', array('dbo', $db));
			$certtrack->load(array('course_id' => $c_id, 'user_id' => $user_id));

			$result['date_of_completion'] = JFactory::getdate($certtrack->grant_date)->format('j F, Y');
			$result['expiry_date'] = JFactory::getdate($certtrack->exp_date)->format('j F, Y');

			return $result;
		}
	}

	/**
	 * Function to replace tags from certificate to actual result
	 *
	 * @param   ARRAY  $cer_data  Certificate data
	 *
	 * @return  Message body
	 *
	 * @since  1.0.0
	 */
	public function tagreplace($cer_data)
	{
		$message_body = $cer_data['msg_body'];
		$message_body = stripslashes($message_body);
		$message_body = str_replace("[STUDENTNAME]", $cer_data['studentname'], $message_body);
		$message_body = str_replace("[STUDENTUSERNAME]", $cer_data['studentusername'], $message_body);
		$message_body = str_replace("[COURSE]", $cer_data['course'], $message_body);
		$message_body = str_replace("[DATE]", $cer_data['date_of_completion'], $message_body);

		/*if ($cer_data['score'])
		$message_body        =     str_replace("[SCORE]", $cer_data['score'], $message_body);
		else
		{
		$message_body        =     str_replace("[SCORE]", '', $message_body);
		$message_body        =     str_replace("score", '', $message_body);
		}*/

		return $message_body;
	}

	/**
	 * Function to get usergroup of a user
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	/*public function getChildGroupsByuser()
	{
		$oluser   = JFactory::getUser();
		$oluserid = $oluser->id;
		$db       = JFactory::getDBO();

		if (comtjlmsHelper::checkAdmin($oluser))
		{
			$query = "SELECT distinct(group_id) FROM #__user_usergroup_map ";

			if (isset($oluser->groups['7']))
				$query .= " where group_id!=8";

			$db->setQuery($query);
			$oluser_groups = $db->loadResultArray();
		}
		else
		{
			$query = "SELECT group_id FROM #__user_usergroup_map where user_id=" . $oluserid;
			$db->setQuery($query);
			$oluser_groups = $db->loadResultArray();
		}

		$default = '';

		if (JRequest::getVar('group_filter'))
			$default = JRequest::getVar('group_filter');

		$groups = array();
		foreach ($oluser_groups as $g)
		{
			if (!in_array($g, $groups))
				$groups[] = $g;

			$query = "SELECT id FROM #__usergroups where     parent_id=" . $g;
			$db->setQuery($query);
			$g_obj = $db->loadResultArray();
			if ($g_obj)
				foreach ($g_obj as $obj)
					$groups[] = $obj;
		}
		return $groups;
	}*/

	/**
	 * Function to check if the user is admin
	 *
	 * @param   OBJ  $user  User object
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function checkAdmin($user)
	{
		$isAdmin = $user->get('isRoot');

		if ($isAdmin)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Function to get Item id
	 *
	 * @param   STRING  $link  URL
	 *
	 * @return  INT  Item id
	 *
	 * @since  1.0.0
	 */
	public function getitemid($link)
	{
		$itemid = 0;

		parse_str($link, $parsedLinked);

		$layout = '';

		if (isset($parsedLinked['layout']))
		{
			$layout = $parsedLinked['layout'];
		}

		if (isset($parsedLinked['view']))
		{
			if ($parsedLinked['view'] == 'course')
			{
				$tjlmsCoursesHelper   = new TjlmsCoursesHelper;
				$itemid    = $tjlmsCoursesHelper->getCourseItemid($parsedLinked['id'], $layout);

				if (!$itemid)
				{
					$link = 'index.php?option=com_tjlms&view=courses';
				}
			}

			if ($parsedLinked['view'] == 'buy' || $parsedLinked['view'] == 'certificate' ||  $parsedLinked['view'] == 'attempts')
			{
				$tjlmsCoursesHelper   = new TjlmsCoursesHelper;
				$itemid    = $tjlmsCoursesHelper->getCourseItemid($parsedLinked['course_id'], $layout);
			}

			if ($parsedLinked['view'] == 'lesson')
			{
				$tjlmLessonHelper  = new TjlmsLessonHelper;
				$itemid    = $tjlmLessonHelper->getLessonItemid($parsedLinked['lesson_id']);
			}
		}

		if (!$itemid)
		{
			$mainframe = JFactory::getApplication();

			if ($mainframe->issite())
			{
				$JSite = new JSite;
				$menu  = $JSite->getMenu();
				$menuItem = $menu->getItems('link', $link, true);

				if ($menuItem)
				{
					$itemid = $menuItem->id;
				}
			}

			if (!$itemid)
			{
				$db = JFactory::getDBO();
				$query = $db->getQuery(true);
				$query->select($db->quoteName('id'));
				$query->from($db->quoteName('#__menu'));
				$query->where($db->quoteName('link') . ' LIKE ' . $db->Quote($link));
				$query->where($db->quoteName('published') . '=' . $db->Quote(1));
				$query->where($db->quoteName('type') . '=' . $db->Quote('component'));
				$db->setQuery($query);
				$itemid = $db->loadResult();
			}

			if (!$itemid)
			{
				$input = JFactory::getApplication()->input;
				$itemid = $input->get('Itemid', 0);
			}
		}

		return $itemid;
	}

	/**
	 * Function to get Order info
	 *
	 * @param   INT  $orderid  Order ID
	 * @param   INT  $step_id  Checkout step
	 *
	 * @return  ARRAY
	 *
	 * @since  1.0.0
	 */
	public function getorderinfo($orderid = '0', $step_id = '')
	{
		$db     = JFactory::getDBO();
		$user   = JFactory::getUser();
		$jinput = JFactory::getApplication()->input;

		if (empty($orderid))
		{
			return 0;
		}

		if ($step_id == 'step_select_subsplan')
		{
			$query = $db->getQuery(true);
			$query->select('o.*');
			$query->from('#__tjlms_orders as o');
			$query->where('o.id=' . $orderid);

			$db->setQuery($query);
			$order_result            = $db->loadObjectList();
			$orderlist['order_info'] = $order_result;

			return $orderlist;
		}
		else
		{
			$query = $db->getQuery(true);
			$query->select('o.*,u.*,o.order_id as orderid_with_prefix');
			$query->from('#__tjlms_orders as o');
			$query->join('LEFT', '#__tjlms_users as u ON o.id = u.order_id');
			$query->where('o.id=' . $orderid);

			$db->setQuery($query);
			$order_result = $db->loadObjectList();

			if (empty($order_result))
			{
				return;
			}

			$orderlist['order_info'] = $order_result;

			$query = $db->getQuery(true);
			$query->select('i.plan_id,CONCAT(s.duration," ",s.time_measure) as order_item_name, s.price');
			$query->from('#__tjlms_order_items as i');
			$query->join('LEFT', '#__tjlms_subscription_plans as s ON s.id=i.plan_id');
			$query->where('i.order_id=' . $orderid . ' GROUP BY i.plan_id');

			$db->setQuery($query);
			$orderlist['items'] = $db->loadObjectList();

			return $orderlist;
		}
	}

	/**
	 * This function Checks whether order user and current logged use is same or not
	 *
	 * @param   INT  $orderuser  User ID
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function getorderAuthorization($orderuser)
	{
		$user = JFactory::getUser();

		if ($user->id == $orderuser)
		{
			return 1;
		}

		return 0;
	}

	/**
	 * This function get the view path
	 *
	 * @param   STRING  $component      Component name
	 * @param   STRING  $viewname       View name
	 * @param   STRING  $layout         Layout
	 * @param   STRING  $searchTmpPath  Site
	 * @param   STRING  $useViewpath    Site
	 *
	 * @return  boolean
	 *
	 * @since  1.0.0
	 */
	public function getViewpath($component, $viewname, $layout = 'default', $searchTmpPath = 'SITE', $useViewpath = 'SITE')
	{
		$app = JFactory::getApplication();

		$searchTmpPath = ($searchTmpPath == 'SITE') ? JPATH_SITE : JPATH_ADMINISTRATOR;
		$useViewpath   = ($useViewpath == 'SITE') ? JPATH_SITE : JPATH_ADMINISTRATOR;

		$layoutname = $layout . '.php';

		$override = $searchTmpPath . '/' . 'templates' . '/' . $app->getTemplate() . '/' . 'html' . '/' . $component . '/' . $viewname . '/' . $layoutname;

		if (JFile::exists($override))
		{
			return $view = $override;
		}
		else
		{
			return $view = $useViewpath . '/' . 'components' . '/' . $component . '/' . 'views' . '/' . $viewname . '/' . 'tmpl' . '/' . $layoutname;
		}
	}

	/**
	 * Function ised to get the way to display price
	 *
	 * @param   INT     $price  Amount to be displayed
	 * @param   STRING  $curr   Currency
	 *
	 * @return formatted price-currency string
	 *
	 * @since  1.0.0
	 */
	public function getFromattedPrice($price, $curr = null)
	{
		$curr_sym                   = $this->getCurrencySymbol();
		$params                     = JComponentHelper::getParams('com_tjlms');
		$currency_display_format    = $params->get('currency_display_format');
		$currency_display_formatstr = '';
		$currency_display_formatstr = str_replace('{AMOUNT}', "&nbsp;" . $price, $currency_display_format);
		$currency_display_formatstr = str_replace('{CURRENCY_SYMBOL}', "&nbsp;" . $curr_sym, $currency_display_formatstr);
		$html                       = '';
		$html                       = "<span>" . $currency_display_formatstr . "</span>";

		return $html;
	}

	/**
	 * Function used to get the currency symbol
	 *
	 * @param   STRING  $currency  Currency
	 *
	 * @return  STRING  Currency symbol
	 *
	 * @since  1.0.0
	 */
	public function getCurrencySymbol($currency = '')
	{
		$params   = JComponentHelper::getParams('com_tjlms');
		$curr_sym = $params->get('currency_symbol');

		if (empty($curr_sym))
		{
			$curr_sym = $params->get('currency');
		}

		return $curr_sym;
	}

	/**
	 * Function used to ID from order id
	 *
	 * @param   INT  $order_id  Currency
	 *
	 * @return  INT  Order Id
	 *
	 * @since  1.0.0
	 */
	public function getIDFromOrderID($order_id)
	{
		$db    = JFactory::getDBO();
		$query = "SELECT id From #__tjlms_orders WHERE order_id LIKE '" . $order_id . "'";
		$db->setQuery($query);
		$result = $db->loadResult();

		return $result;
	}

	/**
	 * Function used to get number of likes for an item.
	 *
	 * @param   INT     $item_id  Item id
	 * @param   STRING  $element  Element name
	 *
	 * @return  INT  Likes number
	 *
	 * @since  1.0.0
	 */
	public function getLikesForItem($item_id, $element)
	{
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('l.like_cnt');
		$query->from('#__jlike_content as l');
		$query->where('l.element="' . $element . '"');
		$query->where('l.element_id=' . $item_id);
		$db->setQuery($query);
		$likesforCourse = $db->loadResult();

		if (empty($likesforCourse))
		{
			$likesforCourse = 0;
		}

		return $likesforCourse;
	}

	/**
	 * Function used as a push activity into Shika activity stream.
	 *
	 * @param   INT     $actor_id       user who perform the action ID
	 * @param   STRING  $action         action performed by the user
	 * @param   INT     $parent_id      Parent element ID.
	 * @param   STRING  $element_title  title for the element
	 * @param   INT     $element_id     Child element ID
	 * @param   STRING  $element_url    Child element URL
	 * @param   STRING  $params         additional info if provided
	 *
	 * @return  boolean true or false
	 *
	 * @since  1.0.0
	 */
	public function addActivity($actor_id, $action, $parent_id = 0, $element_title = '', $element_id = '', $element_url = '', $params = '')
	{
		$db = JFactory::getDBO();

		$data              = new stdClass;
		$data->actor_id    = $actor_id;
		$data->action      = $action;
		$data->parent_id   = $parent_id;
		$data->element     = $element_title;
		$data->element_id  = $element_id;
		$data->element_url = $element_url;
		$data->params      = $params;
		$data->added_time  = date('Y-m-d H:i:s');

		$db->insertObject('#__tjlms_activities', $data, 'id');

		return true;
	}

	/**
	 * function to get html for certificate
	 *
	 * @param   INT  $userId    User ID
	 * @param   INT  $courseId  Course ID
	 *
	 * @return  Html
	 *
	 * @since 1.0
	 */
	public function getcertificateHTML($userId, $courseId)
	{
		$comtjlmsHelper = new comtjlmsHelper;
		require JPATH_ADMINISTRATOR . '/components/com_tjlms/certificate.php';

		$msg = $comtjlmsHelper->getcourse_Certificate_data($courseId, $userId);

		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('certificate_name');
		$query->from($db->quoteName('#__tjlms_courses'));
		$query->where($db->quoteName('id') . " = " . $courseId);
		$db->setQuery($query);
		$result = $db->loadResult();

		$query = $db->getQuery(true);
		$query->select('body');
		$query->from($db->quoteName('#__tjlms_certificate_template'));
		$query->where($db->quoteName('id') . " = " . $result);
		$db->setQuery($query);
		$result = $db->loadResult();

		if (!empty($result))
		{
			$msg['msg_body'] = $result;
		}
		else
		{
			$msg['msg_body'] = $certificate['message_body'];
		}

		// Replace Special Tags from Backend Ticket Template
		$html = $comtjlmsHelper->tagreplace($msg);

		return $html;
	}

	/**
	 * Get social library object depending on the integration set.
	 *
	 * @param   STRING  $integration_option  Soical integration set
	 *
	 * @return  Soical library object
	 *
	 * @since 1.0.0
	 */
	public function getSocialLibraryObject($integration_option = '')
	{
		if (!$integration_option)
		{
			$params             = $this->getcomponetsParams();
			$integration_option = $params->get('social_integration', 'joomla');
		}

		if ($integration_option == 'jomsocial')
		{
			$SocialLibraryObject = new JSocialJomSocial;
		}
		elseif ($integration_option == 'easysocial')
		{
			$SocialLibraryObject = new JSocialEasySocial;
		}
		elseif ($integration_option == 'joomla')
		{
			$SocialLibraryObject = new JSocialJoomla;
		}

		return $SocialLibraryObject;
	}

	/**
	 * This function extracts the non-tags string and returns a correctly formatted string
	 * It can handle all html entities e.g. &amp;, &quot;, etc..
	 *
	 * @param   string        $s       To do
	 * @param   integer       $srt     To do
	 * @param   integer       $len     To do
	 * @param   bool/integer  $strict  If this is set to 2 then the last sentence will be completed.
	 * @param   string        $suffix  A string to suffix the value, only if it has been chopped.
	 *
	 * @return  STRING
	 *
	 * @since  1.0.0
	 */
	public function html_substr($s, $srt, $len = null, $strict = false, $suffix = null)
	{
		if (is_null($len))
		{
			$len = strlen($s);
		}

		$f = 'static $strlen=0;
				if ( $strlen >= ' . $len . ' ) { return "><"; }
				$html_str = html_entity_decode( $a[1] );
				$subsrt   = max(0, (' . $srt . '-$strlen));
				$sublen = ' . (empty($strict) ? '(' . $len . '-$strlen)' : 'max(@strpos( $html_str, "' .
							($strict === 2 ? '.' : ' ') . '", (' . $len . ' - $strlen + $subsrt - 1 )), ' . $len . ' - $strlen)') . ';
				$new_str = substr( $html_str, $subsrt,$sublen);
				$strlen += $new_str_len = strlen( $new_str );
				$suffix = ' . (!empty($suffix) ? '($new_str_len===$sublen?"' . $suffix . '":"")' : '""') . ';
				return ">" . htmlentities($new_str, ENT_QUOTES, "UTF-8") . "$suffix<";';

		return preg_replace(
								array(
									"#<[^/][^>]+>(?R)*</[^>]+>#",
									"#(<(b|h)r\s?/?>){2,}$#is"
								),
								"", trim(rtrim(ltrim(preg_replace_callback("#>([^<]+)<#", create_function('$a', $f), ">$s<"), ">"), "<"))
							);
	}

	/**
	 * Function to course order details
	 *
	 * @param   INT  $where  Where condition for query
	 *
	 * @return  Details of course irder
	 *
	 * @snce 1.0.0
	 */
	public function getallCourseDetailsByOrder($where = '')
	{
		$db    = JFactory::getDBO();

		$query = $db->getQuery(true);
		$query->select('c.id as course_id,c.title,c.image,c.storage,o.*,oi.*');
		$query->from('#__tjlms_orders as o');
		$query->join('LEFT', '#__tjlms_order_items as oi ON oi.order_id = o.id');
		$query->join('LEFT', '#__tjlms_courses as c ON c.id = oi.course_id');
		$query->where($where);

		$db->setQuery($query);
		$result = $db->loadObjectlist();

		return $result;
	}

	/**
	 * Function to get Country
	 *
	 * @param   INT  $countryId  Country Code
	 *
	 * @return  Country name
	 *
	 * @snce 1.0.0
	 */
	public function getCountryById($countryId)
	{
		$TjGeoHelper = JPATH_ROOT . DS . 'components/com_tjfields/helpers/geo.php';

		if (!class_exists('TjGeoHelper'))
		{
			JLoader::register('TjGeoHelper', $TjGeoHelper);
			JLoader::load('TjGeoHelper');
		}

		$this->TjGeoHelper = new TjGeoHelper;

		return $this->TjGeoHelper->getCountryNameFromId($countryId);
	}

	/**
	 * Function to get region
	 *
	 * @param   INT  $regionId  Region Code
	 *
	 * @return  region name
	 *
	 * @snce 1.0.0
	 */
	public function getRegionById($regionId)
	{
		$TjGeoHelper = JPATH_ROOT . DS . 'components/com_tjfields/helpers/geo.php';

		if (!class_exists('TjGeoHelper'))
		{
			JLoader::register('TjGeoHelper', $TjGeoHelper);
			JLoader::load('TjGeoHelper');
		}

		$this->TjGeoHelper = new TjGeoHelper;

		return $this->TjGeoHelper->getRegionNameFromId($regionId);
	}

	/**
	 * Function to send mail
	 *
	 * @param   STRING  $recipient       Email address of reciever
	 * @param   STRING  $subject         Email Subject
	 * @param   STRING  $body            Email Body
	 * @param   STRING  $bcc_string      BCC Email address
	 * @param   INT     $singlemail      Single mail
	 * @param   STRING  $attachmentPath  Attachmen Path
	 * @param   STRING  $cc_array        CC Email address
	 *
	 * @return  true
	 *
	 * @since 1.0.0
	 */
	public function sendmail($recipient, $subject, $body, $bcc_string, $singlemail = 1, $attachmentPath = "", $cc_array = array())
	{
		jimport('joomla.utilities.utility');
		global $mainframe;
		$mainframe = JFactory::getApplication();
		$from      = $mainframe->getCfg('mailfrom');
		$fromname  = $mainframe->getCfg('fromname');
		$recipient = trim($recipient);
		$mode      = 1;
		$cc        = array();
		$bcc       = array();

		if ($singlemail == 1)
		{
			if ($bcc_string)
			{
				$bcc = explode(',', $bcc_string);
			}
			else
			{
				$bcc = array(
					'0' => $mainframe->getCfg('mailfrom')
				);
			}
		}

		if (!empty($cc_array))
		{
			$cc = $cc_array;
		}

		$attachment = null;

		if (!empty($attachmentPath))
		{
			$attachment = $attachmentPath;
		}

		$replyto     = null;
		$replytoname = null;

		return JFactory::getMailer()->sendMail($from, $fromname, $recipient, $subject, $body, $mode, $cc, $bcc, $attachment, $replyto, $replytoname);
	}

	/**
	 * Function to get html and send invoice mail
	 *
	 * @param   INT  $id  Order ID
	 *
	 * @return  true
	 *
	 * @since 1.0.0
	 */
	public function sendInvoiceEmail($id)
	{
		$com_params = JComponentHelper::getParams('com_tjlms');

		$comtjlmsHelper = new comtjlmsHelper;
		$orderItemid    = $comtjlmsHelper->getItemId('index.php?option=com_tjlms&view=orders');
		$jinput         = JFactory::getApplication()->input;
		$order          = $comtjlmsHelper->getorderinfo($id);
		$app            = JFactory::getApplication();
		$sitename       = $app->getCfg('sitename');

		$TjlmsCoursesHelper = new TjlmsCoursesHelper;
		$this->order_mail['courses']    = $TjlmsCoursesHelper->getcourseInfo($order['order_info'][0]->course_id);
		$this->order_mail['order']    = $order['order_info'][0];

		$this->orderinfo        = $order['order_info'];
		$this->orderitems       = $order['items'];
		$this->orders_site      = 1;
		$this->orders_email     = 1;
		$this->order_authorized = 1;

		if ($this->orderinfo[0]->address_type == 'BT')
		{
			$billemail = $this->orderinfo[0]->user_email;
		}
		elseif ($this->orderinfo[1]->address_type == 'BT')
		{
			$billemail = $this->orderinfo[1]->user_email;
		}

		$oWithSuf = $order['order_info'][0]->orderid_with_prefix;
		$processor = $order['order_info'][0]->processor;
		$orderUrl = 'index.php?option=com_tjlms&view=orders&layout=order&orderid=' . $oWithSuf . '&processor=' . $processor . '&Itemid=' . $orderItemid;

		$currenturl = JURI::root() . substr(JRoute::_($orderUrl, false), strlen(JURI::base(true)) + 1);

		$body = JText::_('COM_TJLMS_INVOICE_EMAIL_BODY');

		$status = $order['order_info'][0]->status;

		if ($status == 'I')
		{
			$body = JText::_('COM_TJLMS_ORDER_PLACED_EMAIL_BODY');
		}

		$invoicebody = TjMail::TagReplace($body, $this->order_mail);

		$invoicehtml = '<div class=""><div><span>' . $invoicebody . '</span></div>';

		// Check for view override
		$view = $comtjlmsHelper->getViewpath('com_tjlms', 'orders', 'default', 'SITE', 'SITE');
		ob_start();
		$usedinemail = 1;
		include $view;
		$invoicehtml .= ob_get_contents();
		ob_end_clean();

		$invoicehtml .= '<div class=""><div><span>' . JText::_("COM_TJLMS_INVOICE_LINK") . '</span></div>';
		$invoicehtml .= '<div><span><a href="' . $currenturl . '">' . JText::_("COM_TJLMS_CLICK_HERE") . '</a></span></div></div>';

		$subject = JText::sprintf('COM_TJLMS_INVOICE_EMAIL_SUBJECT');

		if ($status == 'I')
		{
			$subject = JText::sprintf('COM_TJLMS_ORDER_PLACED_EMAIL_SUBJECT');
		}

		$subject = TjMail::TagReplace($subject, $this->order_mail);

		// TRIGGER After Process Payment. Call the plugin and get the result
		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin('system');
		$result = $dispatcher->trigger('tjlms_OnBeforeInvoiceEmail', array(
																				$billemail,
																				$subject,
																				$invoicehtml
																			)
									);

		// SEND INVOICE EMAIL
		$comtjlmsHelper->sendmail($billemail, $subject, $invoicehtml, '', 0, '');
	}

	/**
	 * Function to add User to social groups
	 *
	 * @param   INT  $actor_id   Course ID
	 * @param   INT  $course_id  Course ID
	 *
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function addUserToGroup($actor_id, $course_id)
	{
		$com_params         = JComponentHelper::getParams('com_tjlms');
		$comtjlmsHelper     = new comtjlmsHelper;
		$social_integration = $com_params->get('social_integration', 'joomla', 'STRING');

		$groupId = $comtjlmsHelper->getGroupID($course_id);

		if (empty($groupId) || $groupId == 0)
		{
			return false;
		}
		else
		{
			$comtjlmsHelper->sociallibraryobj->addMemberToGroup($groupId, JFactory::getUser($actor_id));
		}

		return true;
	}

	/**
	 * Function to get group ID
	 *
	 * @param   INT  $course_id  Course ID
	 *
	 * @return  INT  Group ID
	 *
	 * @since  1.0.0
	 */
	public function getGroupID($course_id)
	{
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('group_id');
		$query->from('#__tjlms_courses');
		$query->where('id=' . $course_id);
		$db->setQuery($query);

		return $db->loadResult();
	}

	/**
	 * This function return array of js files which is loaded from tjassesloader plugin.
	 *
	 * @param   array  &$jsFilesArray                  Js file's array.
	 * @param   int    &$firstThingsScriptDeclaration  load script 1st
	 *
	 * @return   ARRAY  $jsFilesArray All JS files to be load
	 *
	 * @since  1.0.0
	 */
	public function getTjlmsJsFiles(&$jsFilesArray, &$firstThingsScriptDeclaration)
	{
		$app    = JFactory::getApplication();
		$input  = JFactory::getApplication()->input;
		$option = $input->get('option', '');
		$view   = $input->get('view', '');
		$layout = $input->get('layout', '');
		$client = $input->get('client', '');

		$config = JFactory::getConfig();
		$debug = $config->get('debug');

		$loadminifiedJs = '';

		if ($debug == 0)
		{
			$loadminifiedJs = '.min';
		}

		// Backend Js files
		if ($app->isAdmin())
		{
			if ($option == "com_tjlms")
			{
				$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlms.js';

				// Load the view specific js
				switch ($view)
				{
					// @TODO - get rid off two auto.js files
					case "modules":
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/ajax_file_upload.js';
							$jsFilesArray[] = 'media/techjoomla_strapper/js/akeebajqui.js';
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjmodules.js';
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/jquery.form.js';
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlmsvalidator.js';
						break;

					case 'course':
					case 'coupon':
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlmsvalidator.js';
						break;

						case "dashboard":
						case "teacher_report":
							$jsFilesArray[] = 'components/com_tjlms/assets/js/morris.min.js';
							$jsFilesArray[] = 'components/com_tjlms/assets/js/raphael.min.js';
							$jsFilesArray[] = 'media/system/js/validate.js';
							$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/tjlmsvalidator.js';
						break;
				}
			}

			if ($option == "com_categories" && ($client == "com_tjlms" || $client == "com_tmt.questions"))
			{
				$jsFilesArray[] = 'administrator/components/com_tjlms/assets/js/cat_helper.js';
			}
		}
		else
		{
			if ($option == "com_tjlms")
			{
				// Needed for all view
				$jsFilesArray[] = 'components/com_tjlms/assets/js/tjlms' . $loadminifiedJs . '.js';
				$jsFilesArray[] = 'components/com_tjlms/assets/js/sco' . $loadminifiedJs . '.js';

				// Load the view specific js
				switch ($view)
				{
					case "buy":
							$jsFilesArray[] = 'components/com_tjlms/assets/js/fuelux2.3loader.min.js';
							$jsFilesArray[] = 'components/com_tjlms/assets/js/steps' . $loadminifiedJs . '.js';
						break;

					case "course":
							// Check id native sharing is enable
							if ($this->tjlmsparams->get('social_sharing'))
							{
								if ($this->tjlmsparams->get('social_shring_type') == 'native')
								{
									$jsFilesArray[] = 'components/com_tjlms/assets/js/native_share' . $loadminifiedJs . '.js';
								}
							}

						break;
				}
			}
		}

		$reqURI = JUri::root();

		// If host have wwww, but Config doesn't.
		if (isset($_SERVER['HTTP_HOST']))
		{
			if ((substr_count($_SERVER['HTTP_HOST'], "www.") != 0) && (substr_count($reqURI, "www.") == 0))
			{
				$reqURI = str_replace("://", "://www.", $reqURI);
			}
			elseif ((substr_count($_SERVER['HTTP_HOST'], "www.") == 0) && (substr_count($reqURI, "www.") != 0))
			{
				// Host do not have 'www' but Config does
				$reqURI = str_replace("www.", "", $reqURI);
			}
		}

		// Defind first thing script declaration.
		$loadFirstDeclarations          = "var root_url = '" . $reqURI . "';";
		$firstThingsScriptDeclaration[] = $loadFirstDeclarations;

		return $jsFilesArray;
	}

	/**
	 * This function return array of css files which is loaded from tjassesloader plugin.
	 *
	 * @param   array  &$cssFilesArray  Css file's array.
	 *
	 * @return   ARRAY  $cssFilesArray All Css files to be load
	 *
	 * @since  1.0.0
	 */
	public function getTjlmsCssFiles(&$cssFilesArray)
	{
		$app    = JFactory::getApplication();
		$input  = JFactory::getApplication()->input;
		$option = $input->get('option', '');
		$view   = $input->get('view', '');
		$layout = $input->get('layout', '');
		$client = $input->get('client', '');

		$config = JFactory::getConfig();
		$debug = $config->get('debug');

		$loadminifiedCss = '';

		if ($debug == 0)
		{
			$loadminifiedCss = '.min';
		}

		// Backend Css files
		if ($app->isAdmin())
		{
			if ($option == "com_tjlms")
			{
				$cssFilesArray[] = 'media/com_tjlms/font-awesome/css/font-awesome.min.css';
				$cssFilesArray[] = 'media/com_tjlms/css/tjlms_backend.css';

				switch ($view)
				{
					case 'dashboard':
						// $cssFilesArray[] = 'media/com_tjlms/bootstrap3/css/bootstrap.min.css';
						$cssFilesArray[] = 'media/techjoomla_strapper/css/bootstrap.j3.css';
						$cssFilesArray[] = 'media/com_tjlms/css/tjdashboard-sb-admin.css';
					break;
				}
			}
		}
		else
		{
			if ($option == "com_tjlms")
			{
				$cssFilesArray[] = 'components/com_tjlms/assets/css/tjlms' . $loadminifiedCss . '.css';
				$cssFilesArray[] = 'media/com_tjlms/font-awesome/css/font-awesome.min.css';

				switch ($view)
				{
					case 'buy':
						$cssFilesArray[] = 'components/com_tjlms/assets/css/tjlms_steps' . $loadminifiedCss . '.css';
						$cssFilesArray[] = 'components/com_tjlms/assets/css/fuelux2.3.1' . $loadminifiedCss . '.css';
						break;
				}
			}
		}

		return $cssFilesArray;
	}

	/**
	 * Function used to get joomla users
	 *
	 * @return  object
	 *
	 * @since  1.0.0
	 */
	public function getJoomlaUser()
	{
		$db    = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('id,name,username');
		$query->from('#__users');
		$query->where('block=0');
		$db->setQuery($query);
		$rows = $db->loadObjectList('id');

		$comtjlmsHelper = new comtjlmsHelper;

		foreach ($rows as $row)
		{
			$row->avatar = $comtjlmsHelper->sociallibraryobj->getAvatar(JFactory::getUser($row->id), 50);
		}

		return $rows;
	}

	/**
	 * Converts date in UTC
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
		$user   = JFactory::getUser();
		$config = JFactory::getConfig();
		$offset = $user->getParam('timezone', $config->get('offset'));

		if (!empty($date) && $date != '0000-00-00 00:00:00')
		{
			$udate = JFactory::getDate($date, $offset);
			$date = $udate->toSQL();
		}

		return $date;
	}

	/**
	 * converts date into local time
	 *
	 * @param   date  $date  date of lesson
	 *
	 * @return   date in utc format
	 *
	 * @since   1.0
	 */
	public function getDateInLocal($date)
	{
		if (!empty($date) && $date != '0000-00-00 00:00:00')
		{
			// Create JDate object set to now in the users timezone.
			$date = JHtml::date($date, 'Y-m-d H:i:s', true);
		}

		return $date;
	}

	/**
	 * SOrt given array with the provided column and provided order
	 *
	 * @param   ARRAY   $array   array of data
	 * @param   STRING  $column  column name
	 * @param   STRING  $order   order in which array has to be sort
	 *
	 * @return  ARRAY
	 *
	 * @since   1.0
	 */
	public function multi_d_sort($array, $column, $order)
	{
		if (isset($array) && count($array))
		{
			foreach ($array as $key => $row)
			{
				$orderby[$key] = $row->$column;
			}

			if ($order == 'asc')
			{
				array_multisort($orderby, SORT_ASC, $array);
			}
			else
			{
				array_multisort($orderby, SORT_DESC, $array);
			}
		}

		return $array;
	}

	/**
	 * Wrapper to JRoute to handle itemid We need to try and capture the correct itemid for different view
	 *
	 * @param   string   $url    Absolute or Relative URI to Joomla resource.
	 * @param   boolean  $xhtml  Replace & by &amp; for XML compliance.
	 * @param   integer  $ssl    Secure state for the resolved URI.
	 *
	 * @return  url with itemif
	 *
	 * @since  1.0
	 */
	public function tjlmsRoute($url, $xhtml = true, $ssl = null)
	{
		static $tjlmsitemid = array();

		$mainframe = JFactory::getApplication();
		$jinput = $mainframe->input;

		if (empty($tjlmsitemid[$url]))
		{
			$tjlmsitemid[$url] = self::getitemid($url);
		}

		$pos = strpos($url, '#');

		if ($pos === false)
		{
			if (isset($tjlmsitemid[$url]))
			{
				if (strpos($url, 'Itemid=') === false && strpos($url, 'com_tjlms') !== false)
				{
					$url .= '&Itemid=' . $tjlmsitemid[$url];
				}
			}
		}
		else
		{
			if (isset($tjlmsitemid[$url]))
			{
				$url = str_ireplace('#', '&Itemid=' . $tjlmsitemid[$view] . '#', $url);
			}
		}

		$routedUrl = JRoute::_($url, $xhtml, $ssl);

		return $routedUrl;
	}

	/**
	 * Method to log the comment in provided file
	 *
	 * @param   String  $filename  filename
	 * @param   String  $filepath  filepath
	 * @param   Array   $params    params : Params includes userid, logEntryTitle, desc, component, logType
	 *
	 * @return  array of the replacements
	 *
	 * @since  1.0
	 */
	public function techjoomlaLog($filename, $filepath, $params = array())
	{
		$userid = $params['userid'];
		$desc = $params['desc'];

		$options = "{DATE}\t{TIME}\t{PRIORITY}\t{USER}\t{DESC}";
		jimport('joomla.log.log');
		JLog::addLogger(
				array(
					'text_file' => $filename,
					'text_entry_format' => $options,
					'text_file_path' => $filepath
				),
				JLog::ALL, $params['component']
			);

		$logEntry            = new JLogEntry(
									$params['logEntryTitle'], $params['logType'], $params['component']
								);
		$logEntry->desc      = json_encode($desc);
		$logEntry->user   = $userid;
		JLog::add($logEntry);
	}

	/**
	 * Function used to get users enrollment and account details
	 *
	 * @param   INT  $courseId       Course ID
	 * @param   INT  $enrolled_user  USER ID
	 *
	 * @return  INT  $getEnrollmentDetails  details
	 *
	 * @since  1.0.0
	 */
	public function getEnrollmentDetails($courseId, $enrolled_user)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('e.*, u.*');
		$query->from('#__tjlms_enrolled_users as e');
		$query->join('INNER', '#__users as u ON u.id = e.user_id');
		$query->where('e.course_id = ' . $courseId);
		$query->where('e.user_id = ' . $enrolled_user);
		$db->setQuery($query);
		$enrollmentDetails = $db->loadObject();

		return $enrollmentDetails;
	}

	/**
	 * Function used to get user details
	 *
	 * @param   INT  $user_id  USER DETAILS
	 *
	 * @return  OBJECT  $userDetails  USER DETAILS
	 *
	 * @since  1.0.0
	 */
	public function getUserDetails($user_id)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('u.*');
		$query->from('#__users as u');
		$query->where('u.id = ' . $user_id);
		$db->setQuery($query);
		$userDetails = $db->loadObject();

		return $userDetails;
	}

	/**
	 * Function used to get course creator and his account details.
	 *
	 * @param   INT  $courseId  Course ID
	 *
	 * @return  INT  $courseCreator  Creator of the course
	 *
	 * @since  1.0.0
	 */
	public function getCourseCreatorDetails($courseId)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('c.created_by, u.*');
		$query->from('#__tjlms_courses as c');
		$query->join('INNER', '#__users as u ON u.id =c.created_by');
		$query->where('c.id = ' . $courseId);
		$db->setQuery($query);
		$courseCreator = $db->loadObject();

		return $courseCreator;
	}

	/**
	 * Function used to get test data
	 *
	 * @param   INT  $lessonId  Lesson ID
	 *
	 * @return  INT  $testData  Test Data
	 *
	 * @since  1.0.0
	 */
	public function getTestData($lessonId)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		$query->select('t.test_id, test.total_marks');
		$query->from('#__tjlms_tmtquiz as t');
		$query->join('INNER', '#__tmt_tests as test ON test.id = t.test_id');
		$query->where('t.lesson_id = ' . $lessonId);
		$db->setQuery($query);
		$testData = $db->loadObject();

		return $testData;
	}

	/**
	 * Function used to revenue data
	 *
	 * @param   INT  $data  Data
	 *
	 * @return  INT  $revenueData  Revenue Data
	 *
	 * @since  1.0.0
	 */
	public function getrevenueData($data)
	{
		$user = JFactory::getUser();
		$olUserid = $user->id;
		$isroot = $user->authorise('core.admin');

		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select('SUM(o.amount) as amount, DATE(o.cdate) as date');
		$query->from('#__tjlms_orders as o');
		$query->join('LEFT', '#__tjlms_courses as c ON c.id = o.course_id');
		$query->where('o.status="C"');

		if (!$isroot)
		{
			$query->where('created_by=' . $olUserid);
		}

		if (isset($data['course_id']) && $data['course_id'] != '')
		{
			$query->where('o.course_id=' . $data['course_id']);
		}

		if (isset($data['start']) && $data['start'] != '' && isset($data['end']) && $data['end'] != '')
		{
			$query->where("( o.cdate BETWEEN " . $db->quote($data['start']) . " AND " . $db->quote($data['end']) . " )");
		}

		$query->group('DATE(o.cdate)');

		$db->setQuery($query);
		$revenueData = $db->loadObjectlist();

		return $revenueData;
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
		JText::script('COM_TJLMS_WANTED_TO_APPLY_COP_BUT_NOT_APPLIED');
	}

	/**
	 * Function to get order status
	 *
	 * @param   INT  $order_id  Order ID
	 *
	 * @return  Object of result
	 *
	 * @since   1.0.0
	 */
	public function getOrderStatus($order_id)
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Add Table Path
		JTable::addIncludePath(JPATH_ROOT . '/administrator/components/com_tjlms/tables');

		$orderTbl = JTable::getInstance('Orders', 'TjlmsTable', array('dbo', $db));
		$orderTbl->load(array('id' => $order_id));

		return $orderTbl->status;
	}
}
