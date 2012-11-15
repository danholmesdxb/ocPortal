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
 * Read an ocSelect parameter value from GET/POST.
 *
 * @param  ID_TEXT			The field name
 * @param  ?ID_TEXT			The field type (NULL: work out what is there to read automatically)
 * @return string				The parameter value
 */
function read_ocselect_parameter_from_env($field_name,$field_type=NULL)
{
	$env=$_POST+$_GET;

	if (is_null($field_type))
	{
		$field_type='line';
		if (!array_key_exists('filter_'.$field_name,$env))
		{
			if (array_key_exists('filter_'.$field_name.'_year',$env))
			{
				$field_type='time';
			}
		} elseif (is_array($env['filter_'.$field_name]))
		{
			$field_type='multilist';
		}
	}

	if (($field_type=='date') || ($field_type=='time'))
	{
		$_default_value=get_input_date('filter_'.$field_name,true);
		$default_value=is_null($_default_value)?'':strval($_default_value);
	} elseif ($field_type=='multilist')
	{
		$default_value=array_key_exists('filter_'.$field_name,$env)?implode(',',$env['filter_'.$field_name]):'';
	} else
	{
		$default_value=either_param('filter_'.$field_name,'');
	}
	return $default_value;
}

/**
 * Get a form for inputting unknown variables within a filter.
 *
 * @param  string				String-based search filter (blank: make one up to cover everything, but only works if $table is known)
 * @param  ?array				Labels for field names (NULL: none, use auto-generated)
 * @param  ?ID_TEXT			Content-type to auto-probe from (NULL: none, use string inputs)
 * @param  ?array				Field types (NULL: none, use string inputs / defaults for table)
 * @return array				The form fields, The modded filter, Merger links
 */
