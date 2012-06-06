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
 * @package		core
 */

/**
 * AJAX script for checking if a new username is valid.
 */
function username_check_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-Type: text/plain');

	require_code('ocf_members_action');
	require_code('ocf_members_action2');
	require_lang('ocf');
	$username=trim(get_param('username',false,true));
	$error=ocf_check_name_valid($username,NULL,trim(post_param('password',NULL)),true);
	if (!is_null($error)) $error->evaluate_echo();
}

/**
 * AJAX script for finding out privileges for the queried resource.
 */
function find_permissions_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-Type: text/plain');

	require_code('zones2');
	require_code('permissions2');

	$serverid=get_param('serverid');
	$x=get_param('x');
	$matches=array();
	preg_match('#^access_(\d+)_sp_(.+)$#',$x,$matches);
	$group_id=intval($matches[1]);
	$sp=$matches[2];
	require_all_lang();
	echo do_lang('PT_'.$sp).'=';
	if ($serverid=='_root')
	{
		echo has_specific_permission_group($group_id,$sp)?do_lang('YES'):do_lang('NO');
	} else
	{
		preg_match('#^([^:]*):([^:]*)(:|$)#',$serverid,$matches);
		$zone=$matches[1];
		$page=$matches[2];

		$_pagelinks=extract_module_functions_page($zone,$page,array('get_page_links'),array(NULL,false,NULL,true));

		$bits=(is_null($_pagelinks[0]))?array('!',''):(is_array($_pagelinks[0])?call_user_func_array($_pagelinks[0][0],$_pagelinks[0][1]):eval($_pagelinks[0])); // If $_pagelinks[0] is NULL then it's an error: extract_page_link_permissions is always there when there are cat permissions
		$module=$bits[1];

		echo has_specific_permission_group($group_id,$sp,$module)?do_lang('YES'):do_lang('NO');
	}
}

/**
 * AJAX script to store an autosave.
 */
function store_autosave()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	$member_id=get_member();
	$key=post_param('key');
	$value=post_param('value');
	$time=time();

	$GLOBALS['SITE_DB']->query_insert('autosave',array(
		'a_member_id'=>$member_id,
		'a_key'=>$key,
		'a_value'=>$value,
		'a_time'=>$time,
	));
}

/**
 * AJAX script to retrieve an autosave.
 */
function retrieve_autosave()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-Type: text/plain');

	$member_id=get_member();
	$key=post_param('key');

	@ini_set('ocproducts.xss_detect','0');

	echo $GLOBALS['SITE_DB']->query_value_null_ok('autosave','a_value',array('a_member_id'=>$member_id,'a_key'=>$key),'ORDER BY a_time DESC');
}

/**
 * AJAX script to make a fractional edit to some data.
 */
function fractional_edit_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-type: text/plain; charset='.get_charset());

	$_POST['fractional_edit']='1';

	global $SESSION_CONFIRMED;
	if ($SESSION_CONFIRMED==0)
	{
		return;
	}

	$zone=get_param('zone');
	$page=get_param('page');
	if (!has_actual_page_access(get_member(),$page,$zone))
		access_denied('ZONE_ACCESS');

	require_code('site');
	request_page($page,true);

	$supports_comcode=get_param_integer('supports_comcode',0)==1;
	convert_data_encodings(true);
	$edited=post_param(get_param('edit_param_name'));
	if ($supports_comcode)
	{
		$_edited=comcode_to_tempcode($edited,get_member());
		$edited=$_edited->evaluate();
	}
	@ini_set('ocproducts.xss_detect','0');
	echo $edited;
}

/**
 * AJAX script to tell if data has been changed.
 */
function change_detection_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-type: text/plain; charset='.get_charset());

	$page=get_param('page');

	require_code('hooks/systems/change_detection/'.filter_naughty($page),true);

	$refresh_if_changed=either_param('refresh_if_changed');
	$object=object_factory('Hook_'.$page);
	$result=$object->run($refresh_if_changed);
	echo ($result)?'1':'0';
}

/**
 * AJAX script for recording that something is currently being edited.
 */
