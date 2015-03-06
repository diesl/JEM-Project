<?php
/**
 * @version 2.1.3
 * @package JEM
 * @subpackage JEM Teaser Module
 * @copyright (C) 2013-2015 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */
defined('_JEXEC') or die;

JModelLegacy::addIncludePath(JPATH_SITE.'/components/com_jem/models', 'JemModel');

/**
 * Module-Teaser
 */
abstract class modJEMteaserHelper
{
	/**
	 * Method to get the events
	 *
	 * @access public
	 * @return array
	 */
	public static function getList(&$params)
	{
		mb_internal_encoding('UTF-8');

		$db         = JFactory::getDBO();
		$user       = JFactory::getUser();
		$levels = $user->getAuthorisedViewLevels();

		# Retrieve Eventslist model for the data
		$model = JModelLegacy::getInstance('Eventslist', 'JemModel', array('ignore_request' => true));

		# Set params for the model
		# has to go before the getItems function
		$model->setState('params', $params);

		# filter published
		#  0: unpublished
		#  1: published
		#  2: archived
		# -2: trashed

		$type = $params->get('type');
		$offset_hours = $params->get('offset_hours', 0);

		# clean parameter data
		$catids = JemHelper::getValidIds($params->get('catid'));
		$venids = JemHelper::getValidIds($params->get('venid'));
		$eventids = JemHelper::getValidIds($params->get('eventid'));

		$opendates = empty($eventids) ? 0 : 1; // allow open dates if limited to specific events

		$model->setState('filter.opendates', $opendates);

		# all upcoming or unfinished events
		if (($type == 0) || ($type == 1)) {
			$offset_minutes = $offset_hours * 60;

			$model->setState('filter.published',1);
			$model->setState('filter.orderby',array('a.dates ASC','a.times ASC'));

			$cal_from = "(a.dates IS NULL OR (TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(a.dates,' ',IFNULL(a.times,'00:00:00'))) > $offset_minutes) ";
			$cal_from .= ($type == 1) ? " OR (TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(IFNULL(a.enddates,a.dates),' ',IFNULL(a.endtimes,'23:59:59'))) > $offset_minutes)) " : ") ";
		}

		# archived events only
		elseif ($type == 2) {
			$model->setState('filter.published',2);
			$model->setState('filter.orderby',array('a.dates DESC','a.times DESC'));
			$cal_from = "";
		}

		# currently running events only (today + offset is inbetween start and end date of event)
		elseif ($type == 3) {
			$offset_days = (int)round($offset_hours / 24);

			$model->setState('filter.published',1);
			$model->setState('filter.orderby',array('a.dates ASC','a.times ASC'));

			$cal_from = " ((DATEDIFF(a.dates, CURDATE()) <= $offset_days) AND (DATEDIFF(IFNULL(a.enddates,a.dates), CURDATE()) >= $offset_days))";
		}

		# featured
		elseif ($type == 4) {
			$offset_minutes = $offset_hours * 60;

			$model->setState('filter.featured',1);
			$model->setState('filter.orderby',array('a.dates ASC','a.times ASC'));

			$cal_from  = "((TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(a.dates,' ',IFNULL(a.times,'00:00:00'))) > $offset_minutes) ";
			$cal_from .= " OR (TIMESTAMPDIFF(MINUTE, NOW(), CONCAT(IFNULL(a.enddates,a.dates),' ',IFNULL(a.endtimes,'23:59:59'))) > $offset_minutes)) ";
		}

		$model->setState('filter.calendar_from',$cal_from);
		$model->setState('filter.groupby','a.id');

		# filter category's
		if ($catids) {
			$model->setState('filter.category_id',$catids);
			$model->setState('filter.category_id.include',true);
		}

		# filter venue's
		if ($venids) {
			$model->setState('filter.venue_id',$venids);
			$model->setState('filter.venue_id.include',true);
		}

		# filter event id's
		if ($eventids) {
			$model->setState('filter.event_id',$eventids);
			$model->setState('filter.event_id.include',true);
		}

		# count
		$count = $params->get('count', '2');
		$model->setState('list.limit',$count);

		if ($params->get('use_modal', 0)) {
			JHtml::_('behavior.modal', 'a.flyermodal');
		}

		# Retrieve the available Events
		$events = $model->getItems();


		# Loop through the result rows and prepare data
		$lists = array();
		$i     = 0;

		foreach ($events as $row)
		{
			# create thumbnails if needed and receive imagedata
			if ($row->datimage) {
				$dimage = JEMImage::flyercreator($row->datimage, 'event');
			} else {
				$dimage = null;
			}
			if ($row->locimage) {
				$limage = JEMImage::flyercreator($row->locimage, 'venue');
			} else {
				$limage = null;
			}

			# cut titel
			$length = mb_strlen($row->title);

			if ($length > $params->get('cuttitle', '25')) {
				$row->title = mb_substr($row->title, 0, $params->get('cuttitle', '18'));
				$row->title = $row->title.'...';
			}

			#################
			## DEFINE LIST ##
			#################

			$lists[$i] = new stdClass();

			# check view access
			if (in_array($row->access, $levels)) {
				# We know that user has the privilege to view the event
				$lists[$i]->link = JRoute::_(JEMHelperRoute::getEventRoute($row->slug));
				$lists[$i]->linkText = JText::_('MOD_JEM_TEASER_READMORE');
			} else {
				$lists[$i]->link = JRoute::_('index.php?option=com_users&view=login');
				$lists[$i]->linkText = JText::_('MOD_JEM_TEASER_READMORE_REGISTER');
			}

			$lists[$i]->title			= htmlspecialchars($row->title, ENT_COMPAT, 'UTF-8');
			$lists[$i]->venue			= htmlspecialchars($row->venue, ENT_COMPAT, 'UTF-8');
			$lists[$i]->catname			= implode(", ", JemOutput::getCategoryList($row->categories, $params->get('linkcategory', 1)));
			$lists[$i]->state			= htmlspecialchars($row->state, ENT_COMPAT, 'UTF-8');
			$lists[$i]->city			= htmlspecialchars( $row->city, ENT_COMPAT, 'UTF-8' );
			$lists[$i]->eventlink		= $params->get('linkevent', 1) ? JRoute::_(JEMHelperRoute::getEventRoute($row->slug)) : '';
			$lists[$i]->venuelink		= $params->get('linkvenue', 1) ? JRoute::_(JEMHelperRoute::getVenueRoute($row->venueslug)) : '';

			# time/date
			$lists[$i]->day 			= modJEMteaserHelper::_format_day($row, $params);
			$lists[$i]->dayname			= modJEMteaserHelper::_format_dayname($row);
			$lists[$i]->daynum 			= modJEMteaserHelper::_format_daynum($row);
			$lists[$i]->month 			= modJEMteaserHelper::_format_month($row);
			$lists[$i]->year 			= modJEMteaserHelper::_format_year($row);
			list($lists[$i]->date,
			     $dummy)				= ModJEMteaserHelper::_format_date_time($row, $params);
			$lists[$i]->time 			= $row->times ? modJEMteaserHelper::_format_time($row->dates, $row->times, $params) : '' ;

			if ($dimage == null) {
				$lists[$i]->eventimage		= JUri::base(true).'/media/system/images/blank.png';
				$lists[$i]->eventimageorig	= JUri::base(true).'/media/system/images/blank.png';
			} else {
				$lists[$i]->eventimage		= JUri::base(true).'/'.$dimage['thumb'];
				$lists[$i]->eventimageorig	= JUri::base(true).'/'.$dimage['original'];
			}

			if ($limage == null) {
				$lists[$i]->venueimage		= JUri::base(true).'/media/system/images/blank.png';
				$lists[$i]->venueimageorig	= JUri::base(true).'/media/system/images/blank.png';
			} else {
				$lists[$i]->venueimage		= JUri::base(true).'/'.$limage['thumb'];
				$lists[$i]->venueimageorig	= JUri::base(true).'/'.$limage['original'];
			}

			$length = $params->get('descriptionlength');
			$length2 = 1;
			$etc = '...';
			$etc2 = JText::_('MOD_JEM_TEASER_NO_DESCRIPTION');

			//strip html tags but leave <br /> tags
			$description = strip_tags($row->introtext, "<br>");

			//switch <br /> tags to space character
			if ($params->get('br') == 0) {
				$description = str_replace('<br />',' ',$description);
			}
			//
			if (strlen($description) > $length) {
				$length -= strlen($etc);
				$description = preg_replace('/\s+?(\S+)?$/', '', substr($description, 0, $length+1));
				$lists[$i]->eventdescription = substr($description, 0, $length).$etc;
			} else
			if (strlen($description) < $length2) {
				$length -= strlen($etc2);
				$description = preg_replace('/\s+?(\S+)?$/', '', substr($description, 0, $length+1));
				$lists[$i]->eventdescription = substr($description, 0, $length).$etc2;
			} else {
				$lists[$i]->eventdescription	= $description;
			}

			$lists[$i]->readmore = strlen(trim($row->fulltext));

			$i++;
		}

		return $lists;
	}

