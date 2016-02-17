<?php
session_start();
include_once 'deere_service.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="images/favicon.ico">

    <title>MyJD API</title>

    <!-- Bootstrap core CSS -->
    <link href="css/bootstrap_min.css" rel="stylesheet">

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="css/ie10-viewport-bug-workaround.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/navbar.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="js/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
	<style type="text/css">
	#user_details td { padding:10px; border: 1px solid #ccc; }
	.user_name { margin-top: 15px; font-style: italic; }
	</style>
  </head>

  <body>
  
  <?php
	// Store the Access Token object in Session, that would later be used to make calls, under AbstractService.php/request() method
	
	// Fetch user info and store in session
	if(!isset($_SESSION['account_name']) || $_SESSION['account_name'] == '') {
		$result = json_decode( $deereService->request( '/users/@currentUser' ), true );
		//print_r($result); exit;
		$_SESSION['account_name'] = $result['accountName'];
		$_SESSION['user'] = $result['givenName'].' '.$result['familyName'];
		$_SESSION['user_type'] = $result['userType'];
		$_SESSION['access_token'] = $config['access_token'];
		$_SESSION['access_token_secret'] = $config['access_token_secret'];
	}
  ?>

    <div class="container">

      <!-- Static navbar -->
      <nav class="navbar navbar-default">
        <div class="container-fluid">
          <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
              <span class="sr-only">Toggle navigation</span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">MyJD API</a>
          </div>
          <div id="navbar" class="navbar-collapse collapse">
            <ul class="nav navbar-nav">
              <li class="active"><a href="javascript:void(0)">Home</a></li>
              <li><a href="create_file.php">Create File</a></li>
              <li><a href="list_files.php">List Files</a></li>
            </ul>
			
			<ul class="nav navbar-nav navbar-right">
				<li class="user_name"><?php echo $_SESSION['user']; ?></li>
				<!--<li>
				<?php //if($_SESSION['account_name'] != '') { ?>
				<a href="logout.php">Logout</span></a>
				<?php //} ?>
				</li>-->
            </ul>
          </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
      </nav>

    </div> <!-- /container -->
	
	<div class="container">
      <div class="page-header">
        <h2>Welcome <?php echo $_SESSION['user']; ?>!</h2>
		<br><br>
		<table id="user_details">
		<tr><td>Account Name</td><td><?php echo $_SESSION['account_name']; ?></td></tr>
		<tr><td>User Type</td><td><?php echo $_SESSION['user_type']; ?></td></tr>
		<tr><td>Access Token</td><td><?php echo $config['access_token']; ?></td></tr>
		<tr><td>Access Token Secret</td><td><?php echo $config['access_token_secret']; ?></td></tr>
		</table>
      </div>
		
    </div>
	
	<footer class="footer">
      <div class="container">
        <p class="text-muted">MyJD API</p>
      </div>
    </footer>


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="js/jquery_min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="js/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>
