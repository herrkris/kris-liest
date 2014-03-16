<h3>Storage Engine Availability</h3>
<table class="widefat">
<thead>
	<tr><th>Storage Engine</th><th>Status</th></tr>
</thead>
<tbody>
<?php

	foreach (FlexiCache_Store::getEngines() as $sStoreId=>$sStoreName) {

		$oStore = FlexiCache_Store::factory($sStoreId);

		echo "<tr>";

		printf("<td>%s</td><td>%s</td>",
			$sStoreName,
			FlexiCache_Wp_Admin::renderStoreStatus($oStore)
		);

		echo "</tr>\n";

	}

?>
</tbody>
</table>
