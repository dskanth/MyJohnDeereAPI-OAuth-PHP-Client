<?php
session_start();
$config = include_once 'config.php';

$renew_token = 0;
// If access token exists, check its validity (1 year by default)
if(isset($config['access_token']) && $config['access_token'] != '' && isset($config['token_issue_date']) && $config['token_issue_date'] != '') {
	$token_issue_date = new DateTime($config['token_issue_date']);
	$token_issue_date->add(new DateInterval('P365D'));
	
	$today_date = new DateTime();
	if ( $token_issue_date <= $today_date ) {
	  $renew_token = 1;
	}
}
	
if(isset($_SESSION['account_name']) && $_SESSION['account_name'] != '' && isset($config['access_token']) && $renew_token == 0) {
	header("Location: home.php");
}
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
	#fetch_access_token { color: green; padding: 10px; font-weight: bold; }
	#fetching_token { display: none; font-size: 19px; color: green; }
	</style>
  </head>

  <body>
  
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
            </ul>
			
			<ul class="nav navbar-nav navbar-right">
            </ul>
          </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
      </nav>

    </div> <!-- /container -->
	
	<div class="container">
      <div class="page-header">
        <h2>Welcome user!</h2>
		<?php if(!isset($config['access_token']) || $config['access_token'] == '' || $renew_token == 1) { ?>
		<h5><input type="button" name="fetch_access_token" id="fetch_access_token" value="Fetch Access Token" onClick="fetch_access_token_and_secret()">
		&nbsp;&nbsp;&nbsp;<span id="fetching_token">Please wait...</span><br></h5>
		<?php } else {
			header("Location: home.php");
		}
		?>
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
	<script>
	function fetch_access_token_and_secret() {
		jQuery.ajax({
			  url: 'fetch_access_token_value.php',
			  type: 'POST',
			  data: { request_token_date: '<?php echo date("m/d/Y"); ?>', 'renew_token': '<?php echo $renew_token; ?>' },
			  beforeSend: function() {
				  jQuery("#fetch_access_token").prop('disabled', true);
				  jQuery("#fetch_access_token").css("color", "grey");
				  jQuery("#fetching_token").show();
			  },
			  complete: function() {
				  jQuery("#fetching_token").hide();
			  },
			  success: function(data) {
					//alert(data); return false;
					
				  	if(data != '') {
							jQuery("#fetch_access_token").prop('disabled', false);
							jQuery("#fetch_access_token").css("color", "green");
							window.location = 'home.php';
						}
						else {
							alert("Oops, an error occurred in fetching Access Token. Please try later");
						}
			  },
			  timeout: 120000, // 120 seconds
			  error: ajaxError // handle error
		});
	}
	function ajaxError(request, type, errorThrown) {
		var message = "Oops…\n"; 
		switch (type) {
		case 'timeout':
		message += "The request timed out.";
		break;
		case 'notmodified':
		message += "The request was not modified but was not retrieved from the cache.";
		break;
		case 'parsererror':
		message += "XML/Json format is bad.";
		break;
		default:
		message += "HTTP Error (" + request.status + " " + request.statusText + ").";
		}
		message += "\n";
		alert(message);
	}
	</script>
  </body>
</html>