function form_for_ocselect($filter,$labels=NULL,$content_type=NULL,$types=NULL)
{
	$table=mixed();
	$db=$GLOBALS['SITE_DB'];
	$info=array();
	if (!is_null($content_type))
	{
		require_code('hooks/systems/content_meta_aware/'.$content_type);
		$ob=object_factory('Hook_content_meta_aware_'.$content_type);
		$info=$ob->info();

		$table=$info['table'];
		if (($content_type=='post') || ($content_type=='topic') || ($content_type=='member') || ($content_type=='group') || ($content_type=='forum'))
		{
			$db=$GLOBALS['FORUM_DB'];
		}
	}

	if (is_null($labels)) $labels=array();
	if (is_null($types)) $types=array();

	$fields_needed=array();

	require_lang('ocselect');

	$catalogue_name=mixed();
	if (preg_match('#^\w+$#',$filter)!=0)
	{
		$catalogue_name=$filter;
	}

	$_links=array();

	// Load up fields to compare to
	if ($table!==NULL)
	{
		$db_fields=collapse_2d_complexity('m_name','m_type',$db->query_select('db_meta',array('m_name','m_type'),array('m_table'=>$table)));

		if (isset($info['feedback_type_code']))
		{
			$db_fields['compound_rating']='INTEGER';
			$types['compound_rating']='rating';

			$db_fields['average_rating']='INTEGER';
			$types['average_rating']='rating';
		}

		if (isset($info['seo_type_code']))
		{
			$db_fields['meta_keywords']='SHORT_TEXT';
			$db_fields['meta_description']='LONG_TEXT';
			$types['meta_keywords']='line';
			$types['meta_description']='line';
		}

		// Custom fields
		require_code('content');
		require_code('hooks/systems/content_meta_aware/'.$content_type);
		$ob2=object_factory('Hook_content_meta_aware_'.$content_type);
		$info2=$ob2->info();
		if ((isset($info2['supports_custom_fields'])) && ($info2['supports_custom_fields']))
		{
			require_code('fields');
			$catalogue_fields=list_to_map('id',get_catalogue_fields(($content_type=='catalogue_entry')?$catalogue_name:'_'.$content_type));
			foreach ($catalogue_fields as $catalogue_field)
			{
				if ($catalogue_field['cf_put_in_search']==1)
				{
					$remapped_name='field_'.strval($catalogue_field['id']);
					$db_fields[$remapped_name]='SHORT_TEXT';
					$types[$remapped_name]=$catalogue_field['cf_type'];
					$labels[$remapped_name]=get_translated_text($catalogue_field['cf_name']);
				}
			}
		}

		if ($filter=='')
		{
			foreach ($db_fields as $key=>$type)
			{
				if ($key=='notes') continue; // Protected, staff notes
				if ((isset($info['ocselect_protected_fields'])) && (in_array($key,$info['ocselect_protected_fields']))) continue;

				$type=str_replace(array('?','*'),array('',''),$type);
				switch ($type)
				{
					// Any of these field types will go into the default filter (we support some that don't, but user likely does not want them)
					case 'BINARY':
					case 'SHORT_INTEGER':
					case 'UINTEGER':
					case 'INTEGER':
					case 'TIME':
					case 'MEMBER':
					case 'REAL':
					case 'LONG_TEXT':
					case 'SHORT_TEXT':
					case 'LONG_TRANS':
					case 'SHORT_TRANS':
					case 'MINIID_TEXT':
					case 'ID_TEXT':
						if ($filter!='') $filter.=',';
						$filter.=$key.'<'.$key.'_op><'.$key.'>';
						break;
				}
			}
		}
	} else
	{
		$db_fields=array();
	}

	$filters=parse_ocselect($filter);

	foreach ($filters as $_filter)
	{
		list(,$filter_op,$filter_val)=$_filter;

		// Operator
		$matches=array();
		if (preg_match('#^<([\w\_\-]+)>$#',$filter_op,$matches)!=0)
		{
			$field_name=filter_naughty_harsh($matches[1]);
			$field_title=array_key_exists($field_name,$labels)?make_string_tempcode($labels[$field_name]):do_lang_tempcode('OPERATOR_FOR',escape_html(titleify(preg_replace('#^filter\_#','',preg_replace('#\_op$#','',$field_name)))));

			$fields_needed[]=array(
				'list',
				$field_name,
				$field_title,
				post_param('filter_'.$field_name,'~='),
				array(
					'<'=>do_lang_tempcode('OCSELECT_OP_LT'),
					'>'=>do_lang_tempcode('OCSELECT_OP_GT'),
					'<='=>do_lang_tempcode('OCSELECT_OP_LTE'),
					'>='=>do_lang_tempcode('OCSELECT_OP_GTE'),
					'='=>do_lang_tempcode('OCSELECT_OP_EQ'),
					'=='=>do_lang_tempcode('OCSELECT_OP_EQE'),
					'~='=>do_lang_tempcode('OCSELECT_OP_CO'),
					'~'=>do_lang_tempcode('OCSELECT_OP_FT'),
					'<>'=>do_lang_tempcode('OCSELECT_OP_NE'),
					'@'=>do_lang_tempcode('OCSELECT_OP_RANGE'),
				)
			);
		}

		// Filter inputter
		$matches=array();
		if (preg_match('#^<([\w\_\-]+)>$#',$filter_val,$matches)!=0)
		{
			$field_name=filter_naughty_harsh($matches[1]);

			$extra=mixed();

			if (array_key_exists($field_name,$types))
			{
				$field_type=$types[$field_name];

				if (($field_type=='list') || ($field_type=='linklist') || ($field_type=='mulilist'))
				{
					// Work out what list values there are
					$extra=array();
					if (!is_null($table))
					{
						if (($field_name!='meta_keywords') && ($field_name!='meta_description') && ($field_name!='compound_rating') && ($field_name!='average_rating'))
						{
							$_extra=$db->query_select($table,array('DISTINCT '.filter_naughty_harsh($field_name)),NULL,'ORDER BY '.filter_naughty_harsh($field_name));
							foreach ($_extra as $e)
							{
								if (!is_string($e[$field_name])) $e[$field_name]=strval($e[$field_name]);
								$extra[$e[$field_name]]=$e[$field_name];
							}
						} else
						{
							if ($field_name=='meta_keywords')
							{
								$_extra=$db->query_select('seo_meta',array('DISTINCT meta_keywords'),NULL,'ORDER BY '.filter_naughty_harsh($field_name));
								foreach ($_extra as $e)
								{
									$keywords=explode(',',$e['meta_keywords']);
									foreach ($keywords as $k)
									{
										$extra[trim($k)]=$e[trim($k)];
									}
								}
							}
						}
					}
					ksort($extra);
				}
			} else
			{
				$field_type='line';
				if (array_key_exists($field_name,$db_fields))
				{
					switch (str_replace(array('?','*'),array('',''),$db_fields[$field_name]))
					{
						case 'TIME':
							$field_type='time';
							break;
						case 'BINARY':
							$field_type='tick';
							break;
						case 'AUTO':
						case 'AUTO_LINK':
						case 'SHORT_INTEGER':
						case 'UINTEGER':
						case 'INTEGER':
						case 'GROUP':
							$field_type='integer';
							break;
						case 'MEMBER':
							$field_type='username';
							break;
						case 'REAL':
							$field_type='float';
							break;
						case 'MD5':
						case 'URLPATH':
						case 'IP':
						case 'LONG_TEXT':
						case 'SHORT_TEXT':
						case 'LONG_TRANS':
						case 'SHORT_TRANS':
							$field_type='line';
							break;
						case 'MINIID_TEXT':
						case 'ID_TEXT':
							$field_type='codename';
							break;
						case 'LANGUAGE_NAME':
							$field_type='list';
							require_code('lang3');
							$_extra=array_keys(find_all_langs());
							$extra=array();
							foreach (array_keys(find_all_langs()) as $lang)
							{
								$extra[$lang]=get_language_title($lang);
							}
							break;
					}
				}
			}

			$field_title=array_key_exists($field_name,$labels)?$labels[$field_name]:titleify(preg_replace('#^filter\_#','',$field_name));

			$default_value=read_ocselect_parameter_from_env($field_name,$field_type);

			$fields_needed[]=array(
				$field_type,
				$field_name,
				$field_title,
				$default_value,
				$extra,
			);
		}
	}

	require_code('form_templates');

	$form_fields=new ocp_tempcode();
	foreach ($fields_needed as $field)
	{
		list($field_type,$field_name,$field_label,$default_value,$extra)=$field;

		switch ($field_type) // NB: These type codes also vaguelly correspond to field hooks, just for convention (we don't use them)
		{
			case 'time':
				$form_fields->attach(form_input_date($field_label,'',$field_name,true,$default_value=='',true,($default_value=='')?NULL:intval($default_value)));
				break;

			case 'date':
				$form_fields->attach(form_input_date($field_label,'',$field_name,true,$default_value=='',false,($default_value=='')?NULL:intval($default_value)));
				break;

			case 'days':
				$list_options=new ocp_tempcode();
				$days_options=array();
				foreach (array(2,5,15,30,45,60,120,240,365) as $days_option)
					$days_options[strval(time()-60*60*24*$days_option)]=do_lang_tempcode('SUBMIT_AGE_DAYS',escape_html(integer_format($days_option)));
				$list_options->attach(form_input_list_entry('',$default_value=='',''));
				foreach ($days_options as $key=>$val)
					$list_options->attach(form_input_list_entry($key,$default_value==$key,$val));
				$form_fields->attach(form_input_list($field_label,'',$field_name,$list_options,NULL,false,false));
				break;

			case 'tick':
				$list_options=new ocp_tempcode();
				foreach (array(''=>'','0'=>do_lang_tempcode('NO'),'1'=>do_lang_tempcode('YES')) as $key=>$val)
					$list_options->attach(form_input_list_entry($key,$default_value==$key,$val));
				$form_fields->attach(form_input_list($field_label,'',$field_name,$list_options,NULL,false,false));
				break;

			case 'rating':
				$list_options=new ocp_tempcode();
				$list_options->attach(form_input_list_entry('',$default_value=='',''));
				foreach (array(1=>'&#10025;',4=>'&#10025;&#10025;',6=>'&#10025;&#10025;&#10025;',8=>'&#10025;&#10025;&#10025;&#10025;',10=>'&#10025;&#10025;&#10025;&#10025;&#10025;') as $rating=>$rating_label)
					$list_options->attach(form_input_list_entry(strval($rating),$default_value==strval($rating),protect_from_escaping($rating_label)));
				$form_fields->attach(form_input_list($field_label,'',$field_name,$list_options,NULL,false,false));
				break;

			case 'list':
				$list_options=new ocp_tempcode();
				$list_options->attach(form_input_list_entry('',$default_value=='',''));
				foreach ($extra as $key=>$val)
					$list_options->attach(form_input_list_entry($key,$default_value==$key,$val));
				$form_fields->attach(form_input_list($field_label,'',$field_name,$list_options,NULL,false,false));
				break;

			case 'multilist':
				$list_options=new ocp_tempcode();
				foreach ($extra as $key=>$val)
					$list_options->attach(form_input_list_entry($key,preg_match('#(^|,)'.preg_quote($key,'#').'(,|$)#',$default_value)!=0,$val));
				$form_fields->attach(form_input_multi_list($field_label,'',$field_name,$list_options,NULL,5,false));
				break;

			case 'linklist':
				foreach ($extra as $key=>$val)
					$_links[$val]=$key;
				break;

			case 'float':
				$form_fields->attach(form_input_float($field_label,'',$field_name,($default_value=='')?NULL:floatval($default_value),false));
				break;

			case 'integer':
				$form_fields->attach(form_input_integer($field_label,'',$field_name,($default_value=='')?NULL:intval($default_value),false));
				break;

			case 'email':
				$form_fields->attach(form_input_email($field_label,'',$field_name,$default_value,false));
				break;

			case 'author':
				$form_fields->attach(form_input_author($field_label,'',$field_name,$default_value,false));
				break;

			case 'username':
				$form_fields->attach(form_input_username($field_label,'',$field_name,$default_value,false));
				break;

			case 'codename':
				$form_fields->attach(form_input_codename($field_label,'',$field_name,$default_value,false));
				break;

			case 'line':
			default:
				$form_fields->attach(form_input_line($field_label,'',$field_name,$default_value,false));
				break;
		}
	}

	return array($form_fields,$filter,$_links);
}

