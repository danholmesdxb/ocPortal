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
 * @package		actionlog
 */

/**
 * Module page class.
 */
class Module_admin_actionlog
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'VIEW_ACTION_LOGS');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		require_all_lang();

		require_code('global4');

		$type=get_param('type','misc');

		if ($type=='misc') return $this->search();
		if ($type=='list') return $this->choose_action();
		if ($type=='view') return $this->view_action();

		return new ocp_tempcode();
	}

	/**
	 * The UI to choose filter parameters.
	 *
	 * @return tempcode		The UI
	 */
	function search()
	{
		$title=get_screen_title('VIEW_ACTION_LOGS');

		set_helper_panel_pic('pagepics/actionlog');
		set_helper_panel_tutorial('tut_trace');

		require_code('form_templates');

		$fields=new ocp_tempcode();

		// Possible selections for member filter
		$_member_choice_list=array();
		if (get_forum_type()=='ocf')
		{
			if ($GLOBALS['FORUM_DB']->query_select_value('f_moderator_logs','COUNT(DISTINCT l_by)')<5000)
			{
				$members=list_to_map('l_by',$GLOBALS['FORUM_DB']->query_select('f_moderator_logs',array('l_by','COUNT(*) AS cnt'),NULL,'GROUP BY l_by ORDER BY cnt DESC'));
				foreach ($members as $member)
				{
					$username=$GLOBALS['FORUM_DRIVER']->get_username($member['l_by']);
					if (is_null($username)) $username=strval($member['l_by']);
					$_member_choice_list[$member['l_by']]=array($username,$member['cnt']);
				}
			}
		}
		if ($GLOBALS['SITE_DB']->query_select_value('adminlogs','COUNT(DISTINCT the_user)')<5000)
		{
			$_staff=list_to_map('the_user',$GLOBALS['SITE_DB']->query_select('adminlogs',array('the_user','COUNT(*) AS cnt'),NULL,'GROUP BY the_user ORDER BY cnt DESC'));
			foreach ($_staff as $staff)
			{
				$username=$GLOBALS['FORUM_DRIVER']->get_username($staff['the_user']);
				if (is_null($username)) $username=strval($staff['the_user']);
				if (!array_key_exists($staff['the_user'],$_member_choice_list))
				{
					$_member_choice_list[$staff['the_user']]=array($username,$staff['cnt']);
				} else
				{
					$_member_choice_list[$staff['the_user']][1]+=$staff['cnt'];
				}
			}
		}
		$member_choice_list=new ocp_tempcode();
		$member_choice_list->attach(form_input_list_entry('-1',true,do_lang_tempcode('_ALL')));
		foreach ($_member_choice_list as $id=>$user_actions)
		{
			list($username,$action_count)=$user_actions;
			$member_choice_list->attach(form_input_list_entry(strval($id),false,do_lang(($action_count==1)?'ACTIONLOG_USERCOUNT_UNI':'ACTIONLOG_USERCOUNT',$username,integer_format($action_count))));
		}
		$fields->attach(form_input_list(do_lang_tempcode('USERNAME'),'','id',$member_choice_list,NULL,true));

		// Possible selections for action type filter
		$_action_type_list=array();
		$rows1=$GLOBALS['FORUM_DB']->query_select('f_moderator_logs',array('DISTINCT l_the_type'));
		$rows2=$GLOBALS['SITE_DB']->query_select('adminlogs',array('DISTINCT the_type'));
		foreach ($rows1 as $row)
		{
			$lang=do_lang($row['l_the_type'],NULL,NULL,NULL,NULL,false);
			if (!is_null($lang)) $_action_type_list[$row['l_the_type']]=$lang;
		}
		foreach ($rows2 as $row)
		{
			$lang=do_lang($row['the_type'],NULL,NULL,NULL,NULL,false);
			if (!is_null($lang)) $_action_type_list[$row['the_type']]=$lang;
		}
		asort($_action_type_list);
		$action_type_list=new ocp_tempcode();
		$action_type_list->attach(form_input_list_entry('',true,do_lang_tempcode('_ALL')));
		foreach ($_action_type_list as $lang_id=>$lang)
		{
			$action_type_list->attach(form_input_list_entry($lang_id,false,$lang));
		}
		$fields->attach(form_input_list(do_lang_tempcode('ACTION'),'','to_type',$action_type_list,NULL,false,false));

		// Filters
		$fields->attach(form_input_line(do_lang_tempcode('PARAMETER_A'),'','param_a','',false));
		$fields->attach(form_input_line(do_lang_tempcode('PARAMETER_B'),'','param_b','',false));

		$post_url=build_url(array('page'=>'_SELF','type'=>'list'),'_SELF',NULL,false,true);
		$submit_name=do_lang_tempcode('VIEW_ACTION_LOGS');

		breadcrumb_set_self(do_lang_tempcode('VIEW_ACTION_LOGS'));

		return do_template('FORM_SCREEN',array('_GUID'=>'f2c6eda24e0e973aa7e253054f6683a5','GET'=>true,'SKIP_VALIDATION'=>true,'HIDDEN'=>'','TITLE'=>$title,'TEXT'=>'','URL'=>$post_url,'FIELDS'=>$fields,'SUBMIT_NAME'=>$submit_name));
	}

	/**
	 * The UI to show a results table of moderation actions for a moderator.
	 *
	 * @return tempcode		The UI
	 */
	function choose_action()
	{
		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('VIEW_ACTION_LOGS'))));
		breadcrumb_set_self(do_lang_tempcode('RESULTS'));

		$title=get_screen_title('VIEW_ACTION_LOGS');

		require_code('templates_internalise_screen');
		$test_tpl=internalise_own_screen($title);
		if (is_object($test_tpl)) return $test_tpl;

		$id=get_param_integer('id',-1);
		$start=get_param_integer('start',0);
		$max=get_param_integer('max',50);
		$sortables=array('date_and_time'=>do_lang_tempcode('DATE_TIME'),'the_type'=>do_lang_tempcode('ACTION'));
		$test=explode(' ',get_param('sort','date_and_time DESC'),2);
		if (count($test)==1) $test[1]='DESC';
		list($sortable,$sort_order)=$test;
		if (((strtoupper($sort_order)!='ASC') && (strtoupper($sort_order)!='DESC')) || (!array_key_exists($sortable,$sortables)))
			log_hack_attack_and_exit('ORDERBY_HACK');
		inform_non_canonical_parameter('sort');

		require_code('templates_results_table');
		$field_titles=array(do_lang_tempcode('USERNAME'),do_lang_tempcode('DATE_TIME'),do_lang_tempcode('ACTION'),do_lang_tempcode('PARAMETER_A'),do_lang_tempcode('PARAMETER_B'));
		if (addon_installed('securitylogging'))
			$field_titles[]=do_lang_tempcode('_BANNED');
		$fields_title=results_field_title($field_titles,$sortables,'sort',$sortable.' '.$sort_order);

		$filter_to_type=get_param('to_type','');
		$filter_param_a=get_param('param_a','');
		$filter_param_b=get_param('param_b','');

		$max_rows=0;

		// Pull up our rows: forum
		if (get_forum_type()=='ocf')
		{
			// Possible filter (called up by URL)
			$where='1=1';
			if ($filter_to_type!='')
				$where.=' AND '.db_string_equal_to('l_the_type',$filter_to_type);
			if ($filter_param_a!='')
				$where.=' AND l_param_a LIKE \''.db_encode_like('%'.$filter_param_a.'%').'\'';
			if ($filter_param_b!='')
				$where.=' AND l_param_b LIKE \''.db_encode_like('%'.$filter_param_b.'%').'\'';
			if ($id!=-1) $where.=' AND l_by='.strval($id);

			// Fetch
			$rows1=$GLOBALS['FORUM_DB']->query('SELECT l_reason,id,l_by AS the_user,l_date_and_time AS date_and_time,l_the_type AS the_type,l_param_a AS param_a,l_param_b AS param_b FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_moderator_logs WHERE '.$where.' ORDER BY '.$sortable.' '.$sort_order,$max+$start);
			$max_rows+=$GLOBALS['FORUM_DB']->query_value_if_there('SELECT COUNT(*) FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'f_moderator_logs WHERE '.$where);
		} else $rows1=array();

		// Pull up our rows: site
		{
			// Possible filter (called up by URL)
			$where='1=1';
			if ($filter_to_type!='')
				$where.=' AND '.db_string_equal_to('the_type',$filter_to_type);
			if ($filter_param_a!='')
				$where.=' AND param_a LIKE \''.db_encode_like('%'.$filter_param_a.'%').'\'';
			if ($filter_param_b!='')
				$where.=' AND param_b LIKE \''.db_encode_like('%'.$filter_param_b.'%').'\'';
			if ($id!=-1) $where.=' AND the_user='.strval($id);

			// Fetch
			$rows2=$GLOBALS['SITE_DB']->query('SELECT id,the_user,date_and_time,the_type,param_a,param_b,ip FROM '.get_table_prefix().'adminlogs WHERE '.$where.' ORDER BY '.$sortable.' '.$sort_order,$max+$start);
			$max_rows+=$GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(*) FROM '.get_table_prefix().'adminlogs WHERE '.$where);
			$rows=array_merge($rows1,$rows2);
		}

		require_code('actionlog');

		$fields=new ocp_tempcode();
		$pos=0;
		while ((count($rows)!=0) && (($pos-$start)<$max))
		{
			$best=0; // Initialise type to integer
			$_best=0; // Initialise type to integer
			$best=NULL;
			$_best=NULL;
			foreach ($rows as $x=>$row)
			{
				if ((is_null($best))
					|| (($row['date_and_time']<$_best) && ($sortable=='date_and_time') && ($sort_order=='ASC'))
					|| (($row['date_and_time']>$_best) && ($sortable=='date_and_time') && ($sort_order=='DESC'))
					|| ((intval($row['the_type'])<$_best) && ($sortable=='the_type') && ($sort_order=='ASC'))
					|| ((intval($row['the_type'])>$_best) && ($sortable=='the_type') && ($sort_order=='DESC'))
					)
				{
					$best=$x;
					if ($sortable=='date_and_time') $_best=$row['date_and_time'];
					if ($sortable=='the_type') $_best=$row['the_type'];
				}
			}
			if ($pos>=$start)
			{
				$myrow=$rows[$best];

				$username=$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($myrow['the_user']);
				$mode=array_key_exists('l_reason',$myrow)?'ocf':'ocp';
				$url=build_url(array('page'=>'_SELF','type'=>'view','id'=>$myrow['id'],'mode'=>$mode),'_SELF');
				$mode_nice=($mode=='ocp')?'ocPortal':'OCF';
				$date=hyperlink($url,get_timezoned_date($myrow['date_and_time']),false,false,$mode_nice.'/'.$row['the_type'].'/'.strval($myrow['id']),NULL,NULL,NULL,'_top');

				if (!is_null($myrow['param_a'])) $a=$myrow['param_a']; else $a='';
				if (!is_null($myrow['param_b'])) $b=$myrow['param_b']; else $b='';

				require_code('templates_interfaces');
				$_a=tpl_crop_text_mouse_over($a,8);
				$_b=tpl_crop_text_mouse_over($b,15);

				$type_str=do_lang($myrow['the_type'],$_a,$_b,NULL,NULL,false);
				if (is_null($type_str)) $type_str=$myrow['the_type'];

				$test=actionlog_linkage($myrow['the_type'],$a,$b,$_a,$_b);
				if (!is_null($test)) list($_a,$_b)=$test;

				$result_entry=array($username,$date,$type_str,$_a,$_b);

				if (addon_installed('securitylogging'))
				{
					$banned_test_1=array_key_exists('ip',$myrow)?ip_banned($myrow['ip'],true):false;
					$banned_test_2=!is_null($GLOBALS['SITE_DB']->query_select_value_if_there('usersubmitban_member','the_member',array('the_member'=>$myrow['the_user'])));
					$banned_test_3=$GLOBALS['FORUM_DRIVER']->is_banned($myrow['the_user']);
					$banned=(((!$banned_test_1)) && ((!$banned_test_2)) && (!$banned_test_3))?do_lang_tempcode('NO'):do_lang_tempcode('YES');

					$result_entry[]=$banned;
				}

				$fields->attach(results_entry($result_entry,true));
			}

			unset($rows[$best]);
			$pos++;
		}
		$table=results_table(do_lang_tempcode('ACTIONS'),$start,'start',$max,'max',$max_rows,$fields_title,$fields,$sortables,$sortable,$sort_order,'sort');

		return do_template('ACTION_LOGS_SCREEN',array('_GUID'=>'d75c813e372c3ca8d1204609e54c9d65','TABLE'=>$table,'TITLE'=>$title));
	}

	/**
	 * The UI to view details of a specific moderation action.
	 *
	 * @return tempcode		The UI
	 */
	function view_action()
	{
		breadcrumb_set_self(do_lang_tempcode('ENTRY'));
		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('VIEW_ACTION_LOGS')),array('_SELF:_SELF:list',do_lang_tempcode('RESULTS'))));

		$mode=get_param('mode','ocf');
		$id=get_param_integer('id');

		if ($mode=='ocf')
		{
			$rows=$GLOBALS['FORUM_DB']->query_select('f_moderator_logs',array('l_reason AS reason','id','l_by AS the_user','l_date_and_time AS date_and_time','l_the_type AS the_type','l_param_a AS param_a','l_param_b AS param_b'),array('id'=>$id),'',1);
		} else
		{
			$rows=$GLOBALS['SITE_DB']->query_select('adminlogs',array('id','the_user','date_and_time','the_type','param_a','param_b','ip'),array('id'=>$id),'',1);
		}

		if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$row=$rows[0];

		$title=get_screen_title('VIEW_ACTION_LOGS');
		$username=$GLOBALS['FORUM_DRIVER']->get_username($row['the_user']);
		if (is_null($username)) $username=do_lang('UNKNOWN');
		$type_str=do_lang($row['the_type'],$row['param_a'],$row['param_b'],NULL,NULL,false);
		if (is_null($type_str)) $type_str=$row['the_type'];
		$fields=array(
						'USERNAME'=>$GLOBALS['FORUM_DRIVER']->member_profile_hyperlink($row['the_user']),
						'DATE_TIME'=>get_timezoned_date($row['date_and_time']),
						'TYPE'=>$type_str,
						'PARAMETER_A'=>is_null($row['param_a'])?'':$row['param_a'],
						'PARAMETER_B'=>is_null($row['param_b'])?'':$row['param_b']);
		if (array_key_exists('ip',$row)) $fields['IP_ADDRESS']=escape_html($row['ip']);
		if (array_key_exists('reason',$row)) $fields['REASON']=escape_html($row['reason']);
		if (addon_installed('securitylogging'))
		{
			if (array_key_exists('ip',$row))
			{
				$banned_test_1=ip_banned($row['ip'],true);
				$fields['IP_BANNED']=(!$banned_test_1)?do_lang_tempcode('NO'):do_lang_tempcode('YES');
				if ($row['ip']!=get_ip_address())
				{
					$fields['IP_BANNED']->attach(do_template('ACTION_LOGS_TOGGLE_LINK',array('URL'=>build_url(array('page'=>'admin_ipban','type'=>'toggle_ip_ban','id'=>$row['ip'],'redirect'=>get_self_url(true)),get_module_zone('admin_ipban')))));
					if (get_option('stopforumspam_api_key').get_option('tornevall_api_username')!='')
					{
						$fields['SYNDICATE_TO_STOPFORUMSPAM']=do_template('ACTION_LOGS_TOGGLE_LINK',array('LABEL'=>do_lang_tempcode('PROCEED'),'URL'=>build_url(array('page'=>'admin_ipban','type'=>'syndicate_ip_ban','ip'=>$row['ip'],'member_id'=>$row['the_user'],'reason'=>do_lang('BANNED_ADDRESSES'),'redirect'=>get_self_url(true)),get_module_zone('admin_ipban'))));
					}
				}
			}
			$banned_test_2=$GLOBALS['SITE_DB']->query_select_value_if_there('usersubmitban_member','the_member',array('the_member'=>$row['the_user']));
			$fields['SUBMITTER_BANNED']=is_null($banned_test_2)?do_lang_tempcode('NO'):do_lang_tempcode('YES');
			if ((!is_guest($row['the_user'])) && ($row['the_user']!=get_member()))
			{
				$fields['SUBMITTER_BANNED']->attach(do_template('ACTION_LOGS_TOGGLE_LINK',array('URL'=>build_url(array('page'=>'admin_ipban','type'=>'toggle_submitter_ban','id'=>$row['the_user'],'redirect'=>get_self_url(true)),get_module_zone('admin_ipban')))));
			}
			$banned_test_3=$GLOBALS['FORUM_DRIVER']->is_banned($row['the_user']);
			$fields['MEMBER_BANNED']=$banned_test_3?do_lang_tempcode('YES'):do_lang_tempcode('NO');
			if (((get_forum_type()=='ocf') && (!is_guest($row['the_user']))) && ($row['the_user']!=get_member()))
			{
				$fields['MEMBER_BANNED']->attach(do_template('ACTION_LOGS_TOGGLE_LINK',array('URL'=>build_url(array('page'=>'admin_ipban','type'=>'toggle_member_ban','id'=>$row['the_user'],'redirect'=>get_self_url(true)),get_module_zone('admin_ipban')))));
			}
		}
		$fields['INVESTIGATE_USER']=hyperlink(build_url(array('page'=>'admin_lookup','id'=>(array_key_exists('ip',$row))?$row['ip']:$row['the_user']),'_SELF'),do_lang_tempcode('PROCEED'));

		require_code('templates_map_table');
		return map_table($title,$fields);
	}

}


