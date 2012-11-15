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
 * Standard code module initialisation function.
 */
function init__permissions()
{
	global $PRIVILEGE_CACHE;
	$PRIVILEGE_CACHE=array();

	global $GROUP_PRIVILEGE_CACHE;
	$GROUP_PRIVILEGE_CACHE=array();

	global $ZONE_ACCESS_CACHE;
	$ZONE_ACCESS_CACHE=array();

	global $PAGE_ACCESS_CACHE;
	$PAGE_ACCESS_CACHE=array();

	global $CATEGORY_ACCESS_CACHE,$LOADED_ALL_CATEGORY_PERMISSIONS_FOR_CACHE;
	$CATEGORY_ACCESS_CACHE=array();
	$LOADED_ALL_CATEGORY_PERMISSIONS_FOR_CACHE=array();

	global $SUBMIT_PERMISSION_CACHE;
	$SUBMIT_PERMISSION_CACHE=array();

	global $TOTAL_PP_CACHE;
	$TOTAL_PP_CACHE=array();

	global $PERMISSION_CHECK_LOGGER,$PERMISSIONS_ALREADY_LOGGED;
	$PERMISSION_CHECK_LOGGER=NULL;
	$PERMISSIONS_ALREADY_LOGGED=array();

	global $SPAM_REMOVE_VALIDATION;
	$SPAM_REMOVE_VALIDATION=false;

	global $USERSUBMITBAN_MEMBER_CACHE;
	$USERSUBMITBAN_MEMBER_CACHE=NULL;
}

/**
 * Log permission checks to the permission_checks.log file, if it exists.
 *
 * @param  MEMBER         The user checking against
 * @param  ID_TEXT        The function that was called to check a permission
 * @param  array          Parameters to this permission-checking function
 * @param  boolean        Whether the permission was held
 */
function handle_permission_check_logging($member,$op,$params,$result)
{
	global $PERMISSION_CHECK_LOGGER,$PERMISSIONS_ALREADY_LOGGED,$SITE_INFO;
	if ($PERMISSION_CHECK_LOGGER===NULL)
	{
		$file_path=get_custom_file_base().'/data_custom/permissioncheckslog.php';
		if (((!isset($SITE_INFO['no_extra_logs'])) || ($SITE_INFO['no_extra_logs']!='1')) && (is_file($file_path)) && (is_writable_wrap($file_path)))
		{
			$PERMISSION_CHECK_LOGGER=fopen($file_path,'at');
			if (!function_exists('get_self_url')) require_code('urls');
			$self_url=get_self_url(true);
			if (!is_string($self_url)) $self_url=get_self_url_easy(); // A weirdness can happen here. If some kind of fatal error happens then output buffers can malfunction making it impossible to use Tempcode as above. So we fall back to this. (This function may be called in a fatal error due to the 'display_php_errors' permissions).
			fwrite($PERMISSION_CHECK_LOGGER,chr(10).chr(10).date('Y/m/d h:m:i').' -- '.$self_url.' -- '.$GLOBALS['FORUM_DRIVER']->get_username(get_member()).chr(10));
		} else $PERMISSION_CHECK_LOGGER=false;
	}
	static $fbe=NULL;
	if ($fbe===NULL) $fbe=function_exists('fb');
	if (($PERMISSION_CHECK_LOGGER===false) && ((!$fbe) || (get_param_integer('keep_firephp',0)==0))) return;
	$sz=serialize(array($member,$op,$params));
	if (array_key_exists($sz,$PERMISSIONS_ALREADY_LOGGED)) return;
	$PERMISSIONS_ALREADY_LOGGED[$sz]=1;
	if ($result) return;
	require_code('permissions2');
	_handle_permission_check_logging($member,$op,$params,$result);
}

/**
 * Show a helpful access-denied page. Has a login ability if it senses that logging in could curtail the error.
 *
 * @param  ID_TEXT		The class of error (e.g. PRIVILEGE). This is a language string.
 * @param  string			The parameteter given to the error message
 * @param  boolean		Force the user to login (even if perhaps they are logged in already)
 */
function access_denied($class,$param='',$force_login=false)
{
	require_code('failure');
	_access_denied($class,$param,$force_login);
}