/**
 * Parse some string based ocSelect search filters into the expected array structure.
 *
 * @param  string				String-based search filter
 * @return array				Parsed structure
 */
function parse_ocselect($filter)
{
	$parsed=array();
	foreach (preg_split('#(,|\n)#',$filter) as $bit)
	{
		if ($bit!='')
		{
			$parts=preg_split('#(<[\w\-\_]+>|<=|>=|<>|<|>|=|==|~=|~|@)#',$bit,2,PREG_SPLIT_DELIM_CAPTURE); // NB: preg_split is not greedy, so longest operators need to go first
			if (count($parts)==3) $parsed[]=$parts;
		}
	}
	return $parsed;
}

/**
 * Take some parsed ocSelect search filters into the string format (i.e. reverse of parse_ocselect).
 *
 * @param  array				Parsed structure
 * @return string				String-based search filter
 */
function unparse_ocselect($parsed)
{
	$filter='';
	foreach ($parsed as $_filter)
	{
		list($filter_key,$filter_op,$filter_val)=$_filter;
		if ($filter!='') $filter.=',';
		$filter.=$filter_key.$filter_op.$filter_val;
	}
	return $filter;
}

/**
 * Make sure we are doing necessary join to be able to access the given field
 *
 * @param  object				Database connection
 * @param  array				Content type info
 * @param  ?ID_TEXT			Name of the catalogue (NULL: unknown; reduces performance)
 * @param  array				List of joins (passed as reference)
 * @param  array				List of selects (passed as reference)
 * @param  ID_TEXT			The field to get
 * @param  string				The field value for this
 * @param  array				Database field data
 * @param  string				What MySQL will join the table with
 * @return ?array				A triple: Proper database field name to access with, The fields API table type (blank: no special table), The new filter value (NULL: error)
 */
