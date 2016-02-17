<?php
session_start();
include_once 'deere_service.php';

// Fetch API Catalog and store it in session
if(!isset($_SESSION['catalog_urls']) || empty($_SESSION['catalog_urls'])) {
	$catalog_result = json_decode( $deereService->request( '/' ), true );
	$catalog_urls = array();
	foreach($catalog_result['links'] as $result) {
		$catalog_urls[$result['rel']] = $result['uri'];
	}
	$_SESSION['catalog_urls'] = $catalog_urls;
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
	<script>
	function validate_file_upload(upload_form) {
		var orgn_selected_val = jQuery("#user_orgn").val();
		var file_selected_val = jQuery('input[type=file]').val();
		if(!orgn_selected_val) {
			alert("Please select an organization");
			return false;
		}
		else if(!file_selected_val) {
			alert("Please select a file");
			return false;
		}
		jQuery("#upload_file").prop('disabled', true);
		jQuery("#reset_form").prop('disabled', true);
		jQuery("#upload_progress").html('Please wait');
		return true;
	}
	</script>
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
	<style type="text/css">
	.user_name { margin-top: 15px; font-style: italic; }
	#upload_progress { color:green; }
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
              <li><a href="home.php">Home</a></li>
              <li class="active"><a href="javascript:void(0)">Create File</a></li>
              <li><a href="list_files.php">List Files</a></li>
            </ul>
			
			<ul class="nav navbar-nav navbar-right">
			<li class="user_name"><?php echo $_SESSION['user']; ?></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div><!--/.container-fluid -->
      </nav>

    </div> <!-- /container -->
	
	<div class="container">
      <div class="page-header">
        <h2>Upload File</h2>
		<?php
		// Fetch user's organizations from session, else make a call to Organizations endpoint
		if(!isset($_SESSION['user_orgs']) || $_SESSION['user_orgs'] == '') {
			$user_organizations_endpoint = $_SESSION['catalog_urls']['organizations'];
			$org_result = json_decode( $deereService->request( $user_organizations_endpoint ), true );
			
			$user_orgs = array();
			
			if(isset($org_result['values']) && count($org_result['values']) > 0) {
				$user_orgs = $org_result['values'];
				
				// Store the user's organizations result in session
				$_SESSION['user_orgs'] = $user_orgs;
			}
		}
		?>
		
		<?php
		if(isset($_SESSION['user_msg']) && $_SESSION['user_msg'] != '') {
			echo '<br>'.$_SESSION['user_msg'].'<br><br>';
			$_SESSION['user_msg'] = '';
		}
		if(isset($_SESSION['uploaded_file_name']) && $_SESSION['uploaded_file_name'] != '') {
			// Attempt to delete the uploaded file in local storage
			@unlink($_SESSION['uploaded_file_name']);
			$_SESSION['uploaded_file_name'] = '';
		}
		?>
		<form method="POST" action="file_upload.php" accept-charset="UTF-8" onsubmit="return validate_file_upload(this);" enctype="multipart/form-data">
		Organization: 
		<select id="user_orgn" name="user_orgn" style="height:40px;">
		<?php
		$user_orgs = $_SESSION['user_orgs'];
		if(count($user_orgs) > 0) {
			foreach($user_orgs as $user_org) {
				if($user_org['member'] == 1) {
					echo '<option value="'.$user_org['id'].'">'.$user_org['name'].'</option>';
				}
			}
		}
		?>
		</select>
		<br><br>
		Zip File:
		<input name="file" type="file">
		<br><br>
		<input type="submit" value="Upload" id="upload_file">&nbsp;&nbsp;
		<input type="reset" value="Reset" id="reset_form">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span id="upload_progress"></span>
		</form>
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