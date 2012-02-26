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
 * @package		news
 */

class Block_main_news
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
		$info['parameters']=array('param','member_based','filter','filter_and','multiplier','fallback_full','fallback_archive','blogs','historic','zone','title','show_in_full');
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
		$info['cache_on']='array(array_key_exists(\'show_in_full\',$map)?$map[\'show_in_full\']:\'0\',array_key_exists(\'title\',$map)?$map[\'title\']:\'\',array_key_exists(\'member_based\',$map)?$map[\'member_based\']:\'0\',array_key_exists(\'blogs\',$map)?$map[\'blogs\']:\'-1\',array_key_exists(\'historic\',$map)?$map[\'historic\']:\'\',$GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true),array_key_exists(\'param\',$map)?intval($map[\'param\']):14,array_key_exists(\'multiplier\',$map)?floatval($map[\'multiplier\']):0.5,array_key_exists(\'fallback_full\',$map)?intval($map[\'fallback_full\']):3,array_key_exists(\'fallback_archive\',$map)?intval($map[\'fallback_archive\']):6,array_key_exists(\'filter\',$map)?$map[\'filter\']:get_param(\'news_filter\',\'\'),array_key_exists(\'zone\',$map)?$map[\'zone\']:get_module_zone(\'news\'),array_key_exists(\'filter_and\',$map)?$map[\'filter_and\']:\'\')';
		$info['ttl']=60;
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
		require_lang('news');
		require_lang('ocf');
		require_css('news');

		$days=array_key_exists('param',$map)?intval($map['param']):14;
		$multiplier=array_key_exists('multiplier',$map)?floatval($map['multiplier']):0.5;
		$fallback_full=array_key_exists('fallback_full',$map)?intval($map['fallback_full']):3;
		$fallback_archive=array_key_exists('fallback_archive',$map)?intval($map['fallback_archive']):6;
		$zone=array_key_exists('zone',$map)?$map['zone']:get_module_zone('news');
		$historic=array_key_exists('historic',$map)?$map['historic']:'';
		$filter_and=array_key_exists('filter_and',$map)?$map['filter_and']:'';
		$blogs=array_key_exists('blogs',$map)?intval($map['blogs']):-1;
		$member_based=(array_key_exists('member_based',$map)) && ($map['member_based']=='1');

		global $NEWS_CATS;
		if (!isset($NEWS_CATS))
		{
			$NEWS_CATS=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('nc_owner'=>NULL));
			$NEWS_CATS=list_to_map('id',$NEWS_CATS);
		}

		$days=intval($days);
	
		$days_full=floatval($days)*$multiplier;
		$days_outline=floatval($days)-$days_full;

		// News Query
		require_code('ocfiltering');
		$filter=array_key_exists('filter',$map)?$map['filter']:get_param('news_filter','*');
		$filters_1=ocfilter_to_sqlfragment($filter,'p.news_category','news_categories',NULL,'p.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
		$filters_2=ocfilter_to_sqlfragment($filter,'d.news_entry_category','news_categories',NULL,'d.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
		$q_filter='('.$filters_1.' OR '.$filters_2.')';
		if ($blogs===0)
		{
			if ($q_filter!='') $q_filter.=' AND ';
			$q_filter.='nc_owner IS NULL';
		}
		elseif ($blogs===1)
		{
			if ($q_filter!='') $q_filter.=' AND ';
			$q_filter.='(nc_owner IS NOT NULL)';
		}
		if ($blogs!=-1)
		{
			$join=' LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'news_categories c ON c.id=p.news_category';
		} else $join='';

		if ($filter_and!='')
		{
			$filters_and_1=ocfilter_to_sqlfragment($filter_and,'p.news_category','news_categories',NULL,'p.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
			$filters_and_2=ocfilter_to_sqlfragment($filter_and,'d.news_entry_category','news_categories',NULL,'d.news_category','id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)
			$q_filter.=' AND ('.$filters_and_1.' OR '.$filters_and_2.')';
		}

		if ($historic=='')
		{
			$rows=($days_full==0.0)?array():$GLOBALS['SITE_DB']->query('SELECT *,p.id AS p_id FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'news p LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'news_category_entries d ON d.news_entry=p.id'.$join.' WHERE '.$q_filter.' AND validated=1 AND date_and_time>='.strval(time()-60*60*24*intval($days_full)).(can_arbitrary_groupby()?' GROUP BY p.id':'').' ORDER BY p.date_and_time DESC',300/*reasonable limit*/);
			if (!array_key_exists(0,$rows)) // Nothing recent, so we work to get at least something
			{
				$rows=$GLOBALS['SITE_DB']->query('SELECT *,p.id AS p_id FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'news p LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'news_category_entries d ON p.id=d.news_entry'.$join.' WHERE '.$q_filter.' AND validated=1'.(can_arbitrary_groupby()?' GROUP BY p.id':'').' ORDER BY p.date_and_time DESC',$fallback_full);
				$rows2=$GLOBALS['SITE_DB']->query('SELECT *,p.id AS p_id FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'news p LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'news_category_entries d ON p.id=d.news_entry'.$join.' WHERE '.$q_filter.' AND validated=1'.(can_arbitrary_groupby()?' GROUP BY p.id':'').' ORDER BY p.date_and_time DESC',$fallback_archive,$fallback_full);
			}
			else $rows2=$GLOBALS['SITE_DB']->query('SELECT *,p.id AS p_id FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'news p LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'news_category_entries d ON p.id=d.news_entry'.$join.' WHERE '.$q_filter.' AND validated=1 AND date_and_time>='.strval(time()-60*60*24*intval($days_full+$days_outline)).' AND date_and_time<'.strval(time()-60*60*24*intval($days_full)).(can_arbitrary_groupby()?' GROUP BY p.id':'').' ORDER BY p.date_and_time DESC',300/*reasonable limit*/);
		} else
		{
			if (function_exists('set_time_limit')) @set_time_limit(0);
			$start=0;
			do
			{
				$_rows=$GLOBALS['SITE_DB']->query('SELECT *,p.id AS p_id FROM '.$GLOBALS['SITE_DB']->get_table_prefix().'news p LEFT JOIN '.$GLOBALS['SITE_DB']->get_table_prefix().'news_category_entries d ON p.id=d.news_entry'.$join.' WHERE '.$q_filter.' AND validated=1'.(can_arbitrary_groupby()?' GROUP BY p.id':'').' ORDER BY p.date_and_time DESC',200,$start);
				$rows=array();
				$rows2=array();
				foreach ($_rows as $row)
				{
					$ok=false;
					switch ($historic)
					{
						case 'month':
							if ((date('m',utctime_to_usertime($row['date_and_time']))==date('m',utctime_to_usertime())) && (date('Y',utctime_to_usertime($row['date_and_time']))!=date('Y',utctime_to_usertime()))) $ok=true;
							break;

						case 'week':
							if ((date('W',utctime_to_usertime($row['date_and_time']))==date('W',utctime_to_usertime())) && (date('Y',utctime_to_usertime($row['date_and_time']))!=date('Y',utctime_to_usertime()))) $ok=true;
							break;

						case 'day':
							if ((date('d',utctime_to_usertime($row['date_and_time']))==date('d',utctime_to_usertime())) && (date('m',utctime_to_usertime($row['date_and_time']))==date('m',utctime_to_usertime())) && (date('Y',utctime_to_usertime($row['date_and_time']))!=date('Y',utctime_to_usertime()))) $ok=true;
							break;
					}
					if ($ok)
					{
						if (count($rows)<$fallback_full) $rows[]=$row;
						elseif (count($rows2)<$fallback_archive) $rows2[]=$row;
						else break 2;
					}
				}
				$start+=200;
			}
			while (count($_rows)==200);
			unset($_rows);
		}
		$rows=remove_duplicate_rows($rows,'p_id');

		$i=0;
		$news_text=new ocp_tempcode();
		while (array_key_exists($i,$rows))
		{
			$myrow=$rows[$i];
	//		$categories=$GLOBALS['SITE_DB']->query_select('news_category_entries',array('news_entry_category'),array('news_entry'=>$myrow['p_id']));
	
			if (has_category_access(get_member(),'news',strval($myrow['news_category'])))
			{
				$id=$myrow['p_id'];
				$date=get_timezoned_date($myrow['date_and_time']);
				$author_url=((addon_installed('authors')) && (!$member_based))?build_url(array('page'=>'authors','type'=>'misc','id'=>$myrow['author']),get_module_zone('authors')):new ocp_tempcode();
				$author=$myrow['author'];
				$news_title=get_translated_tempcode($myrow['title']);
				if ((array_key_exists('show_in_full',$map)) && ($map['show_in_full']=='1'))
				{
					$news=get_translated_tempcode($myrow['news_article']);
					$truncate=false;
					if ($news->is_empty())
					{
						$news=get_translated_tempcode($myrow['news']);
					}
				} else
				{
					$news=get_translated_tempcode($myrow['news']);
					if ($news->is_empty())
					{
						$news=get_translated_tempcode($myrow['news_article']);
						$truncate=true;
					} else $truncate=false;
				}
				$tmp=array('page'=>'news','type'=>'view','id'=>$id);
				if ($filter!='*') $tmp['filter']=$filter;
				if (($filter_and!='*') && ($filter_and!='')) $tmp['filter_and']=$filter_and;
				if ($blogs!=-1) $tmp['blog']=$blogs;
				$full_url=build_url($tmp,$zone);
				if (!array_key_exists($myrow['news_category'],$NEWS_CATS))
				{
					$_news_cats=$GLOBALS['SITE_DB']->query_select('news_categories',array('*'),array('id'=>$myrow['news_category']),'',1);
					if (array_key_exists(0,$_news_cats))
						$NEWS_CATS[$myrow['news_category']]=$_news_cats[0];
				}
				if ((!array_key_exists($myrow['news_category'],$NEWS_CATS)) || (!array_key_exists('nc_title',$NEWS_CATS[$myrow['news_category']])))
					$myrow['news_category']=db_get_first_id();
				$img=find_theme_image($NEWS_CATS[$myrow['news_category']]['nc_img']);
				if (is_null($img)) $img='';
				if ($myrow['news_image']!='')
				{
					$img=$myrow['news_image'];
					if (url_is_local($img)) $img=get_base_url().'/'.$img;
				}
				$category=get_translated_text($NEWS_CATS[$myrow['news_category']]['nc_title']);
				$seo_bits=seo_meta_get_for('news',strval($id));
				$map2=array('TAGS'=>get_loaded_tags('news',explode(',',$seo_bits[0])),'ID'=>strval($id),'TRUNCATE'=>$truncate,'BLOG'=>$blogs===1,'SUBMITTER'=>strval($myrow['submitter']),'CATEGORY'=>$category,'IMG'=>$img,'DATE'=>$date,'DATE_RAW'=>strval($myrow['date_and_time']),'NEWS_TITLE'=>$news_title,'AUTHOR'=>$author,'AUTHOR_URL'=>$author_url,'NEWS'=>$news,'FULL_URL'=>$full_url);
				if ((get_option('is_on_comments')=='1') && (!has_no_forum()) && ($myrow['allow_comments']>=1)) $map2['COMMENT_COUNT']='1';
				$news_text->attach(do_template('NEWS_PIECE_SUMMARY',$map2));
			}

			$i++;
		}
		$j=0;
		$news_text2=new ocp_tempcode();
		while (array_key_exists($j,$rows2))
		{
			$myrow=$rows2[$j];
	//		$categories=$GLOBALS['SITE_DB']->query_select('news_category_entries',array('news_entry_category'),array('news_entry'=>$myrow['id']));
	
			if (has_category_access(get_member(),'news',strval($myrow['news_category'])))
			{
				$date=get_timezoned_date($myrow['date_and_time']);
				$tmp=array('page'=>'news','type'=>'view','id'=>$myrow['p_id']);
				if ($filter!='*') $tmp['filter']=$filter;
				if (($filter_and!='*') && ($filter_and!='')) $tmp['filter_and']=$filter_and;
				if ($blogs!=-1) $tmp['blog']=$blogs;
				$url=build_url($tmp,$zone);
				$title=get_translated_tempcode($myrow['title']);
				$title_plain=get_translated_text($myrow['title']);

				$seo_bits=seo_meta_get_for('news',strval($myrow['p_id']));
				$map2=array('_GUID'=>'d81bda3a0912a1e708af6bb1f503b296','TAGS'=>get_loaded_tags('news',explode(',',$seo_bits[0])),'BLOG'=>$blogs===1,'ID'=>strval($myrow['p_id']),'SUBMITTER'=>strval($myrow['submitter']),'DATE'=>$date,'DATE_RAW'=>strval($myrow['date_and_time']),'URL'=>$url,'TITLE_PLAIN'=>$title_plain,'TITLE'=>$title);

				if ((get_option('is_on_comments')=='1') && (!has_no_forum()) && ($myrow['allow_comments']>=1)) $map2['COMMENT_COUNT']='1';

				$news_text2->attach(do_template('NEWS_BRIEF',$map2));
			}

			$j++;
		}
		$tmp=array('page'=>'news','type'=>'misc');
		if ($filter!='*') $tmp[is_numeric($filter)?'id':'filter']=$filter;
		if (($filter_and!='*') && ($filter_and!='')) $tmp['filter_and']=$filter_and;
		if ($blogs!=-1) $tmp['blog']=$blogs;
		$archive_url=build_url($tmp,$zone);
		$_is_on_rss=get_option('is_rss_advertised',true);
		$is_on_rss=is_null($_is_on_rss)?0:intval($_is_on_rss); // Set to zero if we don't want to show RSS links
		$submit_url=new ocp_tempcode();

		if ((has_actual_page_access(NULL,($blogs===1)?'cms_blogs':'cms_news',NULL,NULL)) && (has_submit_permission('high',get_member(),get_ip_address(),($blogs===1)?'cms_blogs':'cms_news')))
		{
			$map2=array('page'=>($blogs===1)?'cms_blogs':'cms_news','type'=>'ad','redirect'=>SELF_REDIRECT);
			if (is_numeric($filter))
			{
				$map2['cat']=$filter; // select news cat by default, if we are only showing one news cat in this block
			} elseif ($filter!='*')
			{
				$pos_a=strpos($filter,',');
				$pos_b=strpos($filter,'-');
				if ($pos_a!==false)
				{
					$first_cat=substr($filter,0,$pos_a);
				}
				elseif ($pos_b!==false)
				{
					$first_cat=substr($filter,0,$pos_b);
				} else $first_cat='';
				if (is_numeric($first_cat))
				{
					$map2['cat']=$first_cat;
				}
			}
			$submit_url=build_url($map2,get_module_zone(($blogs===1)?'cms_blogs':'cms_news'));
		}
		
		$_title=do_lang_tempcode(($blogs==1)?'BLOGS_POSTS':'NEWS');
		if ((array_key_exists('title',$map)) && ($map['title']!='')) $_title=make_string_tempcode(escape_html($map['title']));

		if (($i==0) && ($j==0))
		{
			return do_template('BLOCK_NO_ENTRIES',array('_GUID'=>'9d7065af4dd4026ffb34243fd931f99d','HIGH'=>false,'TITLE'=>$_title,'MESSAGE'=>do_lang_tempcode(($blogs==1)?'BLOG_NO_NEWS':'NO_NEWS'),'ADD_NAME'=>do_lang_tempcode(($blogs==1)?'ADD_NEWS_BLOG':'ADD_NEWS'),'SUBMIT_URL'=>$submit_url));
		}

		$atom_url=new ocp_tempcode();
		$rss_url=new ocp_tempcode();
		if ($is_on_rss==1)
		{
			$atom_url=make_string_tempcode(find_script('backend').'?type=atom&mode=news&filter='.$filter);
			$atom_url->attach(symbol_tempcode('KEEP'));
			$rss_url=make_string_tempcode(find_script('backend').'?type=rss2&mode=news&filter='.$filter);
			$rss_url->attach(symbol_tempcode('KEEP'));
		}

		return do_template('BLOCK_MAIN_NEWS',array('_GUID'=>'01f5fbd2b0c7c8f249023ecb4254366e','BLOG'=>$blogs===1,'TITLE'=>$_title,'CONTENT'=>$news_text,'BRIEF'=>$news_text2,'FILTER'=>$filter,'ARCHIVE_URL'=>$archive_url,'SUBMIT_URL'=>$submit_url,'RSS_URL'=>$rss_url,'ATOM_URL'=>$atom_url));
	}

}