function _fields_api_ocselect($db,$info,$catalogue_name,&$extra_join,&$extra_select,$filter_key,$filter_val,$db_fields,$table_join_code)
{
	require_code('fields');
	$fields=get_catalogue_fields($catalogue_name);

	$matches=array();
	if (preg_match('#^field\_(\d+)#',$filter_key,$matches)==0) return NULL;
	$field_in_seq=intval($matches[1]);

	if ((!isset($fields[intval($field_in_seq)])) || ($fields[intval($field_in_seq)]['cf_put_in_search']==0)) return NULL;

	$ob=get_fields_hook($fields[intval($field_in_seq)]['cf_type']);
	list(,,$table)=$ob->get_field_value_row_bits($fields[$field_in_seq]);

	if (strpos($table,'_trans')!==false)
	{
		$extra_join[$filter_key]=' LEFT JOIN '.$db->get_table_prefix().'catalogue_efv_'.$table.' f'.strval($field_in_seq).' ON f'.strval($field_in_seq).'.ce_id='.$table_join_code.'.id AND f'.strval($field_in_seq).'.cf_id='.strval($fields[$field_in_seq]['id']).' LEFT JOIN '.$db->get_table_prefix().'translate t'.strval($field_in_seq).' ON f'.strval($field_in_seq).'.cv_value=t'.strval($field_in_seq).'.id';
		return array('t'.strval($field_in_seq).'.text_original',$table,$filter_val);
	}

	$extra_join[$filter_key]=' LEFT JOIN '.$db->get_table_prefix().'catalogue_efv_'.$table.' f'.strval($field_in_seq).' ON f'.strval($field_in_seq).'.ce_id='.$table_join_code.'.id AND f'.strval($field_in_seq).'.cf_id='.strval($fields[$field_in_seq]['id']);
	return array('f'.strval($field_in_seq).'.cv_value',$table,$filter_val);
}

