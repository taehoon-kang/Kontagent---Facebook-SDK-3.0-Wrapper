<?php

// Kontagent's wrapper around Facebook's 3.0 SDK. Overrides methods to 
// automatically send the appropriate tracking messages to Kontagent.
class KontagentFacebook extends Facebook
{
	// Reference to Kontagent's API wrapper object
	public $ktApi = null;
	
	// KT tracking variable names that are used to pass Kontagent values around.
	private $ktVars = array(
		'kt_track_apa',
		'kt_track_pst',
		'kt_track_psr',
		'kt_track_ins',
		'kt_track_inr',
		'kt_track_mes',
		'kt_track_mer',
		'kt_u',
		'kt_su',
		'kt_st1',
		'kt_st2',
		'kt_st3',
		'kt_r',
		'kt_type'
	);
	
	public function __construct($config)
	{
		parent::__construct(array(
			'appId' => $config['appId'], 
			'secret' => $config['secret'])
		);
		
		// instantiate the Kontagent Api object
		$this->ktApi = new KontagentApi(KT_API_KEY, array(
			'useTestServer' => KT_USE_TEST_SERVER,
			'validateParams' => false
		));
		
		// Output config and GET variables to Javascript so they can be 
		// accessed on the client side. Used by the kontagent_facebook.js library.
		$this->outputVarsToJs();
		
		$this->trackLanding();
	}
	
	// Returns the Kontagent API wrapper object which can be used to manually fire
	// off tracking messages.
	public function getKontagentApi()
	{
		return $this->ktApi;
	}
	
	// Overrides the parent method, returns the current URL adding the ability 
	// to strip KT tracking variables.
	protected function getCurrentUrl($stripKtVars = false) 
	{
		$currentUrl = parent::getCurrentUrl();
		return ($stripKtVars) ? $this->stripKtVarsFromUrl($currentUrl) : $currentUrl;
	}
	
	// Returns the Feed Dialog url. This method takes in the parameters defined by Facebook
	// (see FB documentation).
	public function getLoginUrl($params = array()) 
	{
		$params['redirect_uri'] = $this->appendKtVarsToUrl(
			(isset($params['redirect_uri'])) ? $params['redirect_uri'] : $this->getCurrentUrl(true),
			array(
				'kt_track_apa' => 1,
				'kt_u' =>  (isset($_GET['kt_u'])) ? $_GET['kt_u'] : null,
				'kt_su' => (isset($_GET['kt_su'])) ? $_GET['kt_su'] : null
			)
		);
		
		return parent::getLoginUrl($params);
	}
	
	// Returns the Logout url. This method takes in the parameters defined by Facebook
	// (see FB documentation).
	public function getLogoutUrl($params = array()) 
	{			
		return parent::getLogoutUrl(array_merge(
			array('next' => $this->getCurrentUrl(true)),
			$params
		));
	}
	
	// Returns the Feed Dialog url. This method takes in the parameters defined by Facebook
	// (see FB documentation) as well as 'subtype1', 'subtype2', 'subtype3' values.
	public function getFeedDialogUrl($params = array())
	{
		$uniqueTrackingTag = $this->ktApi->genUniqueTrackingTag();
	
		$params['redirect_uri'] = $this->appendKtVarsToUrl(
			(isset($params['redirect_uri'])) ? $params['redirect_uri'] : $this->getCurrentUrl(true),
			array(
				'kt_track_pst' => 1,
				'kt_u' =>  $uniqueTrackingTag,
				'kt_st1' => (isset($params['subtype1'])) ? $params['subtype1'] : null,
				'kt_st2' => (isset($params['subtype2'])) ? $params['subtype2'] : null,
				'kt_st3' => (isset($params['subtype3'])) ? $params['subtype3'] : null,
			)
		);
	
		// append tracking variables to link 
		// TODO: append these variables to ALL possible links (properties, actions - see: http://developers.facebook.com/docs/reference/dialogs/feed/)
		if ($params['link']) {
			$params['link'] = $this->appendKtVarsToUrl(
				$params['link'],
				array(
					'kt_track_psr' => 1,
					'kt_u' =>  $uniqueTrackingTag,
					'kt_st1' => (isset($params['subtype1'])) ? $params['subtype1'] : null,
					'kt_st2' => (isset($params['subtype2'])) ? $params['subtype2'] : null,
					'kt_st3' => (isset($params['subtype3'])) ? $params['subtype3'] : null
				)
			);
		}
	
		return $this->getUrl( 
			'www',
			'dialog/feed',
			array_merge(
				array('app_id' => $this->getAppId()),
				$params
			)
		);
	}
	
	// Returns the Friends Dialog url. This method takes in the parameters defined by Facebook
	// (see FB documentation).
	public function getFriendsDialogUrl($params = array())
	{
		return $this->getUrl( 
			'www',
			'dialog/friends',
			array_merge(
				array(
					'app_id' => $this->getAppId(),
					'redirect_uri' => $this->getCurrentUrl(true)
				),
				$params
			)
		);
	}
	
