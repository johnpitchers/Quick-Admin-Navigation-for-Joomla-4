<?php
	/**
	 * @package         Joomla.Administrator
	 * @subpackage      mod_menu
	 * @copyright   (C) 2006 Open Source Matters, Inc. <https://www.joomla.org>
	 * @license         GNU General Public License version 2 or later; see LICENSE.txt
	 */
	
	defined('_JEXEC') or die;
	
	use Joomla\CMS\Helper\ModuleHelper;
	use Joomla\Module\Quickadminnav\Administrator\Nav\QuickAdminNav;
	use Joomla\Module\Quickadminnav\Administrator\Nav\VueInit;
	
	if($app->input->getBool('hidemainmenu')) return;
	
	$lang = $app->getLanguage();
	$lang->load("mod_menu");
	
	//$menu = new QuickAdminNav('system','System'); // Can be used to generate a single menu ie: system.
	$menu = new QuickAdminNav($params); // all menus.
	$menuItems = $menu->getMenusArray();
	$vueApp = new VueInit();
	$vueApp->addScripts()->addStyles();
	
	
	// Render the module layout
	require ModuleHelper::getLayoutPath('mod_quickadminnav', $params->get('layout', 'default'));
