<?php
	
	$text = $params["_"]?$params["_"]:$params[0];	
	return nl2br($text);
?>