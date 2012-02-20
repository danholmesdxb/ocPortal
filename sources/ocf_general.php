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
 * @package		core_ocf
 */

/**
 * Get some forum stats.
 *
 * @return array	A map of forum stats.
 */
function ocf_get_forums_stats()
{
	$out=array();

	$out['num_topics']=$GLOBALS['OCF_DRIVER']->get_topics();
	$out['num_posts']=$GLOBALS['OCF_DRIVER']->get_num_forum_posts();
	$out['num_members']=$GLOBALS['OCF_DRIVER']->get_members();

	$temp=get_value_newer_than('ocf_newest_member_id',time()-60*60*1);
	$out['newest_member_id']=is_null($temp)?NULL:intval($temp);
	if (!is_null($out['newest_member_id']))
	{
		$out['newest_member_username']=get_value_newer_than('ocf_newest_member_username',time()-60*60*1);
	} else
	{
		$out['newest_member_username']=NULL;
	}
	$out['newest_member_username']=NULL;
	if (is_null($out['newest_member_username']))
	{
		$newest_member=$GLOBALS['FORUM_DB']->query('SELECT m_username,id FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_members WHERE m_validated=1 AND id<>'.strval($GLOBALS['FORUM_DRIVER']->get_guest_id()).' ORDER BY m_join_time DESC',1); // Only ordered by m_join_time and not double ordered with ID to make much faster in MySQL
		$out['newest_member_id']=$newest_member[0]['id'];
		$out['newest_member_username']=$newest_member[0]['m_username'];
		if (get_db_type()!='xml')
		{
			set_value('ocf_newest_member_id',strval($out['newest_member_id']));
			set_value('ocf_newest_member_username',$out['newest_member_username']);
		}
	}

	return $out;
}

/**
 * Get details on a member profile.
 *
 * @param  MEMBER		The member to get details of.
 * @param  boolean	Whether to get a 'lite' version (contains less detail, therefore less costly).
 * @return array 		A map of details.
 */
function ocf_read_in_member_profile($member_id,$lite=true)
{
	$row=$GLOBALS['OCF_DRIVER']->get_member_row($member_id);
	if (is_null($row)) return array();
	$last_visit_time=(($member_id==get_member()) && (array_key_exists('last_visit',$_COOKIE)))?intval($_COOKIE['last_visit']):$row['m_last_visit_time'];
	$join_time=$row['m_join_time'];

	$out=array(
			'username'=>$row['m_username'],
			'last_visit_time'=>$last_visit_time,
			'last_visit_time_string'=>get_timezoned_date($last_visit_time),
			'signature'=>$row['m_signature'],
			'posts'=>$row['m_cache_num_posts'],
			'join_time'=>$join_time,
			'join_time_string'=>get_timezoned_date($join_time),
	);

	if (addon_installed('points'))
	{
		require_code('points');
		$num_points=total_points($member_id);
		$out['points']=$num_points;
	}

	if (!$lite)
	{
		$out['groups']=ocf_get_members_groups($member_id);

		// Custom fields
		$out['custom_fields']=ocf_get_all_custom_fields_match_member($member_id,((get_member()!=$member_id) && (!has_specific_permission(get_member(),'view_any_profile_field')))?1:NULL,((get_member()!=$member_id) && (!has_specific_permission(get_member(),'view_any_profile_field')))?1:NULL);

		// Birthdate
		if ($row['m_reveal_age']==1)
		{
			$out['birthdate']=$row['m_dob_year'].'/'.$row['m_dob_month'].'/'.$row['m_dob_day'];
		}

		// Find title
		if (addon_installed('ocf_member_titles'))
		{
			$title=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_title');
			if ($title=='')
			{
				$primary_group=ocf_get_member_primary_group($member_id);
				$title=ocf_get_group_property($primary_group,$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'title'));
			}
			if ($title!='') $out['title']=$title;
		}

		// Find photo
		$photo=$GLOBALS['OCF_DRIVER']->get_member_row_field($member_id,'m_photo_thumb_url');
		if (($photo!='') && (addon_installed('ocf_member_photos')))
		{
			if (url_is_local($photo)) $photo=get_complex_base_url($photo).'/'.$photo;
			$out['photo']=$photo;
		}

		// Any warnings?
		if ((has_specific_permission(get_member(),'see_warnings')) && (addon_installed('ocf_warnings')))
		{
			$out['warnings']=ocf_get_warnings($member_id);
		}
	}

	// Find avatar
	$avatar=$GLOBALS['OCF_DRIVER']->get_member_avatar_url($member_id);
	if ($avatar!='')
	{
		$out['avatar']=$avatar;
	}

	// Primary usergroup
	$primary_group=ocf_get_member_primary_group($member_id);
	$out['primary_group']=$primary_group;
	$out['primary_group_name']=ocf_get_group_name($primary_group);

	// Find how many points we need to advance
	if (addon_installed('points'))
	{
		$promotion_threshold=ocf_get_group_property($primary_group,'promotion_threshold');
		if (!is_null($promotion_threshold))
		{
			$num_points_advance=$promotion_threshold-$num_points;
			$out['num_points_advance']=$num_points_advance;
		}
	}

	return $out;
}