/**
 * Find if a member's has access to a specified zone
 *
 * @param  MEMBER			The member being checked whether to have the access
 * @param  ID_TEXT		The ID code for the zone being checked
 * @return boolean		Whether the member has zone access
 */
function has_zone_access($member,$zone)
{
	if (running_script('upgrader')) return true;

	if ($zone=='') return true;

	global $ZONE_ACCESS_CACHE;

	if ((!isset($ZONE_ACCESS_CACHE[$member])) && (function_exists('persistent_cache_get')) && (is_guest($member)))
	{
		$ZONE_ACCESS_CACHE=persistent_cache_get('GUEST_ZONE_ACCESS');
	}

	if (isset($ZONE_ACCESS_CACHE[$member]))
	{
		$result=isset($ZONE_ACCESS_CACHE[$member][$zone])?$ZONE_ACCESS_CACHE[$member][$zone]:false;
		handle_permission_check_logging($member,'has_zone_access',array($zone),$result);
		return $result;
	}

	$groups=_get_where_clause_groups($member);
	if ($groups===NULL) return true;

	$rows=$GLOBALS['SITE_DB']->query('SELECT DISTINCT zone_name FROM '.get_table_prefix().'group_zone_access WHERE ('.$groups.') UNION ALL SELECT DISTINCT zone_name FROM '.get_table_prefix().'member_zone_access WHERE member_id='.strval((integer)$member).' AND active_until>'.strval(time()),NULL,NULL,false,true);
	$ZONE_ACCESS_CACHE[$member]=array();
	foreach ($rows as $row)
	{
		$ZONE_ACCESS_CACHE[$member][$row['zone_name']]=true;
	}
	if (!array_key_exists($zone,$ZONE_ACCESS_CACHE[$member])) $ZONE_ACCESS_CACHE[$member][$zone]=false;

	if ((function_exists('persistent_cache_set')) && (is_guest($member)))
	{
		persistent_cache_set('GUEST_ZONE_ACCESS',$ZONE_ACCESS_CACHE);
	}

	$result=$ZONE_ACCESS_CACHE[$member][$zone];
	handle_permission_check_logging($member,'has_zone_access',array($zone),$result);
	return $result;
}

/**
 * Find if a member's has access to a specified page. Zone permissions are taken into account for wherever the page is found at. Also support for category access and privileges. No support for entry-point checks, which are only carried out as an extension of page permissions when actually at a page.
 *
 * @param  ?MEMBER		The member being checked whether to have the access (NULL: current member)
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 * @param  ?ID_TEXT		The ID code for the zone being checked (NULL: search)
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 * @param  ?mixed			Either the ID code of a privilege, an array of alternatives that are acceptable (NULL: none required)
 * @return boolean		Whether the member has zone and page access
 */
function has_actual_page_access($member=NULL,$page=NULL,$zone=NULL,$cats=NULL,$privilege=NULL)
{
	if (running_script('upgrader')) return true;

	if ($member===NULL) $member=get_member();

	if ($page===NULL) // Assumes $zone is null too
	{
		$page=get_page_name();
		$zone=get_zone_name();
	} else
	{
		if ($zone==='_SELF') $zone=get_zone_name();
		elseif (($zone===NULL) || ($zone=='_SEARCH')) $zone=get_module_zone($page);
		if ($zone===NULL) $zone=get_zone_name(); // Weird problem that can happen on some AJAX hooks
	}

	if (!has_zone_access($member,$zone)) return false;
	if (!has_page_access($member,$page,$zone)) return false;
	if ($cats!==NULL)
	{
		for ($i=0;$i<intval(floor(floatval(count($cats))/2.0));$i++)
		{
			if (is_null($cats[$i*2])) continue;
			if (!has_category_access($member,$cats[$i*2+0],$cats[$i*2+1])) return false;
		}
	}
	if ($privilege!==NULL)
	{
		if (!is_array($privilege)) $privilege=array($privilege);
		$privilege_acceptable=false;
		foreach ($privilege as $perm)
		{
			if (has_privilege($member,$perm,$page,$cats)) $privilege_acceptable=true;
		}
		if (!$privilege_acceptable) return false;
	}

	return true;
}

/**
 * For efficiency reasons, load up loads of page permissions.
 *
 * @param  MEMBER			The member being checked whether to have the access
 */
