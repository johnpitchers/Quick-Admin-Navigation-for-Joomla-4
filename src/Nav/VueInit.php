<?php
	
	namespace Joomla\Module\Quickadminnav\Administrator\Nav;
	
	\defined('_JEXEC') or die;
	
	use Joomla\CMS\Factory as JFactory;
	use Joomla\CMS\Application\CMSApplicationInterface;
	
	class VueInit {
		
		protected int $port = 8080;
		
		protected CMSApplicationInterface $app;
		
		public function __construct() {
			$this->app = JFactory::getApplication();
		}
		
		/**
		 * Determines if this is a development server. Hot module reloading will be available.
		 * Add allowedHosts to mod_quickadminnav/app/vue.config.js config.
		 * Add port number to $this->port.
		 * @return bool
		 */
		private function isDevServer(): bool {
			if (@fsockopen('localhost', $this->port)) {
				header("Access-Control-Allow-Origin: *");
				
				return true;
			}
			
			return false;
		}
		
		/**
		 * Add required JS to output.
		 */
		public function addScripts(): object {
			$wa = $this->app->getDocument()->getWebAssetManager();
			
			$scriptURL = $this->isDevServer() ? "http://localhost:{$this->port}/js/chunk-vendors.js" : "administrator/modules/mod_quickadminnav/app/dist/js/chunk-vendors.js";
			$wa->registerAndUseScript('mod_quickadminnav.chunk-vendors', $scriptURL, [], ['defer' => true]);
			
			$scriptURL = $this->isDevServer() ? "http://localhost:{$this->port}/js/app.js" : "administrator/modules/mod_quickadminnav/app/dist/js/app.js";
			$wa->registerAndUseScript('mod_quickadminnav.app', $scriptURL, [], ['defer' => true]);
			
			return $this;
		}
		
		public function addStyles(): object {
			$wa = $this->app->getDocument()->getWebAssetManager();
			if (!$this->isDevServer()) {
				$wa->registerAndUseStyle('mod_quickadminnav.css', "administrator/modules/mod_quickadminnav/app/dist/css/app.css");
			}
			
			return $this;
		}
		
	}
