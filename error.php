<?php
// Error Page
// Redirect here if a PHP exception is thrown
// Shows error message, line, and trace
// @author Gordon Wang <WangGordon@JohnDeere.com>
// @date July 2016
require "Header.php";
?>

<div class="border">
	<div class="error">
		A PHP exception occurred<br>
		<?php echo urldecode($_POST["message"]); ?>
	</div>
	<br>
	<div class="container">
		At <?php echo urldecode($_POST["location"]); ?>.<br><br>
		<b>Trace</b><br>
		<?php echo urldecode($_POST["trace"]); ?>
	</div>
</div>

<div class="footer">&nbsp;</div>