function load_up_all_self_page_permissions($member)
{
	global $TOTAL_PP_CACHE;
	$groups=_get_where_clause_groups($member,false);
	if (is_null($groups)) return;
	if (array_key_exists($groups,$TOTAL_PP_CACHE)) return;
	$TOTAL_PP_CACHE[$groups]=$GLOBALS['SITE_DB']->query('SELECT page_name,zone_name,group_id FROM '.get_table_prefix().'group_page_access WHERE '.$groups);
}

/**
 * Find if a member's has access to a specified page, in a specific zone. Note that page access does not imply zone access; you have access a page, but not the zone, so still couldn't see it.
 *
 * @param  MEMBER			The member being checked whether to have the access
 * @param  ID_TEXT		The ID code for the page being checked
 * @param  ID_TEXT		The ID code for the zone being checked
 * @param  boolean		Whether we want to check we have access to the CURRENT page, using any match tag permissions
 * @return boolean		Whether the member has page access
 */
function has_page_access($member,$page,$zone,$at_now=false)
{
	if (running_script('upgrader')) return true;

	global $PAGE_ACCESS_CACHE;

	if ((!isset($PAGE_ACCESS_CACHE[$member])) && (function_exists('persistent_cache_get')) && (is_guest($member)))
	{
		$PAGE_ACCESS_CACHE=persistent_cache_get('GUEST_PAGE_ACCESS');
	}

	if ((isset($PAGE_ACCESS_CACHE[$member])) && (isset($PAGE_ACCESS_CACHE[$member][$zone.':'.$page])) && ((!$at_now) || ($PAGE_ACCESS_CACHE[$member][$zone.':'.$page])))
	{
		handle_permission_check_logging($member,'has_page_access',array($page,$zone),$PAGE_ACCESS_CACHE[$member][$zone.':'.$page]);
		return $PAGE_ACCESS_CACHE[$member][$zone.':'.$page];
	}

	$groups=_get_where_clause_groups($member,false);
	if ($groups===NULL) return true;

	$pg_where='('.db_string_equal_to('zone_name',$zone).' AND '.db_string_equal_to('page_name',$page).')';
	$select='page_name,zone_name';

	if ($at_now)
	{
		$pg_where.=' OR page_name LIKE \''.db_encode_like('\_WILD:'.$page.':%').'\'';
		$pg_where.=' OR page_name LIKE \''.db_encode_like($zone.':'.$page.':%').'\'';
		$pg_where.=' OR page_name LIKE \''.db_encode_like('\_WILD:\_WILD:%').'\'';
		$pg_where.=' OR page_name LIKE \''.db_encode_like($zone.':\_WILD:%').'\'';
		$pg_where.=' OR page_name LIKE \''.db_encode_like('\_WILD:'.$page).'\'';
		$pg_where.=' OR page_name LIKE \''.db_encode_like($zone.':'.$page).'\'';
		$pg_where.=' OR page_name LIKE \''.db_encode_like('\_WILD:\_WILD').'\'';
		$pg_where.=' OR page_name LIKE \''.db_encode_like($zone.':\_WILD').'\'';
		$pg_where.=' OR page_name LIKE \''.db_encode_like($zone).'\'';
	}
	$select.=',group_id';
	$perhaps=((array_key_exists($groups,$GLOBALS['TOTAL_PP_CACHE'])) && (!$at_now))?$GLOBALS['TOTAL_PP_CACHE'][$groups]:$GLOBALS['SITE_DB']->query('SELECT '.$select.' FROM '.get_table_prefix().'group_page_access WHERE ('.$pg_where.') AND ('.$groups.')');
	$groups2=filter_group_permissivity($GLOBALS['FORUM_DRIVER']->get_members_groups($member,false));

	$found_match_key_one=false;
	$denied_groups=array();
	foreach ($groups2 as $group)
	{
		foreach ($perhaps as $praps)
		{
			if (strpos($praps['page_name'],':')!==false) $found_match_key_one=true;

			if (($praps['group_id']==$group) && (($praps['zone_name']==$zone) || ($praps['zone_name']=='/')))
			{
				if (($praps['page_name']==$page) || (($at_now) && ($praps['zone_name']=='/') && (match_key_match($praps['page_name'],true))))
				{
					$denied_groups[$group]=1;
				}
			}
		}
	}
	if (!$found_match_key_one) $at_now=false; // We found it makes no difference. Let our caching work better.

	if (count($denied_groups)==count($groups2))
	{
		$test=$GLOBALS['SITE_DB']->query_value_if_there('SELECT member_id FROM '.get_table_prefix().'member_page_access WHERE ('.$pg_where.') AND (member_id='.strval((integer)$member).' AND active_until>'.strval(time()).')');
		if (!is_null($test))
		{
			$result=true;
			handle_permission_check_logging($member,'has_page_access',array($page,$zone),$result);
			if (!$at_now)
			{
				$PAGE_ACCESS_CACHE[$member][$zone.':'.$page]=$result;
			}

			if ((function_exists('persistent_cache_set')) && (is_guest($member)))
			{
				persistent_cache_set('GUEST_PAGE_ACCESS',$PAGE_ACCESS_CACHE);
			}

			return $result;
		}
	}
	$result=(count($denied_groups)!=count($groups2)); // Has to be explicitly denied to all the usergroups they're in
	handle_permission_check_logging($member,'has_page_access',array($page,$zone),$result);
	if (!$at_now)
	{
		$PAGE_ACCESS_CACHE[$member][$zone.':'.$page]=$result;
	}

	if ((function_exists('persistent_cache_set')) && (is_guest($member)))
	{
		persistent_cache_set('GUEST_PAGE_ACCESS',$PAGE_ACCESS_CACHE);
	}

	return $result;
}