/**
 * Get a usergroup colour based on it's ID number.
 *
 * @param  GROUP			ID number.
 * @return string			Colour.
 */
function get_group_colour($gid)
{
	$all_colours=array('ocf_gcol_1','ocf_gcol_2','ocf_gcol_3','ocf_gcol_4','ocf_gcol_5','ocf_gcol_6','ocf_gcol_7','ocf_gcol_8','ocf_gcol_9','ocf_gcol_10','ocf_gcol_11','ocf_gcol_12','ocf_gcol_13','ocf_gcol_14','ocf_gcol_15');
	return $all_colours[$gid%count($all_colours)];
}

/**
 * Do the wrapper that fits around OCF module output.
 *
 * @param  tempcode		The title for the module output that we are wrapping.
 * @param  tempcode		The module output that we are wrapping.
 * @param  boolean		Whether to include the personal bar in the wrap.
 * @param  boolean		Whether to include statistics in the wrap.
 * @param  ?AUTO_LINK	The forum to make the search link search under (NULL: Users own PT forum/unknown).
 * @return tempcode		The wrapped output.
 */
function ocf_wrapper($title,$content,$show_personal_bar=true,$show_stats=true,$forum_id=NULL)
{
	global $ZONE;
	$wide=is_wide();
	if (($wide==0) && (get_value('force_forum_bar')!=='1'))
	{
		$show_personal_bar=false;
		$show_stats=false;
	}

	// Notifications
	if ((!is_guest()) && ((get_page_name()=='forumview') || (get_page_name()=='topicview') || (get_page_name()=='vforums')))
	{
		$cache_identifier=serialize(array(get_member()));
		$_notifications=NULL;
		if (((get_option('is_on_block_cache')=='1') || (get_param_integer('keep_cache',0)==1) || (get_param_integer('cache',0)==1)) && ((get_param_integer('keep_cache',NULL)!==0) && (get_param_integer('cache',NULL)!==0)))
		{
			$_notifications=get_cache_entry('_new_pp',$cache_identifier,10000);
		}
		if (is_null($_notifications))
		{
			require_code('ocf_notifications');
			list($notifications,$num_unread_pps)=generate_notifications($cache_identifier);
		} else
		{
			list($__notifications,$num_unread_pps)=$_notifications;
			$notifications=new ocp_tempcode();
			if (!$notifications->from_assembly($__notifications,true))
			{
				require_code('ocf_notifications');
				list($notifications,$num_unread_pps)=generate_notifications($cache_identifier);
			}
			if (!$notifications->is_empty())
			{
				require_javascript('javascript_ajax');
			}
		}
	} else
	{
		$notifications=new ocp_tempcode();
		$num_unread_pps=0;
	}

	if ($show_personal_bar)
	{
		if (get_member()!=$GLOBALS['OCF_DRIVER']->get_guest_id()) // Logged in user
		{
			$member_info=ocf_read_in_member_profile(get_member(),true);

			$profile_url=$GLOBALS['OCF_DRIVER']->member_profile_link(get_member(),true,true);

			$_new_topics=$GLOBALS['FORUM_DB']->query('SELECT COUNT(*) AS mycnt FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_topics WHERE NOT t_forum_id IS NULL AND t_cache_first_time>'.strval((integer)$member_info['last_visit_time']));
			$new_topics=$_new_topics[0]['mycnt'];
			$_new_posts=$GLOBALS['FORUM_DB']->query('SELECT COUNT(*) AS mycnt FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_posts WHERE NOT p_cache_forum_id IS NULL AND p_time>'.strval((integer)$member_info['last_visit_time']));
			$new_posts=$_new_posts[0]['mycnt'];

			$max_avatar_height=ocf_get_member_best_group_property(get_member(),'max_avatar_height');

			// Any unread PT-PPs?
			$pt_extra=($num_unread_pps==0)?new ocp_tempcode():do_lang_tempcode('NUM_UNREAD',integer_format($num_unread_pps));

			$personal_topic_url=build_url(array('page'=>'members','type'=>'view','id'=>get_member()),get_module_zone('members'),NULL,true,false,false,'tab__pts');

			$head=do_template('OCF_MEMBER_BAR',array(
					'_GUID'=>'s3kdsadf0p3wsjlcfksdj',
					'AVATAR'=>array_key_exists('avatar',$member_info)?$member_info['avatar']:'',
					'PROFILE_URL'=>$profile_url,
					'USERNAME'=>$member_info['username'],
					'LOGOUT_URL'=>build_url(array('page'=>'login','type'=>'logout'),get_module_zone('login')),
					'NUM_POINTS_ADVANCE'=>array_key_exists('num_points_advance',$member_info)?make_string_tempcode(integer_format($member_info['num_points_advance'])):do_lang('NA'),
					'NUM_POINTS'=>array_key_exists('points',$member_info)?integer_format($member_info['points']):'',
					'NUM_POSTS'=>integer_format($member_info['posts']),
					'PRIMARY_GROUP'=>$member_info['primary_group_name'],
					'LAST_VISIT_DATE_RAW'=>strval($member_info['last_visit_time']),
					'LAST_VISIT_DATE'=>$member_info['last_visit_time_string'],
					'PERSONAL_TOPIC_URL'=>$personal_topic_url,
					'NEW_POSTS_URL'=>build_url(array('page'=>'vforums','type'=>'misc'),get_module_zone('vforums')),
					'UNREAD_TOPICS_URL'=>build_url(array('page'=>'vforums','type'=>'unread'),get_module_zone('vforums')),
					'RECENTLY_READ_URL'=>build_url(array('page'=>'vforums','type'=>'recently_read'),get_module_zone('vforums')),
					'INLINE_PERSONAL_POSTS_URL'=>build_url(array('page'=>'topicview'),get_module_zone('topicview')),
					'PT_EXTRA'=>$pt_extra,
					'NEW_TOPICS'=>integer_format($new_topics),
					'NEW_POSTS'=>integer_format($new_posts),
					'MAX_AVATAR_HEIGHT'=>strval($max_avatar_height),
			));

		} else // Guest
		{
			if (count($_POST)>0)
			{
				$_this_url=build_url(array('page'=>'forumview'),'forum',array('keep_session'=>1));
			} else
			{
				$_this_url=build_url(array('page'=>'_SELF'),'_SELF',array('keep_session'=>1),true);
			}
			$this_url=$_this_url->evaluate();
			$login_url=build_url(array('page'=>'login','type'=>'login','redirect'=>$this_url),get_module_zone('login'));
			$full_link=build_url(array('page'=>'login','type'=>'misc','redirect'=>$this_url),get_module_zone('login'));
			$join_link=build_url(array('page'=>'join','redirect'=>$this_url),get_module_zone('join'));
			$head=do_template('OCF_GUEST_BAR',array('NAVIGATION'=>''/*deprecated*/,'LOGIN_URL'=>$login_url,'JOIN_LINK'=>$join_link,'FULL_LINK'=>$full_link));
		}
	} else $head=new ocp_tempcode();

	if ($show_stats)
	{
		$stats=ocf_get_forums_stats();

		// Users online
		$users_online=new ocp_tempcode();
		$count=0;
		$members=get_online_members(false,NULL,$count);
		$groups_seen=array();
		if (!is_null($members))
		{
			//$members=collapse_2d_complexity('the_user','cache_username',$members);
			$guests=0;
			foreach ($members as $bits)
			{
				$member=$bits['the_user'];
				$username=$bits['cache_username'];

				if ($member==$GLOBALS['OCF_DRIVER']->get_guest_id())
				{
					$guests++;
					continue;
				}
				if (is_null($username)) continue;
				$url=$GLOBALS['OCF_DRIVER']->member_profile_link($member,false,true);
				if (!array_key_exists('m_primary_group',$bits))
					$bits['m_primary_group']=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member,'m_primary_group');
				$pgid=$bits['m_primary_group'];//$GLOBALS['FORUM_DRIVER']->get_member_row_field($member,'m_primary_group');
				if (is_null($pgid)) continue; // Deleted member
				$groups_seen[$pgid]=1;
				$col=get_group_colour($pgid);
				$usergroup=ocf_get_group_name($pgid);
				if (get_value('disable_user_online_groups')==='1')
				{
					$usergroup=NULL;
					$col=NULL;
					$groups_seen=array();
				}
				$users_online->attach(do_template('OCF_USER_MEMBER',array('_GUID'=>'a9cb1af2a04b14edd70749c944495bff','COLOUR'=>$col,'PROFILE_URL'=>$url,'USERNAME'=>$username,'USERGROUP'=>$usergroup)));
			}
			if ($guests!=0)
			{
				if (!$users_online->is_empty()) $users_online->attach(do_lang_tempcode('LIST_SEP'));
				$users_online->attach(do_lang_tempcode('NUM_GUESTS',integer_format($guests)));
			}
		}

		// Birthdays
		$_birthdays=ocf_find_birthdays();
		$birthdays=new ocp_tempcode();
		foreach ($_birthdays as $_birthday)
		{
			$birthday_link=build_url(array('page'=>'topics','type'=>'birthday','id'=>$_birthday['username']),get_module_zone('topics'));
			$birthday=do_template('OCF_BIRTHDAY_LINK',array('_GUID'=>'a98959187d37d80e134d47db7e3a52fa','AGE'=>array_key_exists('age',$_birthday)?integer_format($_birthday['age']):NULL,'PROFILE_URL'=>$GLOBALS['OCF_DRIVER']->member_profile_link($_birthday['id'],false,true),'USERNAME'=>$_birthday['username'],'BIRTHDAY_LINK'=>$birthday_link));
			$birthdays->attach($birthday);
		}
		if (!$birthdays->is_empty()) $birthdays=do_template('OCF_BIRTHDAYS',array('_GUID'=>'03da2c0d46e76407d63bff22aac354bd','BIRTHDAYS'=>$birthdays));

		// Usergroup keys
		$groups=array();
		$all_groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(true,false,false,NULL,NULL,true);
		foreach ($all_groups as $gid=>$gtitle)
		{
			if ($gid==db_get_first_id()) continue; // Throw out the first, guest
			if (array_key_exists($gid,$groups_seen))
				$groups[]=array('GCOLOUR'=>get_group_colour($gid),'GID'=>strval($gid),'GTITLE'=>$gtitle);
		}

		$foot=do_template('OCF_STATS',array(
			'_GUID'=>'sdflkdlfd303frksdf',
			'NEWEST_MEMBER_PROFILE_URL'=>$GLOBALS['OCF_DRIVER']->member_profile_link($stats['newest_member_id'],false,true),
			'NEWEST_MEMBER_USERNAME'=>$stats['newest_member_username'],
			'NUM_MEMBERS'=>integer_format($stats['num_members']),
			'NUM_TOPICS'=>integer_format($stats['num_topics']),
			'NUM_POSTS'=>integer_format($stats['num_posts']),
			'BIRTHDAYS'=>$birthdays,
			'USERS_ONLINE'=>$users_online,
			'USERS_ONLINE_URL'=>build_url(array('page'=>'onlinemembers'),get_module_zone('onlinemembers')),
			'GROUPS'=>$groups
		));
	} else $foot=new ocp_tempcode();

	$wrap=do_template('OCF_WRAPPER',array('_GUID'=>'456c21db6c09ae260accfa4c2a59fce7','TITLE'=>$title,'NOTIFICATIONS'=>$notifications,'HEAD'=>$head,'FOOT'=>$foot,'CONTENT'=>$content));

	return $wrap;
}