function edit_ping_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-type: text/plain; charset='.get_charset());

	$GLOBALS['SITE_DB']->query('DELETE FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'edit_pings WHERE the_time<'.strval(time()-200));

	$GLOBALS['SITE_DB']->query_delete('edit_pings',array(
		'the_page'=>get_param('page'),
		'the_type'=>get_param('type'),
		'the_id'=>get_param('id',false,true),
		'the_member'=>get_member()
	));

	$GLOBALS['SITE_DB']->query_insert('edit_pings',array(
		'the_page'=>get_param('page'),
		'the_type'=>get_param('type'),
		'the_id'=>get_param('id',false,true),
		'the_time'=>time(),
		'the_member'=>get_member()
	));

	echo '1';
}

/**
 * AJAX script for HTML<>Comcode conversion (and Comcode-Text>Comcode-XML).
 */
function comcode_convert_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	global $EXTRA_HEAD;
	if (!isset($EXTRA_HEAD)) $EXTRA_HEAD=new ocp_tempcode();
	$EXTRA_HEAD->attach('<meta name="robots" content="noindex" />'); // XHTMLXHTML

	require_lang('comcode');

	convert_data_encodings(true);
	$data=post_param('data',NULL,false,false);
	if (is_null($data))
	{
		$title=get_screen_title('_COMCODE');
		$fields=new ocp_tempcode();
		require_code('form_templates');
		$fields->attach(form_input_huge(do_lang_tempcode('TEXT'),'','data','',true));
		$fields->attach(form_input_tick('Convert HTML to Comcode','','from_html',false));
		$fields->attach(form_input_tick('Convert to semihtml','','semihtml',false));
		$fields->attach(form_input_tick('Comes from WYSIWYG','','data__is_wysiwyg',false));
		$fields->attach(form_input_tick('Lax mode (less parse rules)','','lax',false));
		$hidden=new ocp_tempcode();
		$hidden->attach(form_input_hidden('to_comcode_xml',strval(either_param_integer('to_comcode_xml',0))));
		$out2=globalise(do_template('FORM_SCREEN',array('_GUID'=>'dd82970fa1196132e07049871c51aab7','TITLE'=>$title,'SUBMIT_NAME'=>do_lang_tempcode('VIEW'),'TEXT'=>'','HIDDEN'=>$hidden,'URL'=>find_script('comcode_convert',true),'FIELDS'=>$fields)),NULL,'',true);
		$out2->evaluate_echo();
		return;
	}
	if (either_param_integer('to_comcode_xml',0)==1)
	{
		require_code('comcode_conversion');
		$out=comcode_text__to__comcode_xml($data);
	}
	elseif (either_param_integer('from_html',0)==1)
	{
		require_code('comcode_from_html');
		$out=trim(semihtml_to_comcode($data));
	} else
	{
		if (either_param_integer('lax',0)==1) $GLOBALS['LAX_COMCODE']=true;
		if (either_param_integer('is_semihtml',0)==1)
		{
			require_code('comcode_from_html');
			$data=semihtml_to_comcode($data);
		}
		$db=$GLOBALS['SITE_DB'];
		if (get_param_integer('forum_db',0)==1) $db=$GLOBALS['FORUM_DB'];
		$tpl=comcode_to_tempcode($data,get_member(),false,60,NULL,$db,either_param_integer('semihtml',0)==1/*true*/,false,false,false);
		$evaluated=$tpl->evaluate();
		$out='';
		if ($evaluated!='')
		{
			if (get_param_integer('css',0)==1)
			{
				global $CSSS;
				unset($CSSS['global']);
				unset($CSSS['no_cache']);
				$out.=static_evaluate_tempcode(css_tempcode());
			}
			if (get_param_integer('javascript',0)==1)
			{
				global $JAVASCRIPTS;
				unset($JAVASCRIPTS['javascript']);
				unset($JAVASCRIPTS['javascript_staff']);
				$out.=static_evaluate_tempcode(javascript_tempcode());
			}
		}
		$out.=trim(trim($evaluated));
	}

	if (either_param_integer('fix_bad_html',0)==1)
	{
		require_code('xhtml');
		$new=xhtmlise_html($out,true);

		if (preg_replace('#<!--.*-->#Us','',preg_replace('#\s+#','',$new))!=preg_replace('#<!--.*-->#Us','',preg_replace('#\s+#','',$out)))
		{
			/*$myfile=fopen(get_file_base().'/a','wb');
			fwrite($myfile,preg_replace('#<!--.*-->#Us','',preg_replace('#\s+#',chr(10),$new)));
			fclose($myfile);

			$myfile=fopen(get_file_base().'/b','wb');
			fwrite($myfile,preg_replace('#<!--.*-->#Us','',preg_replace('#\s+#',chr(10),$out)));
			fclose($myfile);*/

			$out=$new.do_lang('BROKEN_XHTML_FIXED');
		}
	}
	if (either_param_integer('keep_skip_rubbish',0)==0)
	{
		@ini_set('ocproducts.xss_detect','0');

		$box_title=get_param('box_title','');
		if (is_object($out)) $out=$out->evaluate();
		if (($box_title!='') && ($out!='')) $out=static_evaluate_tempcode(put_in_standard_box(make_string_tempcode($out),$box_title));

		header('Content-Type: text/xml');
		echo '<?xml version="1.0" encoding="'.get_charset().'"?'.'>';
		echo '<request><result>';
		echo xmlentities($out);
		echo '</result></request>';
	} else
	{
		header('Content-type: text/plain; charset='.get_charset());
		echo $out;
	}
}

