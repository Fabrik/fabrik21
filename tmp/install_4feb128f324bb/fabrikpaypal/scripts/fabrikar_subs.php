<?php
defined('_JEXEC') or die('Restricted access');
?>

<?php
/**
 * Optional script for extending the Fabrik PayPal form plugin
 * @package fabrikar
 * @author Hugh Messenger
 * @copyright (C) Hugh Messenger
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

/*
 * In the PayPal form plugin settings, you can select a PHP script to use for custom IPN processing.
 * You should copy this file, and use it as your starting point.  You must not change the class name.
 * During IPN processing, the PayPal plugin will create an instance of this class, and if there is a method
 * named after the 'payment_status' or 'txn_type' specified by PayPal, with payment_status_ or txn_type_ prepended
 * (like payment_type_Completed), the plugin will call your method, passing it a reference to the current Fabrik tableModel,
 * the request params, the 'set_list' and 'err_msg'.
 *
 * The $tableModel allows you to access all the usual data about the table.  See the Fabrik code for details on
 * what you can do with this.
 *
 * The $request is just a copy of $_REQUEST, and will contain all the usual IPN keys/values PayPal send us,
 * like $request['pending_reason'] or $request['mc_shipping'] etc.
 *
 * The $set_list is an array, which contains the table updates the plugin is already going to make.  Array entries
 * will look like ...
 *    $set_list['your_status_field'] = 'Completed'
 * ... and you can change, add or remove as you see fit.  So if you have a custom field you want updated, just add
 * it to the arrays like ...
 *    $set_list['my_custom_field'] = "foo";
 * .... and the plugin will automatically add that to the UPDATE query for the row being processed.
 * (including Quote and nameQuote of fields)
 *
 * The $err_msg is used if you wish to abort processing by returning a status of something other than 'ok',
 * and will be included in any error / debug reporting done by the plugin.
 *
 * Your method MUST return either 'ok' to continue processing, or anything other than 'ok' to abort.  We suggest
 * your error code be form.paypal.ipnfailure.your_code (replace your_code with some informative code).  So, to
 * return an error and abort processing:
 *    $err_msg = "Something is horribly wrong!";
 *    return "form.paypal.ipnfailure.horribly_wrong";
 *
 * We have included simple do-nothing methods for the most common paypal_status values, and some example code
 * for sending some emails on payment_status_Pending, but it is by no means
 * and exhaustive list.  To add a new one, for instance for Voided, just create a payment_status_Voided() method.
 *
 * IMPORTANT NOTE - during development of your script, you REALLY MUST use the PayPal developer sandbox!!
 */

JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabsubs'.DS.'tables');

class fabrikPayPalIPN
{

	function createInvoice()
	{
		$db =& JFactory::getDBO();
		$date = JFactory::getDate();

		JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabsubs'.DS.'tables');

		$planid = JRequest::getInt('fabsubs_users___plan_id', JRequest::getInt('fabsubs_users___plan_id_raw'));
		$gatwayid = JRequest::getInt('fabsubs_users___gateway');

		$db->setQuery("select * from fabsubs_payment_gateways where id = $gatwayid");
		$gateway = $db->loadObject();

		$db->setQuery("select * from fabsubs_plans where id = $planid");
		$plan =& $db->loadObject();


		//create the subscription
		$user =& JFactory::getUser();
		$sub = JTable::getInstance('Subscriptions', 'Table');
		//if upgrading fall back to logged in user id
		$sub->userid = JRequest::getInt('newuserid', $user->get('id'));
		$sub->primary = 1;
		$sub->type = JRequest::getInt('fabsubs_users___gateway');
		$sub->status = $plan->free == 1 ? 'Active' : 'Pending';
		$sub->signup_date = $date->toMySQL();
		$sub->plan = $planid;
		$sub->lifetime = JRequest::getInt('lifetime', 0);
		$sub->recurring = $gateway->subscription;
		if (strstr(COM_FABRIK_LIVESITE, 'subs')) {
			// $$$ rob not sure this is working yet! - Guessing that item_number contains the billing cycle id!?
			$sub->billing_cycle_id = JRequest::getInt('fabsubs_users___billing_cycle');
		}
		JRequest::setVar('recurring', $sub->recurring);
		$sub->store();

		$invoice = JTable::getInstance('Invoices', 'Table');
		$invoice->invoice_number = uniqid('', true);
		JRequest::setVar('invoice_number', $invoice->invoice_number);
		$invoice->gateway_id = $gatwayid;

		/// testing in subs subdomain for now
		if (strstr(COM_FABRIK_LIVESITE, 'subs')) {
			// $$$ rob now costs stored in fabsubs_plan_billing_cycle
			$db->setQuery("SELECT * FROM fabsubs_plan_billing_cycle WHERE id = $sub->billing_cycle_id");
			$billingCycle = $db->loadObject();
			$this->log('fabrik.ipn createInvoice: billing Cycle query', $db->getQuery());
			$this->log('fabrik.ipn createInvoice: request vars',json_encode($_REQUEST));
			$invoice->currency = $billingCycle->currency;
			$invoice->amount = $billingCycle->cost;
		} else {
			$invoice->amount = $plan->cost;
			$invoice->currency = $plan->currency;
		}

		$invoice->created_date = $date->toMySQL();
		$invoice->subscr_id = $sub->id;
		$invoice->store();

	}
	/**
	 * @param $tableModel
	 * @param $request
	 * @param $set_list
	 * @param $err_msg
	 * @return unknown_type
	 */