	// Returns the OAuth Dialog url. This method takes in the parameters defined by Facebook
	// (see FB documentation).
	public function getOAuthDialogUrl($params = array())
	{
		$params['redirect_uri'] = $this->appendKtVarsToUrl(
			(isset($params['redirect_uri'])) ? $params['redirect_uri'] : $this->getCurrentUrl(true),
			array(
				'kt_track_apa' => 1,
				'kt_u' =>  (isset($_GET['kt_u'])) ? $_GET['kt_u'] : null,
				'kt_su' =>  (isset($_GET['kt_su'])) ? $_GET['kt_su'] : null
			)
		);
	
		return $this->getUrl( 
			'www',
			'dialog/oauth',
			array_merge(
				array('client_id' => $this->getAppId()),
				$params
			)
		);
	}
	
	// Returns the Pay Dialog url. This method takes in the parameters defined by Facebook
	// (see FB documentation).
	public function getPayDialogUrl($params = array())
	{
		return $this->getUrl( 
			'www',
			'dialog/pay',
			array_merge(
				array(
					'app_id' => $this->getAppId(),
					'redirect_uri' => $this->getCurrentUrl(true)
				),
				$params
			)
		);
	}
	
	// Returns the Requests Dialog url. This method takes in the parameters defined by Facebook
	// (see FB documentation) as well as 'subtype1', 'subtype2', 'subtype3' values.
	public function getRequestsDialogUrl($params = array())
	{
		$uniqueTrackingTag = $this->ktApi->genUniqueTrackingTag();
		
		$params['redirect_uri'] = $this->appendKtVarsToUrl(
			(isset($params['redirect_uri'])) ? $params['redirect_uri'] : $this->getCurrentUrl(true),
			array(
				'kt_track_ins' => 1,
				'kt_u' => $uniqueTrackingTag,
				'kt_st1' => (isset($params['subtype1'])) ? $params['subtype1'] : null,
				'kt_st2' => (isset($params['subtype2'])) ? $params['subtype2'] : null,
				'kt_st3' => (isset($params['subtype3'])) ? $params['subtype3'] : null
			)
		);
		
		// append tracking variables to link 
		// TODO: append these variables to ALL possible links (properties, actions - see: http://developers.facebook.com/docs/reference/dialogs/feed/)
		$params['data'] = $this->appendKtVarsToDataField(
			isset($params['data']) ? $params['data'] : '',
			array(
				'kt_u' => $uniqueTrackingTag,
				'kt_st1' => (isset($params['subtype1'])) ? $params['subtype1'] : null,
				'kt_st2' => (isset($params['subtype2'])) ? $params['subtype2'] : null,
				'kt_st3' => (isset($params['subtype3'])) ? $params['subtype3'] : null
			)
		);
	
		return $this->getUrl( 
			'www',
			'dialog/apprequests',
			array_merge(
				array('app_id' => $this->getAppId()),
				$params
			)
		);
	}
	
	// Returns the Send Dialog url. This method takes in the parameters defined by Facebook
	// (see FB documentation).
	public function getSendDialogUrl($params = array())
	{		
		return $this->getUrl( 
			'www',
			'dialog/send',
			array_merge(
				array('app_id' => $this->getAppId()),
				$params
			)
		);
	}
	
	// Echos config and GET variables to JS so that they can be accessed
	// by our JS library. 
	private function outputVarsToJs()
	{
		echo '<script>';
		echo 'var KT_API_KEY = "' . KT_API_KEY . '";';
		
		if (KT_USE_TEST_SERVER) {
			echo 'var KT_USE_TEST_SERVER = true;';
		} else {
			echo 'var KT_USE_TEST_SERVER = false;';
		}
		
		if (KT_SEND_CLIENT_SIDE) {
			echo 'var KT_SEND_CLIENT_SIDE = true;';
		} else {
			echo 'var KT_SEND_CLIENT_SIDE = false;';
		}

		echo 'var KT_GET = [];';

		foreach($_GET as $key => $val) {
			if (is_array($val)) {
				echo 'KT_GET["' . $key . '"] = [];';

				for($i=0; $i<sizeof($val); $i++) {
					echo 'KT_GET["' . $key . '"][' . $i . '] = ' . $val[$i] . ';';
				}
			} else if (is_numeric($val)) {
				echo 'KT_GET["' . $key . '"] = ' . $val . ';';
			} else {
				echo 'KT_GET["' . $key . '"] = "' . $val . '";';
			}
		}

		echo '</script>';
	}
	
