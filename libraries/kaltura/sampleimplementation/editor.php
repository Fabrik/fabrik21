<?php
require_once ( "includes.php" );

$uid = getP ( "uid" );
$kshow_id = getP ( "kshow_id" );
$ks = getP ( "ks" );
$back_url = "";//getP ( "back_url");

$kaltura_user = new kalturaUser();
$kaltura_user->puser_id=$uid;
$kaltura_user->puser_name=$uid;

	
$domain = $SERVER_HOST;

$logo_url = ""; 
$btn_txt_back = "Back"; 
$btn_txt_publish = "Publish";

$partner_name = "Sample Implementation";
									
if ( ! $ks )
{
	$kaltura_service = kalturaService::getInstance( $kaltura_user );
	$ks = $kaltura_service->getKs();
}


		    $editor_params = array( "partner_id" => $partner_id , 
		    						"subp_id" => $subp_id , 
		    						"uid" => $uid , 
		    						"ks" => $ks ,
		    						"kshow_id" => $kshow_id ,
		    						"logo_url" => $logo_url , 
		    						"btn_txt_back" => $btn_txt_back , 
		    						"btn_txt_publish" => $btn_txt_publish ,
									"back_url" => $back_url ,
									"partner_name" => $partner_name );

			$editor_params_str = http_build_query( $editor_params , '' , "&" )		;		
							
			$editor_url = $domain . "/kaltura_dev.php/edit?$editor_params_str";
			
		// instead of redirecting - open editro in current special page
//			$iframe_html = "<iframe src='$editor_url' width='100%' height='800px'></iframe>";

	header("Location: $editor_url");

?>