/**
 * For efficiency reasons, load up loads of category permissions.
 *
 * @param  MEMBER			The member being checked whether to have the access
 * @param  ?ID_TEXT		The ID code for the module being checked for category access (NULL: all categories)
 */
function load_up_all_module_category_permissions($member,$module=NULL)
{
	$groups=_get_where_clause_groups($member);
	if ($groups===NULL) return;

	global $CATEGORY_ACCESS_CACHE,$LOADED_ALL_CATEGORY_PERMISSIONS_FOR_CACHE;

	if ((array_key_exists($module,$LOADED_ALL_CATEGORY_PERMISSIONS_FOR_CACHE)) && ($LOADED_ALL_CATEGORY_PERMISSIONS_FOR_CACHE[$module]))
		return;

	if (!is_null($module))
	{
		$catclause='('.db_string_equal_to('module_the_name',$module).') AND ';
		$select='category_name';
	} else
	{
		$catclause='';
		$select='category_name,module_the_name';
	}
	$db=$GLOBALS[($module=='forums')?'FORUM_DB':'SITE_DB'];
	if ($db->query_value_if_there('SELECT COUNT(*) FROM '.$db->get_table_prefix().'group_category_access WHERE '.$catclause.'('.$groups.')')>1000) return; // Performance issue
	$perhaps=$db->query('SELECT '.$select.' FROM '.$db->get_table_prefix().'group_category_access WHERE '.$catclause.'('.$groups.') UNION ALL SELECT '.$select.' FROM '.$db->get_table_prefix().'member_category_access WHERE '.$catclause.'(member_id='.strval((integer)$member).' AND active_until>'.strval(time()).')',NULL,NULL,false,true);

	$LOADED_ALL_CATEGORY_PERMISSIONS_FOR_CACHE[$module]=true;

	$CATEGORY_ACCESS_CACHE[$member]=array();
	foreach ($perhaps as $row)
	{
		if (!is_null($module))
		{
			$for=$module.'/'.$row['category_name'];
		} else
		{
			$for=$row['module_the_name'].'/'.$row['category_name'];
		}
		$CATEGORY_ACCESS_CACHE[$member][$for]=true;
	}
}

/**
 * Find if a member's has access to a specified category
 *
 * @param  MEMBER			The member being checked whether to have the access
 * @param  ID_TEXT		The ID code for the module being checked for category access
 * @param  ID_TEXT		The ID code for the category being checked for access (often, a number cast to a string)
 * @return boolean		Whether the member has category access
 */
