<?php
/**
 * Plugin Name: No Piwik for me
 * Plugin URI: http://ppfeufer.de/wordpress-plugin/no-piwik-for-me/
 * Description: Erstellt den Shortcode <code>[no_piwik]</code> um die nutzerbezogene Deaktivierung von Piwik zu ermöglichen.
 * Version: 1.2
 * Author: H.-Peter Pfeufer
 * Author URI: http://ppfeufer.de
 */

if(!class_exists('No_Piwik_For_Me')) {
	class No_Piwik_For_Me {
		function No_Piwik_For_Me() {
			self::__construct();
		}

		function __construct() {
			add_shortcode('no_piwik', array(
				$this,
				'no_piwik_shortcode'
			));

			if(is_admin()) {
				if(ini_get('allow_url_fopen') || function_exists('curl_init')) {
					add_action('in_plugin_update_message-' . plugin_basename(__FILE__), array(
						$this,
						'_update_notice'
					));
				}
			}
		}

		function no_piwik_shortcode() {
			if(!class_exists('wp_piwik')) {
				return;
			}

			$var_sPiwikUrl = get_option('wp-piwik_global-settings');
			$var_sPiwikUrl = $var_sPiwikUrl['piwik_url'];

			if(empty($var_sPiwikUrl)) {
				return;
			} else {
				$var_sPiwikUrl = esc_url($var_sPiwikUrl . '?module=CoreAdminHome&amp;action=optOut&amp;language=de');
			}

			$var_sHtml = '<iframe id="no-piwik-for-me" src="' . $var_sPiwikUrl . '" style="width:100%; height:auto; background:none repeat scroll 0 0 transparent;"></iframe>';

			return $var_sHtml;
		} // END function no_piwik_shortcode()

		function _update_notice() {
			$array_NPFM_Data = get_plugin_data(__FILE__);
			$var_sUserAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:5.0) Gecko/20100101 Firefox/5.0 WorPress Plugin 2-Click Social Media Buttons (Version: ' . $array_NPFM_Data['Version'] . ') running on: ' . get_bloginfo('url');
			$url_readme = 'http://plugins.trac.wordpress.org/browser/no-piwik-for-me/trunk/readme.txt?format=txt';
			$data = '';

			if(ini_get('allow_url_fopen')) {
				$data = file_get_contents($url_readme);
			} else {
				if(function_exists('curl_init')) {
					$cUrl_Channel = curl_init();
					curl_setopt($cUrl_Channel, CURLOPT_URL, $url_readme);
					curl_setopt($cUrl_Channel, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($cUrl_Channel, CURLOPT_USERAGENT, $var_sUserAgent);
					$data = curl_exec($cUrl_Channel);
					curl_close($cUrl_Channel);
				} // END if(function_exists('curl_init'))
			} // END if(ini_get('allow_url_fopen'))

			if($data) {
				$matches = null;
				$regexp = '~==\s*Changelog\s*==\s*=\s*[0-9.]+\s*=(.*)(=\s*' . preg_quote($array_NPFM_Data['Version']) . '\s*=|$)~Uis';

				if(preg_match($regexp, $data, $matches)) {
					$changelog = (array) preg_split('~[\r\n]+~', trim($matches[1]));

					echo '</div><div class="update-message" style="font-weight: normal;"><strong>What\'s new:</strong>';
					$ul = false;
					$version = 99;

					foreach($changelog as $index => $line) {
						if(version_compare($version, $array_NPFM_Data['Version'], ">")) {
							if(preg_match('~^\s*\*\s*~', $line)) {
								if(!$ul) {
									echo '<ul style="list-style: disc; margin-left: 20px;">';
									$ul = true;
								} // END if(!$ul)

								$line = preg_replace('~^\s*\*\s*~', '', $line);
								echo '<li>' . $line . '</li>';
							} else {
								if($ul) {
									echo '</ul>';
									$ul = false;
								} // END if($ul)

								$version = trim($line, " =");
								echo '<p style="margin: 5px 0;">' . htmlspecialchars($line) . '</p>';
							} // END if(preg_match('~^\s*\*\s*~', $line))
						} // END if(version_compare($version, TWOCLICK_SOCIALMEDIA_BUTTONS_VERSION,">"))
					} // END foreach($changelog as $index => $line)

					if($ul) {
						echo '</ul><div style="clear: left;"></div>';
					} // END if($ul)

					echo '</div>';
				} // END if(preg_match($regexp, $data, $matches))
			} else {
				/**
				 * Returning if we can't use file_get_contents or cURL
				 */
				return;
			} // END if($data)
		} // END function no_piwik_for_me_update_notice()
	}
}

new No_Piwik_For_Me();