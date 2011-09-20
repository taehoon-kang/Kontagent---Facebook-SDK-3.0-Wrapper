<?php
	// The API key of your Kontagent application. This can be found by logging into
	// the Kontagent Dashboard. Note that this is NOT the same as your Facebook AppId.
	define("KT_API_KEY", "cb9e4a66105f441eb68f435bcd795489");
	
	// Whether to send the tracking messages to Kontagent's Test Servers. If this is set to
	// true, data will not be processed to your dashboard. Use this for debugging purposes.
	define("KT_USE_TEST_SERVER", true);
	
	// Whether to send tracking messages on the client-side. If false, messages will be
	// sent server-side. Note that certain messages can only be sent from the client-side (PGR)
	// and some messages can only be sent server-side (INR) - these messages are not affected
	// by this flag.
	define("KT_SEND_CLIENT_SIDE", false);

	// Whether to send client-side tracking messages through HTTPS. This can be set to either
	// true, false, or "auto". If "auto", the library will detect the protocol the current user is using.
	define("KT_USE_HTTPS", "auto");
?>
