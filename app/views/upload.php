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
            <li><a href="<?php echo URL::to('/'); ?>">Home</a></li>
            <li class="active"><a href="#">Create File</a></li>
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
			<p><b>Note: Login to use MyJohnDeere API fully.</b></p>
		<?php } else {
			$org_name = 'APITestOrg_'.Session::get('deere_user_id');
			?>
			<h4>Upload File</h4>
			<br><br>
			<?php
			if (Session::has('upload_msg')) {
			   echo Session::get('upload_msg').'<br><br>';
			}
			?>
			<div style="font-weight: bold; font-size: 14px; color: green; min-height: 250px;">
				<?php
				echo Form::open(array('url'=>'file-upload','files'=>true, 'onsubmit' => 'return validate_file_upload(this);'));
				
				echo 'Organization: <select id="user_orgn" name="user_orgn" style="height:40px;">';
				foreach($deere_user_orgs as $user_org) {
					if($user_org['member'] == 1) {
						echo '<option value="'.$user_org['id'].'">'.$user_org['name'].'</option>';
					}
				}
				echo '</select><br><br>';
				echo 'File: ';
				echo Form::file('file','',array('id'=>'','class'=>''));
				echo '<br><br>';

				echo Form::submit('Save');
				echo '&nbsp;&nbsp;';
				echo Form::reset('Reset');

				echo Form::close();
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
		return true;
	}
	</script>
  </body>
</html>