	function payment_status_Completed($tableModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.payment_status_Completed', $msg);
		return $this->activateSubscription($tableModel, $request, &$set_list, &$err_msg, false);
	}

	/**
	 * @param $tableModel
	 * @param $request
	 * @param $set_list
	 * @param $err_msg
	 * @return unknown_type
	 */

	function payment_status_Pending($tableModel, $request, &$set_list, &$err_msg)
	{
		$this->log('fabrik.ipn.payment_status_Pending', '');
		$app =& JFactory::getApplication();
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		$SiteName = $app->getCfg('sitename');

		$payer_email = $request['payer_email'];
		$receiver_email = $request['receiver_email'];

		$subject = "%s - Payment Pending";
		$subject = sprintf($subject, $SiteName);
		$subject = html_entity_decode($subject, ENT_QUOTES);

		$msgbuyer = 'Your payment on %s is pending. (Paypal transaction ID: %s)<br /><br />%s';
		$msgbuyer = sprintf($msgbuyer, $SiteName, $txn_id, $SiteName);
		$msgbuyer = html_entity_decode($msgbuyer, ENT_QUOTES);
		JUtility::sendMail($MailFrom, $FromName, $payer_email, $subject, $msgbuyer, true);

		$msgseller = 'Payment pending on %s. (Paypal transaction ID: %s)<br /><br />%s';
		$msgseller = sprintf($msgseller, $SiteName, $txn_id, $SiteName);
		$msgseller = html_entity_decode($msgseller, ENT_QUOTES);
		JUtility::sendMail($MailFrom, $FromName, $receiver_email, $subject, $msgseller, true);
		return 'ok';
	}

	function payment_status_Reversed($tableModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.payment_status_Reversed', $msg);
		return 'ok';
	}

	function payment_status_Cancelled_Reversal($tableModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.payment_status_Cancelled_Reversal', $msg);
		return 'ok';
	}

	/**
	 * @param $tableModel
	 * @param $request
	 * @param $set_list
	 * @param $err_msg
	 * @return unknown_type
	 */
	function payment_status_Refunded($tableModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.payment_status_Refunded', $msg);
		$invoice = $this->checkInvoice($request);
		if (!$invoice) {
			return false;
		}
		$sub = $this->getSubscriptionFromInvoice($invoice);

		$now = JFactory::getDate()->toMySQL();
		$sub->status = 'Refunded';
		$sub->cancel_date = $now;
		$sub->eot_date = $now;
		$sub->eot_cause = 'IPN Refund';
		$sub->store();
		return 'ok';
	}

	function txn_type_web_accept($tableModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_web_accept', $msg);
		return 'ok';
	}

	/**
	 * occurs when someone first signs up for a subscription,
	 * you should get a subscr_payment about 3 seconds afterwards.
	 * So again i dont think we need to do anything here
	 * @param $tableModel
	 * @param $request
	 * @param $set_list
	 * @param $err_msg
	 * @return unknown_type
	 */

	function txn_type_subscr_signup($tableModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_signup', $msg);
		return 'ok';
	}

	/**
	 * the user has cancelled a subscription in Paypal,
	 * @param $tableModel
	 * @param $request
	 * @param $set_list
	 * @param $err_msg
	 * @return unknown_type
	 */

	function txn_type_subscr_cancel($tableModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_cancel', $msg);
		$invoice = $this->checkInvoice( $request);
		if (!$invoice) {
			$this->log('fabrik.ipn.txn_type_subscr_cancel invoice not found', $invoice .' not found so didnt cancel a subscription');
			return false;
		}
		$sub = $this->getSubscriptionFromInvoice($invoice);
		if ($sub === false) {
			$this->log('fabrik.ipn.txn_type_subscr_cancel subscription not found', $invoice .' not found so didnt cancel a subscription');
		}
		$now = JFactory::getDate()->toMySQL();
		$sub->status = 'Cancelled';
		$sub->cancel_date = $now;
		$sub->store();

		$this->fallbackPlan($sub);
		// do we want to revert to a
		return 'ok';
	}