/**
 * AJAX script for checking if a username exists.
 */
function username_exists_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-type: text/plain; charset='.get_charset());
	convert_data_encodings(true);
	$username=trim(get_param('username',false,true));
	$member_id=$GLOBALS['FORUM_DRIVER']->get_member_from_username($username);
	if (is_null($member_id)) echo 'false';
}

/**
 * AJAX script for allowing username/author/search-terms home-in.
 */
function namelike_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	convert_data_encodings(true);
	$id=str_replace('*','%',get_param('id',false,true));
	$special=get_param('special','');

	if ($special=='admin_search')
	{
		$names=array();
		if ($id!='')
		{
			require_all_lang();
			$hooks=find_all_hooks('systems','do_next_menus');
			foreach (array_keys($hooks) as $hook)
			{
				require_code('hooks/systems/do_next_menus/'.filter_naughty_harsh($hook));
				$object=object_factory('Hook_do_next_menus_'.filter_naughty_harsh($hook),true);
				if (is_null($object)) continue;
				$info=$object->run(true);
				foreach ($info as $i)
				{
					if (is_null($i)) continue;
					$n=$i[3];
					$n_eval=is_object($n)?$n->evaluate():$n;
					if ($n_eval=='') continue;
					if ((strpos(strtolower($n_eval),strtolower($id))!==false) && (has_actual_page_access(get_member(),$i[2][0],$i[2][2])))
					{
						$names[]='"'.$n_eval.'"';
					}
				}
			}
			if (count($names)>10) $names=array();
			sort($names);
		}
	}
	elseif ($special=='search')
	{
		$names=array();
		$q='SELECT s_primary,COUNT(*) as cnt,MAX(s_num_results) AS s_num_results FROM '.get_table_prefix().'searches_logged WHERE ';
		if ((db_has_full_text($GLOBALS['SITE_DB']->connection_read)) && (method_exists($GLOBALS['SITE_DB']->static_ob,'db_has_full_text_boolean')) && ($GLOBALS['SITE_DB']->static_ob->db_has_full_text_boolean()))
		{
			$q.=preg_replace('#\?(.*)#','s_primary${1}',db_full_text_assemble($id,false));
		} else
		{
			$q.='s_primary LIKE \''./*ideally we would put an % in front, but too slow*/db_encode_like($id).'%\'';
		}
		$q.=' AND s_primary NOT LIKE \'%<%\' AND '.db_string_not_equal_to('s_primary','').' GROUP BY s_primary ORDER BY cnt DESC';
		$past_searches=$GLOBALS['SITE_DB']->query($q,20);
		foreach ($past_searches as $search)
		{
			if ($search['cnt']>5)
				$names[]=$search['s_primary'];
		}
	} else
	{
		if ((strlen($id)==0) && (addon_installed('chat')))
		{
			$rows=$GLOBALS['SITE_DB']->query_select('chat_buddies',array('member_liked'),array('member_likes'=>get_member()),'ORDER BY date_and_time',100);
			$names=array();
			foreach ($rows as $row)
			{
				$names[]=$GLOBALS['FORUM_DRIVER']->get_username($row['member_liked']);
			}
		} else
		{
			$names=array();
			if (addon_installed('authors'))
			{
				if ($special=='author')
				{
					$num_authors=$GLOBALS['SITE_DB']->query_value('authors','COUNT(*)');
					$like=($num_authors<1000)?db_encode_like('%'.$id.'%'):db_encode_like($id.'%'); // performance issue
					$rows=$GLOBALS['SITE_DB']->query('SELECT author FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'authors WHERE author LIKE \''.$like.'\' ORDER BY author',15);
					$names=collapse_1d_complexity('author',$rows);
				}
			}

			$likea=$GLOBALS['FORUM_DRIVER']->get_matching_members($id.'%',15);

			foreach ($likea as $l)
			{
				if (count($names)<15)
					$names[]=$GLOBALS['FORUM_DRIVER']->pname_name($l);
			}
		}

		sort($names);
		$names=array_unique($names);
	}

	@ini_set('ocproducts.xss_detect','0');

	header('Content-Type: text/xml');
	echo '<?xml version="1.0" encoding="'.get_charset().'"?'.'>';
	echo '<request><result>';
	foreach ($names as $name)
	{
		echo '<option value="'.escape_html($name).'" />';
	}
	echo '</result></request>';
}

