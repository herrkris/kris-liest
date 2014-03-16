<?php

require_once 'Admin/Htaccess.php';

class FlexiCache_Wp_Admin {

	/**
	* Admin notification messages which should be passed to the user on the admin page
	*/
	private static $_aUserMessage = array();

	/**
	* Add the admin page to the WordPress back end
	*/
	public static function addOptionsPage ()
	{
		wp_enqueue_style('flexicache-options', WP_PLUGIN_URL . '/' . FlexiCache_Wp::FLEXICACHE_PLUGIN_DIR . '/FlexiCache/Wp/includes/css/admin.css');
		add_options_page('FlexiCache', 'FlexiCache', 'manage_options', 'flexicache', array(__CLASS__,'renderOptionsPage'));
		add_filter('plugin_action_links', array(__CLASS__,'renderPluginActions'), 10, 2 );
	}

	public static function getAdminUrl ()
	{
		return admin_url('options-general.php?page=' . FlexiCache_Wp::FLEXICACHE_PLUGIN_DIR);
	}

	/**
	* Add a config link to next to the plugin on the plugins admin page
	*/
	public static function renderPluginActions ($aLinks, $sFile)
	{

		if (false !== strpos($sFile, FlexiCache_Wp::FLEXICACHE_PLUGIN_DIR)) {
			array_push($aLinks, sprintf('<a href="%s">Configuration &amp; Status</a>', self::getAdminUrl()));
		}

		return $aLinks;

	}

	/**
	* Add a line to the "Right Now" box on the Dashboard to indicate number of
	* cached responses.
	*/
	public static function renderDashboardRightNowInfo ()
	{

		/**
		* Don't get stats if there's no indexing as it will slow down the Dashboard load
		*/
		if (false == FlexiCache_Store::factory(FlexiCache_Config::get('Main','DefaultStore'))->hasAvailableIndex()) {
			return;
		}

		$aStat = FlexiCache_Store::factory(FlexiCache_Config::get('Main','DefaultStore'))->getActivityArray();

		if (false == $aStat || false == is_array($aStat)) {
			return;
		}

		if (false == isset($aStat['Total cached responses'])) {
			return;
		}

		printf('<tr><td class="first b"><a href="%s">%s</a></td><td class="t tags">Responses cached by <a href="%1$s">FlexiCache</a></td></tr>',
			self::getAdminUrl(),
			number_format($aStat['Total cached responses'])
		);

	}

	/**
	* Process any config updates and render the admin page
	*/
	public static function renderOptionsPage ()
	{

		if (false == empty($_POST)) {
			self::_handleUpdates();
		}

		if (false == FlexiCache_Config::get('Main','Enabled')) {
			self::addUserMessage('Caching is currently disabled.  You can enabled it in the "Main Options" section.');
		}

		include 'admin_page.php';

	}

	public static function getDocLink ($sAnchor=null)
	{

		$sUrl = sprintf('%s&amp;section=documentation',
			self::getAdminUrl()
		);

		if (null != $sAnchor) {
			$sUrl .= sprintf('#flexicache-doc-%s',
				$sAnchor
			);
		}

		return $sUrl;

	}

