<?php

	$sLog = FlexiCache_Exception::getLog();

?>
<h3>Exception Log</h3>
<p>If FlexiCache encounters any problems, they will be logged below.</p>
<?php if ('' == $sLog): ?>
<p>The log is empty :)</p>
<?php else: ?>
<pre style="background: #fff; border: solid 1px #dfdfdf; padding: 1em;">
<?php
	echo $sLog;
?>
</pre>
<form method="post">
<input type="hidden" name="_deletelog" value="true" />
<p class="submit"><input class="button-primary" type="submit" value="Clear Log" /></p>
</form>
<?php endif; ?>
