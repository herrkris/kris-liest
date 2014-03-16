<?php

require_once 'ConditionSet.php';
require_once 'ConditionSet/Store.php';
require_once 'ConditionSet/Serve.php';
require_once 'ConditionSet/Expire.php';
require_once 'ConditionSet/ContentVersion.php';

class FlexiCache_Config_Main {

	const DEFAULT_STORAGE_DIR = '_storage';

	public $Enabled = false;
	public $Comments = false;

	public $CacheDir;

	public $EnableStorePlainText = false;

	public $EnableServeGzip = true;
	public $EnableServeDeflate = true;

	public $CompressionLevel = 9;

	public $PurgeOnPostPublish = false;
	public $PurgeOnPagePublish = false;

	public $DefaultStore = FlexiCache_Store::CLASS_FILE;

	public $DefaultExpiryTime = 3600;
	public $PreCacheTime = 0;

	public $ConditionSet_Store;
	public $ConditionSet_Serve;

	public $ConditionSet_Expire;
	public $ConditionSet_ContentVersion;

	public $ServiceUnavailableResponseEnabled = true;
	public $ServiceUnavailableResponseFilePath;
	public $ServiceUnavailableResponseRetrySeconds = 30;

	public $ExpiresHeaderRandMax = 0;

	public static function sanitizePath ($sDir)
	{
		return trim(preg_replace('#[\\\/]+#',DIRECTORY_SEPARATOR,$sDir));
	}

	/**
	* Create a default config
	*/
	public function __construct ()
	{

		$this->CacheDir = self::sanitizePath(join(DIRECTORY_SEPARATOR, array(FlexiCache::getDataDir(),self::DEFAULT_STORAGE_DIR)));

		$this->ConditionSet_Store = new FlexiCache_Config_ConditionSet_Store;

		$this->ConditionSet_Store->addCondition(new FlexiCache_Config_Condition(FlexiCache_Config_Condition::DATA_SOURCE_GET, 'flexicache', 'disabled', FlexiCache_Config_Condition::MATCH_TYPE_EQUALS, true, 'GET var "flexicache" = "disabled"'));
		$this->ConditionSet_Store->addCondition(new FlexiCache_Config_Condition(FlexiCache_Config_Condition::DATA_SOURCE_SERVER, 'HTTP_USER_AGENT', 'bot', FlexiCache_Config_Condition::MATCH_TYPE_CONTAINS, true, 'HTTP_USER_AGENT contains "bot"'));
//		$this->ConditionSet_Store->addCondition(new FlexiCache_Config_Condition(FlexiCache_Config_Condition::DATA_SOURCE_COOKIE, 'mu_auth_token', '', FlexiCache_Config_Condition::MATCH_TYPE_DOES_NOT_EQUAL, true, 'COOKIE "mu_auth_token" is non-empty'));

		$this->ConditionSet_Serve = new FlexiCache_Config_ConditionSet_Serve;

//		$this->ConditionSet_Serve->addCondition(new FlexiCache_Config_Condition(FlexiCache_Config_Condition::DATA_SOURCE_COOKIE, 'mu_auth_token', '', FlexiCache_Config_Condition::MATCH_TYPE_DOES_NOT_EQUAL, true, 'COOKIE "mu_auth_token" is non-empty'));

		$this->ConditionSet_Expire = new FlexiCache_Config_ConditionSet_Expire;

		$this->ConditionSet_Expire->addCondition(new FlexiCache_Config_Condition_Expire(FlexiCache_Config_Condition::DATA_SOURCE_SERVER, 'REQUEST_URI', '^/\d{4}/\d{2}/', FlexiCache_Config_Condition::MATCH_TYPE_REGEX, true, 31557600, 'Posts expire in one year'));

		$this->ConditionSet_ContentVersion = new FlexiCache_Config_ConditionSet_ContentVersion;

		$this->ConditionSet_ContentVersion->addCondition(new FlexiCache_Config_Condition(FlexiCache_Config_Condition::DATA_SOURCE_SERVER, 'HTTP_USER_AGENT', '(iphone|ipod|aspen|incognito|webmate)', FlexiCache_Config_Condition::MATCH_TYPE_REGEX, false, 'Apple Mobile Device'));
		$this->ConditionSet_ContentVersion->addCondition(new FlexiCache_Config_Condition(FlexiCache_Config_Condition::DATA_SOURCE_SERVER, 'HTTP_USER_AGENT', '(2\.0 MMP|240x320|400X240|Android|AvantGo|BlackBerry|Blazer|Cellphone|Cupcake|Danger|DoCoMo|Dream|Elaine/3\.0|EudoraWeb|Googlebot-Mobile|hiptop|IEMobile|KYOCERA/WX310K|LG/U990|MIDP-2\.|MMEF20|MOT-V|NetFront|Newt|Nintendo Wii|Nitro|Nokia|Opera Mini|Palm|PlayStation Portable|portalmmm|Proxinet|ProxiNet|SHARP-TQ-GX10|SHG-i900|Small|SonyEricsson|Symbian OS|SymbianOS|TS21i-10|UP\.Browser|UP\.Link|webOS|Windows CE|WinWAP|YahooSeeker)', FlexiCache_Config_Condition::MATCH_TYPE_REGEX, false, 'Other Mobile Device'));

	}

