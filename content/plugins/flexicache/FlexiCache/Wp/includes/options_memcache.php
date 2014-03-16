<?php

	$bLibraryAvailable = FlexiCache_Store::factory(FlexiCache_Store::CLASS_MEMCACHE)->libraryIsPresent();
	$bDisabled = (true == $bLibraryAvailable)?false:true;

?>
<h3>Memcache Options</h3>
<?php if (false == $bLibraryAvailable): ?>
<p><strong>Use of this feature requires the Memcache extension for PHP, which does not appear to be available on your system</strong>.</p>
<p>For more information, please see the <a href="http://php.net/manual/en/book.memcache.php">PHP Memcache documentation</a>.</p>
<?php endif; ?>
<p>If you're not using Memcache, you don't need to configure these options.</p>
<form method="post">
<input type="hidden" name="_section" value="Store_Memcache" />
<table class="widefat">
<thead>
	<tr><th>Option</th><th>Value</th></tr>
</thead>
<tbody>
<tr>
	<td><label for="Host">Host</label></td>
	<td><?php echo FlexiCache_Config_Form::renderInputText('Host', FlexiCache_Config::get('FlexiCache_Store_Memcache','Host'),32,$bDisabled);?></td>
</tr>
<tr>
	<td><label for="Port">Port</label></td>
	<td><?php echo FlexiCache_Config_Form::renderInputText('Port', FlexiCache_Config::get('FlexiCache_Store_Memcache','Port'),6,$bDisabled);?></td>
</tr>
</tbody>
</table>
<p class="submit"><input <?php if (true == $bDisabled): ?>disabled="disabled" <?php endif; ?>class="button-primary" type="submit" value="Save Changes and Test Connection" /></p>
</form>