/**
 * AJAX script for dynamically extended selection tree.
 */
function ajax_tree_script()
{
	// Closed site
	$site_closed=get_option('site_closed');
	if (($site_closed=='1') && (!has_specific_permission(get_member(),'access_closed_site')) && (!$GLOBALS['IS_ACTUALLY_ADMIN']))
	{
		header('Content-Type: text/plain');
		@exit(get_option('closed'));
	}

	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-Type: text/xml');
	$hook=filter_naughty_harsh(get_param('hook'));
	require_code('hooks/systems/ajax_tree/'.$hook);
	$object=object_factory('Hook_'.$hook);
	convert_data_encodings(true);
	$id=get_param('id','',true);
	if ($id=='') $id=NULL;
	@ini_set('ocproducts.xss_detect','0');
	$html_mask=get_param_integer('html_mask',0)==1;
	if (!$html_mask) echo '<?xml version="1.0" encoding="'.get_charset().'"?'.'>';
	echo ($html_mask?'<html>':'<request>');
	$_options=get_param('options','',true);
	if ($_options=='') $_options=serialize(array());
	$options=unserialize($_options);
	$val=$object->run($id,$options,get_param('default',NULL,true));
	echo str_replace('</body>','<br id="ended" /></body>',$val);
	echo ($html_mask?'</html>':'</request>');
}

/**
 * AJAX script for confirming a session is active.
 */
function confirm_session_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-Type: text/plain');
	global $SESSION_CONFIRMED;
	if ($SESSION_CONFIRMED==0) echo $GLOBALS['FORUM_DRIVER']->get_username(get_member());
	echo '';
}

/**
 * AJAX script for getting the text of a template, as used by a certain theme.
 */
function load_template_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	if (!has_actual_page_access(get_member(),'admin_themes','adminzone')) exit();

	@ini_set('ocproducts.xss_detect','0');

	$theme=filter_naughty(get_param('theme'));
	$id=filter_naughty(get_param('id'));

	$x=get_custom_file_base().'/themes/'.$theme.'/templates_custom/'.$id;
	if (!file_exists($x)) $x=get_file_base().'/themes/'.$theme.'/templates/'.$id;
	if (!file_exists($x)) $x=get_custom_file_base().'/themes/default/templates_custom/'.$id;
	if (!file_exists($x)) $x=get_file_base().'/themes/default/templates/'.$id;
	if (file_exists($x)) echo file_get_contents($x);
}

/**
 * AJAX script for dynamic inclusion of CSS.
 */
function sheet_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-Type: text/css');
	$sheet=get_param('sheet');
	if ($sheet!='') echo str_replace('../../../','',file_get_contents(css_enforce(filter_naughty_harsh($sheet))));
}

/**
 * AJAX script for dynamic inclusion of XHTML snippets.
 */
function snippet_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	header('Content-Type: text/plain; charset='.get_charset());
	$hook=filter_naughty_harsh(get_param('snippet'));
	require_code('hooks/systems/snippets/'.$hook,true);
	$object=object_factory('Hook_'.$hook);
	$tempcode=$object->run();
	$out=$tempcode->evaluate();

	// End early execution listening (this means register_shutdown_function will run after connection closed - faster)
	if (function_exists('apache_setenv')) @apache_setenv('no-gzip','1');
	@ini_set('zlib.output_compression','Off');
	$size=strlen($out);
	header('Connection: close');
	ignore_user_abort(true);
	header('Content-Encoding: none');
	header('Content-Length: '.strval($size));
	echo $out;
	@ob_end_flush();
	flush();
}


