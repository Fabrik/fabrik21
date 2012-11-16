<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();


class TablePlans extends JTable
{
	/** @var int Primary key */
	var $id = null;

	var $active = null;
	var $visible = null;
	var $ordering = null;
	var $plan_name = null;
	var $desc = null;
	var $email_desc = null;
	var $params = null;
	var $cost = null;
	var $currency = null;
	var $duration = null;
	var $period_unit = null;
	var $usergroup = null;
	var $fall_back_plan = null;

	/*
	 *
	 */

	function __construct( &$_db )
	{
		parent::__construct( 'fabsubs_plans', 'id', $_db );
	}

}