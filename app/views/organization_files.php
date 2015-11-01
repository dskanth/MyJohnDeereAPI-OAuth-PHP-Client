<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    
    <title>Easy Setup App</title>

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
	
	<style type="text/css">
	#file_details { margin: 5px; float: left; }
	.content_block { margin-top: 20px; font-weight: bold; font-size: 14px; color: green; display: table; }
	#back_link { float:right; margin:10px; display: none; }
	.navbar, .footer { display: none; }
	.file_desc { margin: 10px; float: left; }
	.file_desc table { min-width: 200px; margin: 10px; float: left; padding: 10px; border: 1px solid #ccc; }
	.file_desc table td { padding: 5px; }
	</style>
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
          <a class="navbar-brand" href="#">Easy Setup App</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="<?php echo URL::to('/'); ?>">Home</a></li>
            <li class="active"><a href="<?php echo URL::to('/'); ?>/organizations">Organizations</a></li>
			<li><a href="<?php echo URL::to('/'); ?>/partnerships">Partnerships</a></li>
			<li><a href="<?php echo URL::to('/'); ?>/upload_file">Upload File</a></li>
			<li><a href="<?php echo URL::to('/'); ?>/status">Status</a></li>
          </ul>
		  
		  <ul class="nav navbar-nav navbar-right">
            <li>
			<?php if($user_name == '') { ?>
			<a href="<?php echo URL::to('/'); ?>/user_oauth">Oauth</span></a>
			<?php } else { ?>
			<a href="<?php echo URL::to('/'); ?>/logout">Logout</span></a>
			<?php } ?>
			</li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Begin page content -->
    <div class="container">
      <!--<div class="page-header">
        <h1>Easy Setup App</h1>
      </div>
      <p><b>Welcome <?php echo $user_name; ?>!</b></p>-->
		<div class="content_block">
		<?php
		echo 'Files found in <i>"'.$org_name.'"</i> Organization: '.$org_file_count.'<br><br>';
		
		if($org_file_count > 0) {
			echo '<div id="file_details">';
			
			foreach($org_files as $file) {
				echo '<div class="file_desc" id="file_'.$file['id'].'">';
				echo '<table><tr><td>ID</td><td>'.$file['id'].'</td></tr>';
				echo '<tr><td>Name</td><td>'.$file['name'].'</td></tr>';
				echo '<tr><td>Type</td><td>'.$file['type'].'</td></tr>';
				echo '<tr><td>Size</td><td>'.sprintf('%0.2f', ($file['nativeSize'] / 1024)).' KB</td></tr>';
				echo '<tr><td>&nbsp;</td><td><a href="'.URL::to('/').'/files/'.$file['id'].'/download" target="_blank">Download</a></td></tr></table>
				</div>';
			}
			echo '</div>';
		}
		?>
		</div>
		
		<?php 
		//$current_url_path = Route::getCurrentRoute()->getPath();
		//if (stripos($current_url_path, 'view_partner_files') !== false) {
		?>
		<div id="back_link"><a href="javascript:history.go(-1);" title="Go Back">Back</a></div>
		<?php
		//}
		?>
    </div>

    <footer class="footer">
      <div class="container">
        <p class="text-muted">Easy Setup App</p>
      </div>
    </footer>


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="<?php echo URL::to('/'); ?>/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="<?php echo URL::to('/'); ?>/js/ie10-viewport-bug-workaround.js"></script>
	<script>
	if( window.self !== window.top ) {
		$("#back_link").show();
		$(".navbar").hide();
		$(".footer").hide();
	}
	else {
		$(".navbar").show();
		$(".footer").show();
	}
	</script>
  </body>
</html>