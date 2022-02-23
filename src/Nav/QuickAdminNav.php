<?php
	
	namespace Joomla\Module\Quickadminnav\Administrator\Nav;
	
	\defined('_JEXEC') or die;
	
	use Joomla\CMS\Application\CMSApplicationInterface;
	use Joomla\CMS\Component\ComponentHelper;
	use Joomla\CMS\Factory;
	use Joomla\CMS\Language\Language;
	use Joomla\CMS\Uri\Uri;
	use Joomla\CMS\User\User;
	use Joomla\Component\Menus\Administrator\Helper\MenusHelper;
	use Joomla\Registry\Registry;
	use JText;
	use JFilterOutput;
	
	class QuickAdminNav {
		
		protected CMSApplicationInterface $app;
		protected User $user;
		protected Language $lang;
		protected Registry $params;
		protected array $items;
		protected string $key;
		
		public function __construct($params, $type = 'default', $key = '') {
			
			$this->app    = Factory::getApplication();
			$this->params = $params;
			$this->user   = $this->app->getIdentity();
			$this->key    = $key;
			$this->lang   = $this->app->getLanguage();
			$this->lang->load("mod_menu");
			
			$this->items = MenusHelper::loadPreset($type)->getChildren();
		}
		
		/**
		 * Returns an array of menu items which ultimately gets output as a JSON object in the view.
		 * @return array
		 */
		public function getMenusArray(): array {
			return $this->buildAdminMenu();
		}
		
		/**
		 * Recursive function to build searchable items for the Quick Admin Nav.
		 * @todo simplify this function. It works but it's ugly.
		 *
		 * @param string $key
		 * @param array  $menuItems
		 *
		 * @return array
		 */
		public function buildAdminMenu(string $key = "", array $menuItems = []): array {
			$menuLevel = [];
			if ($key === "") {
				$key = $this->key;
			}
			if (!count($menuItems)) {
				$menuItems = $this->items;
			}
			foreach ($menuItems as $item) {
				
				if (!$this->userHasAccess($item)) continue;
				
				//Load the relevant language files
				$this->loadLanguage($item);
				
				// Include the icon
				$icon = "";
				if ($item->class != "") {
					$parts = explode(":", $item->class);
					if (count($parts) > 1) {
						$class = $parts[1];
						$icon  = $class . "||";
					}
				}
				
				$item->title   = trim(strip_tags(JText::_($item->title)));
				$childKey      = $key != "" ? $key . "->" . $item->title : $icon . $item->title;
				$childKeyParts = explode('||', $childKey);
				if (count($childKeyParts) > 1) {
					$display  = $childKeyParts[1];
					$iconCode = $childKeyParts[0];
				} else {
					$display  = $childKey;
					$iconCode = '';
				}
				
				$menuItem = [
					'display'   => $display,
					//'type'  => $item->type,
					//'title' => $key,
					'link'      => $item->link,
					'icon'      => $iconCode,
					'searchKey' => str_replace('-', '', JFilterOutput::stringURLSafe($display)) . ' ' . strtolower(str_replace('->', ' ', $display)) . " ~^all^~",
					'json'      => $item
				];
				
				// Exclude placeholders.
				if ($menuItem['link'] !== '' && $menuItem['link'] !== "#") {
					$menuLevel[] = $menuItem;
				}
				
				// Recursively include containers.
				if ($item->type == 'container') {
					$components = MenusHelper::getMenuItems('main', false)->getChildren();
					//$menuItem[] = $this->getJson($childKey, $components);
					$menuLevel = array_merge($menuLevel, $this->buildAdminMenu($childKey, $components));
				}
				
				// Recursively include children
				$children = $item->getChildren();
				if (count($children)) {
					$menuLevel = array_merge($menuLevel, $this->buildAdminMenu($childKey, $children));
				}
				
				// Recursively include dashboard containers.
				if (str_contains($item->link, 'cpanel&dashboard=')) {
					$parts     = explode("=", $item->link);
					$dashboard = end($parts);
					if ($dashboard !== "") {
						$subMenu   = MenusHelper::loadPreset($dashboard)->getChildren();
						$menuLevel = array_merge($menuLevel, $this->buildAdminMenu($childKey, $subMenu));
					}
				}
			}
			
			return ($menuLevel);
		}
		
		/**
		 * Recieves a menu item object and loads the relavant language files.
		 *
		 * @param $item
		 *
		 * @return void
		 */
		private function loadLanguage($item) {
			$extension = $item->element;
			if (str_contains($extension, 'com_') || str_contains($extension, 'mod_') || str_contains($extension, 'plg_')) {
				$this->lang->load($extension . '.sys', JPATH_ADMINISTRATOR) ||
				$this->lang->load($extension . '.sys', JPATH_ADMINISTRATOR . '/components/' . $item->element);
			}
		}
		
		/**
		 * Much of the following code taken from Joomla\Module\Menu\Administrator\Menu\CssMenu::preprocess
		 * Thanks Joomla Team!!
		 *
		 * @param $item
		 *
		 * @return bool
		 */
		private function userHasAccess($item): bool {
			
			$uri   = new Uri($item->link);
			$query = $uri->getQuery(true);
			
			if ($option = $uri->getVar('option')) {
				$item->element = $option;
			}
			
			// Exclude item if is not enabled
			// @todo test this.
			if ($item->element && !ComponentHelper::isEnabled($item->element)) {
				return false;
			}
			
			// Exclude Mass Mail if disabled in global config
			if ($item->scope === 'massmail' && ($this->app->get('mailonline', 1) == 0 || $this->app->get('massmailoff', 0) == 1)) {
				return false;
			}
			
			// Exclude if the user is not authorised
			$assetName = $item->element;
			if ($item->element === 'com_categories') {
				$assetName = $query['extension'] ?? 'com_content';
			} elseif ($item->element === 'com_fields') {
				// Only display Fields menus when enabled in the component
				$createFields = null;
				
				if (isset($query['context'])) {
					$createFields = ComponentHelper::getParams(strstr($query['context'], '.', true))->get('custom_fields_enable', 1);
				}
				
				if (!$createFields) {
					return false;
				}
				
				list($assetName) = isset($query['context']) ? explode('.', $query['context'], 2) : array('com_fields');
			}
			
			// Show help?
			if ($item->link === 'index.php?option=com_cpanel&view=help' || $item->link === 'index.php?option=com_cpanel&view=cpanel&dashboard=help') {
				if ($this->params->get('showhelp', 1)) {
					return true;
				}
				
				return false;
			}
			
			// Only display Workflow menus when enabled in the component
			if ($item->element === 'com_workflow') {
				$workflow = null;
				
				if (isset($query['extension'])) {
					$parts    = explode('.', $query['extension']);
					$workflow = ComponentHelper::getParams($parts[0])->get('workflow_enabled') && $this->user->authorise('core.manage.workflow', $parts[0]);
				}
				if (!$workflow) {
					return false;
				}
				
				list($assetName) = isset($query['extension']) ? explode('.', $query['extension'], 2) : array('com_workflow');
			}
			
			// Components only Super Admins access
			if (in_array($item->element, array('com_config', 'com_privacy', 'com_actionlogs', 'com_joomlaupdate'), true) && !$this->user->authorise('core.admin')) {
				return false;
			}
			
			// Installers for Super admins only.
			if (($item->link === 'index.php?option=com_installer&view=install' || $item->link === 'index.php?option=com_installer&view=languages')
				&& !$this->user->authorise('core.admin')) {
				return false;
			}
			
			// System Info only available to Super Admins.
			if ($item->element === 'com_admin') {
				if (isset($query['view']) && $query['view'] === 'sysinfo' && !$this->user->authorise('core.admin')) {
					return false;
				}
			}
			
			// Check item scope
			if ($assetName && !$this->user->authorise(($item->scope === 'edit') ? 'core.create' : 'core.manage', $assetName)) {
				return false;
			}
			
			// Exclude if link is invalid
			if (is_null($item->link) || !\in_array($item->type, array('separator', 'heading', 'container')) && trim($item->link) === '') {
				return false;
			}
			
			return true;
		}
		
		
		/**
		 * Alternate output. Not currently implemented.
		 *
		 * @param array $menuItems
		 *
		 * @return mixed
		 */
		public function alternateDisplay(array $menuItems): array {
			foreach ($menuItems as &$item) {
				$parts           = explode("->", $item['display']);
				$endPoint        = array_pop($parts);
				$item['display'] = "<strong>$endPoint</strong> | <small>" . implode("->", $parts) . "</small>";
			}
			
			return $menuItems;
		}
	}