	public function update ($aInput)
	{

		$oConfig = FlexiCache_Config::get('Main');

		if (isset($aInput['DefaultStore'])) {

			/**
			* If Memcache is selected, test the connection before allowing it.
			* The connection test is not included in the check() method because
			* it takes too long and delays loading of the "Main Options" page
			* when the server is not online.
			*/
			if ((FlexiCache_Store::CLASS_MEMCACHE == $aInput['DefaultStore']) && (false == FlexiCache_Store::factory(FlexiCache_Store::CLASS_MEMCACHE)->testConnection())) {
				FlexiCache_Wp_Admin::addUserMessage('Memcache could not be selected because the connection test failed.  Check the settings in the "Memcache Options" section.');
			} else {
				$oConfig->DefaultStore = (string) $aInput['DefaultStore'];
			}

		}

		if (isset($aInput['DefaultExpiryTime'])) {

			$oConfig->DefaultExpiryTime	= (int)  $aInput['DefaultExpiryTime'];

			if ($oConfig->DefaultExpiryTime < 0) {
				$oConfig->DefaultExpiryTime = 0;
			}

		}

		if (isset($aInput['PreCacheTime'])) {

			$oConfig->PreCacheTime = (int) $aInput['PreCacheTime'];

			if ($oConfig->PreCacheTime < 0) {
				$oConfig->PreCacheTime = 0;
			}

			if ($oConfig->PreCacheTime > $oConfig->DefaultExpiryTime) {
				$oConfig->PreCacheTime = 0;
			}

		}

		if (isset($aInput['PurgeOnPostPublish'])) {
			$oConfig->PurgeOnPostPublish = (bool) $aInput['PurgeOnPostPublish'];
		}

		if (isset($aInput['PurgeOnPagePublish'])) {
			$oConfig->PurgeOnPagePublish = (bool) $aInput['PurgeOnPagePublish'];
		}


		if (isset($aInput['Comments'])) {
			$oConfig->Comments = (bool) $aInput['Comments'];
		}

		/**
		* Compression
		*/

		if (isset($aInput['EnableStorePlainText'])) {
			$oConfig->EnableStorePlainText = (bool) $_POST['EnableStorePlainText'];
		}

		if (isset($aInput['EnableServeGzip'])) {
			$oConfig->EnableServeGzip = (bool) $_POST['EnableServeGzip'];
		}

		if (isset($aInput['EnableServeDeflate'])) {
			$oConfig->EnableServeDeflate = (bool) $_POST['EnableServeDeflate'];
		}

		if (isset($aInput['CompressionLevel'])) {

			$oConfig->CompressionLevel = (int) $_POST['CompressionLevel'];

			if ($oConfig->CompressionLevel < 1) {
				$oConfig->CompressionLevel = 1;
			} else if ($oConfig->CompressionLevel > 9) {
				$oConfig->CompressionLevel = 9;
			}

		}

		/**
		* 503 Responses
		*/

		if (isset($aInput['ServiceUnavailableResponseEnabled'])) {
			$oConfig->ServiceUnavailableResponseEnabled = (bool) $_POST['ServiceUnavailableResponseEnabled'];
		}

		if (isset($aInput['ServiceUnavailableResponseFilePath'])) {

			$s503FilePath = self::sanitizePath($aInput['ServiceUnavailableResponseFilePath']);

			if (true == empty($s503FilePath)) {

				$oConfig->ServiceUnavailableResponseFilePath = null;

			} else if ((true == file_exists($s503FilePath)) && (true == is_readable($s503FilePath))) {

				$oConfig->ServiceUnavailableResponseFilePath = $s503FilePath;

			} else {

				FlexiCache_Wp_Admin::addUserMessage('The specified 503 response file path does not exist or FlexiCache does not have permission to read it.  The value you entered has been removed.');
				$oConfig->ServiceUnavailableResponseFilePath = null;

			}

		}

		/**
		* Expiry header randomization
		*/
		if (isset($aInput['ExpiresHeaderRandMax'])) {

			$oConfig->ExpiresHeaderRandMax = (int) $aInput['ExpiresHeaderRandMax'];

			if ($oConfig->ExpiresHeaderRandMax < 0) {
				$oConfig->ExpiresHeaderRandMax = 0;
			}

			if ($oConfig->ExpiresHeaderRandMax > $oConfig->DefaultExpiryTime) {
				$oConfig->ExpiresHeaderRandMax = 0;
			}

		}

		/**
		* Data directory and enabled/disabled come last so we can do final checks.
		*/

		if (isset($aInput['CacheDir'])) {

			/**
			* Check that the data directory is valid before allowing it to be selected
			*/

			$sCacheDir = self::sanitizePath($aInput['CacheDir']);

			if ((true == file_exists($sCacheDir)) && (true == is_dir($sCacheDir)) && (true == is_writable($sCacheDir))) {

				$oConfig->CacheDir = $sCacheDir;

			} else {

				FlexiCache_Wp_Admin::addUserMessage('The specified data directory does not exist or FlexiCache does not have permission to write to it.');
				return false;

			}

		}

		if (isset($aInput['Enabled'])) {

			$bEnabled = (bool) $aInput['Enabled'];

			if (true == $bEnabled) {

				/**
				* If caching is set to be enabled, check the store is available,
				* and if not, don't allow enable caching.
				*/

				if (false == FlexiCache_Store::factory($oConfig->DefaultStore)->check()) {

					$oConfig->Enabled = false;
					FlexiCache_Wp_Admin::addUserMessage('Caching could not be enabled because the selected store failed its self-checks: ' . join(', ', $oStore->getCheckFails()));

					return false;

				} else if ((FlexiCache_Store::CLASS_MEMCACHE == $aInput['DefaultStore']) && (false == FlexiCache_Store::factory(FlexiCache_Store::CLASS_MEMCACHE)->testConnection())) {

					FlexiCache_Wp_Admin::addUserMessage('Caching could not be enabled because the Memcache connection test failed.  Check the settings in the "Memcache Options" section or select a different storage engine.');

				} else {

					$oConfig->Enabled = true;

				}

			} else {

				$oConfig->Enabled = false;

			}

		}

		return true;

	}

}