	/**
	 *format days
	 */
	protected static function _format_day($row, &$params)
	{
		//Get needed timestamps and format
		//setlocale (LC_TIME, 'de_DE.UTF8');
		$yesterday_stamp	= mktime(0, 0, 0, date("m") , date("d")-1, date("Y"));
		$yesterday 			= strftime("%Y-%m-%d", $yesterday_stamp);
		$today_stamp		= mktime(0, 0, 0, date("m") , date("d"), date("Y"));
		$today 				= date('Y-m-d');
		$tomorrow_stamp 	= mktime(0, 0, 0, date("m") , date("d")+1, date("Y"));
		$tomorrow 			= strftime("%Y-%m-%d", $tomorrow_stamp);

		$dates_stamp		= strtotime($row->dates);
		$enddates_stamp		= $row->enddates ? strtotime($row->enddates) : null;

		//check if today or tomorrow or yesterday and no current running multiday event
		if ($row->dates == $today && empty($enddates_stamp)) {
			$result = JText::_('MOD_JEM_TEASER_TODAY');
		} elseif ($row->dates == $tomorrow) {
			$result = JText::_('MOD_JEM_TEASER_TOMORROW');
		} elseif ($row->dates == $yesterday) {
			$result = JText::_('MOD_JEM_TEASER_YESTERDAY');
		} else {
			//if daymethod show day
			if ($params->get('daymethod', 1) == 1) {

				//single day event
				$date = strftime('%A', strtotime( $row->dates ));
				$result = JText::sprintf('MOD_JEM_TEASER_ON_DATE', $date);

				//Upcoming multidayevent (From 16.10.2010 Until 18.10.2010)
				if ($dates_stamp > $tomorrow_stamp && $enddates_stamp) {
					$startdate = strftime('%A', strtotime( $row->dates ));
					$result = JText::sprintf('MOD_JEM_TEASER_FROM', $startdate);
				}

				//current multidayevent (Until 18.08.2008)
				if ($row->enddates && $enddates_stamp > $today_stamp && $dates_stamp <= $today_stamp ) {
					//format date
					$result = strftime('%A', strtotime( $row->enddates ));
					$result = JText::sprintf('MOD_JEM_TEASER_UNTIL', $result);
				}
			} else { // show day difference
				//the event has an enddate and it's earlier than yesterday
				if ($row->enddates && $enddates_stamp < $yesterday_stamp) {
					$days = round( ($today_stamp - $enddates_stamp) / 86400 );
					$result = JText::sprintf('MOD_JEM_TEASER_ENDED_DAYS_AGO', $days);

				//the event has an enddate and it's later than today but the startdate is today or earlier than today
				//means a currently running event with startdate = today
				} elseif ($row->enddates && $enddates_stamp > $today_stamp && $dates_stamp <= $today_stamp) {
					$days = round( ($enddates_stamp - $today_stamp) / 86400 );
					$result = JText::sprintf('MOD_JEM_TEASER_DAYS_LEFT', $days);

				//the events date is earlier than yesterday
				} elseif ($dates_stamp < $yesterday_stamp) {
					$days = round( ($today_stamp - $dates_stamp) / 86400 );
					$result = JText::sprintf('MOD_JEM_TEASER_DAYS_AGO', $days );

				//the events date is later than tomorrow
				} elseif ($dates_stamp > $tomorrow_stamp) {
					$days = round( ($dates_stamp - $today_stamp) / 86400 );
					$result = JText::sprintf('MOD_JEM_TEASER_DAYS_AHEAD', $days);
				}
			}
		}
		return $result;
	}

