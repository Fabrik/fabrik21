<?php
require_once ( "includes.php" );

$uid = getP ( "uid" );
$submitted = getP ( "submitted");
$kshow_id = getP ( "kshow_id" );
$error = null;

$back_url = "http" . ( @$_SERVER['HTTPS'] == "on" ? "s" : "" ) . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; 

?>

<html>
<head>
<link rel="stylesheet" type="text/css" href="./kaltura.css"/>
<script type='text/javascript' src="./kaltura.js"></script>
<script type='text/javascript'>
function gotoCW ( kshow_id ) 
{  
	kalturaInitModalBox ( "./contributionwizard.php?uid=<? echo $uid ?>&kshow_id=" + kshow_id ) ;
}

function gotoEditor ( kshow_id ) 
{ 
	alert ( "Editor - Will be implemented in the near future." );
	return;
}

</script>
	
</head>
<body>
<div style="font-family:verdana; font-size: 13px; width: 80%;">
<a href="./startkshow.php?uid=<? echo $uid ?>">Back to Start Kaltura</a>
<br />
<h2>Widget Page</h2>
<? echo $error ?>
Widget for <? echo $kshow_id ?>:<br /><br/>

<?
$widget_html = createGenericWidgetHtml ( $kshow_id , $uid  ); 
?>
<div style="">
<? echo $widget_html ?>
</div>
<div>
<br><br>
There is a php function that helps create the widget:<br>
<pre style='background-color:lightyellow;'>
$widget_html = createGenericWidgetHtml ( $kshow_id , $uid  ); 
</pre>

<br>
In a page hosting the widget, there must be some javascript functions to open the Contribution Wizard and Editor:
	<pre class='kalturaCode' style='background-color:lightgray;'>
function gotoCW ( kshow_id ) 
{  
	kalturaInitModalBox ( "./contributionwizard.php?uid=<? echo $uid ?>&kshow_id=" + kshow_id ) ;
}

function gotoEditor ( kshow_id ) 
{ 
	alert ( "Editor - Will be implemented in the near future." );
	return;
}</pre>
   	
</div>
</div>
</body>
</html>
