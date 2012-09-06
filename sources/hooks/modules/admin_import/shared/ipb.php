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
 * @package		import
 */

/**
 * Standard code module initialisation function.
 */
function init__hooks__modules__admin_import__shared__ipb()
{
	global $TOPIC_FORUM_CACHE;
	$TOPIC_FORUM_CACHE=array();

	global $STRICT_FILE;
	$STRICT_FILE=false; // Disable this for a quicker import that is quite liable to go wrong if you don't have the files in the right place
}

/**
 * Forum Driver.
 */
class Hook_ipb_base
{

	/**
	 * Decode an IPB post to be plain-text (ala comcode).
	 *
	 * @param  LONG_TEXT		IPB post
	 * @return LONG_TEXT		The cleaned post
	 */
	function clean_ipb_post($post)
	{
		$post=str_replace('<br />',"\n",str_replace('<br>',"\n",$post));
		$post=preg_replace('#\[size="?(\d+)"?\]#','[size="${1}of"]',$post);
		return @html_entity_decode($post,ENT_QUOTES,get_charset());
	}

	/**
	 * Fix non-XHTML parts of IPB posts.
	 *
	 * @param  LONG_TEXT		IPB post
	 * @return LONG_TEXT		The cleaned post
	 */
	function clean_ipb_post_2($post)
	{
		$post=str_replace('<br>','<br />',$post);
		return $post;
	}

	/**
	 * Probe a file path for DB access details.
	 *
	 * @param  string			The probe path
	 * @return array			A quartet of the details (db_name, db_user, db_pass, table_prefix)
	 */
	function probe_db_access($file_base)
	{
		global $INFO;
		if (!file_exists($file_base.'/conf_global.php'))
			warn_exit(do_lang_tempcode('BAD_IMPORT_PATH',escape_html('conf_global.php')));
		require_once($file_base.'/conf_global.php');

		return array($INFO['sql_database'],$INFO['sql_user'],$INFO['sql_pass'],$INFO['sql_tbl_prefix']);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_groups($db,$table_prefix,$file_base)
	{
		if (either_param('importer')=='ipb1')
		{
			global $INFO;
			require_once($file_base.'/conf_global.php');
		} else
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'conf_settings');
			$INFO=array();
			foreach ($rows as $row)
			{
				$key=$row['conf_key'];
				$val=$row['conf_value'];
				if ($val=='') $val=$row['conf_default'];
				$INFO[$key]=$val;
			}
		}
		$max_post_length_comcode=$INFO['max_post_length'];
		$max_sig_length_comcode=$INFO['max_sig_length'];
		list($max_avatar_width,$max_avatar_height)=explode('x',$INFO['avatar_dims']);