	// Performs the landing page tracking.
	private function trackLanding()
	{
		// Invite Responses are always generated on the server-side (due to FB limitations, the request
		// data cannot be retrieved on the client-side prior to user auth.
		// If there is request_ids is present and there are no other kt tracking params,
		// then the user is responding to an apprequest/invite.
		if (isset($_GET['request_ids']) && !is_array($_GET['request_ids']) && sizeof($this->extractKtVarsFromUrl($this->getCurrentUrl(false))) == 0) {
			try {
				// User may be responding to more than 1 request. We take the latest one.
				$requestIds = explode(',', $_GET['request_ids']);
				$requestId = $requestIds[sizeof($requestIds)-1];
				$request = $this->api('/' . $requestId);
				
				// extract parameters that was stored in the data field
				// (kt_u, kt_st1, kt_st2, kt_st3)
				$ktDataVars = $this->extractKtVarsFromDataField($request['data']);
				
				// we also store the unique tracking tag parameter to the $_GET 
				// because this is where the code will look for it when application added is generated.
				$_GET['kt_u'] = $ktDataVars['kt_u'];
				echo '<script>KT_GET["kt_u"] = "' . $_GET['kt_u'] . '";</script>';
				
				$this->ktApi->trackInviteResponse($ktDataVars['kt_u'], array(
					'recipientUserId' => $requestId,
					'subtype1' => (isset($ktDataVars['kt_st1'])) ? $ktDataVars['kt_st1'] : null,
					'subtype2' => (isset($ktDataVars['kt_st2'])) ? $ktDataVars['kt_st2'] : null,
					'subtype3' => (isset($ktDataVars['kt_st3'])) ? $ktDataVars['kt_st3'] : null
				));
			} catch (FacebookApiException $e) { }
		}
	
		if (!KT_SEND_CLIENT_SIDE) {
			if ($this->getUser()) {
				if (isset($_GET['kt_track_apa']) && !isset($_GET['error'])) {
					// track the application added
					$this->ktApi->trackApplicationAdded($this->getUser(), array(
						'uniqueTrackingTag' => isset($_GET['kt_u']) ? $_GET['kt_u'] : null,
						'shortUniqueTrackingTag' => isset($_GET['kt_su']) ? $_GET['kt_su'] : null,
					));
					
					// track the user information
					$gender = null;
					$birthYear = null;
					$friendCount = null;
					
					// attempt to retrieve user data from FB api
					try {
						$userInfo = $this->api('/me');
						$userFriendsInfo = $this->api('/me/friends');
						
						$gender = substr($userInfo['gender'], 0, 1);
						
						if ($userInfo['birthday']) {
							$birthdayPieces = explode('/', $userInfo['birthday']);
							
							if (sizeof($birthdayPieces) == 3) {
								$birthYear = $birthdayPieces[2];
							}
						}
						
						$friendCount = sizeof($userFriendsInfo['data']);
					} catch (FacebookApiException $e) { }
					
					$this->ktApi->trackUserInformation($this->getUser(), array(
						'gender' => (isset($gender)) ? $gender : null,
						'birthYear' => (isset($birthYear)) ? $birthYear : null,
						'friendCount' => (isset($friendCount)) ? $friendCount : null
					));
				}
				
				if (isset($_GET['kt_track_ins']) && isset($_GET['request_ids']) && is_array($_GET['request_ids'])) {
					$this->ktApi->trackInviteSent(
						$this->getUser(),
						implode(',', $_GET['request_ids']),
						$_GET['kt_u'], 
						array(
							'subtype1' => (isset($_GET['kt_st1'])) ? $_GET['kt_st1'] : null,
							'subtype2' => (isset($_GET['kt_st2'])) ? $_GET['kt_st2'] : null,
							'subtype3' => (isset($_GET['kt_st3'])) ? $_GET['kt_st3'] : null
						)
					);
				}
				
				if (isset($_GET['kt_track_pst']) && isset($_GET['post_id'])) {
					$this->ktApi->trackStreamPost($this->getUser(), $_GET['kt_u'], 'stream', array(
						'subtype1' => (isset($_GET['kt_st1'])) ? $_GET['kt_st1'] : null,
						'subtype2' => (isset($_GET['kt_st2'])) ? $_GET['kt_st2'] : null,
						'subtype3' => (isset($_GET['kt_st3'])) ? $_GET['kt_st3'] : null
					));
				}
			}
			
		
			if (isset($_GET['kt_track_psr'])) {
				$this->ktApi->trackStreamPostResponse($_GET['kt_u'], 'stream', array(
					'recipientUserId' => ($this->getUser()) ? $this->getUser() : null,
					'subtype1' => (isset($_GET['kt_st1'])) ? $_GET['kt_st1'] : null,
					'subtype2' => (isset($_GET['kt_st2'])) ? $_GET['kt_st2'] : null,
					'subtype3' => (isset($_GET['kt_st3'])) ? $_GET['kt_st3'] : null
				));
			}
			
			if (isset($_GET['kt_type'])) {
				// generate an short tracking tag. We store it in $_GET because
				// this is where the code looks for it when an application added is triggered.
				$_GET['kt_su'] = $this->ktApi->genShortUniqueTrackingTag();
				echo '<script>KT_GET["kt_su"] = "' . $_GET['kt_su'] . '";</script>';
			
				$this->ktApi->trackThirdPartyCommClick($_GET['kt_type'], array(
					'userId' => ($this->getUser()) ? $this->getUser() : null,
					'shortUniqueTrackingTag' => $_GET['kt_su'],
					'subtype1' => (isset($_GET['kt_st1'])) ? $_GET['kt_st1'] : null,
					'subtype2' => (isset($_GET['kt_st2'])) ? $_GET['kt_st2'] : null,
					'subtype3' => (isset($_GET['kt_st3'])) ? $_GET['kt_st3'] : null
				));
			}
		}
	}
	
