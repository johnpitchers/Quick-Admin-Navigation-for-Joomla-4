# Quick Admin Navigation for Joomla 4

Quick Admin Navigation is a navigation and productivity module for use within the Joomla Administrator.

Launch the extension with a keyboard shortcut and navigate to any part of the Joomla administrator.

Instead of navigating through accordion menus and two screens, you can bring up the search box, type a few characters and hit enter. Any endpoint available in the main Joomla menu and the System Dashboard can be navigated to directly with this module.

## Installation instructions

1. Download **mod_quickadminnav.xxx.zip**  from Github. The latest version will be in this repository's root folder.
2. Install via the Joomla Extensions Manager. **System > Install > Extensions**
3. Go to Administrator Modules. **System > Manage > Administrator** Modules
4. Create new module. Select Quick Admin Navigation.
5. Give it a name. We suggest "Quick Admin Navigation".
6. Publish the module in the "menu" position.

## How to use

The module will be invisible until it is activated with the double shift [Shift / Shift] keyboard shortcut. It will display as a modal window.

* Press double shift [Shift / Shift] in quick succession to bring up the search box.
* Start typing the name of your desired location within the navigation. Usually you only need to enter the first few characters.
* Press enter to instantly navigate to the selected location.
* Use keyboard up and down arrows to change the selection between multiple results.
* Press ESC or click outside the window to close it.

## For developers

Quick Admin Navigation is open source and free for anyone to download, modify, extend or do what ever you like.

The PHP code follows Joomla coding and namespace guidelines.

The actual application is built using Vue3 and VueCLI. Hot Module Reloading is available if configured correctly (see below).

To edit Vue source files.
```
cd [site_route]/administrator/modules/mod_quickadminnav/app
```
Install the dependencies.
```
npm install
```
Compile and serve
```
npm run serve
```
To compile and minify for production
```
npm run build
```
### Hot Module Reloading

For hot module reloading to work you may need to make a couple of changes.

1. Make a note of the server port number in the terminal after the serve command. Set the port number in
   `src/Nav/VueInit.php`
2. If your development server is served as anything other than "localhost" add the domain to the allowedHosts array in `app/src/vue.config.js` and change `localhost` to your domain in `src/Nav/VueInit.php`
3. In terminal quit the current serve process and `npm run serve` again.

The Joomla Administrator should now refresh when Vue application files are saved.
