<?php
$errors = array();
if(isset($_POST['login'])){
	$username = preg_replace('/[^A-Za-z]/', '', $_POST['username']);
	$email = $_POST['email'];
	$password = $_POST['password'];
	$c_password = $_POST['c_password'];
	
	if(file_exists('users/' . $username . '.xml')){
		$errors[] = 'Username already exists';
	}
	if($username == ''){
		$errors[] = 'Username is blank';
	}	
	if($email == ''){
		$errors[] = 'Email is blank';
	}
	if($password == '' || $c_password == ''){
		$errors[] = 'Passwords are blank';
	}
	if($password != $c_password){
		$errors[] = 'Passwords do not match';
	}
	if(count($errors) == 0){
		$xml = new SimpleXMLElement('<user></user>');
		$xml->addChild('password', hash("sha256",$password));
		$xml->addChild('email', $email);
		$xml->asXML('users/' . $username . '.xml');
		header('Location: welcome.php');
		die();
	}
}
?>

<html>
	<head>
		<title>Register</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<script src="js/jquery-1.6.2.min.js" type="text/javascript"></script>
	    <script type="text/javascript" src="js/loadData.js"></script>
	    <link rel="stylesheet" href="css/visual.css" type="text/css" media="screen" />        
	</head>
		<body>
		
	        <div id="container">

	            <div id="header">
	                <div class="main-title">
	                	<img src="images/logo.png">
	                </div>
	            </div>
							                       
	            <div id="login" class="enter">
	            
		            <div class="login-box" style="width:350px; background-image: url('images/table-images/cell-blue-register.jpg');">
		            		
						<h1 class="login-header">Register</h1>
						<form method="post" action="">
							<p style="margin-left: 10px">Username <input type="text" name="username" size="18"/></p>
							<p style="margin-left: 10px">Email <input type="text" name="email" size="18"/></p>
							<p style="margin-left: 10px">Password <input type="password" name="password" size="18"/></p>						
							<p style="margin-left: 10px">Confirm Password <input type="password" name="c_password" size="18" /></p>
							<p style="margin-left: 10px; color: gray; font-size: 12px">For username use letters only</p>
							<?php
							
							  if(count($errors) > 0){
								echo '<ul>';
								foreach($errors as $e){
									echo '<li>' . $e . '</li>';
								}
								echo '</ul>';
							  }
							 				
							?>
							<input type="image" value="Register" src="images/Register.png" name="login" alt="Submit" style="margin-left: 50px"/>
					
							<br></br>							
						</form>
												                          
		            </div>
		            <div style="text-align:center; padding-top:20px;">
		            	<img src="images/DIMES.gif">	
		            </div>
	            </div>
	            
	            <?php include("info.php") ?>
	            
	            <div class="footer">
                Copyright © 2011 <a href="http://www.netdimes.org/new/">DIMES</a>
            </div>
            
         </div>
    </body>
</html>