/**
 * Make sure we are doing necessary join to be able to access the given field
 *
 * @param  object				Database connection
 * @param  array				Content type info
 * @param  ?ID_TEXT			Name of the catalogue (NULL: unknown; reduces performance)
 * @param  array				List of joins (passed as reference)
 * @param  array				List of selects (passed as reference)
 * @param  ID_TEXT			The field to get
 * @param  string				The field value for this
 * @param  array				Database field data
 * @param  string				What MySQL will join the table with
 * @return ?array				A triple: Proper database field name to access with, The fields API table type (blank: no special table), The new filter value (NULL: error)
 */
function _default_conv_func($db,$info,$unused,&$extra_join,&$extra_select,$filter_key,$filter_val,$db_fields,$table_join_code)
{
	// Special case for ratings
	$matches=array('',$info['feedback_type_code']);
	if (($filter_key=='compound_rating') || (preg_match('#^compound_rating\_\_(.+)#',$filter_key,$matches)!=0))
	{
		$clause='(SELECT SUM(rating-1) FROM '.$db->get_table_prefix().'rating rat WHERE '.db_string_equal_to('rat.rating_for_type',$matches[1]).' AND rat.rating_for_id='.$table_join_code.'.id)';
		$extra_select[$filter_key]=', '.$clause.' AS compound_rating_'.fix_id($matches[1]);
		return array($clause,'',$filter_val);
	}
	$matches=array('',$info['feedback_type_code']);
	if (($filter_key=='average_rating') || (preg_match('#^average_rating\_\_(.+)#',$filter_key,$matches)!=0))
	{
		$clause='(SELECT AVG(rating)/2 FROM '.$db->get_table_prefix().'rating rat WHERE '.db_string_equal_to('rat.rating_for_type',$matches[1]).' AND rat.rating_for_id='.$table_join_code.'.id)';
		$extra_select[$filter_key]=', '.$clause.' AS average_rating_'.fix_id($matches[1]);
		return array($clause,'',$filter_val);
	}

	// Special case for SEO fields
	if (($filter_key=='meta_keywords') || ($filter_key=='meta_description'))
	{
		$seo_type_code=isset($info['seo_type_code'])?$info['seo_type_code']:'!!!ERROR!!!';
		$join=' LEFT JOIN '.$db->get_table_prefix().'seo_meta sm ON sm.meta_for_id='.$table_join_code.'.id AND '.db_string_equal_to('sm.meta_for_type',$seo_type_code);
		if (!in_array($join,$extra_join))
			$extra_join[$filter_key]=$join;
		return array($filter_key,'',$filter_val);
	}

	// Fields API
	$matches=array();
	if ((preg_match('#^field\_(\d+)#',$filter_key,$matches)!=0) && (isset($info['content_type'])))
	{
		return _fields_api_ocselect($db,$info,'_'.$info['content_type'],$extra_join,$extra_select,$filter_key,$filter_val,$db_fields,$table_join_code);
	}

	$filter_key=filter_naughty_harsh($filter_key);

	// Natural fields
	$field_type='';
	if (array_key_exists($filter_key,$db_fields))
	{
		switch (str_replace(array('?','*'),array('',''),$db_fields[$filter_key]))
		{
			case 'AUTO':
			case 'AUTO_LINK':
			case 'SHORT_INTEGER':
			case 'UINTEGER':
			case 'INTEGER':
			case 'TIME':
			case 'BINARY':
			case 'GROUP':
				$field_type='integer';
				$filter_key=$table_join_code.'.'.$filter_key;
				break;
			case 'MEMBER':
				$field_type='integer';
				if ((!is_numeric($filter_val)) && ($filter_val!=''))
				{
					$_filter_val=$GLOBALS['FORUM_DRIVER']->get_member_from_username($filter_val);
					$filter_val=is_null($_filter_val)?'':strval($_filter_val);
				}
				$filter_key=$table_join_code.'.'.$filter_key;
				break;
			case 'REAL':
				$field_type='float';
				$filter_key=$table_join_code.'.'.$filter_key;
				break;
			case 'MD5':
			case 'URLPATH':
			case 'LANGUAGE_NAME':
			case 'IP':
			case 'MINIID_TEXT':
			case 'ID_TEXT':
			case 'LONG_TEXT':
			case 'SHORT_TEXT':
				$field_type='line';
				$filter_key=$table_join_code.'.'.$filter_key;
				break;
			case 'LONG_TRANS':
			case 'SHORT_TRANS':
				$field_type='line';
				static $filter_i=1;
				$extra_join[$filter_key]=' LEFT JOIN '.$db->get_table_prefix().'translate ft'.strval($filter_i).' ON ft'.strval($filter_i).'.id='.strval($filter_key);
				$filter_key='ft'.strval($filter_i).'.text_original';
				$filter_i++;
				break;
		}
	} else
	{
		// Fields API (named)
		if (isset($info['content_type']))
		{
			require_code('fields');
			$fields=list_to_map('id',get_catalogue_fields('_'.$info['content_type']));
			foreach ($fields as $field)
			{
				if (get_translated_text($field['cf_name'])==$filter_key)
				{
					return _fields_api_ocselect($db,$info,'_'.$info['content_type'],$extra_join,$extra_select,'field_'.strval($field['id']),$filter_val,$db_fields,$table_join_code);
				}
			}
		}

		return NULL;
	}

	// $filter_key is exactly as said in most cases

	return array($filter_key,$field_type,$filter_val);
}

