# MyJohnDeereAPI-OAuth-PHP-New-Client
A web site that demonstrates the functionalities of the MyJohnDeere API.


## Requirements
Requires PHP 7 or newer.  This code is **not** compatible with PHP 5.<br>
Requires the PHP cURL extension to be installed and enabled.  (Check php.ini on your server.)<br>
The user Apache runs under (typically '_www' or 'apache', try `ps aux | egrep '(apache|httpd)'` to find the username) will need write permission to your hosting directory since the sample code uses a textfile to store oAuth credentials.  If you cant get an Oauth Token, check write permissions<br>
Tested with PHP 7.0.6 on Apache 2.4, Windows 7 x86_64.
Tested with PHP 7.2.8 on Apache 2.4.18 Ubuntu 16.04


## Credentials
This app needs credentials from MyJohnDeere to run.  Credentials are stored in APICredentials.php
 * MyJohnDeere_API_URL -- the URL for the API catalog
 * App_Key -- your client key
 * App_Secret -- your client secret
 * Proxy -- a proxy server and port, if required.  For example, proxy.example.com:80
 * ProxyAuth -- a proxy username and password, if required.  For example, user1:password123<br>
All of the fields above except MyJohnDeere_API_URL can be set in index.php.<br>
*Warning*: all fields, including App_Secret and ProxyAuth, are stored in plain text.<br><br>
APICredentials.php is modified by the code itself to change settings.  Any changes you make to this file will be overwritten when the code saves credentials.  Modify the saveSettings() function in Header.php if you want to change the format of APICredentials.


### Mapping (August 2018)
This sample uses OpenLayers 3 to display georreferenced PNG images for Field Operations.
It also uses satellite imagery frng (MicroSoft).  Under current licensing, Bing imagery is free with a user license key, currently hosted at
<a href=”https://msdn.microsoft.com/en-us/library/ff428642.aspx”>Bing Key Instructions</a>


#### Debugging to console (August 2018)
This sample uses a chatty php function `debug_to_console`, located in Header.php, which publishes to console debugging messages.


