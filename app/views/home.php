<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    
    <title>MyJD Oauth</title>

    <!-- Bootstrap core CSS -->
    <link href="<?php echo URL::to('/'); ?>/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="<?php echo URL::to('/'); ?>/css/sticky-footer-navbar.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="<?php echo URL::to('/'); ?>/js/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <!-- Fixed navbar -->
    <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">MyJD Oauth</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="#">Home</a></li>
            <li><a href="<?php echo URL::to('/'); ?>/create_file">Create File</a></li>
			<li><a href="<?php echo URL::to('/'); ?>/list_files">List Files</a></li>
          </ul>
		  
		  <ul class="nav navbar-nav navbar-right">
            <li>
			<?php if($user_name == '') { ?>
			<a href="<?php echo URL::to('/'); ?>/user_oauth">Login</span></a>
			<?php } else { ?>
			<a href="<?php echo URL::to('/'); ?>/logout">Logout</span></a>
			<?php } ?>
			</li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>

    <!-- Begin page content -->
    <div class="container">
      <div class="page-header">
        <h1>MyJD Oauth</h1>
      </div>
      <p><b>Welcome <?php echo $user_name; ?>!</b></p>
		<?php if($user_name == '') { ?>
			<p>MyJohnDeere API Overview
			Find out how to set up your app profile and connect it with this API in the Get Started Guide.</p>
				 
			<p>About MyJohnDeere API
			With MyJohnDeere API, you can develop apps that allow farmers, dealers, organizations, and partners to access and share information on the MyJohnDeere portal via PCs, tablets, and smartphones. MyJohnDeere API uses the wireless data transfer capability provided by the combination of cloud services, machine telematics, and a JDLink subscription. With approval from the customer and John Deere, you can use this API to share data, transfer files to JDLink-enabled machines, and securely share files between MyJohnDeere organizations.</p>
		 
			<p><b>Note: Login to use MyJohnDeere API fully.</b></p>
		<?php } else { ?>
			<div style="font-weight: bold; font-size: 14px; color: green;">
			<?php
			//echo 'Session ID: '.$user_name.'<br><br>';
			
			echo 'Username: '.$user_name.'<br><br>';
			echo 'Name: '.$name.'<br><br>';
			echo 'Email: '.$email.'<br><br>';
			echo 'User Type: '.$deere_user_type.'<br><br>';
			?>
			</div>
		<?php } ?>
    </div>

    <footer class="footer">
      <div class="container">
        <p class="text-muted">MyJD Oauth</p>
      </div>
    </footer>


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="<?php echo URL::to('/'); ?>/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="<?php echo URL::to('/'); ?>/js/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>