	// Appends KT tracking parameters to the data field of the Requests Dialog
	// (see FB documentation for details).
	private function appendKtVarsToDataField($dataString, $vars = array()) 
	{
		// Data will be stored in the following format:
		// data = "<original_data>|<kontagent_data>"
	
		$dataString .= '|';
		
		foreach($vars as $key => $val) {
			if (isset($val)) {
				$dataString .= $key . '=' . $val . '&';
			}
		}
		
		// remove trailing ampersand
		$dataString = substr($dataString, 0, -1);

		return $dataString;
	}
			
	// Strips the Kontagent data and returns a string containing only the original data.
	private function stripKtVarsFromDataField($dataString)
	{
		list($otherDataString, $ktDataString) = explode('|', $dataString);
		
		return $otherDataString;
	}
	
	// Strips the original data and returns a string containing only the Kontagent data.
	private function extractKtVarsFromDataField($dataString)
	{
		list($otherDataString, $ktDataString) = explode('|', $dataString);
		
		parse_str($ktDataString, $ktDataVars);
		
		return $ktDataVars;
	}
	
	// Appends variables to a given URL. $vars should be an associative array
	// in the form: var_name => var_value
	private function appendKtVarsToUrl($url, $vars = array()) 
	{
		if (strstr($url, '?') === false) {
			$url .= '?';
		} else {
			$url .= '&';
		}
	
		foreach($vars as $key => $val) {
			if (isset($val)) {
				$url .= $key . '=' . $val . '&';
			}
		}
		
		// remove trailing ampersand
		$url = substr($url, 0, -1);
		
		return $url;
	}
	
	// Cleans a given URL of KT tracking parameters.
	private function stripKtVarsFromUrl($url) 
	{
		$parts = parse_url($url);
		
		if (empty($parts['query'])) {
			return $url;
		}
		
		$vars = explode('&', $parts['query']);
		$retainedVars = array();
				
		foreach ($vars as $var) {
			list ($key, $val) = explode('=', $var);
			
			if (!in_array($key, $this->ktVars)) {
				$retainedVars[] = $var;
			}
		}

		if (!empty($retainedVars)) {
			$query = '?' . implode($retainedVars, '&');
		}
		
		$port = ($parts['port']) ? ':' . $parts['port'] : '';
		
		return $parts['scheme'] . '://' . $parts['host'] . $port . $parts['path'] . $query;
	}
	
	// Takes in a URL and returns an associative array containing the KT tracking parameters.
	private function extractKtVarsFromUrl($url)
	{
		$ktUrlVars = array();
	
		$parts = parse_url($url);
		
		if (empty($parts['query'])) {
			return $ktUrlVars;
		}
		
		$vars = explode('&', $parts['query']);
		
		foreach ($vars as $var) {
			list ($key, $val) = explode('=', $var);
			
			if (in_array($key, $this->ktVars)) {
				$ktUrlVars[$key] = $val;
			}
		}
		
		return $ktUrlVars;
	}
}

////////////////////////////////////////////////////////////////////////////////	
	
class KontagentApi {
	private $baseApiUrl = "http://api.geo.kontagent.net/api/v1/";
	private $baseTestServerUrl = "http://test-server.kontagent.net/api/v1/";
	
	private $apiKey = null;
	private $validateParams = null;
	private $useTestServer = null;
	
	private $useCurl = null;

	/*
	* Kontagent class constructor
	*
	* @param string $apiKey The app's Kontagent API key
	* @param array $optionalParams An associative array containing paramName => value
	* @param bool $optionalParams['useTestServer'] Whether to send messages to the Kontagent Test Server
	* @param bool $optionalParams['validateParams'] Whether to validate the parameters passed into the tracking methods
	*/
	public function __construct($apiKey, $optionalParams = array()) {
		$this->apiKey = $apiKey;
		$this->useTestServer = ($optionalParams['useTestServer']) ? $optionalParams['useTestServer'] : false;
		$this->validateParams = ($optionalParams['validateParams']) ? $optionalParams['validateParams'] : false;
		
		// determine whether curl is installed on the server
		$this->useCurl = (function_exists('curl_init')) ? true : false;
	}

	/*
	* Sends the API message.
	*
	* @param string $messageType The message type to send ('apa', 'ins', etc.)
	* @param array $params An associative array containing paramName => value (ex: 's'=>123456789)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	private function sendMessage($messageType, $params, &$validationErrorMsg = null) {
		if ($this->validateParams) {
			// validate the message parameters
			$validationErrorMsg = null;
			
			foreach($params as $paramName => $paramValue) {
				if (!KtValidator::validateParameter($messageType, $paramName, $paramValue, $validationErrorMsg)) {
					return false;
				}
			}
		}
	
		// generate URL of the API request
		$url = null;
		
		if ($this->useTestServer) {
			$url = $this->baseTestServerUrl . $this->apiKey . "/" . $messageType . "/?" . http_build_query($params, '', '&');
		} else {
			$url = $this->baseApiUrl . $this->apiKey . "/" . $messageType . "/?" . http_build_query($params, '', '&');
		}
		
		// use curl if available, otherwise use file_get_contents() to send the request
		if ($this->useCurl) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_exec($ch);
			curl_close($ch);
		} else {
			file_get_contents($url);
		}

		return true;
	}
	
	/*
	* Generates a unique tracking tag.
	*
	* @return string The unique tracking tag
	*/
	public function genUniqueTrackingTag() {
		return substr(md5(uniqid(rand(), true)), -16);
	}
	
