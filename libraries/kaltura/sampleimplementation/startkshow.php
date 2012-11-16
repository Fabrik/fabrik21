<?php
require_once ( "includes.php" );

$uid = getP ( "uid" );
$submitted = getP ( "submitted");
$error = null;
if ( ! $uid )
{
	$error = "Must have some user id to start a Kaltura";
}
if ( ! is_numeric( $uid ) )
{
	$error = "<b>For now</b> the user id must be numeric";
}

if( (!$error) && $submitted )
{
	$kaltura_user = new kalturaUser();
	$kaltura_user->puser_id=$uid;
	$kaltura_user->puser_name=$uid;
	
	$kaltura_service = kalturaService::getInstance( $kaltura_user );
	
	$params = array
		(
			"kshow_name" => "{$uid}'s Kaltura",
			"kshow_description" => "A test Kaltura by {$uid}",
			"kshow_tags" => "sample, nice" ,
		);
	
	$res = $kaltura_service->addkshow ( $kaltura_user , $params );

	$kshow = @$res["result"]["kshow"];
	if ( !$kshow )
	{
		// TODO - handle fatal error
		$error = "internal error" . "<pre>" . print_r ( $res , true ) ."</pre>";	
	}
	else
	{
		$kshow_id = $kshow["id"];
	}
}
?>

<html>
<body>
<!--   <a href="<? echo $SERVER_HOST ?>/index.php/partnerservices2/testme">Reference implementation</a> -->
<br>
<div style="font-family:verdana; font-size: 13px; width: 80%;">
<h2>Start a Kaltura</h2>
<? echo $error ?>


<form action="" method="get">
	User id: <input id="uid" name="uid" value="<? echo $uid ?>">
	<input type="hidden" id="submitted" name="submitted" value="submitted"> This is a user's id on the <b>partner's</b> envionemnt. 
	<br>
	By clicking this button, a new Kaltura will be created for the user you specified.
	<br>
	<button id="start" name="start" value="true" >Create Kaltura</button>
	
</form>



<? if ( (!$error) && $submitted ) { 
	echo "A Kaltura was created for user '{$uid}' with the id: {$kshow_id}<br>";	
	echo "<a href='./widgetpage.php?uid={$uid}&kshow_id={$kshow_id}'>View the widget</a>";
?>
<br><br>
What happened on the server side:<br>

There are some parameters that should be set before including the kaltura service:  
<pre style='background-color:lightyellow;'>
$partner_id=250;                                  <- Your partner_id 
$subp_id=25000;                                   <- An id representing the flavor of your application. Should be set to 100 x partner_id 
$secret="dae1be648b8a86d25adafdac2d32e8c3";       <- Your secret that matchs the partner_id
</pre>
All relevant helper functions and objects are availabe for the rest of the code by including 
<pre style='background-color:lightyellow;'>
require_once ( "kalturaapi_php5_lib.php");
require_once ( "kaltura_helpers.php");
</pre>
	
The code executed is the following:
<pre style='background-color:lightyellow;'>
$kaltura_user = new kalturaUser();
$kaltura_user->puser_id=$uid;
$kaltura_user->puser_name=$uid;

$kaltura_service = kalturaService::getInstance( $kaltura_user );

$params = array
	(
		"kshow_name" => "{$uid}'s Kaltura",
		"kshow_description" => "A test Kaltura by {$uid}",
		"kshow_tags" => "sample, nice" ,
	);

$res = $kaltura_service->addkshow ( $kaltura_user , $params );

$kshow = @$res["result"]["kshow"];	
if ( !$kshow )
{
	// TODO - handle fatal error
	$error = "internal error" ;
}
else
{
	$kshow_id = $kshow["id"];
}
</pre>
	
The result of the service is a php array holding required information about the created kshow.
<? 	
	echo "<pre style='background-color:lightgreen'>" . print_r ( $res , true ) ."</pre>";
} ?>

</div>
</body>
</html>