	/**
	 * Method to format date and time information
	 *
	 * @access protected
	 * @return array(string, string) returns date and time strings as array
	 */
	protected static function _format_date_time($row, &$params)
	{
		//Get needed timestamps and format
		$yesterday_stamp	= mktime(0, 0, 0, date("m") , date("d")-1, date("Y"));
		$yesterday 			= strftime("%Y-%m-%d", $yesterday_stamp);
		$today_stamp		= mktime(0, 0, 0, date("m") , date("d"), date("Y"));
		$today 				= date('Y-m-d');
		$tomorrow_stamp 	= mktime(0, 0, 0, date("m") , date("d")+1, date("Y"));
		$tomorrow 			= strftime("%Y-%m-%d", $tomorrow_stamp);

		$dates_stamp		= $row->dates ? strtotime($row->dates) : null;
		$enddates_stamp		= $row->enddates ? strtotime($row->enddates) : null;

		$date_format = $params->get('formatdate', 'D, j. F Y');
		$time_format = $params->get('formattime', '%H:%M');

		//if datemethod show day difference
		if ($params->get('datemethod', 1) == 2) {
			//check if today or tomorrow
			if ($row->dates == $today) {
				$date = JText::_('MOD_JEM_TEASER_TODAY');
				$time = $row->times ? JEMOutput::formattime($row->times, $time_format, false) : '';
			} elseif ($row->dates == $tomorrow) {
				$date = JText::_('MOD_JEM_TEASER_TOMORROW');
				$time = $row->times ? JEMOutput::formattime($row->times, $time_format, false) : '';
			} elseif ($row->dates == $yesterday) {
				$date = JText::_('MOD_JEM_TEASER_YESTERDAY');
				$time = $row->times ? JEMOutput::formattime($row->times, $time_format, false) : '';
			}
			//This one isn't very different from the DAYS AGO output but it seems
			//adequate to use a different language string here.
			//
			//the event has an enddate and it's earlier than yesterday
			elseif ($row->enddates && $enddates_stamp < $yesterday_stamp) {
				$days = round(($today_stamp - $enddates_stamp) / 86400);
				$date = JText::sprintf('MOD_JEM_TEASER_ENDED_DAYS_AGO', $days);
				$time = $row->endtimes ? JEMOutput::formattime($row->endtimes, $time_format, false) : '';
			}
			//the event has an enddate and it's later than today but the startdate is earlier than today
			//means a currently running event
			elseif ($row->dates && $row->enddates && $enddates_stamp > $today_stamp && $dates_stamp < $today_stamp) {
				$days = round(($today_stamp - $dates_stamp) / 86400);
				$date = JText::sprintf('MOD_JEM_TEASER_STARTED_DAYS_AGO', $days);
				$time = $row->times ? JEMOutput::formattime($row->times, $time_format, false) : '';
			}
			//the events date is earlier than yesterday
			elseif ($row->dates && $dates_stamp < $yesterday_stamp) {
				$days = round(($today_stamp - $dates_stamp) / 86400);
				$date = JText::sprintf('MOD_JEM_TEASER_DAYS_AGO', $days);
				$time = $row->times ? JEMOutput::formattime($row->times, $time_format, false) : '';
			}
			//the events date is later than tomorrow
			elseif ($row->dates && $dates_stamp > $tomorrow_stamp) {
				$days = round(($dates_stamp - $today_stamp) / 86400);
				$date = JText::sprintf('MOD_JEM_TEASER_DAYS_AHEAD', $days);
				$time = $row->times ? JEMOutput::formattime($row->times, $time_format, false) : '';
			}
			elseif (empty($row->dates)) {
				$date = JEMOutput::formatDateTime('', ''); // "Open date"
				$time  = $row->times ? JEMOutput::formattime($row->times, $time_format, false) : '';
			}
		} else { // datemethod show date - not shown beause there is a calendar image
			//Upcoming multidayevent (From 16.10.2008 Until 18.08.2008)
			if ($dates_stamp > $today_stamp && $enddates_stamp > $dates_stamp) {
				$startdate = JEMOutput::formatdate($row->dates, $date_format);
				$enddate = JEMOutput::formatdate($row->enddates, $date_format);
				$date = JText::sprintf('MOD_JEM_TEASER_FROM_UNTIL', $startdate, $enddate);
				$time  = $row->times ? JEMOutput::formattime($row->times, $time_format, false) : '';
				//// endtime always starts with separator, also if there is no starttime
				//$time .= $row->endtimes ? (' - ' . JEMOutput::formattime($row->endtimes, $time_format, false)) : '';
			}
			//current multidayevent (Until 18.08.2008)
			elseif ($row->enddates && $enddates_stamp > $today_stamp && $dates_stamp < $today_stamp) {
				//format date
				$enddate = JEMOutput::formatdate($row->enddates, $date_format);
				$date = JText::sprintf('MOD_JEM_TEASER_UNTIL', $enddate);
				$time = $row->endtimes ? JEMOutput::formattime($row->endtimes, $time_format, false) : '';
			}
			//single day event
			else {
				$startdate = JEMOutput::formatdate($row->dates, $date_format);
				$date = JText::sprintf('MOD_JEM_TEASER_ON_DATE', $startdate);
				$time = $row->times ? JEMOutput::formattime($row->times, $time_format, false) : '';
			}
		}

		return array($date, $time);
	}

	/**
	 * Method to format time information
	 *
	 * @access public
	 * @return string
	 */
	protected static function _format_time($date, $time, &$params)
	{
		$time = strftime($params->get('formattime', '%H:%M'), strtotime($date.' '.$time));
		return $time;
	}

	protected static function _format_dayname($row)
	{
		if (empty($row->dates)) {
			return '';
		}

		$jdate	 = new JDate($row->dates);
		$dayname = $jdate->format('l',false,true);
		return $dayname;
	}

	protected static function _format_daynum($row)
	{
		if (empty($row->dates)) {
			return '';
		}

		$jdate	= new JDate($row->dates);
		$day	= $jdate->format('d',false,true);
		return $day;
	}

	protected static function _format_year($row)
	{
		if (empty($row->dates)) {
			return '';
		}

		$jdate	= new JDate($row->dates);
		$year	= $jdate->format('Y',false,true);
		return $year;
	}

	protected static function _format_month($row)
	{
		if (empty($row->dates)) {
			return '';
		}

		$jdate	= new JDate($row->dates);
		$month	= $jdate->format('F',false,true);
		return $month;
	}
}