	/*
	* Generates a short unique tracking tag.
	*
	* @return string The short unique tracking tag
	*/
	public function genShortUniqueTrackingTag() {
		return substr(md5(uniqid(rand(), true)), -8);
	}
	
	/*
	* Sends an Invite Sent message to Kontagent.
	*
	* @param string $userId The UID of the sending user
	* @param string $recipientUserIds A comma-separated list of the recipient UIDs
	* @param string $uniqueTrackingTag 32-digit hex string used to match 
	*	InviteSent->InviteResponse->ApplicationAdded messages. 
	*	See the genUniqueTrackingTag() helper method.
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackInviteSent($userId, $recipientUserIds, $uniqueTrackingTag, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			's' => $userId,
			'r' => $recipientUserIds,
			'u' => $uniqueTrackingTag
		);
		
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }
			
		return $this->sendMessage("ins", $params, $validationErrorMsg);
	}
	
	/*
	* Sends an Invite Response message to Kontagent.
	*
	* @param string $uniqueTrackingTag 32-digit hex string used to match 
	*	InviteSent->InviteResponse->ApplicationAdded messages. 
	*	See the genUniqueTrackingTag() helper method.
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['recipientUserId'] The UID of the responding user
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackInviteResponse($uniqueTrackingTag, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			'i' => 0,
			'u' => $uniqueTrackingTag
		);
		
		if ($optionalParams['recipientUserId']) { $params['r'] = $optionalParams['recipientUserId']; }
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }
	
		return $this->sendMessage("inr", $params, $validationErrorMsg);
	}
	
	/*
	* Sends an Notification Sent message to Kontagent.
	*
	* @param string $userId The UID of the sending user
	* @param string $recipientUserIds A comma-separated list of the recipient UIDs
	* @param string $uniqueTrackingTag 32-digit hex string used to match 
	*	NotificationSent->NotificationResponse->ApplicationAdded messages. 
	*	See the genUniqueTrackingTag() helper method.
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackNotificationSent($userId, $recipientUserIds, $uniqueTrackingTag, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			's' => $userId,
			'r' => $recipientUserIds,
			'u' => $uniqueTrackingTag
		);
		
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }
		
		return $this->sendMessage("nts", $params, $validationErrorMsg);
	}

	/*
	* Sends an Notification Response message to Kontagent.
	*
	* @param string $uniqueTrackingTag 32-digit hex string used to match 
	*	NotificationSent->NotificationResponse->ApplicationAdded messages. 
	*	See the genUniqueTrackingTag() helper method.
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['recipientUserId'] The UID of the responding user
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackNotificationResponse($uniqueTrackingTag, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			'i' => 0,
			'u' => $uniqueTrackingTag
		);
		
		if ($optionalParams['recipientUserId']) { $params['r'] = $optionalParams['recipientUserId']; }
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }
	
		return $this->sendMessage("ntr", $params, $validationErrorMsg);
	}
	
	/*
	* Sends an Notification Email Sent message to Kontagent.
	*
	* @param string $userId The UID of the sending user
	* @param string $recipientUserIds A comma-separated list of the recipient UIDs
	* @param string $uniqueTrackingTag 32-digit hex string used to match 
	*	NotificationEmailSent->NotificationEmailResponse->ApplicationAdded messages. 
	*	See the genUniqueTrackingTag() helper method.
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackNotificationEmailSent($userId, $recipientUserIds, $uniqueTrackingTag, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			's' => $userId,
			'r' => $recipientUserIds,
			'u' => $uniqueTrackingTag
		);
		
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }
	
		return $this->sendMessage("nes", $params, $validationErrorMsg);
	}

	/*
	* Sends an Notification Email Response message to Kontagent.
	*
	* @param string $uniqueTrackingTag 32-digit hex string used to match 
	*	NotificationEmailSent->NotificationEmailResponse->ApplicationAdded messages. 
	*	See the genUniqueTrackingTag() helper method.
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['recipientUserId'] The UID of the responding user
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackNotificationEmailResponse($uniqueTrackingTag, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			'i' => 0,
			'u' => $uniqueTrackingTag
		);
		
		if ($optionalParams['recipientUserId']) { $params['r'] = $optionalParams['recipientUserId']; }
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }	
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }
	
		return $this->sendMessage("nei", $params, $validationErrorMsg);
	}

	/*
	* Sends an Stream Post message to Kontagent.
	*
	* @param string $userId The UID of the sending user
	* @param string $uniqueTrackingTag 32-digit hex string used to match 
	*	NotificationEmailSent->NotificationEmailResponse->ApplicationAdded messages. 
	*	See the genUniqueTrackingTag() helper method.
	* @param string $type The Facebook channel type
	*	(feedpub, stream, feedstory, multifeedstory, dashboard_activity, or dashboard_globalnews).
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackStreamPost($userId, $uniqueTrackingTag, $type, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			's' => $userId,
			'u' => $uniqueTrackingTag,
			'tu' => $type
		);
		
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }
		
		return $this->sendMessage("pst", $params, $validationErrorMsg);
	}

	/*
	* Sends an Stream Post Response message to Kontagent.
	*
	* @param string $uniqueTrackingTag 32-digit hex string used to match 
	*	NotificationEmailSent->NotificationEmailResponse->ApplicationAdded messages. 
	*	See the genUniqueTrackingTag() helper method.
	* @param string $type The Facebook channel type
	*	(feedpub, stream, feedstory, multifeedstory, dashboard_activity, or dashboard_globalnews).
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['recipientUserId'] The UID of the responding user
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackStreamPostResponse($uniqueTrackingTag, $type, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			'i' => 0,
			'u' => $uniqueTrackingTag,
			'tu' => $type
		);
		
		if ($optionalParams['recipientUserId']) { $params['r'] = $optionalParams['recipientUserId']; }
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }
	
		return $this->sendMessage("psr", $params, $validationErrorMsg);
	}

	/*
	* Sends an Custom Event message to Kontagent.
	*
	* @param string $userId The UID of the user
	* @param string $eventName The name of the event
	* @param array $optionalParams An associative array containing paramName => value
	* @param int $optionalParams['value'] A value associated with the event
	* @param int $optionalParams['level'] A level associated with the event (must be positive)
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackEvent($userId, $eventName, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			's' => $userId,
			'n' => $eventName
		);
		
		if ($optionalParams['value']) { $params['v'] = $optionalParams['value']; }
		if ($optionalParams['level']) { $params['l'] = $optionalParams['level']; }
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }
	
		return $this->sendMessage("evt", $params, $validationErrorMsg);
	}

	/*
	* Sends an Application Added message to Kontagent.
	*
	* @param string $userId The UID of the installing user
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['uniqueTrackingTag'] 16-digit hex string used to match 
	*	Invite/StreamPost/NotificationSent/NotificationEmailSent->ApplicationAdded messages. 
	*	See the genUniqueTrackingTag() helper method.
	* @param string $optionalParams['shortUniqueTrackingTag'] 8-digit hex string used to match 
	*	ThirdPartyCommClicks->ApplicationAdded messages. 
	*	See the genShortUniqueTrackingTag() helper method.
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackApplicationAdded($userId, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array('s' => $userId);
		
		if ($optionalParams['uniqueTrackingTag']) { $params['u'] = $optionalParams['uniqueTrackingTag']; }
		if ($optionalParams['shortUniqueTrackingTag']) { $params['su'] = $optionalParams['shortUniqueTrackingTag']; }
	
		return $this->sendMessage("apa", $params, $validationErrorMsg);
	}

	/*
	* Sends an Application Removed message to Kontagent.
	*
	* @param string $userId The UID of the removing user
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackApplicationRemoved($userId, &$validationErrorMsg = null) {
		$params = array('s' => $userId);
	
		return $this->sendMessage("apr", $params, $validationErrorMsg);
	}
	
	/*
	* Sends an Third Party Communication Click message to Kontagent.
	*
	* @param string $type The third party comm click type (ad, partner).
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['shortUniqueTrackingTag'] 8-digit hex string used to match 
	*	ThirdPartyCommClicks->ApplicationAdded messages. 
	* @param string $optionalParams['userId'] The UID of the user
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackThirdPartyCommClick($type, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			'i' => 0,
			'tu' => $type
		);
		
		if ($optionalParams['shortUniqueTrackingTag']) { $params['su'] = $optionalParams['shortUniqueTrackingTag']; }
		if ($optionalParams['userId']) { $params['s'] = $optionalParams['userId']; }
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }	
	
		return $this->sendMessage("ucc", $params, $validationErrorMsg);
	}

	/*
	* Sends an Page Request message to Kontagent.
	*
	* @param string $userId The UID of the user
	* @param array $optionalParams An associative array containing paramName => value
	* @param int $optionalParams['ipAddress'] The current users IP address
	* @param string $optionalParams['pageAddress'] The current page address (ex: index.html)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackPageRequest($userId, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			's' => $userId,
			'ts' => time() 
		);
		
		if ($optionalParams['ipAddress']) { $params['ip'] = $optionalParams['ipAddress']; }
		if ($optionalParams['pageAddress']) { $params['u'] = $optionalParams['pageAddress']; }
	
		return $this->sendMessage("pgr", $params, $validationErrorMsg);
	}

	/*
	* Sends an User Information message to Kontagent.
	*
	* @param string $userId The UID of the user
	* @param array $optionalParams An associative array containing paramName => value
	* @param int $optionalParams['birthYear'] The birth year of the user
	* @param string $optionalParams['gender'] The gender of the user (m,f,u)
	* @param string $optionalParams['country'] The 2-character country code of the user
	* @param int $optionalParams['friendCount'] The friend count of the user
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackUserInformation($userId, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array('s' => $userId);
		
		if ($optionalParams['birthYear']) { $params['b'] = $optionalParams['birthYear']; }
		if ($optionalParams['gender']) { $params['g'] = $optionalParams['gender']; }
		if ($optionalParams['country']) { $params['lc'] = strtoupper($optionalParams['country']); }
		if ($optionalParams['friendCount']) { $params['f'] = $optionalParams['friendCount']; }

		return $this->sendMessage("cpu", $params, $validationErrorMsg);
	}

	/*
	* Sends an Goal Count message to Kontagent.
	*
	* @param string $userId The UID of the user
	* @param array $optionalParams An associative array containing paramName => value
	* @param int $optionalParams['goalCount1'] The amount to increment goal count 1 by
	* @param int $optionalParams['goalCount2'] The amount to increment goal count 2 by
	* @param int $optionalParams['goalCount3'] The amount to increment goal count 3 by
	* @param int $optionalParams['goalCount4'] The amount to increment goal count 4 by
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackGoalCount($userId, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array('s' => $userId);
		
		if ($optionalParams['goalCount1']) { $params['gc1'] = $optionalParams['goalCount1']; }
		if ($optionalParams['goalCount2']) { $params['gc2'] = $optionalParams['goalCount2']; }
		if ($optionalParams['goalCount3']) { $params['gc3'] = $optionalParams['goalCount3']; }
		if ($optionalParams['goalCount4']) { $params['gc4'] = $optionalParams['goalCount4']; }
	
		return $this->sendMessage("gci", $params, $validationErrorMsg);
	}

	/*
	* Sends an Revenue message to Kontagent.
	*
	* @param string $userId The UID of the user
	* @param int $value The amount of revenue in cents
	* @param array $optionalParams An associative array containing paramName => value
	* @param string $optionalParams['type'] The transaction type (direct, indirect, advertisement, credits, other)
	* @param string $optionalParams['subtype1'] Subtype1 value (max 32 chars)
	* @param string $optionalParams['subtype2'] Subtype2 value (max 32 chars)
	* @param string $optionalParams['subtype3'] Subtype3 value (max 32 chars)
	* @param string $validationErrorMsg The error message on validation failure
	* 
	* @return bool Returns false on validation failure, true otherwise
	*/
	public function trackRevenue($userId, $value, $optionalParams = array(), &$validationErrorMsg = null) {
		$params = array(
			's' => $userId,
			'v' => $value
		);
		
		if ($optionalParams['type']) { $params['tu'] = $optionalParams['type']; }
		if ($optionalParams['subtype1']) { $params['st1'] = $optionalParams['subtype1']; }
		if ($optionalParams['subtype2']) { $params['st2'] = $optionalParams['subtype2']; }
		if ($optionalParams['subtype3']) { $params['st3'] = $optionalParams['subtype3']; }
	
		return $this->sendMessage("mtu", $params, $validationErrorMsg);
	}
}

