<h3>Main Options</h3>
<p>Please read the <a href="<?php echo FlexiCache_Wp_Admin::getDocLink(); ?>">documentation</a> to understand how FlexiCache works.</p>
<form method="post">
<input type="hidden" name="_section" value="Main" />

<h4>Basic Options</h4>

<table class="widefat">
<thead>
	<tr><th>Option</th><th>Value</th></tr>
</thead>
<tbody>
<tr>
	<td><label for="Enabled">Enable caching</label></td>
	<td><?php echo FlexiCache_Config_Form::renderBooleanSelect('Enabled', FlexiCache_Config::get('Main','Enabled'));?></td>
</tr>
<tr>
	<td><label for="DefaultStore">Storage engine</label> (see documentation: <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('storage-engines'); ?>">Storage Engines</a>)</td>
	<td><?php echo FlexiCache_Config_Form::renderStorageEngineSelect('DefaultStore',FlexiCache_Config::get('Main','DefaultStore'));?></td>
</tr>
<tr>
	<td colspan="2"><label for="CacheDir">Storage directory</label> (see documentation: <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('storage-directory'); ?>">Storage Directory</a>):<br /><?php echo FlexiCache_Config_Form::renderInputText('CacheDir',FlexiCache_Config::get('Main','CacheDir'),125); ?></td>
</tr>
<tr>
	<td><label for="DefaultExpiryTime">Default expiry time<br /><em>Items will be set to expire using this value unless they match a specific expiry time condition</em></label></td>
	<td><?php echo FlexiCache_Config_Form::renderInputText('DefaultExpiryTime',FlexiCache_Config::get('Main','DefaultExpiryTime'),6); ?> secs</td>
</tr>
</tbody>
</table>

<h4>More Advanced Options</h4>

<table class="widefat">
<thead>
	<tr><th>Option</th><th>Value</th></tr>
</thead>
<tbody>
<tr>
	<td><label for="PreCacheTime">Pre-cache time (see documentation: <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('pre-caching'); ?>">Pre-Caching</a>)</label><br/><em>If a valid cached page is served which is due to expire within this time period, cache a new version of the page <b>after</b> the response has been sent to the client.  A setting of zero disables this behaviour.</em></td>
	<td><?php echo FlexiCache_Config_Form::renderInputText('PreCacheTime',FlexiCache_Config::get('Main','PreCacheTime'),6); ?> secs</td>
</tr>
<tr>
	<td><label for="PurgeOnPostPublish">Empty the cache when a <strong>post</strong> is published (not recommended)</label><br /><em>Any cached item matching a post's permalink is always removed when the post is re-published, regardless of this setting.</em></td>
	<td><?php echo FlexiCache_Config_Form::renderBooleanSelect('PurgeOnPostPublish',FlexiCache_Config::get('Main','PurgeOnPostPublish'));?></td>
</tr>
<tr>
	<td><label for="PurgeOnPagePublish">Empty the cache when a <strong>page</strong> is published (not recommended)</label><br/><em>Any cached item matching a page's permalink is always removed when the page is re-published, regardless of this setting.</em></td>
	<td><?php echo FlexiCache_Config_Form::renderBooleanSelect('PurgeOnPagePublish',FlexiCache_Config::get('Main','PurgeOnPagePublish'));?></td>
</tr>
<tr>
	<td><label for="Comments">Show HTML comments to indicate cached status</label><br /><em>Comments are added dynamically and so will not be appended to gzip- or deflate-encoded responses.  Comments are only ever added to (X)HTML or XML files.</em></td>
	<td><?php echo FlexiCache_Config_Form::renderBooleanSelect('Comments',FlexiCache_Config::get('Main','Comments'));?></td>
</tr>
<tr>
	<td><label for="ServiceUnavailableResponseEnabled">Use HTTP 503 "Service Temporarily Unavailable" responses</label><br /><em>If a user agent requests an item which is not cached, and the item is already being built by another process, send a "Service Unavailable" response instructing the user agent to check back later</em> (see documentation: <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('uncached-item-handling'); ?>">Handling Multiple Concurrent Requests To An Uncached Item</a>)</td>
	<td><?php echo FlexiCache_Config_Form::renderBooleanSelect('ServiceUnavailableResponseEnabled', FlexiCache_Config::get('Main','ServiceUnavailableResponseEnabled'));?></td>
</tr>
<tr>
	<td colspan="2"><label for="ServiceUnavailableResponseFilePath">Path to HTTP 503 "Service Temporarily Unavailable" response file</label>: <?php echo FlexiCache_Config_Form::renderInputText('ServiceUnavailableResponseFilePath',FlexiCache_Config::get('Main','ServiceUnavailableResponseFilePath'),120); ?><br/><em>If 503 responses are enabled, send this file as the response instead of the default basic HTML.  This must be a static HTML or text file as it will NOT be processed by PHP.</em></td>
</tr>
<tr>
	<td><label for="ExpiresHeaderRandMax">Expiry header variation (see documentation: <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('randomizing-expiry-headers'); ?>">Random Expiry Header Variation</a>)</label><br/><em>Add a random number of seconds (between zero and the value defined here) to the expiry headers sent with each response, to prevent multiple clients attempting to fetch fresh copies of items simultaneously.<br />A setting of zero disables randomization.  The value must be less than the value defined for "Default expiry time" above.</em></td>
	<td><?php echo FlexiCache_Config_Form::renderInputText('ExpiresHeaderRandMax',FlexiCache_Config::get('Main','ExpiresHeaderRandMax'),6); ?> secs</td>
</tr>
</tbody>
</table>
<p class="submit"><input class="button-primary" type="submit" value="Save Changes" /></p>
</form>