	private static function _handleUpdates ()
	{

		/**
		* Apply stripslashes before any update
		*/
		foreach ($_POST as $sKey=>$sVal) {
			$_POST[$sKey] = stripslashes($sVal);
		}

		/**
		* Update sections
		*/

		if (isset($_POST['_section'])) {

			$sClassName = 'FlexiCache_Config_' . $_POST['_section'];

			if (class_exists($sClassName) && method_exists($sClassName, 'update')) {

				/**
				* Call update() method on class referenced by $_POST['section']
				*/

				if (true == call_user_func(array($sClassName, 'update'), $_POST)) {

					if (true == FlexiCache_Config::save()) {

						self::addUserMessage('Config was updated successfully.');

					} else {

						self::addUserMessage('Config file could not be saved.');

					}

				} else {

					self::addUserMessage('Config could not be updated.');

				}

			}

		}

		/**
		* Purge
		*/
		if (true == isset($_POST['_purge']) && 'true' == $_POST['_purge']) {

			if (true == empty($_POST['purge_uri'])) {

				/**
				* Purge entire cache
				*/
				if (true == FlexiCache::purge()) {
					self::addUserMessage('Cache was emptied successfully.');
				} else {
					self::addUserMessage('Cache could not be emptied.');
				}

			} else {

				/**
				* Purge individual URL
				*/
				if (true == FlexiCache::purgeUri($_POST['purge_uri'])) {
					self::addUserMessage('Cached entries for URL "<em>' . htmlspecialchars($_POST['purge_uri']) . '</em>" were removed successfully.');
				} else {
					self::addUserMessage('Cached entries for URL "<em>' . htmlspecialchars($_POST['purge_uri']) . '</em>" could not be removed.');
				}

			}


		}

		/**
		* .htaccess
		*/
		if (true == isset($_POST['_htaccess'])) {

			if (true == call_user_func(array('FlexiCache_Wp_Admin_Htaccess',$_POST['_htaccess']))) {
				self::addUserMessage('.htaccess file was updated successfully.');
			} else {
				self::addUserMessage('.htaccess could not be updated.');
			}

		}

		/**
		* Log clearing
		*/
		if (true == isset($_POST['_deletelog']) && 'true' == $_POST['_deletelog']) {

			if (true == FlexiCache_Exception::deleteLog()) {
				self::addUserMessage('Exception log was deleted successfully.');
			} else {
				self::addUserMessage('Exception log could not be deleted.');
			}

		}

		/**
		* Config reset
		*/
		if (true == isset($_POST['_reset']) && 'true' == $_POST['_reset']) {

			FlexiCache_Config::reset();

			if (true == FlexiCache_Config::save()) {
				self::addUserMessage('Settings were successfully reset to defaults.');
			} else {
				self::addUserMessage('Settings could not be reset to defaults.');
			}

		}

	}

	public static function renderStoreStatus ($oStore) {

		$sHtml = '';

		if (true == $oStore->check()) {
			$sHtml = 'Available';
		} else {
			$sHtml = 'Unavailable due to failed checks: ' . join(', ', $oStore->getCheckFails());
		}

		return $sHtml;

	}

	/**
	* Add an admin notification message to the internal array
	*/
	public static function addUserMessage ($sMessage)
	{
		array_push(self::$_aUserMessage, $sMessage);
	}


	/**
	* Return the internal array of notification messages
	*/
	public static function getUserMessages ()
	{
		return self::$_aUserMessage;
	}

	public static function addPostEditBox ()
	{
		self::addItemEditBox('post');
	}

	public static function addPageEditBox ()
	{
		self::addItemEditBox('page');
	}

	/**
	* Add the sidebar box for the edit page
	*/
	public static function addItemEditBox ($sType='post')
	{

		add_meta_box (
			'flexicache',
			'FlexiCache',
			__CLASS__.'::renderEditPostBox',
			$sType,
			'side',
			'low'
		);

	}

	/**
	* Render the HTML for the sidebar box on the post edit page
	*/
	public static function renderEditPostBox ()
	{

		global $post_ID;

		if (false == ($sPath = FlexiCache_Wp::getPathFromPostId($iPostId))) {
			echo "<p>Can't get the permalink for this post.</p>";
			return;
		}

		$iResponses = FlexiCache::getNumCachedResponses($sPath);

		if (false === $iResponses) {

			$sHtml = '<p>Information is unavailable.</p>';

		} else if (0 === $iResponses) {

			$sHtml = '<p>There are no cached responses for this post.</p>';

		} else {

			$sHtml = sprintf("<p>There %s cached response%s for %d content version%2\$s of this post.</p>",
				(1 == $iResponses)?'is a':'are',
				(1 == $iResponses)?'':'s',
				$iResponses
			);

		}

		if (false == FlexiCache_Config::get('Main','Enabled')) {
			$sHtml .= '<p><em>Caching is currently disabled on this site.</em></p>';
		}

		/**
		* If there are cached responses, add a delete button
		* Commented out for now because the form submits as a post update which purges the cache for the wrong reasons.
		* #todo
		*/
/*		if (false != $iResponses && 0 < $iResponses) {

			$sHtml .= sprintf('<form method="post"><input type="hidden" name="purge_uri" value="%s" /><input type="submit" class="button-primary" value="Delete Cached Entries" /></form>',
				$sUri
			);

		}
*/
		echo $sHtml;

	}

}
