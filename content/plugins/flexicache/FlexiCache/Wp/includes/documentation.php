<?php

	$oDefaultConfig = new FlexiCache_Config_Main;

?>
<h3>About FlexiCache</h3>
<p>FlexiCache is a fast, full-featured and flexible caching system which will improve the performance and availability of any WordPress site.</p>
<p>It is highly configurable to allow unlimited caching rules - expiring different pages at different times, not caching some pages at all, caching multiple versions of the same page depending on browser, language, and so on.</p>
<p>FlexiCache will also work straight out of the box without any need for complex configuration.</p>
<h4 id="flexicache-doc-storage-engines">Storage Engines</h4>
<p>FlexiCache comes with a choice of three storage engines; which of these you use will depend on your site:</p>
<ul>
	<li>Filesystem: Default option, requires no special libraries, suitable for any site.</li>
	<li>Memcache: Requires <a href="http://memcached.org/">Memcached server</a> and <a href="http://php.net/manual/en/book.memcache.php">PHP Memcache extension</a>, best option for any site.</li>
	<li>SQLite: Requires SQLite library. Potentially suitable for smaller sites but mostly there for testing purposes.</li>
</ul>
<h5>Indexing</h5>
<p>If the SQLite library is available on your system, FlexiCache will automatically maintain an index of stored responses in an SQLite database to accompany Filesystem and Memcache storage, which speeds up the process of identifying expired responses and clearing unwanted items from the cache.  This also allows for more performance statistics.</p>
<p>The index is <em>only</em> accessed when writing a new item to the cache, deleting items, or providing stats for the admin area - it is <em>not</em> required when serving items from the cache, so does not negatively impact serving performance.</p>
<h4 id="flexicache-doc-storage-directory">Storage Directory</h4>
<p>By default, FlexiCache stores config and cache data in the "_data/_storage" directory within the plugin directory. This is required to allow FlexiCache to run in Standalone mode, since in this mode of operation, FlexiCache does not access the WordPress configuration, and so cannot accurately determine the location of the "wp-content" directory, which is where most plugins store their data.</p>
<p>You may change the cache storage directory via the admin interface.  If you do so, you may safely delete the "_storage" directory, but do not remove the "_data" directory as this is where the config file is saved to.</p>
<p>Within the storage directory, files are stored inside directories corresponding to the hostname of the site.  For WordPress MU and WordPress 3, a directory will be created for each site.</p>
<p>If you upgrade the plugin using WordPress's built-in upgrade process, the entire plugin directory will be replaced, including your config and cache data. To preserve these, take a copy of the "_data" directory before upgrading, and then copy it back after the upgrade is complete.</p>
<p>If you upgrade directly via SVN, this should not be required, since only the files under version control will be updated.</p>
<h4 id="flexicache-doc-configuration">Configuration and Control</h4>
<p>FlexiCache's decision-making process is based on conditions you define, which are grouped into condition sets:</p>
<ul>
	<li>Expiry time conditions: Where a request matches one of these conditions, its expiry time is set according to the condition rather then the default.</li>
	<li>No-store conditions: If a request matches any condition in this set, the response will not be stored.</li>
	<li>No-serve conditions: If a request matches any condition in this set, a dynamic response will be generated and served even if there is a cached response available.</li>
	<li>Content version conditions: If a request matches a condition in this set, it will be stored and served separately (see <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('content-versions'); ?>">Content Versions</a>)</li>
</ul>
<h5>Conditions</h5>
<p>Condition sets contain conditions, each of which is defined with the following components:</p>
<ul>
	<li>Source: The source array for the item of data you want to test - <code>$_GET</code>, <code>$_POST</code>, <code>$_COOKIE</code>, <code>$_SERVER</code>, etc.</li>
	<li>Key: The data key within the source array chosen above.  For example to match a browser, you would select <code>$_SERVER</code> as the source, and enter <code>HTTP_USER_AGENT</code> as the key.</li>
	<li>Match Type: How to match, e.g. "Begins with", "Contains", "Does not equal".</li>
	<li>Value: A string to match against the source.</li>
	<li>Description: Optional but recommended - something to remind you what the condition does.</li>
	<li>Enabled: If this is box is ticked, the condition will be processed, otherwise it will be ignored.</li>
</ul>
<p>Expiry time conditions have one additional parameter:</p>
<ul>
	<li>Expires: The expiry time (in seconds) to set on a response which matches the condition.</li>
</ul>
<h5>Examples of Conditions</h5>
<table class="widefat">
<thead>
	<tr><th>Source</th><th>Key</th><th>Match Type</th><th>Value</th><th>Description</th></tr>
</thead>
<tbody>
	<tr><td>$_SERVER</td><td>REQUEST_URI</td><td>Begins with</td><td>/news/</td><td>Requested page starts with "/news/"</td></tr></tbody>
	<tr><td>$_SERVER</td><td>USER_AGENT</td><td>Contains</td><td>iPhone</td><td>Browser's User-Agent string contains "iPhone"</td></tr></tbody>
	<tr><td>$_COOKIE</td><td>my_site_cookie</td><td>Does not equal</td><td></td><td>Browser supplies a cookie "my_site_cookie" which is not blank</td></tr></tbody>
	<tr><td>$_ENV</td><td>APPLICATION_ENV</td><td>Equals</td><td>development</td><td>This is the development server</td></tr></tbody>
	<!--
	<tr><td>A</td><td>B</td><td>C</td><td>D</td><td>E</td></tr></tbody>
	-->
</table>
<p>When FlexiCache processes condition sets it stops at the first matching condition it finds.  All string comparisons are case-insensitive.</p>
<h5 id="flexicache-doc-content-versions">Content Versions</h5>
<p>If your website produces different versions of pages for different browsers, languages, or in other situations, you should tell FlexiCache which rules you use to determine those versions using the "Content Versions" condition set, so it can store and serve cached responses according to the same rules.</p>
<p>FlexiCache comes with a couple of examples of these, but they should be considered as examples only, and not as exhaustive or authoritative.</p>
<h5 id="flexicache-doc-custom-control-headers">Custom Control Headers</h5>
<p>As well as the condition sets defined in the admin area, certain aspects of FlexiCache can be controlled from within WordPress templates and plugins by setting a special HTTP response header <code><?php echo FlexiCache_Headers::CUSTOM_CONTROL_KEY; ?></code> using PHP's <code>header()</code> function.</p>
<p>The following directives are currently supported:</p>
<ul>
	<li><code>max-age</code> sets the expiry duration of the current response in seconds (see <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('order-of-processing'); ?>">Order Of Processing</a> below)</li>
	<li><code>must-revalidate</code> instructs FlexiCache never to send an expired version of the current response (which may otherwise happen in occasional circumstances - see <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('uncached-item-handling'); ?>">Handling Multiple Concurrent Requests To An Uncached Item</a> below)</li>
	<li><code>no-pre-cache</code> disables pre-caching for the current response (see <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('pre-caching'); ?>">Pre-Caching</a> below)</li>
</ul>
<p>PHP code example: <code>header('<?php echo FlexiCache_Headers::CUSTOM_CONTROL_KEY; ?>: max-age=1800; must-revalidate; no-pre-cache');</code>
<h5 id="flexicache-doc-order-of-processing">Order Of Processing</h5>
<p>FlexiCache processes rules in the following order and priority:</p>
<ol>
	<li>Security restrictions: If any of the following conditions are true, FlexiCache will exit, and will not store or serve a cached response:<ul>
		<li>The request is for a WordPress admin page</li>
		<li>The user making the request is logged in to WordPress</li>
		<li>The user making the request has a WordPress comment author cookie set</li>
		<li>The user making the request has a WordPress passworded page access cookie set</li>
		<li>The request is not using the HTTP GET method</li>
	</ul></li>
	<li>Configured condition sets are processed.</li>
	<li>"<?php echo FlexiCache_Headers::CUSTOM_CONTROL_KEY; ?>" custom HTTP headers are processed; these override anything set above.</p>
	<li>Sanity check: If a response is empty and no "Location" header is set, this is treated as an application error and the response will not be cached.</p>
</ol>
<h4 id="flexicache-doc-compression">Compression</h4>
<p>FlexiCache automatically stores responses in both <em>gzip</em> and <em>deflate</em> formats, since most modern browsers support at least one of those formats.  On the rare occasion that a browser does not accept a compressed response, FlexiCache will decompress one of those responses on the server and send it out uncompressed.</p>
<p>You have the option of caching plain-text (uncompressed) versions of responses as well, but this will only improve performance if you know that the majority of browsers accessing your site do not accept either <em>gzip</em> or <em>deflate</em>, which is an unlikely scenario.</p>
<h4 id="flexicache-doc-uncached-item-handling">Handling Multiple Concurrent Requests To An Uncached Item</h4>
<p>In the event that FlexiCache receives a request for a response which is currently being built by WordPress, FlexiCache will prevent this process from re-building the same item at the same time, in order to reduce server load.</p>
<p>How this is handled depends on whether an existing expired response exists, and on configuration, as detailed in the table below:</p>
<table class="flexicache-doc">
<tr>
	<th></th>
	<th>An expired cached response exists</th>
	<th>No cached response exists at all</th>
</tr>
<tr>
	<th>503 responses enabled in config</th>
	<td>The expired cached response is served</td>
	<td>A 503 response is sent</td>
</tr>
<tr>
	<th>503 responses disabled</th>
	<td>The expired cached response is served</td>
	<td>Process waits for WordPress to complete building the page and then serves the fresh cached copy</td>
</tr>
</table>
<p>Enabling 503 responses quickly frees up your server resources to serve more requests during busy periods, at the expense of the user seeing a "Service Temporarily Unavailable" page in rare circumstances.</p>
<p>You can provide a custom static file to serve in this situation instead of the default "Service Temporarily Unavailable" message.  This file is passed directly to the client as-is - it is <em>not</em> processed by PHP.</p>
<h4 id="flexicache-doc-randomizing-expiry-headers">Random Expiry Header Variation</h4>
<p>In order to prevent multiple clients checking back to the server to refresh their copy of an expired item at the same moment, FlexiCache allows you to add a random element to the expiry time headers which are sent with each response.  This can help reduce server load spikes.</p>
<p>If the config for this setting is a non-zero value, FlexiCache will add a random number of seconds between zero and that value to the times in the "Expires" and "Cache-Control" headers returned to the client in each response.  You cannot set a value less than zero or greater than the default expiry time.</p>
<p>The default value for this config option is zero - i.e. no randomization is added and the headers are sent as-is.</p>
<h4 id="flexicache-doc-pre-caching">Pre-Caching</h4>
<p>Standard practise for a cache is to look for a cached response, and if none is found, generate a new response dynamically and store it at the same time as sending it to the client.  The client has to wait while the fresh response is generated.</p>
<p>When pre-caching is enabled, FlexiCache can cache a new copy of a response <em>before</em> the current cached version expires, meaning that clients are more likely to be served a fresh cached response rather than waiting for a dynamic page to be generated.</p>
<p>Pre-caching is triggered by a client request occurring within the pre-cache threshold time set in the config.  The new response is generated <em>after</em> the current cached response has been sent to the client and the client's connection to the server has been closed, so the client does not experience a delay while the new version is being generated.</p>
<p>The best value for the pre-cache time will depend on your site's traffic, but as a general rule, the busier your site, the smaller the pre-cache time you should set.</p>
<p>If the pre-cache time is set to 60 seconds, and FlexiCache serves a cached file which is less than 60 seconds from its expiry time, it will serve the cached response, and then generate and store a new version of the page at that time.</p>
<p>If your site produces custom headers using PHP's <code>header()</code> function, PHP will generate a warning "Cannot modify header information - headers already sent" when this function is called during a pre-caching operation, as the response has already been sent to the client.  In this situation it is recommended either that you do not enable pre-caching, or that you accompany the <code>header()</code> call with an additional custom control header specifying "no-pre-cache" (see <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('custom-control-headers'); ?>">Custom Control Headers</a> above)</p>
<h4 id="flexicache-doc-standalone">Standalone Mode</h4>
<p>Standalone mode works by sending page requests directly to FlexiCache instead of to WordPress.  If FlexiCache finds a cached page, it can serve it direct to the client without having to include any WordPress files or third-party theme and plugin files, which may execute code and make database connections which are not required and could slow things down unneccessarily.</p>
<p>If FlexiCache does not find a cached page, it hands control to WordPress, which generates the page as usual and then includes FlexiCache as a plugin to store the page afterwards (if configured to to do).  As such, there is a small overhead in serving a non-cached page, but a huge improvement in serving cached pages, and since the goal is to serve more cached pages than not, the overall improvement is significant.</p>
<p>To enabled standalone mode, add the following to your .htaccess file, immediately before the WordPress rules.  If you currently have no .htaccess file, create one with this content in:</p>
<pre>
<?php echo preg_replace('#\t#','    ',htmlspecialchars(FlexiCache_Wp_Admin_Htaccess::getIncludeTextWithComments())); ?>
</pre>
<p>When running multiple sites in WordPress MU or WordPress 3, you should adjust the line which specifies <code>HTTP_HOST</code> to accept multiple sites, e.g.:</p>
<pre>    RewriteCond %{HTTP_HOST}        (\.domain1\.com|\.domain2\.org|\.domain3\.net)$</pre>
<p>For more information on rewriting URLs in Apache, see <a href="http://httpd.apache.org/docs/2.1/rewrite/">Apache's URL Rewriting Guide</a>.  If you're not sure what an .htaccess file is, read <a href="http://httpd.apache.org/docs/2.1/howto/htaccess.html">Apache's .htaccess tutorial</a>.</p>
<p><strong>Important: If you deactivate FlexiCache, you should remove (or comment out) these lines from your .htaccess file first.  If you deactivate FlexiCache on an individual blog in WordPress MU or WordPress 3, you should remove the site's hostname from the <code>HTTP_HOST</code> condition before deactivation.</strong></p>
<h4>Housekeeping</h4>
<p>FlexiCache automatically removes out-of-date items from the cache from time to time.</p>
<p>Default configuration is to delete a maximum of <code><?php echo FlexiCache::EXPIRED_ITEMS_TO_DELETE_ON_CLEANUP; ?></code> expired responses an average of once every <code><?php echo FlexiCache::AVERAGE_REQUESTS_BEFORE_CLEANUP; ?></code> requests served from the cache.  This configuration is currently hard-coded but future versions of FlexiCache may allow this to be configured per-site.</p>
<p>Housekeeping is triggered using a random function on each client request served from a cached page.  Housekeeping tasks are performed on the server <em>after</em> the response has been served and the connection to the server has been closed, so no delay is experienced by the client.</p>
<h4>HTTP Headers</h4>
<p>FlexiCache will override any pre-existing <code>Cache-Control</code> response header with the same expiry details used to cache the item on the server.</p>
<p>Additionally, FlexiCache adds a new header <code>X-FlexiCache</code> which can be used to verify that responses are being cached as you intended, e.g:</p>
<pre>
Cache-Control: must-revalidate; max-age=856
<?php echo FlexiCache_Headers::CUSTOM_OUTPUT_KEY; ?>: cached; serve-mode=standalone; created=Tue, 24 Jan 2012 12:41:17 GMT; expires=Tue, 24 Jan 2012 13:41:17 GMT
</pre>
<p>If you're not sure if you've been served a cached page or not, look for the <code>X-FlexiCache</code> header in the HTTP response.</p>