function has_category_access($member,$module,$category)
{
	if (running_script('upgrader')) return true;

	global $CATEGORY_ACCESS_CACHE,$LOADED_ALL_CATEGORY_PERMISSIONS_FOR_CACHE;
	if ((isset($CATEGORY_ACCESS_CACHE[$member])) && (isset($CATEGORY_ACCESS_CACHE[$member][$module.'/'.$category])))
	{
		handle_permission_check_logging($member,'has_category_access',array($module,$category),$CATEGORY_ACCESS_CACHE[$member][$module.'/'.$category]);
		return $CATEGORY_ACCESS_CACHE[$member][$module.'/'.$category];
	}
	if (array_key_exists($module,$LOADED_ALL_CATEGORY_PERMISSIONS_FOR_CACHE))
	{
		handle_permission_check_logging($member,'has_category_access',array($module,$category),false);
		return false;
	}

	$groups=_get_where_clause_groups($member);
	if ($groups===NULL) return true;

	$_category=db_string_equal_to('category_name',$category);

	$db=$GLOBALS[($module=='forums')?'FORUM_DB':'SITE_DB'];
	$perhaps=$db->query('SELECT category_name FROM '.$db->get_table_prefix().'group_category_access WHERE ('.db_string_equal_to('module_the_name',$module).' AND '.$_category.') AND ('.$groups.') UNION ALL SELECT category_name FROM '.$db->get_table_prefix().'member_category_access WHERE ('.db_string_equal_to('module_the_name',$module).' AND '.$_category.') AND (member_id='.strval((integer)$member).' AND active_until>'.strval(time()).')',1,NULL,false,true);

	$result=(count($perhaps)>0);
	handle_permission_check_logging($member,'has_category_access',array($module,$category),$result);
	$CATEGORY_ACCESS_CACHE[$member][$module.'/'.$category]=$result;
	return $result;
}

/**
 * Get the SQL WHERE clause to select for any the given member is in (gets combined with some condition, to check against every).
 *
 * @param  MEMBER			The member who's usergroups will be OR'd
 * @param  boolean		Whether to consider clubs (pass this false if considering page permissions, which work via explicit-denys across all groups, which could not happen for clubs as those denys could not have been set in the UI)
 * @return ?string		The SQL query fragment (NULL: admin, so permission regardless)
 */
function _get_where_clause_groups($member,$consider_clubs=true)
{
	if ($GLOBALS['FORUM_DRIVER']->is_super_admin($member)) return NULL;

	$groups=$GLOBALS['FORUM_DRIVER']->get_members_groups($member,false);
	if (!$consider_clubs) $groups=filter_group_permissivity($groups);
	$out='';
	foreach ($groups as $id)
	{
		if (!is_numeric($id)) $id=-10; // Workaround problems in some forum driver data

		if ($out!='') $out.=' OR ';
		$out.='group_id='.strval($id);
	}
	if ($out=='') fatal_exit(do_lang_tempcode('MEMBER_NO_GROUP')); // Shouldn't happen

	return $out;
}

/**
 * Find which of a list of usergroups are permissive ones.
 *
 * @param  array			List of groups to filter
 * @return array			List of permissive groups, filtered from those given
 */
function filter_group_permissivity($groups)
{
	if (get_forum_type()=='ocf')
	{
		static $permissive_groups=NULL;
		if ($permissive_groups===NULL)
		{
			$permissive_groups=collapse_1d_complexity('id',$GLOBALS['FORUM_DB']->query_select('f_groups',array('id'),array('g_is_private_club'=>0)));
		}

		$groups_new=array();
		foreach ($groups as $id)
		{
			if (in_array($id,$permissive_groups)) $groups_new[]=$id;
		}
		return $groups_new;
	}
	return $groups;
}

/**
 * Only allow members here that are either the give member, admins, or have a privilege. All other members come up to an error message wall.
 *
 * @param  MEMBER		The member who typically (i.e. when it's not an administrative override) we want the current member to be.
 * @param  ?ID_TEXT  The override permission the current member must have (NULL: no general override).
 * @param  ?ID_TEXT  An alternative permission to the 'assume_any_member' permission (NULL: no override).
 * @param  ?MEMBER	The member who is doing the viewing (NULL: current member).
 */
function enforce_personal_access($member_id,$permission=NULL,$permission2=NULL,$member_viewing=NULL)
{
	if (is_null($member_viewing)) $member_viewing=get_member();

	if (is_guest($member_id)) warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
	if ((!has_privilege($member_viewing,'assume_any_member')) && ((is_null($permission2)) || (!has_privilege($member_viewing,$permission2))))
	{
		if (($member_id!=$member_viewing) || ((!is_null($permission)) && (!has_privilege($member_viewing,$permission))))
		{
			if (!is_null($permission)) access_denied('PRIVILEGE',$permission);
			else access_denied('PRIVILEGE',is_null($permission2)?'assume_any_member':$permission2);
		}
	}
}

