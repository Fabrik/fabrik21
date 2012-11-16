<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();


class TableSubscriptions extends JTable
{
	/** @var int Primary key */
	var $id = null;

	/** @var int user id */
	var $userid = null;

	/** @var bol users primary subscription */
	var $primary =null;

	/** @var string payment processor (e.g free, paypal, paypal_subscription) 	 */
	var $type = null;

	/** @var string Active/Cancelled/Closed/Excluded/Expired/Pending **/
	var $status = null;

	/** @var timedate */
	var $signup_date = null;

	/** @var timedate */
	var $lastpay_date = null;

	/** @var timedate */
	var $cancel_date = null;

	/** @var timedate */
	var $eot_date = null;

	/** @var int plan id */
	var $plan = null;

	/** @var bol */
	var $recurring = null;

	/** @var bol */
	var $lifetime = 1;

	/** @var timedate */
	var $expiration = null;

	/*
	 *
	 */

	function __construct( &$_db )
	{
		parent::__construct( 'fabsubs_subscriptions', 'id', $_db );
	}
}