/**
 * Convert some ocSelect filters into some SQL fragments.
 *
 * @param  object				Database object to use
 * @param  array				Parsed ocSelect structure
 * @param  ID_TEXT			The content type (blank: no function needed, direct in-table mapping always works)
 * @param  string				First parameter to send to the conversion function, may mean whatever that function wants it to. If we have no conversion function, this is the name of a table to read field meta data from
 * @param  string				What MySQL will join the table with
 * @return array				Tuple: array of extra select, array of extra join, string of extra where
 */
function ocselect_to_sql($db,$filters,$content_type='',$context='',$table_join_code='r')
{
	// Nothing to do?
	if ((is_null($filters)) || ($filters==array())) return array(array(),array(),'');

	// Get the conversion function. The conversion function takes field names and works out how that results in SQL
	$info=array();
	$conv_func='_default_conv_func';
	if ($content_type!='')
	{
		require_code('hooks/systems/content_meta_aware/'.$content_type);
		$ob=object_factory('Hook_content_meta_aware_'.$content_type);
		$info=$ob->info();
		$info['content_type']=$content_type; // We'll need this later, so add it in

		if (isset($info['ocselect']))
		{
			if (strpos($info['ocselect'],'::')!==false)
			{
				list($code_file,$conv_func)=explode('::',$info['ocselect']);
				require_code($code_file);
			} else
			{
				$conv_func=$info['ocselect'];
			}
		}
	}

	$extra_select=array();
	$extra_join=array();
	$where_clause='';

	$disallowed_fields=array('notes');
	$disallowed_fields[]='notes';
	if (isset($info['ocselect_protected_fields']))
	{
		$disallowed_fields=array_merge($disallowed_fields,$info['ocselect_protected_fields']);
	}
	$configured_protected_fields=get_value('ocselect_protected_fields');
	if ((!is_null($configured_protected_fields)) && ($configured_protected_fields!=''))
	{
		$disallowed_fields=array_merge($disallowed_fields,explode(',',$configured_protected_fields));
	}

	// Load up fields to compare to
	$db_fields=array();
	if (isset($info['table']))
	{
		$table=$info['table'];
		$db_fields=collapse_2d_complexity('m_name','m_type',$db->query_select('db_meta',array('m_name','m_type'),array('m_table'=>$table)));
	}

	foreach ($filters as $filter_i=>$filter)
	{
		list($filter_keys,$filter_op,$filter_val)=$filter;

		// Allow specification of reading from the environment
		$matches=array();
		if (preg_match('#^<([\w\_\-]+)>$#',$filter_op,$matches)!=0)
		{
			$filter_op=either_param($matches[1],'~=');
		}
		if (preg_match('#^<([\w\_\-]+)>$#',$filter_val,$matches)!=0)
		{
			$filter_val=read_ocselect_parameter_from_env($matches[1]);
		}

		if ($filter_op!='==')
		{
			if ($filter_val=='') continue;
		}

		$alt='';

		// Go through each filter (these are ANDd)
		foreach (explode('|',$filter_keys) as $filter_key)
		{
			if (in_array($filter_key,$disallowed_fields)) continue;

			$bits=call_user_func_array($conv_func,array($db,$info,&$context,&$extra_join,&$extra_select,&$filter_key,$filter_val,$db_fields,$table_join_code)); // call_user_func_array has to be used for reference passing, bizarrely
			if (is_null($bits))
			{
				require_lang('ocselect');
				attach_message(do_lang_tempcode('OCSELECT_UNKNOWN_FIELD',escape_html($filter_key)),'warn');

				continue;
			}
			list($filter_key,$field_type,$filter_val)=$bits;

			if (strpos($filter_key,'<')!==false)
				$filter_key=preg_replace('#[^\w\s\|\.]#','',$filter_key); // So can safely come from environment

			if (in_array($filter_key,$disallowed_fields)) continue;

			switch ($filter_op)
			{
				case '@':
					if ((preg_match('#^\d+-\d+$#',$filter_val)!=0) && (($field_type=='integer') || ($field_type=='float') || ($field_type=='')))
					{
						if ($alt!='') $alt.=' OR ';
						$_filter_val=explode('-',$filter_val,2);
						$alt.=$filter_key.'>='.$_filter_val[0].' AND '.$filter_key.'<='.$_filter_val[1];
					}
					break;

				case '<':
				case '>':
				case '<=':
				case '>=':
					if ((is_numeric($filter_val)) && (($field_type=='integer') || ($field_type=='float') || ($field_type=='')))
					{
						if ($alt!='') $alt.=' OR ';
						$alt.=$filter_key.$filter_op.$filter_val;
					}
					break;

				case '<>':
					if ((is_numeric($filter_val)) && (($field_type=='integer') || ($field_type=='float') || ($field_type=='')))
					{
						if ($filter_val!='')
						{
							if ($alt!='') $alt.=' OR ';
							$alt.=$filter_key.'<>'.$filter_val;
						}
					} else
					{
						if ($alt!='') $alt.=' OR ';
						$alt.=db_string_not_equal_to($filter_key,$filter_val);
					}
					break;

				case '=':
					if ($alt!='') $alt.=' OR ';
					$alt.='(';
					foreach (explode('|',$field_val) as $it_id=>$it_value)
					{
						if ($it_id!=0) $alt.=' OR ';
						if ((is_numeric($filter_val)) && (($field_type=='integer') || ($field_type=='float') || ($field_type=='')))
							$alt.=$filter_key.'='.$filter_val;
						else
							$alt.=db_string_equal_to($filter_key,$filter_val);
					}
					$alt.=')';
					break;

				case '~':
					if (strlen($filter_val)>3) // Within MySQL filter limits
					{
						if ($filter_val!='')
						{
							if ($alt!='') $alt.=' OR ';
							$alt.='(';
							foreach (explode('|',$filter_val) as $it_id=>$it_value)
							{
								if ($it_id!=0) $alt.=' OR ';
								$alt.=str_replace('?',$filter_key,db_full_text_assemble($it_value,false));
							}
							$alt.=')';
						}
						break;
					}

				case '~=':
					if ($filter_val!='')
					{
						if ($alt!='') $alt.=' OR ';
						$alt.='(';
						foreach (explode('|',$filter_val) as $it_id=>$it_value)
						{
							if ($it_id!=0) $alt.=' OR ';
							$alt.=$filter_key.' LIKE \''.db_encode_like('%'.$it_value.'%').'\'';
						}
						$alt.=')';
					}
					break;

				default:
					fatal_exit(do_lang_tempcode('INTERNAL_ERROR')); // Impossible opcode
			}
		}
		if ($alt!='') $where_clause.=' AND ('.$alt.')';
	}

	return array($extra_select,$extra_join,$where_clause);
}

