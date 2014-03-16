<h3>Compression Options</h3>
<p>See documentation: <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('compression'); ?>">Compression</a></p>
<form method="post">
<input type="hidden" name="_section" value="Main" />
<table class="widefat">
<thead>
	<tr><th>Option</th><th>Value</th></tr>
</thead>
<tbody>
<tr>
	<td><label for="EnableStorePlainText">Store plain-text responses<?php if (true == FlexiCache_Response::getMustStorePlainText()): ?> [THIS OPTION HAS BEEN DISABLED ON YOUR SYSTEM AS REQUIRED DECOMPRESSION FUNCTIONS ARE NOT PRESENT]<?php endif; ?><br /><em>Disabling storing of plain-text responses will reduce the amount of disk space taken up by the cache.  Most modern browsers accept compressed files, and those few which don't can still be served by decompressing a gzip- or deflate- encoded file on the server before sending it.</em></label></td>
<?php if (true == FlexiCache_Response::getMustStorePlainText()): ?>
	<td>Yes<input type="hidden" name="EnableStorePlainText" id="EnableStorePlainText" value="1" /></td>
<?php else: ?>
	<td><?php echo FlexiCache_Config_Form::renderBooleanSelect('EnableStorePlainText',FlexiCache_Config::get('Main','EnableStorePlainText'));?></td>
<?php endif; ?>
</tr>
<tr>
	<td><label for="EnableServeGzip">Send gzip-encoded responses to supported browsers</label></td>
	<td><?php echo FlexiCache_Config_Form::renderBooleanSelect('EnableServeGzip',FlexiCache_Config::get('Main','EnableServeGzip'));?></td>
</tr>
<tr>
	<td><label for="EnableServeDeflate">Send deflate-encoded responses to supported browsers<br /><em>FlexiCache will try to serve a gzip-encoded file before a deflate-encoded one.</em></label></td>
	<td><?php echo FlexiCache_Config_Form::renderBooleanSelect('EnableServeDeflate',FlexiCache_Config::get('Main','EnableServeDeflate'));?></td>
</tr>
<tr>
	<td><label for="CompressionLevel">Compression level<br /><em>1 to 9; 1 = minimum compression, 9 = maximum compression</em></label></td>
	<td><?php echo FlexiCache_Config_Form::renderCompressionLevelSelect('CompressionLevel',FlexiCache_Config::get('Main','CompressionLevel'));?></td>
</tr>
</tbody>
</table>
<p class="submit"><input class="button-primary" type="submit" value="Save Changes" /></p>
</form>
