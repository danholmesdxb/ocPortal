<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ecommerce
 */

class Hook_paypal
{

	/**
	 * Get the PayPal payment address.
	 *
	 * @return string			The answer.
	 */
	function _get_payment_address()
	{
		return ecommerce_test_mode()?get_option('ipn_test'):get_option('ipn');
	}

	/**
	 * Get the PayPal IPN URL.
	 *
	 * @return URLPATH		The IPN url.
	 */
	function get_ipn_url()
	{
		return ecommerce_test_mode()?'https://www.sandbox.paypal.com/cgi-bin/webscr':'https://www.paypal.com/cgi-bin/webscr';
	}

	/**
	 * Make a transaction (payment) button.
	 *
	 * @param  ID_TEXT		The product codename.
	 * @param  SHORT_TEXT	The human-readable product title.
	 * @param  ID_TEXT		The purchase ID.
	 * @param  float			A transaction amount.
	 * @param  ID_TEXT		The currency to use.
	 * @return tempcode		The button
	 */
	function make_transaction_button($product,$item_name,$purchase_id,$amount,$currency)
	{
		$payment_address=$this->_get_payment_address();
		$ipn_url=$this->get_ipn_url();

      $user_details=array();
		if (!is_guest())
		{
			$user_details['first_name']=get_ocp_cpf('firstname');
			$user_details['last_name']=get_ocp_cpf('lastname');
			$user_details['address1']=get_ocp_cpf('building_name_or_number');
			$user_details['city']=get_ocp_cpf('city');
			$user_details['state']=get_ocp_cpf('state');
			$user_details['zip']=get_ocp_cpf('post_code');
			$user_details['country']=get_ocp_cpf('country');
		}

		return do_template('ECOM_BUTTON_VIA_PAYPAL',array('_GUID'=>'b0d48992ed17325f5e2330bf90c85762','PRODUCT'=>$product,'ITEM_NAME'=>$item_name,'PURCHASE_ID'=>$purchase_id,'AMOUNT'=>float_to_raw_string($amount),'CURRENCY'=>$currency,'PAYMENT_ADDRESS'=>$payment_address,'IPN_URL'=>$ipn_url,'MEMBER_ADDRESS'=>$user_details));
	}

	/**
	 * Make a subscription (payment) button.
	 *
	 * @param  ID_TEXT		The product codename.
	 * @param  SHORT_TEXT	The human-readable product title.
	 * @param  ID_TEXT		The purchase ID.
	 * @param  float			A transaction amount.
	 * @param  integer		The subscription length in the units.
	 * @param  ID_TEXT		The length units.
	 * @set    d w m y
	 * @param  ID_TEXT		The currency to use.
	 * @return tempcode		The button
	 */
	function make_subscription_button($product,$item_name,$purchase_id,$amount,$length,$length_units,$currency)
	{
		$payment_address=$this->_get_payment_address();
		$ipn_url=$this->get_ipn_url();
		return do_template('ECOM_SUBSCRIPTION_BUTTON_VIA_PAYPAL',array('PRODUCT'=>$product,'ITEM_NAME'=>$item_name,'LENGTH'=>strval($length),'LENGTH_UNITS'=>$length_units,'PURCHASE_ID'=>$purchase_id,'AMOUNT'=>float_to_raw_string($amount),'CURRENCY'=>$currency,'PAYMENT_ADDRESS'=>$payment_address,'IPN_URL'=>$ipn_url));
	}

	/**
	 * Make a subscription cancellation button.
	 *
	 * @param  ID_TEXT		The purchase ID.
	 * @return tempcode		The button
	 */
	function make_cancel_button($purchase_id)
	{
		return do_template('ECOM_CANCEL_BUTTON_VIA_PAYPAL',array('PURCHASE_ID'=>$purchase_id));
	}

	/**
	 * Find whether the hook auto-cancels (if it does, auto cancel the given trans-id).
	 *
	 * @param  string		Transaction ID to cancel
	 * @return ?boolean	True: yes. False: no. (NULL: cancels via a user-URL-directioning)
	 */
	function auto_cancel($trans_id)
	{
		return NULL;
	}

	/**
	 * Find a transaction fee from a transaction amount. Regular fees aren't taken into account.
	 *
	 * @param  float	A transaction amount.
	 * @return float	The fee
	 */
	function get_transaction_fee($amount)
	{
		return round(0.25+0.034*$amount,2);
	}

