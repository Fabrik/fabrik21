<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();


class TableInvoices extends JTable
{
	/** @var int Primary key */
	var $id = null;

	/** @var int subscription id */
	var $subscr_id = null;

	/** @var string  */
	var $invoice_number =null;

	/** @var timedate */
	var $created_date = null;

	/** @var timedate */
	var $transaction_date = null;

	/** @var int gateway id (the payment processor selected */
	var $gateway_id = null;

	/** @var float invoice amount */
	var $amount = null;

	/** @var string currency code e.g. USD, GBP, EUR*/
	var $currency = null;

	/** @var int invoice paid */
	var $paid = 0;

	/** @var int paypal transaction id */
	var $pp_txn_id = null;

	/** @var int paypal payment amount */
	var $pp_payment_amount = null;

	/** @var int paypal payment_status */
	var $pp_payment_status = null;

	/** @var int paypal transaction type */
	var $pp_txn_type = null;

	/** @var int paypal fee */
	var $pp_fee = null;

	/*
	 *
	 */

	function __construct( &$_db )
	{
		parent::__construct( 'fabsubs_invoices', 'id', $_db );
	}

}