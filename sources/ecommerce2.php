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

/**
 * Add a usergroup subscription.
 *
 * @param  SHORT_TEXT	The title
 * @param  LONG_TEXT		The description
 * @param  SHORT_TEXT	The cost
 * @param  integer		The length
 * @param  SHORT_TEXT	The units for the length
 * @set    y m d w
 * @param  ?GROUP			The usergroup that purchasing gains membership to (NULL: super members)
 * @param  BINARY			Whether this is applied to primary usergroup membership
 * @param  BINARY			Whether this is currently enabled
 * @param  ?LONG_TEXT	The text of the e-mail to send out when a subscription is start (NULL: default)
 * @param  ?LONG_TEXT	The text of the e-mail to send out when a subscription is ended (NULL: default)
 * @param  ?LONG_TEXT	The text of the e-mail to send out when a subscription cannot be renewed because the subproduct is gone (NULL: default)
 * @return AUTO_LINK		The ID
 */
function add_usergroup_subscription($title,$description,$cost,$length,$length_units,$group_id,$uses_primary,$enabled,$mail_start,$mail_end,$mail_uhoh)
{
	$dbs_bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
	$GLOBALS['NO_DB_SCOPE_CHECK']=true;

	$id=$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_insert('f_usergroup_subs',array(
		's_title'=>insert_lang($title,2,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
		's_description'=>insert_lang($description,2,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
		's_cost'=>$cost,
		's_length'=>$length,
		's_length_units'=>$length_units,
		's_group_id'=>$group_id,
		's_uses_primary'=>$uses_primary,
		's_enabled'=>$enabled,
		's_mail_start'=>insert_lang($mail_start,2,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
		's_mail_end'=>insert_lang($mail_end,2,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
		's_mail_uhoh'=>insert_lang($mail_uhoh,2,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
	),true);

	log_it('ADD_USERGROUP_SUBSCRIPTION',strval($id),$title);

	$GLOBALS['NO_DB_SCOPE_CHECK']=$dbs_bak;

	return $id;
}

/**
 * Edit a usergroup subscription.
 *
 * @param  AUTO_LINK		The ID
 * @param  SHORT_TEXT	The title
 * @param  LONG_TEXT		The description
 * @param  SHORT_TEXT	The cost
 * @param  integer		The length
 * @param  SHORT_TEXT	The units for the length
 * @set    y m d w
 * @param  ?GROUP			The usergroup that purchasing gains membership to (NULL: super members)
 * @param  BINARY			Whether this is applied to primary usergroup membership
 * @param  BINARY			Whether this is currently enabled
 * @param  ?LONG_TEXT	The text of the e-mail to send out when a subscription is start (NULL: default)
 * @param  ?LONG_TEXT	The text of the e-mail to send out when a subscription is ended (NULL: default)
 * @param  ?LONG_TEXT	The text of the e-mail to send out when a subscription cannot be renewed because the subproduct is gone (NULL: default)
 */
function edit_usergroup_subscription($id,$title,$description,$cost,$length,$length_units,$group_id,$uses_primary,$enabled,$mail_start,$mail_end,$mail_uhoh)
{
	$dbs_bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
	$GLOBALS['NO_DB_SCOPE_CHECK']=true;

	$rows=$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_select('f_usergroup_subs',array('*'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$rows[0];

	// If usergroup has changed, do a move
	if ($myrow['s_group_id']!=$group_id)
	{
		require_code('ocf_groups_action');
		require_code('ocf_groups_action2');
		$product='USERGROUP'.strval($id);
		$subscriptions=$GLOBALS['SITE_DB']->query_select('subscriptions',array('*'),array('s_type_code'=>$product));
		foreach ($subscriptions as $sub)
		{
			$member_id=$sub['s_member_id'];
			if ((get_value('unofficial_ecommerce')=='1') && (get_forum_type()!='ocf'))
			{
				$GLOBALS['FORUM_DB']->remove_member_from_group($member_id,$group_id);
				$GLOBALS['FORUM_DB']->add_member_to_group($member_id,$group_id);
			} else
			{
				$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_delete('f_group_members',array('gm_group_id'=>$group_id,'gm_member_id'=>$member_id),'',1);
				ocf_add_member_to_group($member_id,$group_id);
			}
		}
	}

	$_title=$myrow['s_title'];
	$_description=$myrow['s_description'];
	$_mail_start=$myrow['s_mail_start'];
	$_mail_end=$myrow['s_mail_end'];
	$_mail_uhoh=$myrow['s_mail_uhoh'];

	$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_update('f_usergroup_subs',array(
		's_title'=>lang_remap($_title,$title,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
		's_description'=>lang_remap($_description,$description,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
		's_cost'=>$cost,
		's_length'=>$length,
		's_length_units'=>$length_units,
		's_group_id'=>$group_id,
		's_uses_primary'=>$uses_primary,
		's_enabled'=>$enabled,
		's_mail_start'=>lang_remap($_mail_start,$mail_start,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
		's_mail_end'=>lang_remap($_mail_end,$mail_end,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
		's_mail_uhoh'=>lang_remap($_mail_uhoh,$mail_uhoh,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']),
	),array('id'=>$id),'',1);

	log_it('EDIT_USERGROUP_SUBSCRIPTION',strval($id),$title);

	$GLOBALS['NO_DB_SCOPE_CHECK']=$dbs_bak;
}

/**
 * Delete a usergroup subscription.
 *
 * @param  AUTO_LINK		The ID
 * @param  LONG_TEXT		The cancellation mail to send out
 */
function delete_usergroup_subscription($id,$uhoh_mail)
{
	$dbs_bak=$GLOBALS['NO_DB_SCOPE_CHECK'];
	$GLOBALS['NO_DB_SCOPE_CHECK']=true;

	$rows=$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_select('f_usergroup_subs',array('*'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$rows[0];
	$new_group=$myrow['s_group_id'];

	// Remove benefits
	$product='USERGROUP'.strval($id);
	$subscriptions=$GLOBALS['SITE_DB']->query_select('subscriptions',array('*'),array('s_type_code'=>$product));
	$to_members=array();
	foreach ($subscriptions as $sub)
	{
		$member_id=$sub['s_member_id'];

		$test=in_array($new_group,$GLOBALS['FORUM_DRIVER']->get_members_groups($member_id));
		if ($test)
		{
			if (is_null($GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_select_value_if_there('f_group_member_timeouts','member_id',array('member_id'=>$member_id,'group_id'=>$new_group))))
			{
				// Remove them from the group

				if ((get_value('unofficial_ecommerce')=='1') && (get_forum_type()!='ocf'))
				{
					$GLOBALS['FORUM_DB']->remove_member_from_group($member_id,$new_group);
				} else
				{
					$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_delete('f_group_members',array('gm_group_id'=>$new_group,'gm_member_id'=>$member_id),'',1);
				}
				$to_members[]=$member_id;
			}
		}
	}
	if ($uhoh_mail!='')
	{
		require_code('notifications');
		dispatch_notification('paid_subscription_ended',NULL,do_lang('PAID_SUBSCRIPTION_ENDED',NULL,NULL,NULL,get_site_default_lang()),$uhoh_mail,$to_members);
	}

	$_title=$myrow['s_title'];
	$_description=$myrow['s_description'];
	$title=get_translated_text($_title,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']);
	$_mail_start=$myrow['s_mail_start'];
	$_mail_end=$myrow['s_mail_end'];
	$_mail_uhoh=$myrow['s_mail_uhoh'];

	$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']->query_delete('f_usergroup_subs',array('id'=>$id),'',1);
	delete_lang($_title,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']);
	delete_lang($_description,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']);
	delete_lang($_mail_start,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']);
	delete_lang($_mail_end,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']);
	delete_lang($_mail_uhoh,$GLOBALS[(get_forum_type()=='ocf')?'FORUM_DB':'SITE_DB']);

	log_it('DELETE_USERGROUP_SUBSCRIPTION',strval($id),$title);

	$GLOBALS['NO_DB_SCOPE_CHECK']=$dbs_bak;
}

