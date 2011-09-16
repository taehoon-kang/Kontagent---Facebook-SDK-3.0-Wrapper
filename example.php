<?php
	require_once './kontagent/kontagent_config.php';

	require_once './facebook-php-sdk/src/facebook.php';
	require_once './kontagent/kontagent_facebook.php';

	$ktFacebook = new KontagentFacebook(array(
		'appId'  => '192889707410670',
		'secret' => 'f627a650863145bf36969f899723d165',
	));
	
?>
<html>
	<head>
		<title>Kontagent</title>
	</head>
	<body>
		<div id="fb-root"></div>
		<script src="http://connect.facebook.net/en_US/all.js"></script>
		<script src="./kontagent/kontagent_facebook.js"></script>
		<script>
			KT_FB.init({
				appId  : '192889707410670',
				status : true, // check login status
				cookie : true, // enable cookies to allow the server to access the session
				xfbml  : true, // parse XFBML
				channelUrl : 'http://www.andy.com/channel.html', // channel.html file
				oauth  : true // enable OAuth 2.0
			});
		</script>
	</body>
</html>
