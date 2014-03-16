<div id="col-container">

<!-- Begin right column -->
<div id="col-right">
<h3>Storage Status</h3>
<table class="widefat">
<thead>
	<tr><th>Item</th><th>Value</th></tr>
</thead>
<tbody>
<?php

	foreach (FlexiCache_Store::factory(FlexiCache_Config::get('Main','DefaultStore'))->getActivityArray() as $sKey=>$sVal) {

		printf("<tr><td>%s</td><td>%s</td></tr>\n",
			htmlspecialchars($sKey),
			htmlspecialchars($sVal)
		);

	}

?>
</tbody>
</table>
</div>
<!-- End right column -->

<!-- Begin left column -->
<div id="col-left">
<h3>General Status</h3>
<table class="widefat">
<thead>
	<tr><th>Item</th><th>Value</th></tr>
</thead>
<tbody>
<tr>
	<td>Plugin version</td>
	<td><?php echo FlexiCache_Wp::getPluginVersion(); ?></td>
</tr>
<tr>
	<td>Site hostname</td>
	<td><?php echo FlexiCache::getSiteHost(); ?></td>
</tr>
<tr>
	<td>Caching enabled</td>
	<td><?php echo (true == FlexiCache_Config::get('Main','Enabled'))?'Yes':'No'; ?></td>
</tr>
<tr>
	<td>Standalone mode enabled</td>
	<td><?php echo (true == FlexiCache_Wp_Admin_Htaccess::isIncluded())?'Yes':'No'; ?></td>
</tr>
<tr>
	<td>Selected storage engine</td>
	<td><?php echo FlexiCache_Store::getEngineDisplayName(FlexiCache_Config::get('Main','DefaultStore')); ?></td>
</tr>
<tr>
	<td>Default expiry time</td>
	<td><?php echo FlexiCache_Config::get('Main','DefaultExpiryTime'); ?> seconds</td>
</tr>
</tbody>
</table>
</div>
<!-- End left column -->

</div>