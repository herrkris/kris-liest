<h3>Remove Items From The Cache</h3>
<p>Enter a URL (<em>e.g. "/category/news/"</em>) to remove cached copies of that URL, or leave blank to empty the entire cache for <?php echo FlexiCache::getSiteHost(); ?>.</p>
<p>This feature removes cached items from the currently selected storage engine only.</p>
<?php if ((FlexiCache_Store::CLASS_MEMCACHE == FlexiCache_Config::get('Main','DefaultStore')) && (false == FlexiCache_Store::factory(FlexiCache_Config::get('Main','DefaultStore'))->hasAvailableIndex())): ?>
<p><strong>Warning: You are using Memcache without SQLite indexing enabled.  If you empty the cache, all items in the Memcache store will be removed, including any items which may have been placed there by other applications.</strong></p>
<?php endif; ?>
<form method="post">
<input type="hidden" name="_purge" value="true" />
<label for="purge_uri">URL to purge (or leave blank to delete all cached items):</label>
<input type="text" size="40" id="purge_uri" name="purge_uri" />
<input class="button-primary" type="submit" value="Empty Cache" />
</form>
<p>Note: If you choose to delete the entire cache, FlexiCache will temporarily disable caching while the process completes.  If the cache is large and the value of <code>max_execution_time</code> in your PHP config is exceeded while it is being deleted, it will not be possible to automatically re-enable it.  The value of your <code>max_execution_time</code> is currently <?php echo ini_get('max_execution_time'); ?> seconds.</p>