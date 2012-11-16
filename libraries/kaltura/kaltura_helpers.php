<?php
class KalturaHelpers
{

	/**
	 * get the url of the server hosting kaltura
	 * @return unknown_type
	 */
	function kalturaGetServerUrl()
	{
		return 'http://www.kaltura.com';
	}
	
	/**
	 * get a kaltura session user based on the logged in Joomla user
	 * @return object kaltura session user
	 */
	function kalturaGetSessionUser()
	{
		$user =& JFactory::getUser();
		$kaltura_user = new KalturaSessionUser();

		if ((int)$user->get('id') === 0) {
			$kaltura_user->userId = KALTURA_ANONYMOUS_USER_ID;
			return $kaltura_user;
		}

		$kaltura_user->userId = (int)$user->get('id');
		$kaltura_user->screenName = $user->get('name');
		return $kaltura_user;
	}

	function getServiceConfiguration( $params = null )
	{
		static $config;
		if(!isset($config)){
			$partnerId = $params->get( 'kaltura_partnerid' );
			$subPartnerId = $params->get( 'kaltura_sub_partnerid' );
			$config = new KalturaConfiguration($partnerId, $subPartnerId);
			//$config->serviceUrl = str_replace('/administrator', '', JURI::base());
			$config->serviceUrl = KalturaHelpers::kalturaGetServerUrl();
			$config->setLogger(new KalturaJoomlaLogger());
		}
		return $config;
	}


	////wordpress stuff;
	function getContributionWizardFlashVars($ks, $kshowId)
	{
		$sessionUser = KalturaHelpers::kalturaGetSessionUser();
		$config = KalturaHelpers::getServiceConfiguration();

		$flashVars = array();

		$flashVars["userId"] = $sessionUser->userId;
		$flashVars["sessionId"] = $ks;
		if ($sessionUser->userId == KALTURA_ANONYMOUS_USER_ID) {
			$flashVars["isAnonymous"] = true;
		}
			
		$flashVars["partnerId"] 	= $config->partnerId;
		$flashVars["subPartnerId"] 	= $config->subPartnerId;
		$flashVars["kshow_id"] 		= $kshowId;
		$flashVars["afterAddentry"] = "onContributionWizardAfterAddEntry";
		$flashVars["close"] 		= "onContributionWizardClose";
		$flashVars["terms_of_use"] 	= "http://corp.kaltura.com/static/tandc" ;

		return $flashVars;
	}

	function getSimpleEditorFlashVars($ks, $kshowId)
	{
		$config = KalturaHelpers::getServiceConfiguration();
		$sessionUser = KalturaHelpers::kalturaGetSessionUser();

		$flashVars = array();

		$flashVars["entry_id"] 		= -1;
		$flashVars["kshow_id"] 		= $kshowId;
		$flashVars["partner_id"] 	= $config->partnerId;;
		$flashVars["subp_id"] 		= $config->subPartnerId;
		$flashVars["uid"] 			= $sessionUser->userId;
		$flashVars["ks"] 			= $ks;
		$flashVars["backF"] 		= "onSimpleEditorBackClick";
		$flashVars["saveF"] 		= "onSimpleEditorSaveClick";

		return $flashVars;
	}

	function getKalturaPlayerFlashVars($ks, $kshowId = -1, $entryId = -1)
	{
		$config = KalturaHelpers::getServiceConfiguration();
		$sessionUser = KalturaHelpers::kalturaGetSessionUser();

		$flashVars = array();

		$flashVars["kshowId"] 		= $kshowId;
		$flashVars["entryId"] 		= $entryId;
		$flashVars["partner_id"] 	= $config->partnerId;
		$flashVars["subp_id"] 		= $config->subPartnerId;
		$flashVars["uid"] 			= $sessionUser->userId;
		$flashVars["ks"] 			= $ks;

		return $flashVars;
	}

	function getTinyPlayerFlashVars($ks, $kshowId) {
		$sessionUser = KalturaHelpers::kalturaGetSessionUser();
		$flashVars = KalturaHelpers::getKalturaPlayerFlashVars($ks, $kshowId, -1);
		$flashVars["layoutId"] = "playerOnly";
		return $flashVars;
	}

	function flashVarsToString($flashVars)
	{
		$flashVarsStr = "";
		foreach($flashVars as $key => $value)
		{
			$flashVarsStr .= ($key . "=" . $value . "&");
		}
		return substr($flashVarsStr, 0, strlen($flashVarsStr) - 1);
	}

	function getSwfUrlForBaseWidget($type)
	{
		$player = KalturaHelpers::getPlayerByType($type);
		return KalturaHelpers::kalturaGetServerUrl() . "/index.php/kwidget/wid/" . $player["uiConfId"];
	}

	function getSwfUrlForWidget($widgetId)
	{
		return KalturaHelpers::kalturaGetServerUrl() . "/index.php/kwidget/wid/" . $widgetId;
	}

	function getContributionWizardUrl($uiConfId)
	{
		return KalturaHelpers::kalturaGetServerUrl() . "/kse/ui_conf_id/" . $uiConfId;
	}

	function getSimpleEditorUrl($uiConfId)
	{
		return KalturaHelpers::kalturaGetServerUrl() . "/kcw/ui_conf_id/" . $uiConfId;
	}

	function userCanEdit($override = null) {
		global $current_user;

		$roles = array();
		foreach($current_user->roles as $key => $val)
		$roles[$val] = 1;

		if ($override !== "0" && $override !== "1" && $override !== "2" && $override !== "3")
		$permissionsEdit = @get_option('kaltura_permissions_edit');
		else
		$permissionsEdit = $override;

		// note - there are no breaks in the switch (code should jump to next case)
		switch($permissionsEdit)
		{
			case "0":
				return true;
			case "1":
				if (@$roles["subscriber"])
				return true;
			case "2":
				if (@$roles["editor"])
				return true;
				else if (@$roles["author"])
				return true;
				else if (@$roles["contributor"])
				return true;
			case "3":
				if (@$roles["administrator"])
				return true;
		}

		return false;
	}

