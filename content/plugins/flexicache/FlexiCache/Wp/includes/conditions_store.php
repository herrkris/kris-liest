<h3>No-Store Conditions</h3>
<p>See documentation <a href="<?php echo FlexiCache_Wp_Admin::getDocLink('configuration'); ?>">Configuration and Control</a></p>
<p>Pages will not be <em>stored</em> in the cache if the request matches <em>any</em> of the following rules.</p>
<p><em>Condition values are treated as case-insensitive.  Pages are never cached for users who are logged in to WordPress.</em></p>

<form method="post">
<input type="hidden" name="_section" value="ConditionSet_Store" />

<h4>Existing Conditions</h4>
<?php if (true == FlexiCache_Config::get('Main', 'ConditionSet_Store')->hasConditions()): ?>
<table class="widefat">
<thead>
	<tr><th>Source</th><th>Key</th><th>Match Type</th><th>Value</th><th>Description</th><th>Enabled</th><th>Delete</th></tr>
</thead>
<tbody>
<?php FlexiCache_Config_Form::renderConditions(FlexiCache_Config::get('Main', 'ConditionSet_Store')->getConditions()); ?>
</tbody>
</table>
<?php else: ?>
<p>There are currently no conditions configured.</p>
<?php endif; ?>

<h4>Add New Condition</h4>
<table class="widefat">
<thead>
	<tr><th>Source</th><th>Key</th><th>Match Type</th><th>Value</th><th>Description</th><th>Enabled</th><th><strike>Delete</strike></th></tr>
</thead>
<tbody>
<?php FlexiCache_Config_Form::renderCondition(FlexiCache_Config_Condition::getEmpty()); ?>
</tbody>
</table>

<p class="submit"><input class="button-primary" type="submit" value="Save Changes" /></p>

</form>
