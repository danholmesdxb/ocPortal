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
 * @package		awards
 */

class Block_main_content
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
		$info['parameters']=array('param','efficient','id','filter','filter_b','title','zone','no_links','give_context','include_breadcrumbs','render_if_empty','guid');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(array_key_exists(\'guid\',$map)?$map[\'guid\']:\'\',(array_key_exists(\'give_context\',$map)?$map[\'give_context\']:\'0\')==\'1\',(array_key_exists(\'include_breadcrumbs\',$map)?$map[\'include_breadcrumbs\']:\'0\')==\'1\',array_key_exists(\'no_links\',$map)?$map[\'no_links\']:0,array_key_exists(\'title\',$map)?$map[\'title\']:\'\',$GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true),array_key_exists(\'param\',$map)?$map[\'param\']:\'download\',array_key_exists(\'id\',$map)?$map[\'id\']:\'\',array_key_exists(\'efficient\',$map)?$map[\'efficient\']:\'_SEARCH\',array_key_exists(\'filter\',$map)?$map[\'filter\']:\'\',array_key_exists(\'filter_b\',$map)?$map[\'filter_b\']:\'\',array_key_exists(\'zone\',$map)?$map[\'zone\']:\'_SEARCH\')';
		$info['ttl']=60*24; // Intentionally, do randomisation acts as 'of the day'
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		require_lang('awards');
		require_code('awards');

		$guid=array_key_exists('guid',$map)?$map['guid']:'';
		if (array_key_exists('param',$map))
		{
			$type_id=$map['param'];
		} else
		{
			if (addon_installed('downloads'))
			{
				$type_id='download';
			} else
			{
				$hooks=find_all_hooks('systems','awards');
				$type_id=key($hooks);
			}
		}
		$content_id=array_key_exists('id',$map)?$map['id']:NULL;
		$randomise=is_null($content_id);
		$zone=array_key_exists('zone',$map)?$map['zone']:'_SEARCH';
		$efficient=(array_key_exists('efficient',$map)?$map['efficient']:'1')=='1';
		$filter=array_key_exists('filter',$map)?$map['filter']:'';
		$filter_b=array_key_exists('filter_b',$map)?$map['filter_b']:'';
		$title=array_key_exists('title',$map)?$map['title']:NULL;
		if ($title===NULL) $title=do_lang('RANDOM_CONTENT');
		$give_context=(array_key_exists('give_context',$map)?$map['give_context']:'0')=='1';
		$include_breadcrumbs=(array_key_exists('include_breadcrumbs',$map)?$map['include_breadcrumbs']:'0')=='1';

		if ((!file_exists(get_file_base().'/sources/hooks/systems/awards/'.filter_naughty_harsh($type_id,true).'.php')) && (!file_exists(get_file_base().'/sources_custom/hooks/systems/awards/'.filter_naughty_harsh($type_id,true).'.php')))
			return paragraph(do_lang_tempcode('NO_SUCH_CONTENT_TYPE',$type_id));

		require_code('hooks/systems/awards/'.filter_naughty_harsh($type_id,true),true);
		$object=object_factory('Hook_awards_'.$type_id);
		$info=$object->info();
		if (is_null($info)) warn_exit(do_lang_tempcode('IMPOSSIBLE_TYPE_USED'));
		if (((!array_key_exists('id_is_string',$info)) || (!$info['id_is_string'])) && (!is_null($content_id)) && (!is_numeric($content_id)))
		{
			require_code('hooks/systems/content_meta_aware/'.filter_naughty_harsh($type_id,true),true);
			$object_cm=object_factory('Hook_content_meta_aware_'.$type_id);
			$info_cm=$object_cm->info();
			list(,$resource_page,$resource_type)=explode(':',$info_cm['view_pagelink_pattern']);
			$content_id=$info['connection']->query_select_value_if_there('url_id_monikers','m_resource_id',array('m_resource_page'=>$resource_page,'m_resource_type'=>$resource_type,'m_moniker'=>$content_id));
			if (is_null($content_id)) return new ocp_tempcode();
		}

		$submit_url=$info['add_url'];
		if (is_object($submit_url)) $submit_url=$submit_url->evaluate();
		if (!has_actual_page_access(NULL,$info['cms_page'],NULL,NULL)) $submit_url='';

		// Randomisation mode
		if ($randomise)
		{
			if (is_array($info['category_field']))
			{
				$category_field_access=$info['category_field'][0];
				$category_field_filter=$info['category_field'][1];
			} else
			{
				$category_field_access=$info['category_field'];
				$category_field_filter=$info['category_field'];
			}
			if (array_key_exists('category_type',$info))
			{
				if (is_array($info['category_type']))
				{
					$category_type_access=$info['category_type'][0];
					$category_type_filter=$info['category_type'][1];
				} else
				{
					$category_type_access=$info['category_type'];
					$category_type_filter=$info['category_type'];
				}
			} else
			{
				$category_type_access=mixed();
				$category_type_filter=mixed();
			}

			$where='';
			$query='FROM '.get_table_prefix().$info['table'].' g';
			if ((!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) && (!$efficient))
			{
				$_groups=$GLOBALS['FORUM_DRIVER']->get_members_groups(get_member(),false,true);
				$groups='';
				foreach ($_groups as $group)
				{
					if ($groups!='') $groups.=' OR ';
					$groups.='a.group_id='.strval((integer)$group);
				}

				$query='FROM '.get_table_prefix().$info['table'].' g';
				if (!is_null($category_field_access))
				{
					if ($category_type_access==='!')
					{
						$query.=' LEFT JOIN '.get_table_prefix().'group_page_access a ON (g.'.$category_field_filter.'=a.page_name AND g.'.$category_field_access.'=a.zone_name AND ('.$groups.'))';
						$query.=' LEFT JOIN '.get_table_prefix().'group_zone_access a2 ON (g.'.$category_field_access.'=a2.zone_name)';
					} else
					{
						$query.=' LEFT JOIN '.get_table_prefix().'group_category_access a ON ('.db_string_equal_to('a.module_the_name',$category_type_access).' AND g.'.$category_field_access.'=a.category_name)';
					}
				}
				if ((!is_null($category_field_filter)) && ($category_field_filter!=$category_field_access) && ($info['category_type']!=='!'))
				{
					$query.=' LEFT JOIN '.get_table_prefix().'group_category_access a2 ON ('.db_string_equal_to('a.module_the_name',$category_type_filter).' AND g.'.$category_field_filter.'=a2.category_name)';
				}
				if (!is_null($category_field_access))
				{
					if ($where!='') $where.=' AND ';
					if ($info['category_type']==='!')
					{
						$where.='(a.group_id IS NULL) AND ('.str_replace('a.','a2.',$groups).') AND (a2.group_id IS NOT NULL)';
					} else
					{
						$where.='('.$groups.') AND (a.group_id IS NOT NULL)';
					}
				}
				if ((!is_null($category_field_filter)) && ($category_field_filter!=$category_field_access) && ($info['category_type']!=='!'))
				{
					if ($where!='') $where.=' AND ';
					$where.='('.str_replace('a.group_id','a2.group_id',$groups).') AND (a2.group_id IS NOT NULL)';
				}
				if (array_key_exists('where',$info))
				{
					if ($where!='') $where.=' AND ';
					$where.=$info['where'];
				}
			}

			if ((array_key_exists('validated_field',$info)) && ($info['validated_field']!=''))
			{
				if ($where!='') $where.=' AND ';
				$where.=$info['validated_field'].'=1';
			}

			$x1='';
			$x2='';
			if (($filter!='') && (!is_null($category_field_access)))
				$x1=$this->build_filter($filter,$info,$category_field_access,is_array($info['category_is_string'])?$info['category_is_string'][0]:$info['category_is_string']);
			if (($filter_b!='') && (!is_null($category_field_filter)))
				$x2=$this->build_filter($filter_b,$info,$category_field_filter,is_array($info['category_is_string'])?$info['category_is_string'][1]:$info['category_is_string']);

			if ($where.$x1.$x2!='')
			{
				if ($where=='') $where='1=1';
				$query.=' WHERE '.$where;
				if ($x1!='') $query.=' AND ('.$x1.')';
				if ($x2!='') $query.=' AND ('.$x2.')';
			}

			$rows=$info['connection']->query('SELECT COUNT(*) as cnt '.$query);

			$cnt=$rows[0]['cnt'];
			if ($cnt==0)
			{
				return do_template('BLOCK_NO_ENTRIES',array('_GUID'=>($guid!='')?$guid:'13f060922a5ab6c370f218b2ecc6fe9c','HIGH'=>true,'TITLE'=>$title,'MESSAGE'=>do_lang_tempcode('NO_ENTRIES'),'ADD_NAME'=>do_lang_tempcode('ADD'),'SUBMIT_URL'=>str_replace('=%21','__ignore=1',$submit_url)));
			}

			$rows=$info['connection']->query('SELECT * '.$query,1,mt_rand(0,$cnt-1));
			$award_content_row=$rows[0];

			if (is_array($info['id_field']))
			{
				$content_id='';
				foreach ($info['id_field'] as $f)
				{
					$x=$award_content_row[$f];
					if (!is_string($x)) $x=strval($x);

					if ($content_id!='') $content_id.=':';
					$content_id.=$x;
				}
			} else
			{
				$content_id=$award_content_row[$info['id_field']];
				if (!is_string($content_id)) $content_id=strval($content_id);
			}
		}

		// Select mode
		else
		{
			$wherea=array();
			if (is_array($info['id_field']))
			{
				$bits=explode(':',$content_id);

				// FUDGE
				if ($type_id=='comcode_page')
				{
					// Try and force a parse of the page, so it's in the system
					$result=request_page(array_key_exists(1,$bits)?$bits[1]:get_comcode_zone($bits[0]),false,$bits[0],'comcode_custom',true);
					if ($result===NULL || $result->is_empty()) return new ocp_tempcode();
				}

				$wherea=array();
				foreach ($bits as $i=>$bit)
				{
					$wherea[$info['id_field'][$i]]=$info['id_is_string']?$bit:intval($bit);
				}
			} else $wherea[$info['id_field']]=$info['id_is_string']?$content_id:intval($content_id);

			$rows=$info['connection']->query_select($info['table'].' g',array('g.*'),$wherea,'',1);
			if (!array_key_exists(0,$rows))
			{
				return do_template('BLOCK_NO_ENTRIES',array('_GUID'=>($guid!='')?$guid:'12d8cdc62cd78480b83c8daaaa68b686','HIGH'=>true,'TITLE'=>$title,'MESSAGE'=>do_lang_tempcode('MISSING_RESOURCE'),'ADD_NAME'=>do_lang_tempcode('ADD'),'SUBMIT_URL'=>str_replace('=%21','__ignore=1',$submit_url)));
			}
			$award_content_row=$rows[0];
		}

		if (is_null($award_content_row))
		{
			warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
		}

		$submit_url=str_replace('%21',$content_id,$submit_url);

		$archive_url=$info['archive_url'];

		$rendered_content=$object->run($award_content_row,$zone,$give_context,$include_breadcrumbs,NULL,false,$guid);

		if ((array_key_exists('no_links',$map)) && ($map['no_links']=='1'))
		{
			$submit_url='';
			$archive_url='';
		}

		$raw_date=($info['date_field']=='')?mixed():$award_content_row[$info['date_field']];
		return do_template('BLOCK_MAIN_CONTENT',array('_GUID'=>($guid!='')?$guid:'fce1eace6008d650afc0283a7be9ec30','TYPE'=>$info['title'],'TITLE'=>$title,'RAW_AWARD_DATE'=>is_null($raw_date)?'':strval($raw_date),'AWARD_DATE'=>is_null($raw_date)?'':get_timezoned_date($raw_date),'CONTENT'=>$rendered_content,'SUBMIT_URL'=>$submit_url,'ARCHIVE_URL'=>$archive_url));
	}

	/**
	 * Make a filter SQL fragment.
	 *
	 * @param  string		The filter string.
	 * @param  array		Map of details of our content type.
	 * @param  string		The field name of the category to filter against.
	 * @param  boolean	Whether the category is a string.
	 * @return string		SQL fragment.
	 */
	function build_filter($filter,$info,$category_field_filter,$category_is_string)
	{
		$parent_spec__table_name=array_key_exists('parent_spec__table_name',$info)?$info['parent_spec__table_name']:$info['table'];
		$parent_field_name=array_key_exists('parent_field_name',$info)?$info['parent_field_name']:NULL;
		$parent_spec__parent_name=array_key_exists('parent_spec__parent_name',$info)?$info['parent_spec__parent_name']:NULL;
		$parent_spec__field_name=array_key_exists('parent_spec__field_name',$info)?$info['parent_spec__field_name']:NULL;
		require_code('ocfiltering');
		return ocfilter_to_sqlfragment($filter,$category_field_filter,$parent_spec__table_name,$parent_spec__parent_name,$parent_field_name,$parent_spec__field_name,!$category_is_string,!$category_is_string);
	}
}