	function userCanAdd($override = null) {
		global $current_user;

		$roles = array();
		foreach($current_user->roles as $key => $val)
		$roles[$val] = 1;

		if ($override !== "0" && $override !== "1" && $override !== "2" && $override !== "3")
		$permissionsAdd = @get_option('kaltura_permissions_add');
		else
		$permissionsAdd = $override;
			
		// note - there are no breaks in the switch (code should jump to next case)
		switch($permissionsAdd)
		{
			case "0":
				return true;
			case "1":
				if (@$roles["subscriber"])
				return true;
			case "2":
				if (@$roles["editor"])
				return true;
				else if (@$roles["author"])
				return true;
				else if (@$roles["contributor"])
				return true;
			case "3":
				if (@$roles["administrator"])
				return true;
		}
		return false;
	}

	function anonymousCommentsAllowed()
	{
		return @get_option("kaltura_allow_anonymous_comments") == true ? true : false;
	}

	function videoCommentsEnabled()
	{
		return @get_option("kaltura_enable_video_comments") == true ? true : false;
	}

	function getThumbnailUrl($widgetId = null, $entryId = null, $width = 240, $height= 180, $version = 100000)
	{
		$config = KalturaHelpers::getServiceConfiguration();
		$url = kalturaGetCdnUrl();
		$url .= "/p/" . $config->partnerId;
		$url .= "/sp/" . $config->subPartnerId;
		$url .= "/thumbnail";
		if ($widgetId)
		$url .= "/widget_id/" . $widgetId;
		else if ($entryId)
		$url .= "/entry_id/" . $entryId;
		$url .= "/width/" . $width;
		$url .= "/height/" . $height;
		$url .= "/type/2";
		$url .= "/bgcolor/000000";
		if ($version !== null)
		$url .= "/version/" . $version;
		return $url;
	}

	function getCommentPlaceholderThumbnailUrl($widgetId = null, $entryId = null, $width = 240, $height= 180, $version = 100000)
	{
		$url = KalturaHelpers::getThumbnailUrl($widgetId, $entryId, $width, $height, $version);
		$url .= "/crop_provider/wordpress_comment_placeholder";
		return $url;
	}

	function compareWPVersion($compareVersion, $operator)
	{
		global $wp_version;

		return version_compare($wp_version, $compareVersion, $operator);
	}

	function addWPVersionJS()
	{
		global $wp_version;
		echo("<script type='text/javascript'>\n");
		echo('var Kaltura_WPVersion = "' . $wp_version . '";'."\n");
		echo('var Kaltura_PluginUrl = "' . kalturaGetPluginUrl() . '";'."\n");
		echo("</script>\n");
	}

	function getPlayers()
	{
			return  array (
		"whiteblue" => 
			array(
				"name" => "White/Blue", 
				"uiConfId" => 520,
				"horizontalSpacer" => 10,
				"verticalSpacer" => 64,
				"videoAspectRatio" => "4:3",
				"previewHeaderColor" => "#000"
			),
		"dark" => 
			array(
				"name" => "Dark", 
				"uiConfId" => 521,
				"horizontalSpacer" => 10,
				"verticalSpacer" => 64,
				"videoAspectRatio" => "4:3",
				"previewHeaderColor" => "#fff"
			),
		"grey" => 
			array(
				"name" => "Grey", 
				"uiConfId" => 522,
				"horizontalSpacer" => 10,
				"verticalSpacer" => 64,
				"videoAspectRatio" => "4:3",
				"previewHeaderColor" => "#31302E"
			)
	);
	}

	function getPlayerByType($type)
	{
		$players = KalturaHelpers::getPlayers();
		if (array_key_exists($type, $players))
		{
			$player = $players[$type];
		}
		else
		{
			$player = $players[get_option('kaltura_default_player_type')];
		}

		return $player;
	}

	function calculatePlayerHeight($type, $width)
	{
		$player = KalturaHelpers::getPlayerByType($type);

		$aspectRatio = (@$player["videoAspectRatio"] ? $player["videoAspectRatio"] : "4:3");
		$hSpacer = (@$player["horizontalSpacer"] ? $player["horizontalSpacer"] : 0);
		$vSpacer = (@$player["verticalSpacer"] ? $player["verticalSpacer"] : 0);

		switch($aspectRatio)
		{
			case "4:3":
				$screenHeight = ($width - $hSpacer) / 4 * 3;
				$height = $screenHeight + $vSpacer;
				break;
			case "16:9":
				$screenHeight = ($width - $hSpacer) / 16 * 9;
				$height = $screenHeight + $vSpacer;
				break;
		}

		return round($height);
	}

	function runKalturaShortcode($content, $callback)
	{
		global $shortcode_tags;

		// we will backup the shortcode array, and run only our shortcode
		$shortcode_tags_backup = $shortcode_tags;

		add_shortcode('kaltura-widget', $callback);
			
		$content = do_shortcode($content);

		// now we can restore the original shortcode list
		$shortcode_tags = $shortcode_tags_backup;
	}

	function dieWithConnectionErrorMsg()
	{
		echo '
		<div class="error">
			<p>
				<strong>Your connection has failed to reach the Kaltura servers. Please check if your web host blocks outgoing connections and then retry.</strong>
			</p>
		</div>';
		die();
	}
}

class KalturaJoomlaLogger implements IKalturaLogger
{
	function log($str)
	{
		//print ($str . "<br />\n");
	}
}
?>