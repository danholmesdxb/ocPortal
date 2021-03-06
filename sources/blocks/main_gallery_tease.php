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
 * @package		galleries
 */

class Block_main_gallery_tease
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
		$info['parameters']=array('param','zone','reverse_thumb_order');
		return $info;
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	/*function cacheing_environment()
	{
		$info['cache_on']='array(array_key_exists(\'param\',$map)?$map[\'param\']:\'root\',array_key_exists(\'zone\',$map)?$map[\'zone\']:'',(array_key_exists(\'reverse_thumb_order\',$map))?$map[\'reverse_thumb_order\']:\'0\')';
		$info['ttl']=60*2;
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
		require_lang('galleries');
		require_code('galleries');
		require_css('galleries');

		$content=new ocp_tempcode();

		$parent_id=array_key_exists('param',$map)?$map['param']:'root';
		require_code('ocfiltering');
		$parent_ids=ocfilter_to_idlist_using_db($parent_id,'name','galleries','galleries','parent_id','parent_id','name',false,false);

		$zone=array_key_exists('zone',$map)?$map['zone']:get_module_zone('galleries');

		global $NON_CANONICAL_PARAMS;
		$NON_CANONICAL_PARAMS[]='max';

		$max=get_param_integer('max',5);
		if ($max<1) $max=1;
		$start=get_param_integer('start',0);

		// For all galleries off the given gallery
		$where='';
		foreach ($parent_ids as $parent_id)
		{
			if ($where!='') $where.=' OR ';
			$where.=db_string_equal_to('parent_id',$parent_id);
		}
		$query='FROM '.get_table_prefix().'galleries WHERE ('.$where.') AND name NOT LIKE \''.db_encode_like('download\_%').'\'';
		$galleries=$GLOBALS['SITE_DB']->query('SELECT * '.$query.' ORDER BY add_date DESC',$max,$start);
		foreach ($galleries as $child)
		{
			$url=build_url(array('page'=>'galleries','type'=>'misc','id'=>$child['name']),$zone);

			$member_id=get_member_id_from_gallery_name($child['name'],$child,true);
			$is_member=!is_null($member_id);
			$_title=get_translated_text($child['fullname']);
			$pic=$child['rep_image'];
			if (($pic=='') && ($is_member)) $pic=$GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id,'m_avatar_url');
			$teaser=get_translated_text($child['teaser']);
			if (($is_member) && (get_forum_type()=='ocf'))
			{
				require_code('ocf_members');
				require_code('ocf_members2');
				$member_info=ocf_show_member_box($member_id,true);
			} else $member_info=new ocp_tempcode();
			list($num_children,$num_images,$num_videos)=get_recursive_gallery_details($child['name']);
			if (($num_images==0) && ($num_videos==0)) continue;
			$thumb_order='ORDER BY id ASC';
			if ((array_key_exists('reverse_thumb_order',$map)) && ($map['reverse_thumb_order']=='1')) $thumb_order='ORDER BY id DESC';
			if ($pic=='') $pic=$GLOBALS['SITE_DB']->query_value_null_ok('images','thumb_url',array('cat'=>$child['name']),$thumb_order);
			if (is_null($pic)) $pic=$GLOBALS['SITE_DB']->query_value_null_ok('videos','thumb_url',array('cat'=>$child['name']),$thumb_order);
			if (is_null($pic)) $pic='';
			if (($pic!='') && (url_is_local($pic))) $pic=get_custom_base_url().'/'.$pic;
			$add_date=get_timezoned_date($child['add_date'],false);

			$sub=do_template('GALLERY_TEASE_PIC',array('_GUID'=>'37cd5f3fc64ac1c76f85980e69a50154','TEASER'=>$teaser,'ADD_DATE'=>$add_date,'NUM_CHILDREN'=>integer_format($num_children),'NUM_IMAGES'=>integer_format($num_images),'NUM_VIDEOS'=>integer_format($num_videos),'MEMBER_INFO'=>$member_info,'URL'=>$url,'PIC'=>$pic,'TITLE'=>$_title));
			$content->attach($sub);
		}

		$page_num=intval(floor(floatval($start)/floatval($max)))+1;
		$count=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT COUNT(*) '.$query);
		$num_pages=intval(ceil(floatval($count)/floatval($max)));
		if ($num_pages==0) $page_num=0;

		$previous_url=($start==0)?new ocp_tempcode():build_url(array('page'=>'_SELF','start'=>$start-$max),'_SELF');
		$next_url=($page_num==$num_pages)?new ocp_tempcode():build_url(array('page'=>'_SELF','start'=>$start+$max),'_SELF');
		$browse=do_template('NEXT_BROWSER_BROWSE_NEXT',array('_GUID'=>'6fb2def18957c246ddb2f19bf74abf9a','NEXT_LINK'=>$next_url,'PREVIOUS_LINK'=>$previous_url,'PAGE_NUM'=>integer_format($page_num),'NUM_PAGES'=>integer_format($num_pages)));

		return do_template('BLOCK_MAIN_GALLERY_TEASE',array('_GUID'=>'0e7f84042ab0c873155998eae41b8a16','CONTENT'=>$content,'BROWSE'=>$browse));
	}

}


