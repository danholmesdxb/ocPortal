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
 * @package		tickets
 */

/**
 * Module page class.
 */
class Module_tickets
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
		$info['version']=5;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_table_if_exists('ticket_types');
		$GLOBALS['SITE_DB']->drop_table_if_exists('tickets');

		delete_config_option('ticket_text');
		delete_config_option('ticket_forum_name');
		delete_config_option('ticket_member_forums');
		delete_config_option('ticket_type_forums');

		delete_privilege('view_others_tickets');
		delete_privilege('support_operator');

		$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>'tickets'));

		delete_menu_item_simple('_SEARCH:tickets:type=misc');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		require_lang('tickets');

		if ((!is_null($upgrade_from)) && ($upgrade_from<5))
		{
			$GLOBALS['SITE_DB']->delete_table_field('ticket_types','send_sms_to');
		}

		if (is_null($upgrade_from))
		{
			add_privilege('SUPPORT_TICKETS','support_operator',false);

			add_config_option('TICKET_MEMBER_FORUMS','ticket_member_forums','tick','return \'0\';','FEATURE','SUPPORT_TICKETS');
			add_config_option('TICKET_TYPE_FORUMS','ticket_type_forums','tick','return \'0\';','FEATURE','SUPPORT_TICKETS');

			$GLOBALS['SITE_DB']->create_table('tickets',array(
				'ticket_id'=>'*SHORT_TEXT',
				'topic_id'=>'AUTO_LINK',
				'forum_id'=>'AUTO_LINK',
				'ticket_type'=>'SHORT_TRANS'
			));

			$GLOBALS['SITE_DB']->create_table('ticket_types',array(
				'ticket_type'=>'*SHORT_TRANS',
				'guest_emails_mandatory'=>'BINARY',
				'search_faq'=>'BINARY',
				'cache_lead_time'=>'?TIME'
			));

			$groups=$GLOBALS['FORUM_DRIVER']->get_usergroup_list(false,true);

			$default_types=array(/*'TT_FEATURE_REQUEST','TT_FEATURE_INQUIRY','TT_MODDING_HELP','TT_REPAIR_HELP',*/'TT_OTHER',/*'TT_FINANCIAL_INQUIRY',*/'TT_COMPLAINT');
			foreach ($default_types as $type)
			{
				$GLOBALS['SITE_DB']->query_insert('ticket_types',array('ticket_type'=>insert_lang(do_lang($type),1),'guest_emails_mandatory'=>0,'search_faq'=>0,'cache_lead_time'=>NULL));

				foreach (array_keys($groups) as $id)
				{
					$GLOBALS['SITE_DB']->query_insert('group_category_access',array('module_the_name'=>'tickets','category_name'=>do_lang($type),'group_id'=>$id));
				}
			}

			add_config_option('PAGE_TEXT','ticket_text','transtext','return do_lang(\'NEW_TICKET_WELCOME\');','FEATURE','SUPPORT_TICKETS');
			add_config_option('TICKET_FORUM_NAME','ticket_forum_name','forum','require_lang(\'tickets\'); return do_lang(\'TICKET_FORUM_NAME\',\'\',\'\',\'\',get_site_default_lang());','FEATURE','SUPPORT_TICKETS');

			add_privilege('SUPPORT_TICKETS','view_others_tickets',false);

			add_menu_item_simple('main_website',NULL,'SUPPORT_TICKETS','_SEARCH:tickets:type=misc');
		}
	}

	/**
	 * Standard modular entry-point finder function.
	 *
	 * @return ?array	A map of entry points (type-code=>language-code) (NULL: disabled).
	 */
	function get_entry_points()
	{
		return array('misc'=>'SUPPORT_TICKETS');
	}

	/**
	 * Standard modular page-link finder function (does not return the main entry-points that are not inside the tree).
	 *
	 * @param  ?integer  The number of tree levels to computer (NULL: no limit)
	 * @param  boolean	Whether to not return stuff that does not support permissions (unless it is underneath something that does).
	 * @param  ?string	Position to start at in the tree. Does not need to be respected. (NULL: from root)
	 * @param  boolean	Whether to avoid returning categories.
	 * @return ?array 	A tuple: 1) full tree structure [made up of (pagelink, permission-module, permissions-id, title, children, ?entry point for the children, ?children permission module, ?whether there are children) OR a list of maps from a get_* function] 2) permissions-page 3) optional base entry-point for the tree 4) optional permission-module 5) optional permissions-id (NULL: disabled).
	 */
	function get_page_links($max_depth=NULL,$require_permission_support=false,$start_at=NULL,$dont_care_about_categories=false)
	{
		$permission_page='tickets';
		$tree=array();
		$rows=$dont_care_about_categories?array():$GLOBALS['SITE_DB']->query_select('ticket_types c LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND c.ticket_type=t.id',array('ticket_type','text_original'));
		foreach ($rows as $row)
		{
			if (is_null($row['text_original'])) $row['text_original']=get_translated_text($row['ticket_type']);

			$tree[]=array('_SELF:_SELF:type=ticket:default='.strval($row['ticket_type']),'tickets',$row['text_original'],$row['text_original'],array());
		}
		return array($tree,$permission_page);
	}

	/**
	 * Standard modular new-style deep page-link finder function (does not return the main entry-points).
	 *
	 * @param  string  	Callback function to send discovered page-links to.
	 * @param  MEMBER		The member we are finding stuff for (we only find what the member can view).
	 * @param  integer	Code for how deep we are tunnelling down, in terms of whether we are getting entries as well as categories.
	 * @param  string		Stub used to create page-links. This is passed in because we don't want to assume a zone or page name within this function.
	 * @param  ?string	Where we're looking under (NULL: root of tree). We typically will NOT show a root node as there's often already an entry-point representing it.
	 * @param  integer	Our recursion depth (used to calculate importance of page-link, used for instance by Google sitemap). Deeper is typically less important.
	 * @param  ?array		Non-standard for API [extra parameter tacked on] (NULL: yet unknown). Contents of database table for performance.
	 */
	function get_sitemap_pagelinks($callback,$member_id,$depth,$pagelink_stub,$parent_pagelink=NULL,$recurse_level=0,$category_data=NULL)
	{
		require_code('tickets');
		require_code('tickets2');

		// We read in all data for efficiency
		if (is_null($category_data))
			$category_data=$GLOBALS['SITE_DB']->query_select('ticket_types c LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate t ON '.db_string_equal_to('language',user_lang()).' AND c.ticket_type=t.id',array('ticket_type AS id','text_original AS title'));

		// This is where we start
		if (is_null($parent_pagelink))
		{
			$parent_pagelink=$pagelink_stub.':misc'; // This is the entry-point we're under

			// Subcategories
			foreach ($category_data as $row)
			{
				if (is_null($row['title'])) $row['title']=get_translated_text($row['id']);

				if (!has_category_access($member_id,'tickets',$row['title'])) continue;

				$pagelink=$pagelink_stub.'ticket:default='.urlencode($row['title']);
				if (__CLASS__!='')
				{
					$this->get_sitemap_pagelinks($callback,$member_id,$depth,$pagelink_stub,$pagelink.':defaultb='.strval($row['id']),$recurse_level+1,$category_data); // Recurse
				} else
				{
					call_user_func_array(__FUNCTION__,array($callback,$member_id,$depth,$pagelink_stub,$pagelink.':defaultb='.strval($row['id']),$recurse_level+1,$category_data)); // Recurse
				}
				call_user_func_array($callback,array($pagelink,$parent_pagelink,NULL,NULL,max(0.7-$recurse_level*0.1,0.3),$row['title'])); // Callback
			}
		} else
		{
			list(,$parent_attributes,)=page_link_decode($parent_pagelink);

			// Entries
			if (($depth>=DEPTH__ENTRIES) && (!is_guest($member_id)))
			{
				$entry_data=get_tickets($member_id,intval($parent_attributes['defaultb']));
				foreach ($entry_data as $row)
				{
					$ticket_id=extract_topic_identifier($row['description']);

					$pagelink=$pagelink_stub.'ticket:'.$ticket_id;
					call_user_func_array($callback,array($pagelink,$parent_pagelink,$row['firsttime'],$row['lasttime'],0.2,$row['firsttitle'])); // Callback
				}
			}
		}
	}

	/**
	 * Convert a page link to a category ID and category permission module type.
	 *
	 * @param  string	The page link
	 * @return array	The pair
	 */
	function extract_page_link_permissions($page_link)
	{
		$matches=array();
		preg_match('#^([^:]*):([^:]*):type=ticket:default=(.*)$#',$page_link,$matches);
		return array(is_numeric($matches[3])?get_translated_text(intval($matches[3])):$matches[3],'tickets');
	}

	/**
	 * Standard modular run function.
	 *
	 * @return tempcode	The result of execution.
	 */
	function run()
	{
		if (has_no_forum()) warn_exit(do_lang_tempcode('NO_FORUM_INSTALLED'));
		set_feed_url(find_script('backend').'?mode=tickets&filter=');

		require_lang('tickets');
		require_javascript('javascript_validation');
		require_css('tickets');
		require_code('tickets');
		require_code('tickets2');

		$type=get_param('type','misc');

		if ($type=='ticket') return $this->do_ticket();
		if ($type=='post') return $this->do_update_ticket();
		if ($type=='misc') return $this->do_choose_ticket();
		if ($type=='toggle_ticket_closed') return $this->toggle_ticket_closed();

		return new ocp_tempcode();
	}

	/**
	 * Checks the ticket ID is valid, and there is access for the current member to view it. Bombs out if there's a problem.
	 *
	 * @param  string			The ticket ID to check
	 */
	function check_id($id)
	{
		// Check we are allowed
		$_temp=explode('_',$id);
		if (array_key_exists(2,$_temp)) log_hack_attack_and_exit('TICKET_SYSTEM_WEIRD');
		if ((!has_privilege(get_member(),'view_others_tickets')) && (intval($_temp[0])!=get_member()))
		{
			if (is_guest()) access_denied('NOT_AS_GUEST');
			if (is_guest(intval($_temp[0])))
				access_denied(do_lang('TICKET_OTHERS_HACK'));
			log_hack_attack_and_exit('TICKET_OTHERS_HACK');
		}
	}

	/**
	 * The UI to show support tickets we may view.
	 *
	 * @return tempcode		The UI
	 */
	function do_choose_ticket()
	{
		require_code('feedback');

		$title=get_screen_title('SUPPORT_TICKETS');

		$message=new ocp_tempcode();
		$links=new ocp_tempcode();

		if (!is_guest())
		{
			// Our tickets
			$ticket_type=get_param_integer('ticket_type',NULL);
			if (!is_null($ticket_type))
				set_feed_url(find_script('backend').'?mode=tickets&filter='.strval($ticket_type));
			$tickets=get_tickets(get_member(),$ticket_type);

			// List (our?) tickets
			if (!is_null($tickets))
			{
				if (has_privilege(get_member(),'support_operator'))
					$message=do_lang_tempcode('TICKETS_STAFF');
				else
					$message=do_lang_tempcode('TICKETS_USER');

				foreach ($tickets as $topic)
				{
					if (($topic['closed']) && (has_privilege(get_member(),'support_operator')) && (count($tickets)>3)) continue; // Staff don't see closed tickets

					$ticket_id=extract_topic_identifier($topic['description']);

					$url=build_url(array('page'=>'_SELF','type'=>'ticket','id'=>$ticket_id),'_SELF');
					$_title=$topic['firsttitle'];
					$date=get_timezoned_date($topic['lasttime']);
					$member=$GLOBALS['FORUM_DRIVER']->get_member_from_username($topic['lastusername']);
					if (!is_null($member))
					{
						$profile_link=$GLOBALS['FORUM_DRIVER']->member_profile_url($member,false,true);
						$last_poster=$topic['lastusername'];
					} else
					{
						$profile_link='';
						$last_poster=do_lang('UNKNOWN');
					}
					$unclosed=(!$GLOBALS['FORUM_DRIVER']->is_staff($topic['lastmemberid']));

					$params=array('NUM_POSTS'=>integer_format($topic['num']),'CLOSED'=>strval($topic['closed']),'URL'=>$url,'TITLE'=>$_title,'DATE'=>$date,'DATE_RAW'=>strval($topic['lasttime']),'PROFILE_URL'=>$profile_link,'LAST_POSTER'=>$last_poster,'UNCLOSED'=>$unclosed);

					$links->attach(do_template('SUPPORT_TICKET_LINK',$params));
				}
			}
		} else
		{
			$_login_url=build_url(array('page'=>'login'));
			$login_url=$_login_url->evaluate();
			$message=do_lang_tempcode('NO_TICKETS_GUESTS',escape_html($login_url));
		}

		$map=array('page'=>'_SELF','type'=>'ticket');
		if (get_param('default','')!='') $map['default']=get_param('default');
		$add_ticket_url=build_url($map,'_SELF');

		$tpl=do_template('SUPPORT_TICKETS_SCREEN',array('_GUID'=>'b208a9f1504d6b8a76400d89a8265d91','TITLE'=>$title,'MESSAGE'=>$message,'LINKS'=>$links,'ADD_TICKET_URL'=>$add_ticket_url,'TYPES'=>$this->build_types_list(get_param_integer('ticket_type',NULL))));

		require_code('templates_internalise_screen');
		return internalise_own_screen($tpl,30,$tickets);
	}

	/**
	 * Build a list of ticket types.
	 *
	 * @param  ?AUTO_LINK	The current selected ticket type (NULL: none)
	 * @return array			A map between ticket types, and template-ready details about them
	 */
	function build_types_list($selected_ticket_type)
	{
		$_types=$GLOBALS['SITE_DB']->query_select('ticket_types LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'translate ON id=ticket_type',array('ticket_type','text_original','cache_lead_time'),NULL,'ORDER BY text_original');
		$types=array();
		foreach ($_types as $type)
		{
			if (!has_category_access(get_member(),'tickets',$type['text_original'])) continue;
			if (is_null($type['cache_lead_time'])) $lead_time=do_lang('UNKNOWN');
			else $lead_time=display_time_period($type['cache_lead_time']);
			$types[$type['ticket_type']]=array('TICKET_TYPE'=>strval($type['ticket_type']),'SELECTED'=>($type['ticket_type']===$selected_ticket_type),'NAME'=>$type['text_original'],'LEAD_TIME'=>$lead_time);
		}
		return $types;
	}

	/**
	 * The UI to either show an existing ticket and allow a reply, or to start a new ticket.
	 *
	 * @return tempcode		The UI
	 */
	function do_ticket()
	{
		require_lang('comcode');

		$id=get_param('id',NULL);
		if ($id=='') $id=NULL;

		if (!is_null($id))
		{
			$_temp=explode('_',$id);
			$ticket_owner=intval($_temp[0]);
			$ticket_id=$_temp[1];

			if (is_guest()) access_denied('NOT_AS_GUEST');
			$this->check_id($id);
		} else
		{
			$ticket_owner=get_member();
			$ticket_id=uniqid('');
		}

		breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('SUPPORT_TICKETS'))));

		$poster='';
		$new=true;
		$serialized_options=mixed();
		$hash=mixed();
		if ((!is_guest()) || (is_null($id))) // If this isn't a guest posting their ticket
		{
			$member=get_member();
			$new=is_null($id);

			$num_to_show_limit=get_param_integer('max_comments',intval(get_option('comments_to_show_in_thread')));
			$start=get_param_integer('start_comments',0);

			if ($new)
			{
				$id=strval($member).'_'.$ticket_id;
				$title=get_screen_title('ADD_TICKET');

				$_comments=array();
			} else
			{
				$ticket_type=$GLOBALS['SITE_DB']->query_select_value_if_there('tickets','ticket_type',array('ticket_id'=>$id));
				$ticket_type_text=get_translated_text($ticket_type);
				$ticket_type_details=get_ticket_type($ticket_type);

				$forum=1; $topic_id=1; $_ticket_type=1; // These will be returned by reference
				$_comments=get_ticket_posts($id,$forum,$topic_id,$_ticket_type,$start,$num_to_show_limit);
				$_comments_all=get_ticket_posts($id,$forum,$topic_id,$_ticket_type);
				if ((!is_array($_comments)) || (!array_key_exists(0,$_comments))) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));

				$ticket_title=$_comments[0]['title'];
				if ($ticket_title=='') $ticket_title=do_lang('UNKNOWN');

				$title=get_screen_title('_VIEW_SUPPORT_TICKET',true,array(escape_html($ticket_title),escape_html($ticket_type_text)));
				breadcrumb_set_self($ticket_title);
			}

			$ticket_page_text=comcode_to_tempcode(get_option('ticket_text'),NULL,true);
			$staff_details=new ocp_tempcode();
			$types=$this->build_types_list(get_param('default',''));

			$pagination=NULL;

			if (!$new)
			{
				if (is_null($_comments)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
				if (has_privilege(get_member(),'support_operator'))
					$staff_details=make_string_tempcode($GLOBALS['FORUM_DRIVER']->topic_url($topic_id,escape_html(get_option('ticket_forum_name'))));
				else $staff_details=new ocp_tempcode();

				require_code('topics');
				$renderer=new OCP_Topic();
				$renderer->_inject_posts_for_scoring_algorithm($_comments);
				$renderer->topic_id=$topic_id;

				// Posts
				$max_thread_depth=get_param_integer('max_thread_depth',intval(get_option('max_thread_depth')));
				list($comments,$serialized_options,$hash)=$renderer->render_posts($num_to_show_limit,$max_thread_depth,true,$ticket_owner,array(),$forum);

				// Pagination
				if (!$renderer->is_threaded)
				{
					if (count($_comments_all)>$num_to_show_limit)
					{
						require_code('templates_pagination');
						$pagination=pagination(do_lang_tempcode('COMMENTS'),$start,'start_comments',$num_to_show_limit,'max_comments',count($_comments_all));
					}
				}

				set_extra_request_metadata(array(
					'created'=>date('Y-m-d',$_comments[0]['date']),
					'creator'=>$GLOBALS['FORUM_DRIVER']->get_username($_comments[0]['user']),
					'publisher'=>'', // blank means same as creator
					'modified'=>'',
					'type'=>'Support ticket',
					'title'=>$_comments[0]['title'],
					'identifier'=>'_SEARCH:tickets:ticket:'.$id,
					'description'=>'',
					'image'=>find_theme_image('bigicons/tickets'),
				));

				// "Staff only reply" tickbox
				if ((get_forum_type()=='ocf') && ($GLOBALS['FORUM_DRIVER']->is_staff(get_member())))
				{
					require_code('form_templates');
					$staff_only=form_input_tick(do_lang('TICKET_STAFF_ONLY'),do_lang('TICKET_STAFF_ONLY_DESCRIPTION'),'staff_only',false);
				} else $staff_only=new ocp_tempcode();
			} else
			{
				$comments=new ocp_tempcode();
				$staff_only=new ocp_tempcode();
				$ticket_type_details=get_ticket_type(NULL);
			}

			if (($poster=='') || ($GLOBALS['FORUM_DRIVER']->get_guest_id()!=intval($poster))) // We can post a new ticket reply to an existing ticket that isn't from a guest
			{
				$em=$GLOBALS['FORUM_DRIVER']->get_emoticon_chooser();
				require_javascript('javascript_editing');
				require_javascript('javascript_validation');
				require_javascript('javascript_posting');
				require_javascript('javascript_swfupload');
				require_css('swfupload');
				require_code('form_templates');
				list($attachments,$attach_size_field)=(get_forum_type()=='ocf')?get_attachments('post'):array(NULL,NULL);
				if (addon_installed('captcha'))
				{
					require_code('captcha');
					$use_captcha=((get_option('captcha_on_feedback')=='1') && (use_captcha()));
					if ($use_captcha)
					{
						generate_captcha();
					}
				} else $use_captcha=false;
				$comment_form=do_template('COMMENTS_POSTING_FORM',array(
					'_GUID'=>'aaa32620f3eb68d9cc820b18265792d7',
					'JOIN_BITS'=>'',
					'FIRST_POST_URL'=>'',
					'FIRST_POST'=>'',
					'USE_CAPTCHA'=>$use_captcha,
					'ATTACHMENTS'=>$attachments,
					'ATTACH_SIZE_FIELD'=>$attach_size_field,
					'POST_WARNING'=>'',
					'COMMENT_TEXT'=>'',
					'GET_EMAIL'=>is_guest(),
					'EMAIL_OPTIONAL'=>((is_guest()) && ($ticket_type_details['guest_emails_mandatory'])),
					'GET_TITLE'=>true,
					'EM'=>$em,
					'DISPLAY'=>'block',
					'COMMENT_URL'=>'',
					'SUBMIT_NAME'=>do_lang_tempcode('MAKE_POST'),
					'TITLE'=>do_lang_tempcode($new?'CREATE_TICKET_MAKE_POST':'MAKE_POST'),
				));
			} else
			{
				$comment_form=new ocp_tempcode();
			}

			$post_url=build_url(array('page'=>'_SELF','id'=>$id,'type'=>'post','redirect'=>get_param('redirect',NULL),'start_comments'=>get_param('start_comments',NULL),'max_comments'=>get_param('max_comments',NULL)),'_SELF');

			require_code('form_templates');
			require_code('feedback');
			list($warning_details,$ping_url)=handle_conflict_resolution(NULL,true);
			$other_tickets=new ocp_tempcode();
			$our_topic=NULL;
			if (!is_guest($ticket_owner))
			{
				$tickets_of_member=get_tickets($ticket_owner,NULL,true);
				if (!is_null($tickets_of_member))
				{
					foreach ($tickets_of_member as $topic)
					{
						$ticket_id=extract_topic_identifier($topic['description']);

						if ($id!=$ticket_id)
						{
							$url=build_url(array('page'=>'_SELF','type'=>'ticket','id'=>$ticket_id),'_SELF');
							$_title=$topic['firsttitle'];
							$date=get_timezoned_date($topic['lasttime']);
							$ticket_owner_name=$GLOBALS['FORUM_DRIVER']->get_username($ticket_owner);
							if (is_null($ticket_owner_name))
							{
								$profile_link='';
							} else
							{
								$profile_link=$GLOBALS['FORUM_DRIVER']->member_profile_url($ticket_owner,false,true);
							}
							$last_poster=$topic['lastusername'];
							$unclosed=(!$GLOBALS['FORUM_DRIVER']->is_staff($topic['lastmemberid']));

							$params=array('NUM_POSTS'=>integer_format($topic['num']-1),'CLOSED'=>strval($topic['closed']),'URL'=>$url,'TITLE'=>$_title,'DATE'=>$date,'DATE_RAW'=>strval($topic['lasttime']),'PROFILE_URL'=>$profile_link,'LAST_POSTER'=>$last_poster,'UNCLOSED'=>$unclosed);

							$other_tickets->attach(do_template('SUPPORT_TICKET_LINK',$params));
						} else
						{
							$our_topic=$topic;
						}
					}
				}
			}

			$toggle_ticket_closed_url=NULL;
			if ((get_forum_type()=='ocf') && (!$new))
			{
				$toggle_ticket_closed_url=build_url(array('page'=>'_SELF','type'=>'toggle_ticket_closed','id'=>$id),'_SELF');
			}

			$map=array('page'=>'_SELF','type'=>'ticket');
			if (get_param('default','')!='') $map['default']=get_param('default');
			$add_ticket_url=build_url($map,'_SELF');

			$tpl=do_template('SUPPORT_TICKET_SCREEN',array(
				'_GUID'=>'d21a9d161008c6c44fe7309a14be2c5b',
				'SERIALIZED_OPTIONS'=>$serialized_options,
				'HASH'=>$hash,
				'TOGGLE_TICKET_CLOSED_URL'=>$toggle_ticket_closed_url,
				'CLOSED'=>is_null($our_topic)?'0':strval($our_topic['closed']),
				'OTHER_TICKETS'=>$other_tickets,
				'USERNAME'=>$GLOBALS['FORUM_DRIVER']->get_username($ticket_owner),
				'PING_URL'=>$ping_url,
				'WARNING_DETAILS'=>$warning_details,
				'NEW'=>$new,
				'TICKET_PAGE_TEXT'=>$ticket_page_text,
				'TYPES'=>$types,
				'STAFF_ONLY'=>$staff_only,
				'POSTER'=>$poster,
				'TITLE'=>$title,
				'COMMENTS'=>$comments,
				'COMMENT_FORM'=>$comment_form,
				'STAFF_DETAILS'=>$staff_details,
				'URL'=>$post_url,
				'ADD_TICKET_URL'=>$add_ticket_url,
				'PAGINATION'=>$pagination,
			));

			require_code('templates_internalise_screen');
			return internalise_own_screen($tpl,30,$_comments);
		} else // Guest has posted ticket successfully. Actually, this code problem never runs (as they in fact see a separate screen from do_update_ticket), but it's here as a fail safe.
		{
			return inform_screen(get_screen_title('ADD_TICKET'),do_lang_tempcode('SUCCESS'));
		}
	}

	/**
	 * Actualise to toggle the closed state of a ticket.
	 *
	 * @return tempcode		The UI
	 */
	function toggle_ticket_closed()
	{
		$id=get_param('id');

		require_code('feedback');

		$action='CLOSE_TICKET';

		// Our tickets - search them for this ticket, acting as a kind of security check (as we will only iterate through tickets we have access to)
		$tickets=get_tickets(get_member(),NULL);
		foreach ($tickets as $ticket)
		{
			$ticket_id=extract_topic_identifier($ticket['description']);
			if ($ticket_id==$id)
			{
				if ($ticket['closed']==0) $action='OPEN_TICKET';
				$GLOBALS['FORUM_DB']->query_update('f_topics',array('t_is_open'=>$ticket['closed']),array('id'=>$ticket['id']),'',1);
			}
		}

		$title=get_screen_title($action);

		$url=build_url(array('page'=>'_SELF','type'=>'ticket','id'=>$id),'_SELF');
		if (is_guest()) $url=build_url(array('page'=>'_SELF'),'_SELF');
		return redirect_screen($title,$url,do_lang_tempcode('SUCCESS'));
	}

	/**
	 * Actualise ticket creation/reply, then show the ticket again.
	 *
	 * @return tempcode		The UI
	 */
	function do_update_ticket()
	{
		$title=get_screen_title('SUPPORT_TICKETS');

		$id=get_param('id');
		$_title=post_param('title');
		$post=post_param('post');
		if ($post=='') warn_exit(do_lang_tempcode('NO_PARAMETER_SENT','post'));

		$ticket_type=post_param_integer('ticket_type',-1);
		$this->check_id($id);

		$staff_only=post_param_integer('staff_only',0)==1;

		// Update
		$_home_url=build_url(array('page'=>'_SELF','type'=>'ticket','id'=>$id,'redirect'=>NULL),'_SELF',NULL,false,true,true);
		$home_url=$_home_url->evaluate();
		$email='';
		if ($ticket_type!=-1)
		{
			$type_string=get_translated_text($ticket_type);
			$ticket_type_details=get_ticket_type($ticket_type);

			if (!has_category_access(get_member(),'tickets',$type_string)) access_denied('I_ERROR');

			// Check FAQ search results first
			if (($ticket_type_details['search_faq']) && (post_param_integer('faq_searched',0)==0))
			{
				$results=$this->do_search($title,$id,$post);
				if (!is_null($results)) return $results;
			}

			$new_post=new ocp_tempcode();
			$new_post->attach(do_lang('THIS_WITH_COMCODE',do_lang('TICKET_TYPE'),$type_string)."\n\n");
			$email=trim(post_param('email',''));
			if ($email!='')
			{
				$body='> '.str_replace(chr(10),chr(10).'> ',$post);
				if (substr($body,-2)=='> ') $body=substr($body,0,strlen($body)-2);
				$new_post->attach('[email subject="Re: '.comcode_escape(post_param('title')).' ['.get_site_name().']" body="'.comcode_escape($body).'"]'.$email.'[/email]'."\n\n");
			}
			elseif ((is_guest()) && ($ticket_type_details['guest_emails_mandatory']))
			{
				// Error if the e-mail address is required for this ticket type
				warn_exit(do_lang_tempcode('ERROR_GUEST_EMAILS_MANDATORY'));
			}
			$new_post->attach($post);
			$post=$new_post->evaluate();
		}
		if (addon_installed('captcha'))
		{
			if (get_option('captcha_on_feedback')=='1')
			{
				require_code('captcha');
				enforce_captcha();
			}
		}
		ticket_add_post(get_member(),$id,$ticket_type,$_title,$post,$home_url,$staff_only);

		// Find true ticket title
		$_forum=1; $_topic_id=1; $_ticket_type=1; // These will be returned by reference
		$posts=get_ticket_posts($id,$_forum,$_topic_id,$_ticket_type);
		if (!is_array($posts)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		$__title=$_title;
		foreach ($posts as $ticket_post)
		{
			$__title=$ticket_post['title'];
			if ($__title!='') break;
		}

		// Send email
		if (!$staff_only)
			send_ticket_email($id,$__title,$post,$home_url,$email,$ticket_type);

		$url=build_url(array('page'=>'_SELF','type'=>'ticket','id'=>$id),'_SELF');
		if (is_guest()) $url=build_url(array('page'=>'_SELF'),'_SELF');
		if (get_param('redirect','')!='') $url=make_string_tempcode(get_param('redirect'));
		return redirect_screen($title,$url,do_lang_tempcode('TICKET_STARTED'));
	}

	/**
	 * Check for existing FAQs matching a ticket to be submitted, via searching.
	 *
	 * @param  tempcode		Page title
	 * @param  string			Ticket ID we'd be creating
	 * @param  string			What is being searched for
	 * @return ?tempcode		The search results (NULL: could not search)
	 */
	function do_search($title,$ticket_id,$content)
	{
		require_code('database_search');

		// We don't want to display too many --- just enough to show the top results
		$max=10;

		// Search under all hooks we've asked to search under
		$results=array();
		require_code('hooks/modules/search/catalogue_entries');
		$object=object_factory('Hook_search_catalogue_entries');
		$info=$object->info();
		if (is_null($info)) return NULL;

		// Get the ID of the default FAQ catalogue
		$catalogue_id=$GLOBALS['SITE_DB']->query_select_value('catalogue_categories','id',array('c_name'=>'faqs'),'',1);
		if (is_null($catalogue_id)) return NULL;

		// Category filter
		$where_clause=db_string_equal_to('r.'.$info['category'],$catalogue_id);
		$boolean_operator='OR';
		$content_where=build_content_where($content,true,$boolean_operator);
		$hook_results=$object->run($content,false,'ASC',$max,0,false,$content_where,'',NULL,NULL,'relevance',NULL,$boolean_operator,$where_clause,NULL,true);
		if ((is_null($hook_results)) || (count($hook_results)==0)) return NULL;

		foreach ($hook_results as $i=>$result)
		{
			$result['object']=$object;
			$hook_results[$i]=$result;
		}

		$results=sort_search_results($hook_results,array(),'ASC');
		$out=build_search_results_interface($results,0,$max,'ASC');

		return do_template('SUPPORT_TICKETS_SEARCH_SCREEN',array('_GUID'=>'427e28208e15494a8f126eb4fb2aa60c','TITLE'=>$title,'URL'=>build_url(array('page'=>'_SELF','id'=>$ticket_id,'type'=>'post'),'_SELF'),'POST_FIELDS'=>build_keep_post_fields(),'RESULTS'=>$out));
	}
}