	/**
	 * this occurs when a user upgrades their account in Paypal
	 * We don't allow this in fabrik so nothing should need to be done here
	 * @param $tableModel
	 * @param $request
	 * @param $set_list
	 * @param $err_msg
	 * @return unknown_type
	 */

	function txn_type_subscr_modify($tableModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_modify', $msg);
		return 'ok';
	}

	protected function activateSubscription($tableModel, $request, &$set_list, &$err_msg, $sub = true)
	{
		$db = JFactory::getDBO();
		$invoice = $this->checkInvoice($request);
		if ($invoice === false) {
			return false;
		}
		//update subscription details
		$inv = $this->getInvoice($invoice);
		$sub = $this->getSubscriptionFromInvoice($invoice);

		$now = JFactory::getDate()->toMySQL();
		$sub->status = 'Active';
		$sub->lastpay_date = $now;
		$sub->store();

		//update invoice status
		$inv->transaction_date = $now;
		$inv->pp_txn_id = $request['txn_id'];
		$inv->pp_payment_status = $request['payment_status'];
		$inv->pp_payment_amount = $request['mc_gross'];
		$inv->pp_txn_type = $request['txn_type'];
		$inv->pp_fee = $request['mc_fee'];
		$inv->pp_payer_email = $request['payer_email'];
		// $$$ hugh @TODO - make sure payment_amount == amount
		$inv->paid = 1;
		$inv->store();

		//set user to desired group
		$subUser =& JFactory::getUser($sub->userid);
		$this->log('fabrik.ipn.txn_type_subscr_payment sub userid', $subUser->get('id'));

		$db->setQuery('SELECT max(usergroup) AS gid FROM `fabsubs_subscriptions` AS s
		INNER JOIN fabsubs_plans AS p ON p.id = s.plan
		WHERE userid = ' . (int)$subUser->get('id'));//' and status = "Active"'
		$gid = $db->loadResult();

		$this->log('fabrik.ipn.txn_type_subscr_payment gid query', $db->getQuery());

		$subUser->gid = $gid;
		// $$$ hugh - this line blows up fabrikar.com site, I think 'cos you
		// can't call empty() on a function?  Anyway, splitting into separate
		// lines fixes the fatal error.
		//if(!$subUser->save() || !empty($subUser->getErrors())) {
		$subSaved = $subUser->save();
        $subErrors = $subUser->getErrors();
        if(!$subSaved || !empty($subErrors)) {
			$errMsg = $subUser->getError() . "<br>" . $subUser->get('email') . " / userid = " . $subUser->get('id') . ' NOT set to '. $gid;
			JUtility::sendMail('subscription-errors@fabrikar.com', 'FABRIKAR.COM: Subscription errors', 'rob@pollen-8.co.uk', 'Subscription error - setting user access level', $errMsg, true);
		} else {
			$this->log('fabrik.ipn.setusergid', $subUser->get('id') . ' set to '. $gid . "\n last error in user : ".$subUser->getError() . "\n ". $db->getErrorMsg());
		}

		$app =& JFactory::getApplication();
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		$SiteName = $app->getCfg('sitename');

		$payer_email = $request['payer_email'];

		$subject = $sub ? "%s - Subscription payment complete" : "%s - Payment complete";
		$subject = sprintf($subject, $SiteName);
		$subject = html_entity_decode($subject, ENT_QUOTES);

		$type = $sub ? 'subscription payment' : 'payment';
		$msgbuyer = 'Your '.$type.' on %s has successfully completed. (Paypal transaction ID: %s)<br /><br />%s';
		$msgbuyer = sprintf($msgbuyer, $SiteName, $txn_id, $SiteName);
		$msgbuyer = html_entity_decode($msgbuyer, ENT_QUOTES);
		JUtility::sendMail($MailFrom, $FromName, $payer_email, $subject, $msgbuyer, true);

		$msgseller = $type.' success on %s. (Paypal transaction ID: %s)<br /><br />%s';
		$msgseller = sprintf($msgseller, $SiteName, $txn_id, $SiteName);
		$msgseller = html_entity_decode($msgseller, ENT_QUOTES);
		JUtility::sendMail($MailFrom, $FromName, $receiver_email, $subject, $msgseller, true);

		$this->expireOldSubs($subUser->get('id'));
		return 'ok';
	}

	/**
	 * a subscription payment has been successfully made
	 * @param $tableModel
	 * @param $request
	 * @param $set_list
	 * @param $err_msg
	 * @return unknown_type
	 */

	function txn_type_subscr_payment($tableModel, $request, &$set_list, &$err_msg)
	{
		$db =& JFactory::getDBO();
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_payment', $msg);
		return $this->activateSubscription($tableModel, $request, &$set_list, &$err_msg, true);
	}

	/**
	 * this gets triggered when Paypal tries to charge the user for a recurring subscription
	 * but there are not enough funds in the account
	 * The user is emailed by Paypal regarding the issue
	 * If you have paypal's 'Re-attempt on Failure option' option turned on then Paypal will try to
	 * re-send the charge 3 days after the initial failed request.
	 * Once the max number of failures has occurred Paypal sends out a thx_type_subscr_cancel call
	 * Its there that we need to do something
	 * @param $tableModel
	 * @param $request
	 * @param $set_list
	 * @param $err_msg
	 * @return unknown_type
	 */
	function txn_type_subscr_failed($tableModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_failed', $msg);
		return 'ok';
	}

	/**
	 *
	 * @param $tableModel
	 * @param $request
	 * @param $set_list
	 * @param $err_msg
	 * @return unknown_type
	 *
	 * seems to get called when you do a silver paypal payment (not sub)
	 * but as it occurs before anything else (eg. form.paypal.ipn.Completed the expired invoice doesnt
	 * rest expired but shows as active
	 */

	function txn_type_subscr_eot($tableModel, $request, &$set_list, &$err_msg)
	{
		$msg = new stdClass();
		$msg->request = $request;
		$msg->set_list = $set_list;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.txn_type_subscr_eot', $msg);
		$invoice = $this->checkInvoice($request);
		if (!$invoice) {
			$this->log('fabrik.ipn.txn_type_subscr_eot', 'no invoice found for :'. json_encode($request));
			return false;
		}
		$sub = $this->getSubscriptionFromInvoice($invoice);

		if ($sub->recurring != 1) {
			$this->log('fabrik.ipn.txn_type_subscr_eot', 'not expiring as sub is not recurring (so eot is triggered on sub signup)');
			// $$$ rob 09/06/2011 added cos I think if user has one sub non recurring
			// and that expires and they sign up for a new one (possibly before the
			// end of the first subs term, both subs are expired if we don't return here
			return 'ok';
		}
		$this->expireSub($sub);
		return 'ok';
	}

	/**
	 * expire a single subscription
	 * @param JTable subscription object
	 */

	protected function expireSub($sub, $msg = 'IPN expireSub')
	{
		$now = JFactory::getDate()->toMySQL();
		$sub->status = 'Expired';
		$sub->eot_date = $now;
		$sub->eot_cause = $msg;
		$sub->store();
	}


	/**
	 * get subscription row from a given invoice number
	 * @param string invoice number
	 * @return J table object
	 */

	private function getSubscriptionFromInvoice($inv)
	{
		$db = & JFactory::getDBO();
		$db->setQuery("SELECT subscr_id FROM fabsubs_invoices WHERE invoice_number = " . $db->Quote($inv));
		$subid = (int)$db->loadResult();
		if($subid === 0) {
			return false;
		}
		$sub = JTable::getInstance('Subscriptions', 'Table');
		$sub->load($subid);
		return $sub;
	}

	protected function expireOldSubs($userid)
	{
		$db =& JFactory::getDBO();
		// $$$ rob 10/06/2011 - don't load up active accounts with no eot_date!
		$db->setQuery("SELECT id FROM `fabsubs_subscriptions` WHERE userid = " . (int)$userid . ' AND status = "Active" AND date_format( eot_date, "%Y" ) = "0000" ORDER BY lastpay_date DESC');
		$rows = $db->loadObjectList();

		if (count($rows) > 1) {
			//user can have up to one active subscirption - if theres more we're going to expire the older ones
			for ($i = 1; $i < count($rows); $i++) {
				$sub = JTable::getInstance('Subscriptions', 'Table');
				$subid = (int)$rows[$i]->id;
				if ($subid !== 0) {
					$sub->load($subid);
					$this->expireSub($sub, 'Expire Old Subs');
				}
			}
		}

		$msg = new stdClass();
		$msg->subscriptionids = $rows;
		$msg = json_encode($msg);
		$this->log('fabrik.ipn.expireOldSubs', $msg);
	}

	/**
	 * get an invoice JTable object from its invoice number
	 * @param unknown_type $inv
	 * @return unknown_type
	 */

	private function getInvoice($inv)
	{
		$row = JTable::getInstance('Invoices', 'Table');
		//$row->_tbl_key = 'invoice_number';
		$row->set('_tbl_key', 'invoice_number');
		$row->load($inv);
		return $row;
	}

	/**
	 *
	 * @param string $msg
	 * @param string $to
	 * @param array data to log
	 * @return unknown_type
	 */
	private function reportError($msg, $to, $data)
	{
		$app =& JFactory::getApplication();
		$MailFrom = $app->getCfg('mailfrom');
		$FromName = $app->getCfg('fromname');
		$body  = "\n\n\\";
		foreach ($data as $k=>$v) {
			$body .= "$k = $v \n";
		}
		$subject = 'fabrik.ipn.fabrikar_subs error';
		JUtility::sendMail($MailFrom, $FromName, $to, $subject, $body);
		$this->log($subject, $body);
	}

	private function log($subject, $body)
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables');
		$log =& JTable::getInstance('log', 'Table');
		$log->referring_url = $_SERVER['REQUEST_URI'];
		$log->message_type = $subject;
		$log->message = $body;
		$log->store();
	}

