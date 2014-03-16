<h3>Content Versions</h3>
<p>See documentation: <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('configuration'); ?>">Configuration and Control</a> and <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('content-versions'); ?>">Content Versions</a></p>
<p>If your website produces different versions of pages for different browsers (or in other situations), you will need to let FlexiCache know so it can store and serve those versions separately.</p>
<p>For example, if you produce different versions of pages for the iPhone, add the same conditions below that you use to detect an iPhone browser.</p>
<p><em>Condition values are treated as case-insensitive.</p>
<form method="post">
<input type="hidden" name="_section" value="ConditionSet_ContentVersion" />
<table class="widefat">
<thead>
	<tr><th>Source</th><th>Key</th><th>Match Type</th><th>Value</th><th>Description</th><th>Enabled</th><th>Delete</th></tr>
</thead>
<tbody>
<?php FlexiCache_Config_Form::renderConditions(FlexiCache_Config::get('Main', 'ConditionSet_ContentVersion')->getConditions()); ?>
</tbody>
</table>

<h4>Add New Version</h4>
<table class="widefat">
<thead>
	<tr><th>Source</th><th>Key</th><th>Match Type</th><th>Value</th><th>Description</th><th>Enabled</th><th>Delete</th></tr>
</thead>
<tbody>
<?php FlexiCache_Config_Form::renderCondition(FlexiCache_Config_Condition::getEmpty()); ?>
</tbody>
</table>

<p class="submit"><input class="button-primary" type="submit" value="Save Changes" /></p>

</form>
