<h3>Standalone Mode</h3>
<p>Running FlexiCache in standalone mode can significantly increase the speed of serving cached files (see documentation <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('standalone'); ?>">Standalone Mode</a>).</p>
<p>Enabling standalone mode involves updating your webserver configuration to serve files from FlexiCache directly, as opposed to running it as a plugin within WordPress.</p>
<h4>Current Status</h4>
<form method="post">
<table class="widefat">
<thead>
	<tr><th>Item</th><th>Value</th></tr>
</thead>
<tbody>
<tr>
	<td><strong>Standalone modification in place</strong></td>
	<td><strong><?php echo (true == FlexiCache_Wp_Admin_Htaccess::isIncluded())?'Yes':'No'; ?></strong></td>
</tr>
<tr>
	<td>.htaccess file exists</td>
	<td><?php echo (true == FlexiCache_Wp_Admin_Htaccess::exists())?'Yes':'No'; ?></td>
</tr>
<tr>
	<td>.htaccess file is updatable automatically</td>
	<td><?php echo (true == FlexiCache_Wp_Admin_Htaccess::canUpdate())?'Yes':'No'; ?></td>
</tr>
<tr>
	<td>WordPress MU</td>
	<td><?php echo (true == FlexiCache_Wp::isMu())?'Yes':'No'; ?></td>
</tr>
</tbody>
</table>
<form method="post">

<?php if (false == FlexiCache_Wp_Admin_Htaccess::isIncluded()): // Begin test for modification done ?>
<h4>Modification For .htaccess File</h4>
<p>The following needs to go just before the WordPress rules (if present) in your .htaccess file:</p>
<pre>
<?php echo preg_replace('#\t#','    ',htmlspecialchars(FlexiCache_Wp_Admin_Htaccess::getIncludeTextWithComments())); ?>
</pre>
<?php endif; // End test for modification done ?>

<h4>Automatic File Update</h4>

<?php if (true == FlexiCache_Wp::isMu() || true == FlexiCache_Wp::isMultisite()): // Begin test for MU/Multisite and updatability ?>

<p>Automatic update of the .htaccess file is disabled for WordPress MU and installations running Multisite.</p>

<?php elseif (false == FlexiCache_Wp_Admin_Htaccess::exists ()): // Doesn't exist ?>

<p>You do not appear to have an .htaccess file.  Please create a file called <code>.htaccess</code> in the root of your web site directory and ensure that its permissions are set so that the web server process can modify it.</p>
<p>As a safety precaution, FlexiCache will not update an empty file, so unless you have something else useful to add, just add a comment (e.g. "<code># Comment</code>").</p>
<p>Once you've done that, refresh this page.</p>

<?php elseif (false == FlexiCache_Wp_Admin_Htaccess::canUpdate()): // Can't update ?>

<p>FlexiCache cannot update your .htaccess file.</p>

<?php else: // Can update ?>

<script type="text/javascript">
<!--
	function toggleModificationPreview ()
	{
		jQuery('#flexicache-htaccess-preview').slideToggle('slow');
		return false;
	}
// -->
</script>

<p>FlexiCache can update your .htaccess file for you automatically, but it is recommended that you make the change manually, particularly if you already have a customized .htaccess file.  You should definitely make a backup copy of the file before applying the modification in case any problems arise.</p>
<p><a href="#" onclick="return toggleModificationPreview();">Show/hide modification preview</a></p>

<?php if (false == FlexiCache_Wp_Admin_Htaccess::isIncluded()): // Begin test for modification done ?>
<input type="hidden" name="_htaccess" value="add" />
<div id="flexicache-htaccess-preview">
<h5>Your Current .htaccess file (<em>without</em> Standalone modification)</h5>
<pre><?php echo preg_replace('#\t#','    ',htmlspecialchars(FlexiCache_Wp_Admin_Htaccess::getExistingText())); ?></pre>
<h5>Your .htaccess file as it will be after update (Standalone modification <em>added</em>)</h5>
<pre><?php echo preg_replace('#\t#','    ',htmlspecialchars(FlexiCache_Wp_Admin_Htaccess::getAddStandaloneText())); ?></pre>
</div>
<p class="submit"><input class="button-primary" type="submit" value="Add Standalone Modification" /></p>
<?php endif; // End test for modification done ?>

<?php if (true == FlexiCache_Wp_Admin_Htaccess::isIncluded()): // Begin test for modification done ?>
<input type="hidden" name="_htaccess" value="remove" />
<div id="flexicache-htaccess-preview">
<h5>Your Current .htaccess file (<em>with</em> Standalone modification)</h5>
<pre><?php echo preg_replace('#\t#','    ',htmlspecialchars(FlexiCache_Wp_Admin_Htaccess::getExistingText())); ?></pre>
<h5>Your .htaccess file as it will be after update (Standalone modification <em>removed</em>)</h5>
<pre><?php echo preg_replace('#\t#','    ',htmlspecialchars(FlexiCache_Wp_Admin_Htaccess::getRemoveStandaloneText())); ?></pre>
</div>
<p class="submit"><input class="button-primary" type="submit" value="Remove Standalone Modification" /></p>
<?php endif; // End test for modification done ?>

<?php endif; // End test for MU and updatability ?>
