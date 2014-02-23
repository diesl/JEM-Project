<?php
/**
 * @version 1.9.6
 * @package JEM
 * @copyright (C) 2013-2014 joomlaeventmanager.net
 * @copyright (C) 2005-2009 Christoph Lukes
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

jimport('joomla.application.component.view');
require JPATH_COMPONENT_SITE.'/classes/view.class.php';

/**
 * HTML View class for the Day View
 *
 * @package JEM
 *
 */
class JEMViewDay extends JEMView
{
	/**
	 * Creates the Day View
	 *
	 *
	 */
	function display($tpl = null)
	{
		// Initialize variables
		$app 			= JFactory::getApplication();
		$jemsettings 	= JEMHelper::config();
		$settings 		= JEMHelper::globalattribs();
		$menu 			= $app->getMenu();
		$menuitem 		= $menu->getActive();
		$user			= JFactory::getUser();
		$params 		= $app->getParams();
		$db 			= JFactory::getDBO();
		$uri 			= JFactory::getURI();
		$task 			= JRequest::getWord('task');
		$pathway 		= $app->getPathWay();
		$jinput 		= $app->input;

		// Decide which parameters should take priority
		$useMenuItemParams = ($menuitem && $menuitem->query['option'] == 'com_jem'
		                                && $menuitem->query['view'] == 'day'
		                                && !isset($menuitem->query['id']));

		// Retrieving data
		$requestVenueId = $jinput->get('locid', null, 'int');
		$requestCategoryId = $jinput->get('catid', null, 'int');
		$requestDate = $jinput->get('id', null, 'int');

		// Load css
		JHtml::_('stylesheet', 'com_jem/jem.css', array(), true);
		$this->document->addCustomTag('<!--[if IE]><style type="text/css">.floattext{zoom:1;}, * html #jem dd { height: 1%; }</style><![endif]-->');

		// get variables
		$filter_order		= $app->getUserStateFromRequest('com_jem.day.filter_order', 'filter_order', 	'a.dates', 'cmd');
		$filter_order_Dir	= $app->getUserStateFromRequest('com_jem.day.filter_order_Dir', 'filter_order_Dir',	'', 'word');
		$filter 			= $app->getUserStateFromRequest('com_jem.day.filter', 'filter', '', 'int');
		$search 			= $app->getUserStateFromRequest('com_jem.day.filter_search', 'filter_search', '', 'string');
		$search 			= $db->escape(trim(JString::strtolower($search)));

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		// Get data from model
		$rows 		= $this->get('Data');
		$day		= $this->get('Day');

		$daydate 	= JEMOutput::formatdate($day);
		$showdaydate = true; // show by default

		// Show page heading specified on menu item or TODAY as heading - idea taken from com_content.
		if ($useMenuItemParams) {
			$pagetitle   = $params->get('page_title', $menuitem->title);
			$params->def('page_heading', $pagetitle);
			$pathway->setItemName(1, $menuitem->title);
		} else {
			// TODO: If we can integrate $daydate into page_heading we should set $showdaydate to false.
			$pagetitle   = JText::_('COM_JEM_DEFAULT_PAGE_TITLE_DAY');
			$params->set('page_heading', $pagetitle);
			$pathway->addItem($pagetitle);
		}
		$pageclass_sfx = $params->get('pageclass_sfx');

		// Add site name to title if param is set
		if ($app->getCfg('sitename_pagetitles', 0) == 1) {
			$pagetitle = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $pagetitle);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2) {
			$pagetitle = JText::sprintf('JPAGETITLE', $pagetitle, $app->getCfg('sitename'));
		}

		$this->document->setTitle($pagetitle);

		// Are events available?
		if (!$rows) {
			$noevents = 1;
		} else {
			$noevents = 0;
		}

		if ($requestVenueId){
			$print_link = JRoute::_('index.php?view=day&tmpl=component&print=1&locid='.$requestVenueId.'&id='.$requestDate);
		}
		elseif ($requestCategoryId){
			$print_link = JRoute::_('index.php?view=day&tmpl=component&print=1&catid='.$requestCategoryId.'&id='.$requestDate);
		}
		else /*(!$requestCategoryId && !$requestVenueId)*/ {
			$print_link = JRoute::_('index.php?view=day&tmpl=component&print=1&id='.$requestDate);
		}

		//Check if the user has access to the form
		$maintainer = JEMUser::ismaintainer('add');
		$genaccess 	= JEMUser::validate_user($jemsettings->evdelrec, $jemsettings->delivereventsyes);

		if ($maintainer || $genaccess || $user->authorise('core.create','com_jem')) {
			$dellink = 1;
		} else {
			$dellink = 0;
		}

		//add alternate feed link (w/o specific date)
		$link    = 'index.php?option=com_jem&view=day&format=feed';
		$attribs = array('type' => 'application/rss+xml', 'title' => 'RSS 2.0');
		$this->document->addHeadLink(JRoute::_($link.'&type=rss'), 'alternate', 'rel', $attribs);
		$attribs = array('type' => 'application/atom+xml', 'title' => 'Atom 1.0');
		$this->document->addHeadLink(JRoute::_($link.'&type=atom'), 'alternate', 'rel', $attribs);

		//search filter
		$filters = array();

		if ($jemsettings->showtitle == 1) {
			$filters[] = JHtml::_('select.option', '1', JText::_('COM_JEM_TITLE'));
		}
		if ($jemsettings->showlocate == 1 && !($requestVenueId)) {
			$filters[] = JHtml::_('select.option', '2', JText::_('COM_JEM_VENUE'));
		}
		if ($jemsettings->showcity == 1 && !($requestVenueId)) {
			$filters[] = JHtml::_('select.option', '3', JText::_('COM_JEM_CITY'));
		}
		if ($jemsettings->showcat == 1 && !($requestCategoryId)) {
			$filters[] = JHtml::_('select.option', '4', JText::_('COM_JEM_CATEGORY'));
		}
		if ($jemsettings->showstate == 1 && !($requestVenueId)) {
			$filters[] = JHtml::_('select.option', '5', JText::_('COM_JEM_STATE'));
		}
		$lists['filter'] = JHtml::_('select.genericlist', $filters, 'filter', 'size="1" class="inputbox"', 'value', 'text', $filter);

		// search filter
		$lists['search']= $search;

		// Create the pagination object
		$pagination = $this->get('Pagination');

		$this->lists			= $lists;
		$this->rows				= $rows;
		$this->noevents			= $noevents;
		$this->print_link		= $print_link;
		$this->params			= $params;
		$this->dellink			= $dellink;
		$this->pagination		= $pagination;
		$this->action			= $uri->toString();
		$this->task				= $task;
		$this->jemsettings		= $jemsettings;
		$this->settings			= $settings;
		$this->lists			= $lists;
		$this->daydate			= $daydate;
		$this->showdaydate		= $showdaydate; // if true daydate will be shown as h2 sub heading
		$this->pageclass_sfx	= htmlspecialchars($pageclass_sfx);

		// Doesn't really help - each view has less or more specific needs.
		//$this->prepareDocument();

		parent::display($tpl);
	}
}
?>
