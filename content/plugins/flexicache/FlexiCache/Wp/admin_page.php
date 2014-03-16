<div class="wrap flexicache">

<div id="icon-options-general" class="icon32"></div>

<h2>FlexiCache</h2>

<?php if (0 != count($aAdminMessage = FlexiCache_Wp_Admin::getUserMessages())): ?>
<div id="message" class="updated fade">
<?php foreach ($aAdminMessage as $sAdminMessage): ?>
<p><?php echo $sAdminMessage; ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php

	$aaSection = array (
		'status'			=> 'Status',
		'main'				=> 'Main Options',
		'compression'		=> 'Compression Options',
		'conditions-expire'	=> 'Expiry Time Conditions',
		'conditions-store'	=> 'No-Store Conditions',
		'conditions-serve'	=> 'No-Serve Conditions',
		'conditions-version'=> 'Content Versions',
		'memcache'			=> 'Memcache Options',
		'standalone'		=> 'Standalone Mode',
		'documentation'		=> 'Documentation',
		'log'				=> 'Exception Log'
	);

?>
<form method="get">
<input type="hidden" name="page" value="<?php echo FlexiCache_Wp::FLEXICACHE_PLUGIN_DIR; ?>" />
<label for="section">Select Section:</label> <select id="section" name="section">
<?php

	$sCurrentSection = (isset($_GET['section']))?$_GET['section']:'status';

	foreach ($aaSection as $sSection=>$sTitle) {
		printf('<option value="%s"%s>%s</option>',
			$sSection,
			($sCurrentSection == $sSection)?' selected="selected"':'',
			htmlspecialchars($sTitle)
		);
	}

?>
</select>
<input type="submit" value="Go" />
</form>

<?php if ('status' == $sCurrentSection): ?>
	<?php include 'includes/status.php'; ?>
	<?php include 'includes/purge_cache.php'; ?>
	<?php include 'includes/reset_config.php'; ?>
<?php elseif ('main' == $sCurrentSection): ?>
	<?php include 'includes/options_main.php'; ?>
	<?php include 'includes/storage_engine_availability.php'; ?>
<?php elseif ('compression' == $sCurrentSection): ?>
	<?php include 'includes/options_compression.php'; ?>
<?php elseif ('conditions-expire' == $sCurrentSection): ?>
	<?php include 'includes/conditions_expire.php'; ?>
<?php elseif ('conditions-store' == $sCurrentSection): ?>
	<?php include 'includes/conditions_store.php'; ?>
<?php elseif ('conditions-serve' == $sCurrentSection): ?>
	<?php include 'includes/conditions_serve.php'; ?>
<?php elseif ('conditions-version' == $sCurrentSection): ?>
	<?php include 'includes/conditions_version.php'; ?>
<?php elseif ('memcache' == $sCurrentSection): ?>
	<?php include 'includes/options_memcache.php'; ?>
<?php elseif ('standalone' == $sCurrentSection): ?>
	<?php include 'includes/standalone.php'; ?>
<?php elseif ('documentation' == $sCurrentSection): ?>
	<?php include 'includes/documentation.php'; ?>
<?php elseif ('log' == $sCurrentSection): ?>
	<?php include 'includes/log.php'; ?>
<?php endif; ?>
</div>
