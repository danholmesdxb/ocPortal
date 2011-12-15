<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2010

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		activity_feed
 */

class Block_main_activities
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Warburton';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=1;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		$info['parameters']=array('max','param','member','mode','grow');
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('activities');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		$GLOBALS['SITE_DB']->create_table('activities',array(
			'id'=>'*AUTO',
			'a_member_id'=>'*USER',
			'a_language_string_code'=>'*ID_TEXT',
			'a_label_1'=>'SHORT_TEXT',
			'a_label_2'=>'SHORT_TEXT',
			'a_label_3'=>'SHORT_TEXT',
			'a_pagelink_1'=>'SHORT_TEXT',
			'a_pagelink_2'=>'SHORT_TEXT',
			'a_pagelink_3'=>'SHORT_TEXT',
			'a_time'=>'TIME',
			'a_addon'=>'ID_TEXT',
			'a_is_public'=>'SHORT_TEXT'
		));

		require_code('activities_submission');
		log_newest_activity(0,1000,true);
	}

	// CACHE MESSES WITH POST REMOVAL
	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	/*function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(array_key_exists(\'param\',$map)?$map[\'param\']:do_lang(\'ACTIVITIES_TITLE\'),array_key_exists(\'mode\',$map)?$map[\'mode\']:\'all\',get_member())';
		$info['ttl']=3;
		return $info;
	}*/

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		require_lang('activities');
		require_css('activities');
		require_javascript('javascript_activities');
		require_javascript('javascript_jquery');
		require_javascript('javascript_base64');
		$stored_max=$GLOBALS['SITE_DB']->query_value_null_ok('values', 'the_value', array('the_name'=>get_zone_name()."_".get_page_name()."_update_max"));

		if (is_null($stored_max))
		{
			if (!array_key_exists('max',$map))
			{
				$map['max']='10';
			}

			$GLOBALS['SITE_DB']->query_insert('values', array('the_value'=>$map['max'], 'the_name'=>get_zone_name()."_".get_page_name()."_update_max", 'date_and_time'=>time()));
		}
		else
		{
			if (!array_key_exists('max',$map))
			{
				$map['max']=$stored_max;
			}
			else
			{
				$GLOBALS['SITE_DB']->query_update('values', array('the_value'=>$map['max'], 'date_and_time'=>time()), array('the_name'=>get_zone_name()."_".get_page_name()."_update_max"));
			}
		}

		if (array_key_exists('param',$map))
			$title=$map['param'];
		else
			$title=do_lang_tempcode('ACTIVITIES_TITLE');

		// See if we're displaying for a specific member
		if (array_key_exists('member',$map))
		{
			// Assume that we've been given a member ID
			$username=$GLOBALS['FORUM_DRIVER']->get_member_row_field(intval($map['member']),'m_username');
			// See if that worked
			if (is_null($username))
			{
				// If not then we can try treating it as a username, if the forum
				// supports it
				if (method_exists($GLOBALS['FORUM_DRIVER'],'get_member_from_username'))
				{
					$username=$map['member'];
					$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($map['member']);
				}
				// If we've still got nothing then forget the parameter completely
				if (is_null($username))
				{
					return do_lang_tempcode('_USER_NO_EXIST',escape_html($map['member']));
				}
			}
			else
			{
				// It worked, so the parameter must have been a member ID
				$member_id=intval($map['member']);
			}
		}
		else
		{
			// No specific user. Use ourselves.
			$member_id=get_member();
			$username=$GLOBALS['FORUM_DRIVER']->get_member_from_username($member_id);
		}

		require_css('side_blocks');
		require_lang('activities');
		require_code('activities');
		require_code('addons_overview');

		$mode=(array_key_exists('mode',$map))?$map['mode']:'all';

		$viewer_id=get_member(); //We'll need this later anyway.

		$guest_id=$GLOBALS['FORUM_DRIVER']->get_guest_id();

		list($proceed_selection,$whereville)=find_activities($viewer_id,$mode,$member_id);

		$content=array();

		if ($proceed_selection===true)
		{
			$activities=$GLOBALS['SITE_DB']->query('SELECT * FROM '.get_table_prefix().'activities WHERE '.$whereville.' ORDER BY a_time DESC',$map['max']);

			if (!is_null($activities) && (count($activities)>0))
			{
				foreach ($activities as $row)
				{
					list($message,$memberpic,$datetime,$member_url)=render_activity($row);
					$content[]=array('ADDON_ICON'=>find_addon_icon($row['a_addon']), 'BITS'=>$message,'MEMPIC'=>$memberpic,'USERNAME'=>$GLOBALS['FORUM_DRIVER']->get_username($member_id), 'DATETIME'=>strval($datetime), 'MEMBER_URL'=>$member_url, 'LIID'=>strval($row['id']), 'ALLOW_REMOVE'=>(($row['a_member_id']==$viewer_id) || $can_remove_others)?'1':'0');
				}

				return do_template('BLOCK_MAIN_ACTIVITIES',array(
					'TITLE'=>$title,
					'MODE'=>strval($mode),
					'MEMBER_ID'=>strval($member_id),
					'CONTENT'=>$content,
					'GROW'=>(array_key_exists('grow',$map)? $map['grow']=='1' : true),
					'MAX'=>$map['max'],
				));
			}
		}

		switch($mode)
		{
			case 'own':
				$memberpic=$GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id); //Get avatar if available
				$donkey_url=build_url(array('page'=>'members', 'type'=>'view', 'id'=>$member_id), get_module_zone('members')); //Drop in a basic url that just comes straight back
				$member=$GLOBALS['FORUM_DB']->query_value_null_ok('f_members', 'm_username', array('id'=>$member_id));
		      if (is_null($member)) $member='no-one'; //Make sure it's not allowed to be null in a graceful fashion
		      break;
			case 'friends':
			case 'all':
			default:
				$memberpic='';
				$donkey_url=build_url(array('page'=>'members', 'type'=>'view', 'id'=>$member_id), get_module_zone('members'));
				$member='no-one';
		      break;
		}

		return do_template('BLOCK_MAIN_ACTIVITIES',array(
			'TITLE'=>$title,
			'MODE'=>strval($mode),
			'CONTENT'=>$content,
			'MEMBER_ID'=>strval($member_id),
			'GROW'=>(array_key_exists('grow',$map)? $map['grow']=='1' : true),
			'MAX'=>$map['max'],
		));
	}

}