/**
 * Find all the birthdays in a certain day.
 *
 * @param  ?TIME	A timestamps that exists in the certain day (NULL: now).
 * @return array	List of maps describing the members whose birthday it is on the certain day.
 */
function ocf_find_birthdays($time=NULL)
{
	if (is_null($time)) $time=time();

	$num_members=$GLOBALS['FORUM_DB']->query_value('f_members','COUNT(*)');
	if ($num_members>365*20) return array(); // 20 birthdays on average per day is more than worth reporting! And would kill performance

	list($day,$month,$year)=explode(' ',date('j m Y',servertime_to_usertime($time)));
	$rows=$GLOBALS['FORUM_DB']->query_select('f_members',array('id','m_username','m_reveal_age','m_dob_year'),array('m_dob_day'=>intval($day),'m_dob_month'=>intval($month)));

	$birthdays=array();
	foreach ($rows as $row)
	{
		$birthday=array('id'=>$row['id'],'username'=>$row['m_username']);
		if ($row['m_reveal_age']==1) $birthday['age']=intval($year)-$row['m_dob_year'];

		$birthdays[]=$birthday;
	}

	return $birthdays;
}

/**
 * Turn a list of maps describing buttons, into a tempcode button panel.
 *
 * @param  array		List of maps (each map contains: url, img, title).
 * @return tempcode  The button panel.
 */
function ocf_screen_button_wrap($buttons)
{
	if (count($buttons)==0) return new ocp_tempcode();

	$b=new ocp_tempcode();
	foreach ($buttons as $button)
	{
		$b->attach(do_template('SCREEN_BUTTON',array('_GUID'=>'bdd441c40c5b03134ce6541335fece2c','REL'=>array_key_exists('rel',$button)?$button['rel']:NULL,'IMMEDIATE'=>$button['immediate'],'URL'=>$button['url'],'IMG'=>$button['img'],'TITLE'=>$button['title'])));
	}
	return $b;
}