		$rows=$db->query('SELECT * FROM '.$table_prefix.'groups');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('group',strval($row['g_id']))) continue;

			list($_promotion_target,$_promotion_threshold)=explode('&',$row['g_promotion']);
			$promotion_target=intval($_promotion_target);
			$promotion_threshold=intval($_promotion_threshold);
			if (($promotion_target==-1) || ($promotion_threshold==-1))
			{
				$promotion_target=NULL;
				$promotion_threshold=NULL;
			}

			$id_new=$GLOBALS['FORUM_DB']->query_value_null_ok('f_groups g LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON g.g_name=t.id WHERE '.db_string_equal_to('text_original',$row['g_title']),'g.id');
			if (is_null($id_new))
			{
				$id_new=ocf_make_group(@html_entity_decode($row['g_title'],ENT_QUOTES,get_charset()),0,$row['g_access_cp'],$row['g_is_supmod'],'','',$promotion_target,$promotion_threshold,NULL,$row['g_avoid_flood']?0:$row['g_search_flood'],0,5,5,$max_avatar_width,$max_avatar_height,$max_post_length_comcode,$max_sig_length_comcode);
			}

			// Zone permissions
			if ($row['g_view_board']==0)
			{
				$GLOBALS['SITE_DB']->query_delete('group_zone_access',array('group_id'=>$id_new));
			}

			// Page permissions
			$denies=array();
			if ($row['g_use_search']==0) $denies[]=array('search',get_module_zone('search'));
			list($_contact_member,)=explode(':',$row['g_email_limit']);
			$contact_member=intval($_contact_member);
			if ($contact_member==0) $denies[]=array('contactmember',get_module_zone('contactmember'));
			foreach ($denies as $deny)
			{
				list($page,$zone)=$deny;
				if (is_null($zone)) continue;
				$test=$GLOBALS['SITE_DB']->query_value_null_ok('group_page_access','group_id',array('group_id'=>$id_new,'zone_name'=>$zone,'page_name'=>$page));
				if (is_null($test)) $GLOBALS['SITE_DB']->query_insert('group_page_access',array('group_id'=>$id_new,'zone_name'=>$zone,'page_name'=>$page));
			}

			// privileges
			set_specific_permission($id_new,'comcode_dangerous',$row['g_dohtml']);
			set_specific_permission($id_new,'view_member_photos',$row['g_mem_info']);
			set_specific_permission($id_new,'edit_own_midrange_content',$row['g_edit_topic']);
			set_specific_permission($id_new,'edit_own_lowrange_content',$row['g_edit_posts']);
			set_specific_permission($id_new,'delete_own_midrange_content',$row['g_delete_own_topics']);
			set_specific_permission($id_new,'bypass_validation_lowrange_content',$row['g_avoid_q']);
			set_specific_permission($id_new,'submit_midrange_content',$row['g_post_new_topics']);
			set_specific_permission($id_new,'submit_lowrange_content',$row['g_reply_other_topics']);
			set_specific_permission($id_new,'delete_own_lowrange_content',$row['g_delete_own_posts']);
			set_specific_permission($id_new,'close_own_topics',$row['g_open_close_posts']);
			set_specific_permission($id_new,'vote_in_polls',$row['g_vote_polls']);
			set_specific_permission($id_new,'use_pt',$row['g_use_pm']);
			set_specific_permission($id_new,'delete_account',$row['g_can_remove']);
			set_specific_permission($id_new,'access_closed_site',$row['g_access_offline']);

			import_id_remap_put('group',strval($row['g_id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_calendar($db,$table_prefix,$file_base)
	{
		require_code('calendar2');

		if (either_param('importer')=='ipb1')
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'calendar_events');
		} else
		{
			$rows=$db->query('SELECT ce.event_id AS eventid, ce.event_member_id AS userid, ce.event_tz AS event_repeat, ce.event_recurring AS repeat_unit,ce.* FROM '.$table_prefix.'cal_events as ce');
		}
		foreach ($rows as $row)
		{
			if (import_check_if_imported('event',strval($row['eventid']))) continue;

			$submitter=import_id_remap_get('member',strval($row['userid']),true);
			if (is_null($submitter)) $submitter=$GLOBALS['FORUM_DRIVER']->get_guest_id();

			$recurrence='none';
			$recurrences=NULL;

			if (either_param('importer')=='ipb1')
			{
				if ($row['event_repeat']!=0)
				{
					switch ($row['repeat_unit'])
					{
						case 'w':
							$recurrence='weekly';
							break;
						case 'm':
							$recurrence='monthly';
							break;
						case 'y':
							$recurrence='yearly';
							break;
					}
				}

				$event_title=$row['title'];
				$event_text=$row['event_text'];
				$private_event=$row['priv_event'];
				$start_year=$row['year'];
				$start_month=$row['month'];
				$start_day=$row['mday'];

				$end_year=$row['end_year'];
				$end_month=$row['end_month'];
				$end_day=$row['end_day'];

			} else
			{
				if ($row['event_repeat']!=0)
				{
					switch ($row['repeat_unit'])
					{
						case '1':
							$recurrence='weekly';
							break;
						case '2':
							$recurrence='monthly';
							break;
						case '3':
							$recurrence='yearly';
							break;
					}
				}

				$event_title=$row['event_title'];
				$event_text=$row['event_content'];
				$private_event=$row['event_private'];
				$start_year=date('Y',$row['event_unix_from']);
				$start_month=date('n',$row['event_unix_from']);
				$start_day=date('j',$row['event_unix_from']);

				$end_year=date('Y',$row['event_unix_to']);
				$end_month=date('n',$row['event_unix_to']);
				$end_day=date('j',$row['event_unix_to']);
			}

			ocf_over_msn();
			//$id_new=add_calendar_event(db_get_first_id()+1,$recurrence,$recurrences,0,$event_title,$event_text,3,1-$private_event,$start_year,$start_month,$start_day,'day_of_month',0,0,$end_year,$end_month,$end_day,'day_of_month',NULL,1,0,0,$submitter); //old code

			$id_new=add_calendar_event(db_get_first_id()+1,$recurrence,$recurrences,0,$event_title,$event_text,3,1-$private_event,$start_year,$start_month,$start_day,'day_of_month',0,0,$end_year,$end_month,$end_day,'day_of_month',NULL,NULL,NULL,1,1,1,1,1,'',$submitter);

			ocf_over_local();

			import_id_remap_put('event',strval($row['eventid']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_members($db,$table_prefix,$file_base)
	{
		$row_start=0;
		$rows=array();
		do
		{
			$query='SELECT * FROM '.$table_prefix.'members ORDER BY id';
			if (either_param('importer')=='ipb2') $query='SELECT * FROM '.$table_prefix.'members m LEFT JOIN '.$table_prefix.'members_converge c ON c.converge_id=m.id ORDER BY id';
			$rows=$db->query($query,200,$row_start);
			foreach ($rows as $row)
			{
				$row['name']=@html_entity_decode($row['name'],ENT_QUOTES,get_charset());

				if (import_check_if_imported('member',strval($row['id']))) continue;

				if ($row['id']==0)
				{
					import_id_remap_put('member','0',$GLOBALS['OCF_DRIVER']->get_guest_id());
					continue;
				}
				$test=$GLOBALS['OCF_DRIVER']->get_member_from_username($row['name']);
				if (!is_null($test))
				{
					import_id_remap_put('member',strval($row['id']),$test);
					continue;
				}

				if ($row['mgroup']==0) $row['mgroup']=db_get_first_id(); // Not really necessary - but repairs problem in my test db
				$primary_group=import_id_remap_get('group',strval($row['mgroup']));
				$language=is_null($row['language'])?'':strtoupper($row['language']);
				if ((!file_exists(get_custom_file_base().'/lang_custom/'.$language)) && (!file_exists(get_file_base().'/lang/'.$language)))
					$language='';

				if (either_param('importer')=='ipb1')
				{
					$custom_fields=array(
											ocf_make_boiler_custom_field('im_icq')=>$row['icq_number'],
											ocf_make_boiler_custom_field('im_aim')=>$row['aim_name'],
											ocf_make_boiler_custom_field('im_msn')=>$row['msnname'],
											ocf_make_boiler_custom_field('im_yahoo')=>$row['yahoo'],
											ocf_make_boiler_custom_field('interests')=>$row['interests'],
											ocf_make_boiler_custom_field('location')=>$row['location'],
										);
					if ($row['website']!='')
						$custom_fields[ocf_make_boiler_custom_field('website')]=(strlen($row['website'])>0)?('[url]'.$row['website'].'[/url]'):'';
				} else
				{
					$custom_fields=array();
					$signature='';
				}

				$rows2=$db->query('SELECT * FROM '.$table_prefix.'member_extra WHERE id='.strval($row['id']));
				$notes='';
				if (array_key_exists(0,$rows2))
				{
					$row2=$rows2[0];

					$custom_fields[ocf_make_boiler_custom_field('SELF_DESCRIPTION')]=@html_entity_decode($row2['bio'],ENT_QUOTES,get_charset());
					$notes=$row2['notes'];

					if (either_param('importer')=='ipb2')
					{
						ocf_over_msn();
						$signature=html_to_comcode($this->clean_ipb_post_2($row2['signature']));
						ocf_over_local();
						$custom_fields=array(
												ocf_make_boiler_custom_field('im_aim')=>$row2['aim_name'],
												ocf_make_boiler_custom_field('im_msn')=>$row2['msnname'],
												ocf_make_boiler_custom_field('im_yahoo')=>$row2['yahoo'],
												ocf_make_boiler_custom_field('interests')=>$row2['interests'],
												ocf_make_boiler_custom_field('location')=>$row2['location'],
											);
						if ($row2['website']!='')
							$custom_fields[ocf_make_boiler_custom_field('website')]=(strlen($row2['website'])>0)?('[url]'.$row2['website'].'[/url]'):'';
						if ($row2['icq_number']!=0)
							$custom_fields[ocf_make_boiler_custom_field('im_icq')]=$row2['icq_number'];
					}
				}
				if (either_param('importer')=='ipb1')
				{
					ocf_over_msn();
					$signature=html_to_comcode($this->clean_ipb_post_2($row['signature']));
					ocf_over_local();
				}
				$validated=1;

				if (either_param('importer')=='ipb2')
				{
					$password=$row['converge_pass_hash'];
					$type='converge';
					$salt=$row['converge_pass_salt'];
				} else
				{
					$password=$row['password'];
					$type='md5';
					$salt='';
				}
				if (is_null($password)) $password='';
				$id_new=ocf_make_member($row['name'],$password,$row['email'],NULL,$row['bday_day'],$row['bday_month'],$row['bday_year'],$custom_fields,strval($row['time_offset']),$primary_group,$validated,$row['joined'],$row['last_visit'],'','',$signature,0,1,1,$row['title'],'','',$row['view_sigs'],$row['auto_track'],$language,$row['email_pm'],$row['email_pm'],$notes,$row['ip_address'],'',false,$type,$salt);

				if ($row['mgroup']==5) $GLOBALS['FORUM_DB']->query_update('f_members',array('m_is_perm_banned'=>1),array('id'=>$id_new),'',1);

				import_id_remap_put('member',strval($row['id']),$id_new);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_member_files($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;

		$row_start=0;
		$rows=array();
		do
		{
			$query='SELECT * FROM '.$table_prefix.'members ORDER BY id';
			$rows=$db->query($query,200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('member_files',strval($row['id']))) continue;

				$member_id=import_id_remap_get('member',strval($row['id']));

				$photo_url='';
				$photo_thumb_url='';

				$rows2=$db->query('SELECT * FROM '.$table_prefix.'member_extra WHERE id='.strval($row['id']));
				if (array_key_exists(0,$rows2))
				{
					$row2=$rows2[0];

					if ($row2['photo_type']=='upload')
					{
						$filename=rawurldecode($row2['photo_location']);
						if ((file_exists(get_custom_file_base().'/uploads/ocf_photos/'.$filename)) || (@rename($file_base.'/uploads/'.$filename,get_custom_file_base().'/uploads/ocf_photos/'.$filename)))
						{
							$photo_url='uploads/ocf_photos/'.$filename;
							sync_file($photo_url);
						} else
						{
							if ($STRICT_FILE) warn_exit(do_lang_tempcode('MISSING_PHOTO',$filename));
							$photo_url='';
						}
					} else
					{
						$photo_url=$row2['photo_location'];
						$rrpos=strrpos($photo_url,'/');
						$filename=(($rrpos===false)?$photo_url:substr($photo_url,$rrpos));
					}

					if (($photo_url!='') && (function_exists('imagecreatefromstring')))
					{
						$photo_thumb_url='uploads/ocf_photos_thumbs/'.find_derivative_filename('ocf_photos_thumbs',$filename,true);
						require_code('images');
						convert_image($photo_url,$photo_thumb_url,-1,-1,intval(get_option('thumb_width')),false,NULL,true);
					}

					if (either_param('importer')=='ipb2')
					{
						$row['avatar']=$row2['avatar_location'];
						$row['avatar_type']=$row2['avatar_type'];
					}
				}
				if (either_param('importer')=='ipb2')
				{
					if (!array_key_exists('avatar',$row)) $row['avatar']=NULL;
				}

				$avatar_url='';
				switch ($row['avatar'])
				{
					case NULL:
						break;
					case 'noavatar':
						break;
					default:
						if (substr($row['avatar'],0,7)=='upload:')
						{
							$filename=substr($row['avatar'],7);
							if ((file_exists(get_custom_file_base().'/uploads/ocf_avatars/'.$filename)) || (@rename($file_base.'/uploads/'.$filename,get_custom_file_base().'/uploads/ocf_avatars/'.$filename)))
							{
								$avatar_url='uploads/ocf_avatars/'.$filename;
								sync_file($avatar_url);
							} else
							{
								if ($STRICT_FILE) warn_exit(do_lang_tempcode('MISSING_AVATAR',$filename));
								$avatar_url='';
							}
						}
						elseif (url_is_local($row['avatar']))
						{
							$filename=rawurldecode($row['avatar']);
							if ((file_exists(get_custom_file_base().'/uploads/ocf_avatars/'.$filename)) || (@rename($file_base.'/uploads/'.$filename,get_custom_file_base().'/uploads/ocf_avatars/'.$filename)))
							{
								$avatar_url='uploads/ocf_avatars/'.substr($filename,strrpos($filename,'/'));
								sync_file($avatar_url);
							} else
							{
								// Try as a pack avatar then
								$filename=rawurldecode($row['avatar']);
								$striped_filename=str_replace('/','_',$filename);
								if ((file_exists(get_custom_file_base().'/uploads/ocf_avatars/'.$striped_filename)) || (@rename($file_base.'/style_avatars/'.$filename,get_custom_file_base().'/uploads/ocf_avatars/'.$striped_filename)))
								{
									$avatar_url='uploads/ocf_avatars/'.substr($filename,strrpos($filename,'/'));
									sync_file($avatar_url);
								} else
								{
									if ($STRICT_FILE) warn_exit(do_lang_tempcode('MISSING_AVATAR',$filename));
									$avatar_url='';
								}
							}
						} else $avatar_url=$row['avatar'];
				}

				$GLOBALS['FORUM_DB']->query_update('f_members',array('m_avatar_url'=>$avatar_url,'m_photo_url'=>$photo_url,'m_photo_thumb_url'=>$photo_thumb_url),array('id'=>$member_id),'',1);

				import_id_remap_put('member_files',strval($row['id']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_custom_profile_fields($db,$table_prefix,$file_base)
	{
		$where='*';
		if (either_param('importer')=='ipb2')
			$where='pf_position as forder,pf_type as ftype,pf_id as fid,pf_title as ftitle,pf_desc as fdesc,pf_member_hide as fhide,pf_member_edit as fedit,pf_show_on_reg as freq';
		$rows=$db->query('SELECT '.$where.' FROM '.$table_prefix.'pfields_data');
		$members=$db->query('SELECT * FROM '.$table_prefix.'pfields_content');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('cpf',strval($row['fid']))) continue;

			$type='short_text';
			if ($row['ftype']=='text') $type='short_text';
			elseif ($row['ftype']=='area') $type='long_text';

			$id_new=$GLOBALS['FORUM_DB']->query_value_null_ok('f_custom_fields f LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON f.cf_name=t.id','f.id',array('text_original'=>$row['ftitle']));
			if (is_null($id_new))
			{
				$id_new=ocf_make_custom_field($row['ftitle'],0,$row['fdesc'],'',1-$row['fhide'],1-$row['fhide'],$row['fedit'],0,$type,$row['freq'],0,0,$row['forder'],'',true);
			}

			foreach ($members as $member)
			{
				ocf_set_custom_field($member['member_id'],$id_new,@html_entity_decode($member['field_'.strval($row['fid'])],ENT_QUOTES,get_charset()));
			}

			import_id_remap_put('cpf',strval($row['fid']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_topics($db,$table_prefix,$file_base)
	{
		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'topics ORDER BY tid',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('topic',strval($row['tid']))) continue;

				$forum_id=import_id_remap_get('forum',strval($row['forum_id']),true);
				if (is_null($forum_id))
				{
//					import_id_remap_put('topic',strval($row['tid']),-1);  Want to allow coming back if accidently a forum was missed
					continue;
				}

				$emoticon='';
				switch($row['icon_id'])
				{
					case 1:
						$emoticon='ocf_emoticons/smile';
						break;
					case 2:
						$emoticon='ocf_emoticons/dry';
						break;
					case 3:
						$emoticon='ocf_emoticons/glee';
						break;
					case 4:
						$emoticon='ocf_emoticons/cheeky';
						break;
					case 5:
						$emoticon='ocf_emoticons/shocked';
						break;
					case 6:
						$emoticon='ocf_emoticons/lol';
						break;
					case 7:
						$emoticon='ocf_emoticons/angry';
						break;
					case 8:
						$emoticon='ocf_emoticons/sick';
						break;
					case 9:
						$emoticon='ocf_emoticons/confused';
						break;
					case 10:
						$emoticon='ocf_emoticons/cool';
						break;
					case 11:
						$emoticon='ocf_emoticons/thumbs';
						break;
					case 12:
						$emoticon='ocf_emoticons/wub';
						break;
					case 13:
						$emoticon='ocf_emoticons/upsidedown';
						break;
					case 14:
						$emoticon='ocf_emoticons/sarcy';
						break;
				}

				$id_new=ocf_make_topic($forum_id,@html_entity_decode($row['description'],ENT_QUOTES,get_charset()),$emoticon,$row['approved'],$row['state']=='open'?1:0,$row['pinned'],0,0,NULL,NULL,false,$row['views']);

				import_id_remap_put('topic',strval($row['tid']),$id_new);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_posts($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;

		$row_start=0;
		$rows=array();
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'posts ORDER BY pid',200,$row_start);
			foreach ($rows as $row)
			{
				if ((get_param_integer('keep_import_test',0)==1) && ($row['new_topic']==0)) continue;

				if (import_check_if_imported('post',strval($row['pid']))) continue;

				$topic_id=import_id_remap_get('topic',strval($row['topic_id']),true);
				if (is_null($topic_id))
				{
					import_id_remap_put('post',strval($row['pid']),-1);
					continue;
				}
				$member_id=import_id_remap_get('member',strval($row['author_id']),true);
				if (is_null($member_id)) $member_id=db_get_first_id();

				// This speeds up addition... using the cache can reduce about 7/8 of a query per post on average
				global $TOPIC_FORUM_CACHE;
				if (array_key_exists($topic_id,$TOPIC_FORUM_CACHE))
				{
					$forum_id=$TOPIC_FORUM_CACHE[$topic_id];
				} else
				{
					$forum_id=$GLOBALS['FORUM_DB']->query_value_null_ok('f_topics','t_forum_id',array('id'=>$topic_id));
					if (is_null($forum_id)) continue;
					$TOPIC_FORUM_CACHE[$topic_id]=$forum_id;
				}

				$title='';
				if ($row['new_topic']==1)
				{
					$topics=$db->query('SELECT * FROM '.$table_prefix.'topics WHERE tid='.strval($row['topic_id']));
					$title=strip_tags(@html_entity_decode($topics[0]['title'],ENT_QUOTES,get_charset()));
				}
				elseif (!is_null($row['post_title'])) $title=@html_entity_decode($row['post_title'],ENT_QUOTES,get_charset());

				ocf_over_msn();
				$post=html_to_comcode($this->clean_ipb_post_2($row['post']));
				ocf_over_local();

				$last_edit_by=NULL;
				if (!is_null($row['edit_name']))
				{
					$last_edit_by=$GLOBALS['OCF_DRIVER']->get_member_from_username(@html_entity_decode($row['edit_name'],ENT_QUOTES,get_charset()));
				}

				if (either_param('importer')=='ipb2')
				{
					$post=str_replace('style_emoticons/<#EMO_DIR#>','[/html]{$BASE_URL}[html]/data/legacy_emoticons',$post);

					$end=0;
					while (($pos=strpos($post,'[right]'))!==false)
					{
						$e_pos=strpos($post,'[/right]',$pos);
						if ($e_pos===false) break;
						$end=$e_pos+strlen('[/right]');
						$segment=substr($post,$pos,$end-$pos);
						global $LAX_COMCODE;
						$temp=$LAX_COMCODE;
						$LAX_COMCODE=true;
						$_comcode=comcode_to_tempcode($segment,$member_id);
						$LAX_COMCODE=$temp;
						$comcode=$_comcode->evaluate();
						$comcode=str_replace($comcode,get_base_url(),'{$BASE_URL}');
						$post=substr($post,0,$pos).$comcode.substr($post,$end);
					}
				}

				$id_new=ocf_make_post($topic_id,$title,$post,0,$row['new_topic']==1,1-$row['queued'],0,@html_entity_decode($row['author_name'],ENT_QUOTES,get_charset()),$row['ip_address'],$row['post_date'],$member_id,NULL,$row['edit_time'],$last_edit_by,false,false,$forum_id,false);

				import_id_remap_put('post',strval($row['pid']),$id_new);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_post_files($db,$table_prefix,$file_base)
	{
		global $STRICT_FILE;
		require_code('attachments2');
		require_code('attachments3');
		require_code('images');

		$row_start=0;
		$select=(either_param('importer')=='ipb1')?'pid,attach_id,attach_file,attach_hits,post_date':'pid,post_date';
		$rows=array();
		do
		{
			$rows=$db->query('SELECT '.$select.' FROM '.$table_prefix.'posts ORDER BY pid',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('post_files',strval($row['pid']))) continue;

				$post_id=import_id_remap_get('post',strval($row['pid']),true);
				if (is_null($post_id)) continue;

				$post_row=$GLOBALS['FORUM_DB']->query_select('f_posts p LEFT JOIN '.$GLOBALS['FORUM_DB']->get_table_prefix().'translate t ON p.p_post=t.id',array('p_time','text_original','p_poster','p_post'),array('p.id'=>$post_id),'',1);
				if (!array_key_exists(0,$post_row))
				{
					import_id_remap_put('post_files',strval($row['pid']),1);
					continue; // Orphaned post
				}
				$post=$post_row[0]['text_original'];
				$lang_id=$post_row[0]['p_post'];
				$member_id=import_id_remap_get('member',$post_row[0]['p_poster']);
				$post_date=$post_row[0]['p_time'];

				if (either_param('importer')=='ipb1')
				{
					$has_attachment=false;
					if ($row['attach_id']!='')
					{
						$target_path=get_custom_file_base().'/uploads/attachments/'.$row['attach_id'];
						if ((file_exists(get_custom_file_base().'/uploads/attachments/'.$row['attach_id'])) || (@rename($file_base.'/uploads/'.$row['attach_id'],$target_path)))
						{
							$url='uploads/attachments/'.$row['attach_id'];
							sync_file($url);
							$thumb_url='';
							if (is_image($target_path))
							{
								/*
								require_code('images');
								$thumb_url='uploads/attachments_thumbs/'.$row['attach_id'];
								convert_image($url,$thumb_url,-1,-1,intval(get_option('thumb_width')),false,NULL,true);*/
							}
							$_a_id=$GLOBALS['SITE_DB']->query_insert('attachments',array('a_member_id'=>$member_id,'a_file_size'=>@filesize($target_path),'a_url'=>$url,'a_thumb_url'=>$thumb_url,'a_original_filename'=>$row['attach_file'],'a_num_downloads'=>$row['attach_hits'],'a_last_downloaded_time'=>NULL,'a_add_time'=>$row['post_date'],'a_description'=>''),true);
							$has_attachment=true;
						} else
						{
							if ($STRICT_FILE) warn_exit(do_lang_tempcode('MISSING_ATTACHMENT',$row['attach_location']));
						}
					}
				} else
				{
					if (either_param('importer')=='ipb1')
					{
						$attachments=$db->query('SELECT * FROM '.$table_prefix.'attachments WHERE attach_pid='.strval($row['pid']).' AND attach_approved=1');
					} else
					{
						$attachments=$db->query('SELECT * FROM '.$table_prefix.'attachments WHERE attach_rel_id='.strval($row['pid']).' AND '.db_string_equal_to('attach_rel_module','post'));
					}
					$i=0;
					$a_id=array();
					foreach ($attachments as $attachment)
					{
						$target_path=get_custom_file_base().'/uploads/attachments/'.$attachment['attach_location'];
						if ((file_exists(get_custom_file_base().'/uploads/attachments/'.$attachment['attach_location'])) || (@rename($file_base.'/uploads/'.$attachment['attach_location'],$target_path)))
						{
							$url='uploads/attachments/'.$attachment['attach_location'];
							sync_file($url);
							$thumb_url='';
							if (is_image($target_path))
							{
								/*
								require_code('images');
								$thumb_url='uploads/attachments_thumbs/'.$attachment['attach_location'];
								convert_image($url,$thumb_url,-1,-1,intval(get_option('thumb_width')),false,NULL,true);*/
							}
							$a_id[$i]=$GLOBALS['SITE_DB']->query_insert('attachments',array('a_member_id'=>$member_id,'a_file_size'=>$attachment['attach_filesize'],'a_url'=>$url,'a_thumb_url'=>$thumb_url,'a_original_filename'=>$attachment['attach_file'],'a_num_downloads'=>$attachment['attach_hits'],'a_last_downloaded_time'=>NULL,'a_add_time'=>$post_date,'a_description'=>''),true);
							$has_attachment=true;
						} else
						{
							if ($STRICT_FILE) warn_exit(do_lang_tempcode('MISSING_ATTACHMENT',$attachment['attach_location']));
						}
						$i++;
					}
				}

				if (either_param('importer')=='ipb1')
				{
					if ($has_attachment)
					{
						$GLOBALS['SITE_DB']->query_insert('attachment_refs',array('r_referer_type'=>'ocf_post','r_referer_id'=>strval($post_id),'a_id'=>$_a_id));
						$post.="\n\n".'[attachment]'.strval($_a_id).'[/attachment]';
						ocf_over_msn();
						update_lang_comcode_attachments($lang_id,$post,'ocf_post',strval($post_id));
						ocf_over_local();
					}
				} elseif (count($a_id)!=0)
				{
					$i=0;
					foreach ($attachments as $attachment)
					{
						if (array_key_exists($i,$a_id))
						{
							$GLOBALS['SITE_DB']->query_insert('attachment_refs',array('r_referer_type'=>'ocf_post','r_referer_id'=>strval($post_id),'a_id'=>$a_id[$i]));
							$post.="\n\n".'[attachment]'.$a_id[$i].'[/attachment]';
						}
						$i++;
					}
					ocf_over_msn();
					update_lang_comcode_attachments($lang_id,$post,'ocf_post',strval($post_id));
					ocf_over_local();
				}

				import_id_remap_put('post_files',strval($row['pid']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_polls_and_votes($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'polls');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('poll',strval($row['pid']))) continue;

			$topic_id=import_id_remap_get('topic',strval($row['tid']),true);
			if (is_null($topic_id)) continue;

			$topic=$db->query('SELECT * FROM '.$table_prefix.'topics WHERE tid='.strval($row['tid']));
			$is_open=($topic[0]['poll_state']=='open')?1:0;

			$_answers=unserialize($row['choices']);
			$answers=array(); // An array of answers
			foreach ($_answers as $answer)
			{
				$answers[]=array(@html_entity_decode($answer[1],ENT_QUOTES,get_charset()),$answer[2]);
			}

			$rows2=$db->query('SELECT * FROM '.$table_prefix.'voters WHERE tid='.strval($row['tid']));

			$id_new=ocf_make_poll($topic_id,@html_entity_decode($row['poll_question'],ENT_QUOTES,get_charset()),0,$is_open,1,1,0,$answers,false);

			$answers=collapse_1d_complexity('id',$GLOBALS['FORUM_DB']->query_select('f_poll_answers',array('id'),array('pa_poll_id'=>$id_new))); // Effectively, a remapping from IPB vote number to ocP vote number
			$vote_list=array();
			$j=0;
			foreach ($_answers as $answer)
			{
				for ($i=0;$i<intval($answer[2]);$i++) // For each vote of this answer
				{
					array_push($vote_list,$answers[$j]); // Push the mapped ocPortal vote id onto the list of votes
				}
				$j++;
			}

			foreach ($rows2 as $row2) // For all votes. We have to match votes to members - but it is arbitrary because no such mapping is stored from IPB
			{
				$member_id=import_id_remap_get('member',$row2['member_id'],true);
				if (is_null($member_id)) $member_id=db_get_first_id();

				if ($member_id!=$GLOBALS['OCF_DRIVER']->get_guest_id())
				{
					$answer=array_pop($vote_list);
					if (is_null($answer)) $answer=-1;
					$GLOBALS['FORUM_DB']->query_insert('f_poll_votes',array('pv_poll_id'=>$id_new,'pv_member_id'=>$member_id,'pv_answer_id'=>$answer));
				}
			}

			import_id_remap_put('poll',strval($row['pid']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_multi_moderations($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'topic_mmod');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('multi_moderation',strval($row['mm_id']))) continue;

			if ($row['topic_move']>0) $move_to=import_id_remap_get('forum',strval($row['topic_move']),true); else $move_to=NULL;
			$pin_state=NULL;
			if ($row['topic_pin']=='pin') $pin_state=1;
			elseif ($row['topic_pin']=='unpin') $pin_state=0;
			$open_state=NULL;
			if ($row['topic_state']=='close') $open_state=0;
			elseif ($row['topic_state']=='open') $open_state=1;
			$id_new=ocf_make_multi_moderation(@html_entity_decode($row['mm_title'],ENT_QUOTES,get_charset()),'[html]'.$row['topic_reply_content'].'[/html]',$move_to,$pin_state,0,$open_state);

			import_id_remap_put('multi_moderation',strval($row['mm_id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_notifications($db,$table_prefix,$file_base)
	{
		require_code('notifications');

		$rows=$db->query('SELECT * FROM '.$table_prefix.'forum_tracker');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('forum_notification',strval($row['frid']))) continue;

			$member_id=import_id_remap_get('member',$row['member_id'],true);
			if (is_null($member_id)) continue;
			$forum_id=import_id_remap_get('forum',strval($row['forum_id']),true);
			if (is_null($forum_id)) continue;
			enable_notifications('ocf_topic','forum:'.strval($forum_id),$member_id);

			import_id_remap_put('forum_notification',strval($row['frid']),1);
		}
		$row_start=0;
		do
		{
			$rows=$db->query('SELECT * FROM '.$table_prefix.'tracker',200,$row_start);
			foreach ($rows as $row)
			{
				if (import_check_if_imported('topic_notification',strval($row['trid']))) continue;

				$member_id=import_id_remap_get('member',strval($row['member_id']),true);
				if (is_null($member_id)) continue;
				$topic_id=import_id_remap_get('topic',strval($row['topic_id']),true);
				if (is_null($topic_id)) continue;
				enable_notifications('ocf_topic',strval($topic_id),$member_id);

				import_id_remap_put('topic_notification',strval($row['trid']),1);
			}

			$row_start+=200;
		}
		while (count($rows)>0);
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_ocf_warnings($db,$table_prefix,$file_base)
	{
		$select='*';
		if (either_param('importer')=='ipb2') $select='wlog_id AS id,wlog_mid,wlog_notes,wlog_date,wlog_addedby,wlog_contact_content';
		$rows=$db->query('SELECT '.$select.' FROM '.$table_prefix.'warn_logs');
		foreach ($rows as $row)
		{
			if (import_check_if_imported('warning',strval($row['id']))) continue;

			$member_id=import_id_remap_get('member',strval($row['wlog_mid']),true);
			if (is_null($member_id)) continue;
			$by=import_id_remap_get('member',strval($row['wlog_addedby']));
			$id_new=ocf_make_warning($member_id,@html_entity_decode($row['wlog_contact_content'],ENT_QUOTES,get_charset()),$by,$row['wlog_date']);

			import_id_remap_put('warning',strval($row['id']),$id_new);
		}
	}

	/**
	 * Standard import function.
	 *
	 * @param  object			The DB connection to import from
	 * @param  string			The table prefix the target prefix is using
	 * @param  PATH			The base directory we are importing from
	 */
	function import_wordfilter($db,$table_prefix,$file_base)
	{
		$rows=$db->query('SELECT * FROM '.$table_prefix.'badwords');
		$rows=remove_duplicate_rows($rows,'type');
		foreach ($rows as $row)
		{
			add_wordfilter_word($row['type'],$row['swop'],$row['m_exact']);
		}
	}

}