////////////////////////////////////////////////////////////////////////////////

/*
* Helper class to validate the paramters for the Kontagent API messages
*/
class KtValidator
{
	/*
	* Validates a parameter of a given message type.
	*
	* @param string $messageType The message type that the param belongs to (ex: ins, apa, etc.)
	* @param string $paramName The name of the parameter (ex: s, su, u, etc.)
	* @param mixed $paramValue The value of the parameter
	* @param string $validationErrorMsg If the parameter value is invalid, this will be populated with the error message
	*
	* @returns bool Returns true on success and false on failure.

	*/
	public static function validateParameter($messageType, $paramName, $paramValue, &$validationErrorMsg = null) {
		// generate name of the dynamic method
		$methodName = 'validate' . ucfirst($paramName);
		
		if (!self::$methodName($messageType, $paramValue, $validationErrorMsg)) {
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateB($messageType, $paramValue, &$validationErrorMsg = null) {
		// birthyear param (cpu message)
		if (!filter_var($paramValue, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1900, 'max_range' => 2011)))) {
			$validationErrorMsg = 'Invalid birth year.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateF($messageType, $paramValue, &$validationErrorMsg = null) {
		// friend count param (cpu message)
		if(!filter_var($paramValue, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)))) {
			$validationErrorMsg = 'Invalid friend count.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateG($messageType, $paramValue, &$validationErrorMsg = null) {
		// gender param (cpu message)
		if (preg_match('/^[mfu]$/', $paramValue) == 0) {
			$validationErrorMsg = 'Invalid gender.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateGc1($messageType, $paramValue, &$validationErrorMsg = null) {
		// goal count param (gc1, gc2, gc3, gc4 messages)
		if (!filter_var($paramValue, FILTER_VALIDATE_INT, array('options' => array('min_range' => -16384, 'max_range' => 16384)))) {
			$validationErrorMsg = 'Invalid goal count value.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateGc2($messageType, $paramValue, &$validationErrorMsg = null) {
		return self::validateGc1($messageType, $paramValue, $validationErrorMsg);
	}
	
	private static function validateGc3($messageType, $paramValue, &$validationErrorMsg = null) {
		return self::validateGc1($messageType, $paramValue, $validationErrorMsg);
	}
	
	private static function validateGc4($messageType, $paramValue, &$validationErrorMsg = null) {
		return self::validateGc1($messageType, $paramValue, $validationErrorMsg);
	}
	
	private static function validateI($messageType, $paramValue, &$validationErrorMsg = null) {
		// isAppInstalled param (inr, psr, ner, nei messages)
		if (preg_match('/^[01]$/', $paramValue) == 0) {
			$validationErrorMsg = 'Invalid isAppInstalled value.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateIp($messageType, $paramValue, &$validationErrorMsg = null) {
		// ip param (pgr messages)
		if (!filter_var($paramValue, FILTER_VALIDATE_IP)) {
			$validationErrorMsg = 'Invalid ip address value.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateL($messageType, $paramValue, &$validationErrorMsg = null) {
		// level param (evt messages)
		if (!filter_var($paramValue, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)))) {
			$validationErrorMsg = 'Invalid level value.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateLc($messageType, $paramValue, &$validationErrorMsg = null) {
		// country param (cpu messages)
		if (preg_match('/^[A-Z]{2}$/', $paramValue) == 0) {
			$validationErrorMsg = 'Invalid country value.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateLp($messageType, $paramValue, &$validationErrorMsg = null) {
		// postal/zip code param (cpu messages)
		// this parameter isn't being used so we just return true for now
		return true;
	}
	
	private static function validateLs($messageType, $paramValue, &$validationErrorMsg = null) {
		// state param (cpu messages)
		// this parameter isn't being used so we just return true for now
		return true;
	}
	
	private static function validateN($messageType, $paramValue, &$validationErrorMsg = null) {
		// event name param (evt messages)
		if (preg_match('/^[A-Za-z0-9-_]{1,32}$/', $paramValue) == 0) {
			$validationErrorMsg = 'Invalid event name value.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateR($messageType, $paramValue, &$validationErrorMsg = null) {
		// Sending messages include multiple recipients (comma separated) and
		// response messages can only contain 1 recipient UID.
		if ($messageType == 'ins' || $messageType == 'nes' || $messageType == 'nts') {
			// recipients param (ins, nes, nts messages)
			if (preg_match('/^[0-9]+(,[0-9]+)*$/', $paramValue) == 0) {
				$validationErrorMsg = 'Invalid recipient user ids.';
				return false;
			}
		} elseif ($messageType == 'inr' || $messageType == 'psr' || $messageType == 'nei' || $messageType == 'ntr') {
			// recipient param (inr, psr, nei, ntr messages)
			if (!filter_var($paramValue, FILTER_VALIDATE_INT)) {
				$validationErrorMsg = 'Invalid recipient user id.';
				return false;
			}
		}
	
		return true;
	}
	
	private static function validateS($messageType, $paramValue, &$validationErrorMsg = null) {
		// userId param
		if (!filter_var($paramValue, FILTER_VALIDATE_INT)) {
			$validationErrorMsg = 'Invalid user id.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateSt1($messageType, $paramValue, &$validationErrorMsg = null) {
		// subtype1 param
		if (preg_match('/^[A-Za-z0-9-_]{1,32}$/', $paramValue) == 0) {
			$validationErrorMsg = 'Invalid subtype value.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateSt2($messageType, $paramValue, &$validationErrorMsg = null) {
		return self::validateSt1($messageType, $paramValue, $validationErrorMsg);
	}
	
	private static function validateSt3($messageType, $paramValue, &$validationErrorMsg = null) {
		return self::validateSt1($messageType, $paramValue, $validationErrorMsg);
	}

	private static function validateSu($messageType, $paramValue, &$validationErrorMsg = null) {
		// short tracking tag param
		if (preg_match('/^[A-Fa-f0-9]{8}$/', $paramValue) == 0) {
			$validationErrorMsg = 'Invalid short unique tracking tag.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateTs($messageType, $paramValue, &$validationErrorMsg = null) {
		// timestamp param (pgr message)
		if (!filter_var($paramValue, FILTER_VALIDATE_INT)) {
			$validationErrorMsg = 'Invalid timestamp.';
			return false;
		} else {
			return true;
		}
	}
	
	private static function validateTu($messageType, $paramValue, &$validationErrorMsg = null) {
		// type parameter (mtu, pst/psr, ucc messages)
		// acceptable values for this parameter depends on the message type
		if ($messageType == 'mtu') {
			if (preg_match('/^(direct|indirect|advertisement|credits|other)$/', $paramValue) == 0) {
				$validationErrorMsg = 'Invalid monetization type.';
				return false;
			}
		} elseif ($messageType == 'pst' || $messageType == 'psr') {
			if (preg_match('/^(feedpub|stream|feedstory|multifeedstory|dashboard_activity|dashboard_globalnews)$/', $paramValue) == 0) {
				$validationErrorMsg = 'Invalid stream post/response type.';
				return false;
			}
		} elseif ($messageType == 'ucc') {
			if (preg_match('/^(ad|partner)$/', $paramValue) == 0) {
				$validationErrorMsg = 'Invalid third party communication click type.';
				return false;
			}
		}
		
		return true;
	}
	
	private static function validateU($messageType, $paramValue, &$validationErrorMsg = null) {
		// unique tracking tag parameter for all messages EXCEPT pgr.
		// for pgr messages, this is the "page address" param
		if ($messageType != 'pgr') {
			if (preg_match('/^[A-Fa-f0-9]{32}$/', $paramValue) == 0) {
				$validationErrorMsg = 'Invalid unique tracking tag.';
				return false;
			}
		}
		
		return true;
	}
	
	private static function validateV($messageType, $paramValue, &$validationErrorMsg = null) {
		// value param (mtu, evt messages)
		if (!filter_var($paramValue, FILTER_VALIDATE_INT)) {
			$validationErrorMsg = 'Invalid value.';
			return false;
		} else {
			return true;
		}
	}
}
?>
