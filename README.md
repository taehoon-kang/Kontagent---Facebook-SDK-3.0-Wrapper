Overview
-----------------

This is Kontagents wrapper around Facebook's 3.0 SDK. It provides the functionality of the original FB library. In addition, it also automatically fires off the relevant messages to Kontagent.

Getting Started
-----------------

To get started, make sure you've included and instantiated the libraries. You MUST include AND instantiate both PHP and JS libraries on all pages you want to track. The order in which you include the libraries matters.

    <?php
        require_once './kontagent/kontagent_config.php';

        require_once './facebook-php-sdk/src/facebook.php';
        require_once './kontagent/kontagent_facebook.php';
        
        $ktFacebook = new KontagentFacebook(array(
            'appId'  => '',
            'secret' => '',
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
                    appId  : '',
                    status : true, // check login status
                    cookie : true, // enable cookies to allow the server to access the session
                    xfbml  : true, // parse XFBML
                    channelUrl : 'http://www.andy.com/channel.html', // channel.html file
                    oauth  : true // enable OAuth 2.0
                });
            </script>
        </body>
    </html>

Tracking Installs
-----------------

To track installs, you simply need to prompt for authorization with the Facebook library.

In PHP, simply redirect them to the login page:

    $KontagentFacebook->getLoginUrl();

Or in JavaScript:

    KT_FB.login(function(response) {
        console.log(response);
    }, {scope: 'email, user_birthday'});

Both of these methods are compliant with Facebooks new OAuth 2.0 standards.

Tracking Invites
-----------------

To track invites, you simply need to display the Requests Dialog to the user (see FB documentation for more info).

In PHP, simply redirect them to:

    $KontagentFacebook->getRequestsDialogUrl(array(
        'message' => 'do it!', 
        'subtype1' => 'st1',
        'subtype2' => 'st2'
    ));

Or in JavaScript:

    KT_FB.ui(
        {
            "method": "apprequests", 
            "message": "You should learn more about this awesome game.", 
            "data": "tracking information for the user",
            "subtype1": "st1",
            "subtype2": "st2"
        },
        function(response) {
            console.log(response);
        }
    );

Tracking Stream Posts
-----------------

To track Stream Posts, you simply need to display the Feed Dialog to the user (see FB documentation for more info). Make sure to include a link back to your application.

In PHP, simply redirect them to:

    $KontagentFacebook->getRequestsDialogUrl(array(
        'link' => 'http://yourapp.facebook.com'
        'subtype1' => 'st1',
        'subtype2' => 'st2'
    ));

Or in JavaScript:

    KT_FB.ui(
        {
            "method": "feed", 
            "link": "http://yourapp.facebook.com", 
            "subtype1": "st1",
            "subtype2": "st2"
        },
        function(response) {
            console.log(response);
        }
    );

Tracking Other Methods
-----------------

To send other tracking methods to Kontagent (such as custom events, goal counts, etc.) you can retrieve the Kontagent API object. This object provides a method to fire off all the message types supported by Kontagent.

In PHP:

    $KontagentFacebook->getKontagentApi();

Or in JavaScript:

    KontagentFacebook.ktApi;



