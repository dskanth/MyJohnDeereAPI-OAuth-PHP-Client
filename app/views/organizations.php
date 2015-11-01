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
	<link rel="stylesheet" href="<?php echo URL::to('/'); ?>/css/colorbox.css" />

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="<?php echo URL::to('/'); ?>/js/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
	
	<style type="text/css">
	#user_orgs { float: left; }
	#user_orgs li { height: 30px; margin: 5px; padding: 5px; list-style-type: none; cursor: pointer; }
	.content_block { font-weight: bold; font-size: 14px; color: green; display: table; }
	#org_create_link, #back_link { float:right; margin:10px; }
	.org_details { display: none; margin-top: 10px; margin-left: 30px; float: left; }
	.org_details table { width: 500px; padding: 10px; border: 1px solid #ccc; }
	.org_details table td { padding: 10px; width: 50%; color: green; font-weight: bold; font-size: 14px; }
	#pagination_links { margin-left: 200px; margin-top: 100px; float: left; }
	a.iframe { border: 1px solid #ccc; padding: 5px; }
	</style>
	
	<script type="text/javascript">
	function get_org_info(org_id) {
		jQuery(".org_details").hide();
		jQuery(".user_org_li").css("border", "none");
		jQuery("li#"+org_id).css("border", "1px solid #ccc");
		jQuery("#org_"+org_id).show();
	}
	</script>
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
            <li><a href="<?php echo URL::to('/'); ?>/create_file">Create File</a></li>
			<li class="active"><a href="#">List Files</a></li>
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
        </div><!--/.nav-collapse -->
      </div>
    </nav>

    <!-- Begin page content -->
    <div class="container">
      <div class="page-header">
        <h1>MyJD Oauth</h1>
      </div>
      <p><b>Welcome <?php echo $user_name; ?>!</b></p>
		
		<div class="content_block">
		<h4>Select Organization to view Files</h4>
		<br><br>
		<?php
		if(count($deere_user_orgs) > 0) {
			echo '<div id="user_org_details">';
			echo '<ul id="user_orgs">';
			
			$start = 1; $end = '';
			
			$start_counter = $start;
			foreach($deere_user_orgs as $org) {
				if(trim($org['member']) == 1) {
					echo '<li id="'.$org['id'].'" class="user_org_li" onClick="get_org_info('.$org['id'].')">'.$start_counter.'.&nbsp;&nbsp;'.$org['name'].'</li>';
					$start_counter++;
				}
			}
			echo '</ul>';
			
			foreach($deere_user_orgs as $org) {
				if(trim($org['member']) == 1) {
				
				echo '<div class="org_details" id="org_'.$org['id'].'">';
				echo '<table><tr><td>ID</td><td>'.$org['id'].'</td></tr>';
				echo '<tr><td>Name</td><td>'.$org['name'].'</td></tr>';
				echo '<tr><td>Type</td><td>'.$org['type'].'</td></tr>';
				
				echo '<tr><td colspan="2" align="center"><a class="iframe" href="'.URL::to('/').'/organizations/'.$org['id'].'/'.$org['name'].'/files">List Files</a></td></tr>';
				
				echo '</table>
				</div>';
			}
			}
			echo '</div>';
		}
		?>
		</div>
		<div id="back_link"><a href="javascript:history.go(-1);" title="Go Back">Back</a></div>
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
	<script src="<?php echo URL::to('/'); ?>/js/jquery.colorbox.js"></script>
	<script>
			$(document).ready(function() {
				//Examples of how to assign the Colorbox event to elements
				$(".iframe").colorbox({iframe:true, escKey:true, opacity: 0.2, width:"60%", height:"70%", fastIframe:false});
				
				$("#org_create_link a").click(function() {
					$(this).colorbox({iframe:true, escKey:true, opacity: 0.2, width:"60%", height:"60%", fastIframe:false});
				});
			});
	</script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="<?php echo URL::to('/'); ?>/js/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>