/**
 * Get template-ready details for a merger-link style ocfilter. This is used to do filtering via drill-down using links.
 *
 * @param  string				ocSelect filter
 * @return array				Template-ready details
 */
function prepare_ocselect_merger_link($_link_filter)
{
	$active_filter=parse_ocselect(either_param('active_filter',''));
	$link_filter=parse_ocselect($_link_filter);
	$extra_params=array();
	$old_filter=$active_filter;
	foreach ($link_filter as $filter_bits)
	{
		list($filter_key,$filter_op,$filter_val)=$filter_bits;

		// Propagate/inject in filter value
		$matches=array();
		if (preg_match('#^<([\w\_\-]+)>$#',$filter_val,$matches)!=0)
		{
			$filter_val=read_ocselect_parameter_from_env($matches[1]);
			$extra_params['filter_'.$matches[1]]=$filter_val;
		}

		// Take out any rules pertaining to this key from the active filter
		foreach ($old_filter as $i2=>$filter_bits_2)
		{
			list($filter_key_2,$filter_op_2,$filter_val_2)=$filter_bits_2;
			if ($filter_key_2==$filter_key) unset($old_filter[$i2]);
		}
	}
	$extra_params['active_filter']=unparse_ocselect(array_merge($old_filter,$link_filter));
	$link_url=get_self_url(false,false,$extra_params);
	$active=true;
	foreach ($extra_params as $key=>$val)
	{
		if (read_ocselect_parameter_from_env($key)!=$val) $active=false;
	}

	return array(
		'ACTIVE'=>$active,
		'URL'=>$link_url,
	);
}