/**
 * Require presence of a permission for the current member; otherwise exit.
 *
 * @param  ID_TEXT		The permission to require
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 * @param  ?MEMBER		Member to check for (NULL: current user)
 */
function check_privilege($permission,$cats=NULL,$member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();
	if (!has_privilege($member_id,$permission,get_page_name(),$cats)) access_denied('PRIVILEGE',$permission);
}

/**
 * Find if a member has a specified permission in any category
 *
 * @param  MEMBER			The member being checked whether to have the permission
 * @param  ID_TEXT		The ID code for the permission being checked for
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 * @param  ID_TEXT		The ID code for the permission module being checked for
 * @return boolean		Whether the member has the permission
 */
function has_some_cat_privilege($member,$permission,$page,$permission_module)
{
	$page_wide_test=has_privilege($member,$permission,$page); // To make sure permissions are cached, and test if page-wide or site-wide exists
	if ($page_wide_test) return true;

	global $PRIVILEGE_CACHE;
	if ((array_key_exists($member,$PRIVILEGE_CACHE)) && (array_key_exists($permission,$PRIVILEGE_CACHE[$member])) && (array_key_exists('',$PRIVILEGE_CACHE[$member][$permission])) && (array_key_exists($permission_module,$PRIVILEGE_CACHE[$member][$permission][''])))
	{
		foreach ($PRIVILEGE_CACHE[$member][$permission][''][$permission_module] as $_permission)
		{
			if ($_permission==1) return true;
		}
	}

	return false;
}

/**
 * Find if a member has a specified permission
 *
 * @param  MEMBER			The member being checked whether to have the permission
 * @param  ID_TEXT		The ID code for the permission being checked for
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 * @return boolean		Whether the member has the permission
 */