	/**
	 * ensures that an invoice num was found in the request data.
	 * @param array $request
	 * @return mixed false if not found, otherwise returns invoice num
	 */
	private function checkInvoice($request)
	{
		$invoice = $request['invoice'];
		$receiver_email = $request['receiver_email'];
		//eekk no invoice number found in returned data - inform the sys admin
		if ($invoice === '') {
			$this->reportError('missing invoice', $receiver_email, array_merge($request, $set_list));
			return false;
		}
		return $invoice;
	}

	/**
	 * if the plan has a fall back plan - e.g. silver falls back to bronze
	 * we want to change the user type
	 * @param unknown_type $sub
	 * @return unknown_type
	 */
	public function fallbackPlan($sub)
	{
		$plan = JTable::getInstance('Plans', 'Table');
		$newPlan = JTable::getInstance('Plans', 'Table');
		$plan->load((int)$sub->plan);
		$this->log('fabrik.ipn. fallback', ' getting fallback sub plan :  ' . (int)$sub->plan .' = ' . (int)$plan->fall_back_plan);
		$fallback = false;
		if ($plan->fall_back_plan != 0) {
			$fallback = true;
			$newPlan->load((int)$plan->fall_back_plan);
			$gid = (int)$newPlan->usergroup;
			if ($gid <18) {
				$gid = 18;
			}
		} else {
			$gid = 18;
		}
		$subUser = JFactory::getUser($sub->userid);
		$subUser->gid = $gid;
		$this->log('fabrik.ipn. fallback', $subUser->get('id') .' gid set to ' . $gid);
		$subUser->save();

		if ($fallback) {
			//create new subscription for fall back plan

			//get the expration date (length of new plan - that of previous plan)
			$newLength = $this->charToPeriod($newPlan->period_unit);
			$oldLength = $this->charToPeriod($plan->period_unit);
			$expDate = strtotime("+{$newPlan->duration} $newLength	");

			$minus = strtotime("-{$plan->duration} $oldLength");

			$this->log('fabrik.ipn. fallback', 'expiration date = strtotime(+'.$newPlan->duration.' '.$newLength.")\n minus = strtotime(-" .$plan->duration.' '.$oldLength.") \n =: $expDate - $minus"	);
			$expDate = JFactory::getDate()->toUnix() - ( $expDate - $minus);

			$sub = JTable::getInstance('Subscriptions', 'Table');
			$sub->userid = $subUser->get('id');
			$sub->type = 1; //paypal payment - no recurring
			$sub->status = 'Active';
			$sub->signup_date = JFactory::getDate()->toMySQL();
			$sub->plan = $newPlan->id;
			$sub->recurring = 0;
			$sub->lifetime = 0;
			$sub->expiration = JFactory::getDate($expDate)->toMySQL();
			$this->log('fabrik.ipn. fallback', 'new sub expiration set to ' . $sub->expiration);
			$sub->store();
			$msg = "<h3>new sub expiration set to $sub->expiration</h3>";
			foreach ($sub as $k=>$v) {
				$msg .= "$k = $v <br>";
			}
			JUtility::sendMail('rob@pollen-8.co.uk', 'fabrikar subs', 'rob@pollen-8.co.uk', 'fabrikar sub: fall back plan created', $msg, true);
		}
	}

	private function charToPeriod($p)
	{

		switch($p) {
			case 'D':
				$newLength = 'day';
				break;
			case 'M':
				$newLength = 'month';
				break;
			case 'Y':
				$newLength = 'year';
				break;
			default:
				$newLength = '';
				break;
		}
		return $newLength;
	}

}

?>