	/**
	 * Handle IPN's. The function may produce output, which would be returned to the Payment Gateway. The function may do transaction verification.
	 *
	 * @return array	A long tuple of collected data.
	 */
	function handle_transaction()
	{	
		if ((file_exists(get_file_base().'/data_custom/ecommerce.log')) && (is_writable_wrap(get_file_base().'/data_custom/ecommerce.log')))
		{
			$myfile=fopen(get_file_base().'/data_custom/ecommerce.log','at');
			fwrite($myfile,serialize($_POST).chr(10));
			fclose($myfile);
		}

		// assign posted variables to local variables
		$purchase_id=post_param_integer('custom','-1');

		$txn_type=post_param('txn_type',NULL);

		if ($txn_type=='cart')
		{	
			require_lang('shopping');
			$item_name=do_lang('CART_ORDER',$purchase_id);
		}
		else
		{
			$item_name=(substr(post_param('txn_type',''),0,6)=='subscr')?'':post_param('item_name','');
		}

		$payment_status=post_param('payment_status',''); // May be blank for subscription
		$reason_code=post_param('reason_code','');
		$pending_reason=post_param('pending_reason','');
		$memo=post_param('memo','');
		$mc_gross=post_param('mc_gross',''); // May be blank for subscription
		$tax=post_param('tax','');
		if (($tax!='') && (intval($tax)>0) && ($mc_gross!='')) $mc_gross=float_to_raw_string(floatval($mc_gross)-floatval($tax));
		$mc_currency=post_param('mc_currency',''); // May be blank for subscription
		$txn_id=post_param('txn_id',''); // May be blank for subscription
		$parent_txn_id=post_param('parent_txn_id','-1');
		$receiver_email=post_param('receiver_email');

		// post back to PayPal system to validate
		if (!ecommerce_test_mode())
		{
			require_code('files');
			$pure_post=isset($GLOBALS['PURE_POST'])?$GLOBALS['PURE_POST']:$_POST;
			$x=0;
			$res=mixed();
			do
			{
				$res=http_download_file('http://'.(ecommerce_test_mode()?'www.sandbox.paypal.com':'www.paypal.com').'/cgi-bin/webscr',NULL,false,false,'ocPortal',$pure_post+array('cmd'=>'_notify-validate'));
				$x++;
			}
			while ((is_null($res)) && ($x<3));
			if (is_null($res)) my_exit(do_lang('IPN_SOCKET_ERROR'));
			if (!(strcmp($res,'VERIFIED')==0))
			{
				if (post_param('txn_type','')=='send_money') exit('Unexpected'); // PayPal has been seen to mess up on send_money transactions, making the IPN unverifiable
				my_exit(do_lang('IPN_UNVERIFIED').' - '.$res.' - '.flatten_slashed_array($pure_post),strpos($res,'<html')!==false);
			}
		}

		$txn_type=str_replace('-','_',post_param('txn_type'));
		if ($txn_type=='subscr-modify')
		{
			$payment_status='SModified';
			$txn_id=post_param('subscr_id').'-m';
		}
		elseif ($txn_type=='subscr_signup')
		{
			$payment_status='Completed';
			$mc_gross=post_param('mc_amount3');
			if (post_param_integer('recurring')!=1) my_exit(do_lang('IPN_SUB_RECURRING_WRONG'));
			$txn_id=post_param('subscr_id');
		}
		elseif ($txn_type=='subscr_cancel')
		{
			$payment_status='SCancelled';
			$txn_id=post_param('subscr_id').'-c';
		}

		$primary_paypal_email=get_value('primary_paypal_email');

		if (!is_null($primary_paypal_email))
		{
			if ($receiver_email!=$primary_paypal_email) my_exit(do_lang('IPN_EMAIL_ERROR'));
		} else
		{
			if ($receiver_email!=$this->_get_payment_address()) my_exit(do_lang('IPN_EMAIL_ERROR'));
		}

		if (addon_installed('shopping'))
		{
			$this->store_shipping_address($purchase_id);
		}

		return array($purchase_id,$item_name,$payment_status,$reason_code,$pending_reason,$memo,$mc_gross,$mc_currency,$txn_id,$parent_txn_id);
	}

	/**
	 * Make a transaction (payment) button for multiple shopping cart items
	 *
	 * @param  array			Items array
	 * @param  tempcode		Currency symbol
	 * @param  AUTO_LINK		Order Id
	 * @return tempcode		The button
	 */
	function make_cart_transaction_button($items,$currency,$order_id)
	{
		$payment_address=$this->_get_payment_address();

		$ipn_url=$this->get_ipn_url();

		$notification_text=do_lang_tempcode('CHECKOUT_NOTIFICATION_TEXT',$order_id);

		$user_details=array();

		if (!is_guest())
		{
			$user_details['first_name']=get_ocp_cpf('firstname');
			$user_details['last_name']=get_ocp_cpf('lastname');
			$user_details['address1']=get_ocp_cpf('building_name_or_number');
			$user_details['city']=get_ocp_cpf('city');
			$user_details['state']=get_ocp_cpf('state');
			$user_details['zip']=get_ocp_cpf('post_code');
			$user_details['country']=get_ocp_cpf('country');
		}

		return do_template('ECOM_CART_BUTTON_VIA_PAYPAL',array('ITEMS'=>$items,'CURRENCY'=>$currency,'PAYMENT_ADDRESS'=>$payment_address,'IPN_URL'=>$ipn_url,'ORDER_ID'=>strval($order_id),'NOTIFICATION_TEXT'=>$notification_text,'MEMBER_ADDRESS'=>$user_details));
	}

	/**
	 * Store shipping address for orders
	 *
	 * @param  AUTO_LINK		Order id
	 * @return ?mixed			Address id (NULL: No address record found)
	 */
	function store_shipping_address($order_id)
	{
		if (is_null(post_param('address_name',NULL))) return NULL;

		if (is_null($GLOBALS['SITE_DB']->query_value_null_ok('shopping_order_addresses','id',array('order_id'=>$order_id))))
		{
			$shipping_address=array();
			$shipping_address['order_id']=$order_id;
			$shipping_address['address_name']=post_param('address_name','');
			$shipping_address['address_street']=post_param('address_street','');
			$shipping_address['address_zip']=post_param('address_zip','');
			$shipping_address['address_city']=post_param('address_city','');
			$shipping_address['address_country']=post_param('address_country','');
			$shipping_address['receiver_email']=post_param('payer_email','');

			return $GLOBALS['SITE_DB']->query_insert('shopping_order_addresses',$shipping_address,true);	
		}

		return NULL;
	}
}


