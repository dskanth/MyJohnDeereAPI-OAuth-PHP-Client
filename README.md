# MyJohnDeereAPI-OAuth-PHP-New-Client
A web site that demonstrates the functionalities of the MyJohnDeere API.
## Requirements
Requires PHP 7 or newer.  This code is **not** compatible with PHP 5.<br>
Requires the PHP cURL extension to be installed and enabled.  (Check php.ini on your server.)<br>
Tested with PHP 7.0.6 on Apache 2.4, Windows 7 x86_64.
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