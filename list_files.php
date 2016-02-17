<?php
session_start();
include_once 'deere_service.php';

// Fetch API Catalog and store it in session
if(!isset($_SESSION['catalog_urls'])) {
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
	<link rel="stylesheet" href="css/colorbox.css" />

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="js/ie-emulation-modes-warning.js"></script>
	<script>
	</script>
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
	<style type="text/css">
	#user_orgs_list { margin: 30px 0; }
	#user_orgs_list td { padding:10px; border: 1px solid #ccc; }
	.user_name { margin-top: 15px; font-style: italic; }
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
              <li><a href="create_file.php">Create File</a></li>
              <li class="active"><a href="javascript:void(0)">List Files</a></li>
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
        <h2>View Files</h2>
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
		
		$user_orgs = $_SESSION['user_orgs'];
		if(count($user_orgs) > 0) {
		?>
			<div id="orgs_list">
			<table id="user_orgs_list">
			<tr><td><b>ID</b></td><td><b>Organization</b></td><td><b>View Files</b></td></tr>
			<?php
			foreach($user_orgs as $user_org) {
				if($user_org['member'] == 1) {
			?>	
				<tr><td><?php echo $user_org['id']; ?></td><td><?php echo $user_org['name']; ?></td>
				<td><a class="iframe" href="view_org_files.php?org_id=<?php echo $user_org['id']; ?>">View</a></td></tr>
			<?php
				}
			}
			?>
			</table>
			</div>
		<?php } ?>
		
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
	<script src="js/jquery.colorbox.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="js/ie10-viewport-bug-workaround.js"></script>
	<script>
		jQuery(document).ready(function() {
			jQuery(".iframe").colorbox({iframe:true, escKey:true, opacity: 0.2, width:"90%", height:"90%", fastIframe:false});
			});
	</script>
  </body>
</html>