function has_privilege($member,$permission,$page=NULL,$cats=NULL)
{
	if (running_script('upgrader')) return true;
	if ($GLOBALS['IN_MINIKERNEL_VERSION']==1) return true;

	if ($page===NULL) $page=get_page_name();

	global $SPAM_REMOVE_VALIDATION;
	if (($SPAM_REMOVE_VALIDATION) && ($member==get_member()) && (($permission=='bypass_validation_highrange_content') || ($permission=='bypass_validation_midrange_content') || ($permission=='bypass_validation_lowrange_content')))
	{
		return false;
	}

	$groups=_get_where_clause_groups($member);
	if ($groups===NULL) return true;

	global $PRIVILEGE_CACHE;
	if (isset($PRIVILEGE_CACHE[$member]))
	{
		if ($cats!==NULL)
		{
			$okay=false;
			for ($i=0;$i<intval(floor((float)count($cats)/2.0));$i++)
			{
				if (is_null($cats[$i*2])) continue;
				if (isset($PRIVILEGE_CACHE[$member][$permission][''][$cats[$i*2+0]][$cats[$i*2+1]]))
				{
					$result=$PRIVILEGE_CACHE[$member][$permission][''][$cats[$i*2+0]][$cats[$i*2+1]]==1;
					if (!$result)
					{
						handle_permission_check_logging($member,'has_privilege',array_merge(array($permission,$page),is_null($cats)?array():$cats),$result);
						return $result;
					}
					$okay=true;
				}
			}
			if ($okay)
			{
				$result=$okay;
				handle_permission_check_logging($member,'has_privilege',array_merge(array($permission,$page),is_null($cats)?array():$cats),$result);
				return $result;
			}
		}
		if ($page!='')
		{
			if (isset($PRIVILEGE_CACHE[$member][$permission][$page]['']['']))
			{
				$result=$PRIVILEGE_CACHE[$member][$permission][$page]['']['']==1;
				handle_permission_check_logging($member,'has_privilege',array_merge(array($permission,$page),is_null($cats)?array():$cats),$result);
				return $result;
			}
		}
		if (isset($PRIVILEGE_CACHE[$member][$permission]['']['']['']))
		{
			$result=$PRIVILEGE_CACHE[$member][$permission]['']['']['']==1;
			handle_permission_check_logging($member,'has_privilege',array_merge(array($permission,$page),is_null($cats)?array():$cats),$result);
			return $result;
		}
		$result=false;

		handle_permission_check_logging($member,'has_privilege',array_merge(array($permission,$page),($cats===NULL)?array():$cats),$result);
		return $result;
	}

	$where='';
	if ($member!=get_member()) $where.=' AND '.db_string_equal_to('privilege',$permission);
	$perhaps=$GLOBALS['SITE_DB']->query('SELECT privilege,the_page,module_the_name,category_name,the_value FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'group_privileges WHERE ('.$groups.')'.$where.' UNION ALL SELECT privilege,the_page,module_the_name,category_name,the_value FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'member_privileges WHERE member_id='.strval((integer)$member).' AND active_until>'.strval(time()).$where,NULL,NULL,false,true);
	if ((isset($GLOBALS['FORUM_DB'])) && ($GLOBALS['SITE_DB']->connection_write!=$GLOBALS['FORUM_DB']->connection_write) && (get_forum_type()=='ocf'))
	{
		$perhaps=array_merge($perhaps,$GLOBALS['FORUM_DB']->query('SELECT privilege,the_page,module_the_name,category_name,the_value FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'group_privileges WHERE ('.$groups.') AND '.db_string_equal_to('module_the_name','forums').$where.' UNION ALL SELECT privilege,the_page,module_the_name,category_name,the_value FROM '.$GLOBALS['FORUM_DB']->get_table_prefix().'member_privileges WHERE '.db_string_equal_to('module_the_name','forums').' AND member_id='.strval((integer)$member).' AND active_until>'.strval(time()).$where,NULL,NULL,false,true));
	}
	$PRIVILEGE_CACHE[$member]=array();
	foreach ($perhaps as $p)
	{
		if (@$PRIVILEGE_CACHE[$member][$p['privilege']][$p['the_page']][$p['module_the_name']][$p['category_name']]!=1)
			$PRIVILEGE_CACHE[$member][$p['privilege']][$p['the_page']][$p['module_the_name']][$p['category_name']]=$p['the_value'];
	}

	// Note: due to the way the "detect at override level" code works, the "best of" permission system does not hold with inconsistant overriding against all usergroups

	$result=has_privilege($member,$permission,$page,$cats);
	handle_permission_check_logging($member,'has_privilege',array_merge(array($permission,$page),is_null($cats)?array():$cats),$result);
	if ($member!=get_member()) unset($PRIVILEGE_CACHE[$member]);
	return $result;
}

/**
 * Check to see if a member's has permission to submit an item. If it doesn't, an error message is outputted.
 *
 * @param  string			The range of permission we are checking to see if they have; these ranges are like trust levels
 * @set	 low mid high cat_low cat_mid cat_high
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 */
function check_submit_permission($range,$cats=NULL,$page=NULL)
{
	if (is_null($page)) $page=get_page_name();

	if (!has_submit_permission($range,get_member(),get_ip_address(),$page,$cats))
		access_denied('PRIVILEGE','submit_'.$range.'range_content');
}

/**
 * Find if a member's has permission to submit
 *
 * @param  string			The range of permission we are checking to see if they have; these ranges are like trust levels
 * @set    low mid high cat_low cat_mid cat_high
 * @param  MEMBER			The member being checked whether to have the access
 * @param  IP				The member's IP address
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 * @return boolean		Whether the member can submit in this range
 */
function has_submit_permission($range,$member,$ip,$page,$cats=NULL)
{
	global $SUBMIT_PERMISSION_CACHE,$USERSUBMITBAN_MEMBER_CACHE;
	if (isset($SUBMIT_PERMISSION_CACHE[$range][$member][$ip][$page][($cats===NULL)?'':serialize($cats)]))
		return $SUBMIT_PERMISSION_CACHE[$range][$member][$ip][$page][($cats===NULL)?'':serialize($cats)];

	$result=NULL;

	if ((addon_installed('securitylogging')) && ((get_value('pinpoint_submitban_check')!=='1') || (get_zone_name()=='cms')))
	{
		if (is_null($USERSUBMITBAN_MEMBER_CACHE))
		{
			$test=$GLOBALS['SITE_DB']->query_select_value_if_there('usersubmitban_member','the_member',array('the_member'=>$member));
			$USERSUBMITBAN_MEMBER_CACHE=!is_null($test);
		}
		if ($USERSUBMITBAN_MEMBER_CACHE) $result=false;
	}

	if (is_null($result))
	{
		$result=has_privilege($member,'submit_'.$range.'range_content',$page,$cats);
	}

	$SUBMIT_PERMISSION_CACHE[$range][$member][$ip][$page][($cats===NULL)?'':serialize($cats)]=$result;

	return $result;
}

/**
 * Check to see if a member's has permission to edit an item. If it doesn't, an error message is outputted.
 *
 * @param  string			The range of permission we are checking to see if they have; these ranges are like trust levels
 * @set    low mid high cat_low cat_mid cat_high
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 */
function check_some_edit_permission($range,$cats=NULL)
{
	$ret=false;
	$member=get_member();
	if (is_guest($member)) $ret=false;
	if (has_privilege($member,'edit_'.$range.'range_content',get_page_name(),$cats)) $ret=true;
	if (has_privilege($member,'edit_own_'.$range.'range_content',get_page_name(),$cats)) $ret=true;

	if (!$ret) access_denied('PRIVILEGE','edit_own_'.$range.'range_content');
}

/**
 * Check to see if a member's has permission to edit an item. If it doesn't, an error message is outputted.
 *
 * @param  string			The range of permission we are checking to see if they have; these ranges are like trust levels
 * @set    low mid high cat_low cat_mid cat_high
 * @param  ?MEMBER		The member that owns this resource (NULL: no-one)
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 */
function check_edit_permission($range,$resource_owner,$cats=NULL,$page=NULL)
{
	if (is_null($page)) $page=get_page_name();

	if (!has_edit_permission($range,get_member(),$resource_owner,$page,$cats))
		access_denied('PRIVILEGE','edit_'.(($resource_owner==get_member())?'own_':'').$range.'range_content');
}

/**
 * Find if a member's has permission to edit
 *
 * @param  string			The range of permission we are checking to see if they have; these ranges are like trust levels
 * @set    low mid high cat_low cat_mid cat_high
 * @param  MEMBER			The member being checked for access
 * @param  ?MEMBER		The member that owns this resource (NULL: no-one)
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 * @return boolean		Whether the member may edit the resource
 */
function has_edit_permission($range,$member,$resource_owner,$page,$cats=NULL)
{
	if (is_guest($member)) return false;
	if ((!is_null($resource_owner)) && ($member==$resource_owner) && (has_privilege($member,'edit_own_'.$range.'range_content',$page,$cats))) return true;
	if (has_privilege($member,'edit_'.$range.'range_content',$page,$cats)) return true;
	return false;
}

/**
 * Check if a member's has permission to delete a specific resource. If it doesn't, an error message is outputted.
 *
 * @param  string			The range of permission we are checking to see if they have; these ranges are like trust levels
 * @set    low mid high cat_low cat_mid cat_high
 * @param  ?MEMBER		The member that owns this resource (NULL: no-one)
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 */
function check_delete_permission($range,$resource_owner,$cats=NULL,$page=NULL)
{
	if (is_null($page)) $page=get_page_name();

	if (!has_delete_permission($range,get_member(),$resource_owner,$page,$cats))
		access_denied('PRIVILEGE','delete_'.(($resource_owner==get_member())?'own_':'').$range.'range_content');
}

/**
 * Check to see if a member's has permission to delete a specific resource
 *
 * @param  string			The range of permission we are checking to see if they have; these ranges are like trust levels
 * @set    low mid high cat_low cat_mid cat_high
 * @param  MEMBER			The member being checked for access
 * @param  ?MEMBER		The member that owns this resource (NULL: no-one)
 * @param  ?ID_TEXT		The ID code for the page being checked (NULL: current page)
 * @param  ?array			A list of cat details to require access to (c-type-1,c-id-1,c-type-2,c-d-2,...) (NULL: N/A)
 * @return boolean		Whether the member may delete the resource
 */
function has_delete_permission($range,$member,$resource_owner,$page,$cats=NULL)
{
	if (is_guest($member)) return false;
	if ((!is_null($resource_owner)) && ($member==$resource_owner) && (has_privilege($member,'delete_own_'.$range.'range_content',$page,$cats))) return true;
	if (has_privilege($member,'delete_'.$range.'range_content',$page,$cats)) return true;
	return false;
}


