<?php

define("KALTURA_SERVER_URL", "http://www.kaltura.com");
define("KALTURA_CDN_URL", "http://cdn.kaltura.com");
define("KALTURA_ANONYMOUS_USER_ID", "Anonymous");
define("KALTURA_KSE_UICONF", 502);
define("KALTURA_KCW_UICONF", 501);
define("KALTURA_KCW_UICONF_COMMENTS", 503);
define("KALTURA_KCW_UICONF_FOR_SE", 504);
define("KALTURA_THUMBNAIL_WIDGET", 523);
define("KalturaConvesionProfileFilter_ORDER_BY_CREATED_AT_ASC","+created_at");
define("KalturaConvesionProfileFilter_ORDER_BY_CREATED_AT_DESC","-created_at");
define("KalturaConvesionProfileFilter_ORDER_BY_PROFILE_TYPE_ASC","+profile_type");
define("KalturaConvesionProfileFilter_ORDER_BY_PROFILE_TYPE_DESC","-profile_type");
define("KalturaConvesionProfileFilter_ORDER_BY_ID_ASC","+id");
define("KalturaConvesionProfileFilter_ORDER_BY_ID_DESC","-id");
define("KalturaKShowFilter_ORDER_BY_CREATED_AT_ASC","+created_at");
define("KalturaKShowFilter_ORDER_BY_CREATED_AT_DESC","-created_at");
define("KalturaKShowFilter_ORDER_BY_VIEWS_ASC","+views");
define("KalturaKShowFilter_ORDER_BY_VIEWS_DESC","-views");
define("KalturaKShowFilter_ORDER_BY_RANK_ASC","+rank");
define("KalturaKShowFilter_ORDER_BY_RANK_DESC","-rank");
define("KalturaKShowFilter_ORDER_BY_ID_ASC","+id");
define("KalturaKShowFilter_ORDER_BY_ID_DESC","-id");

class kalturaModel extends JModel
{
	/**
	 * Constructor
	 */
	function __construct( $params )
	{
		$this->params =& $params;
	}

	/**
	 * get the kaltura client
	 * @param bol $isAdmin default = false
	 * @param $privileges
	 * @return unknown_type
	 */
	function getClient($isAdmin = false, $privileges = null)
	{
		// get the configuration to use the kaltura client
		$kalturaConfig = KalturaHelpers::getServiceConfiguration( $this->params );

		// inititialize the kaltura client using the above configurations
		$kalturaClient = new KalturaClient($kalturaConfig);
		// get the current logged in user
		$sessionUser = KalturaHelpers::kalturaGetSessionUser();
		if ($isAdmin)
		{
			$adminSecret = $this->params->get("kaltura_webservice_admin_secret");
			$result = $kalturaClient->startsession($sessionUser, $adminSecret, true, $privileges);
		}
		else
		{
			$secret = $this->params->get("kaltura_webservice_secret");
			$result = $kalturaClient->startsession($sessionUser, $secret, false, $privileges);
		}
		if (count(@$result["error"]))
		{
			return null;
		}
		else
		{
			// now lets get the session key
			$session = $result["result"]["ks"];

			// set the session so we can use other service methods
			$kalturaClient->setKs($session);
		}

		return $kalturaClient;
	}

	/**
	 * get a list of shows
	 * @param $kalturaAdminClient
	 * @param $pageSize
	 * @param $page
	 * @return unknown_type
	 */
	function getKshows($kalturaAdminClient, $pageSize, $page)
	{
		$sessionUser = KalturaHelpers::kalturaGetSessionUser();
					
		$filter = new KalturaKShowFilter();
		$filter->orderBy = KalturaKShowFilter_ORDER_BY_CREATED_AT_DESC;
		$result = $kalturaAdminClient->listKShows($sessionUser, $filter, true, $pageSize, $page);
		if(is_array($result)){
		return $result["result"];
		}
		return '';
	}
	
	/**
	 * delete a show
	 * @param $kalturaAdminClient
	 * @param $kshowId
	 * @return unknown_type
	 */
	function deleteKShow($kalturaAdminClient, $kshowId)
	{
		$sessionUser = KalturaHelpers::kalturaGetSessionUser();
		return $kalturaAdminClient->deleteKShow($sessionUser, $kshowId);
	}
	
	/**
	 * get a show
	 * @param $kalturaClient
	 * @param $kshowId
	 * @return unknown_type
	 */
	function getKshow($kalturaClient, $kshowId) 
	{
		$sessionUser = KalturaHelpers::kalturaGetSessionUser();
		$result = $kalturaClient->getKShow($sessionUser, $kshowId, true);
		return @$result["result"]["kshow"];
	}

}
?>