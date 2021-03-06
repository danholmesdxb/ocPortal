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
 * @package		weather
 */

class Block_side_weather
{
	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Manuprathap';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=6;
		$info['update_require_upgrade']=1;
		$info['locked']=false;
		$info['parameters']=array('param','unit');
		return $info;
	}

	/**
	 * Standard modular uninstall function.
	 */
	function uninstall()
	{
		$GLOBALS['SITE_DB']->drop_if_exists('cached_weather_codes');
	}

	/**
	 * Standard modular install function.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
	function install($upgrade_from=NULL,$upgrade_from_hack=NULL)
	{
		$GLOBALS['SITE_DB']->create_table('cached_weather_codes',array(
			'id'=>'*AUTO',
			'w_string'=>'SHORT_TEXT',
			'w_code'=>'INTEGER',
		));
	}

	/**
	 * Standard modular cache function.
	 *
	 * @return ?array	Map of cache details (cache_on and ttl) (NULL: module is disabled).
	 */
	function cacheing_environment()
	{
		$info=array();
		$info['cache_on']='array(cron_installed()?NULL:$GLOBALS[\'FORUM_DRIVER\']->is_staff(get_member()),(array_key_exists(\'unit\',$map) && ($map[\'unit\']!=\'\'))?$map[\'unit\']:\'c\',array_key_exists(\'param\',$map)?$map[\'param\']:\'\')';
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
		require_code('rss');
		require_lang('weather');

		if (array_key_exists('param',$map))
			$loc_code=$map['param']; // need to pass loc id ex :INXX0087
		else
			$loc_code='34503'; // if not found setting a default location for weather

		if (!is_numeric($loc_code))
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('cached_weather_codes','w_code',array('w_string'=>$loc_code));
			if (is_null($test))
			{
				require_code('files');
				$result=http_download_file('http://uk.weather.yahoo.com/search/weather?p='.urlencode($loc_code));
				$matches=array();
				if (preg_match('#<a href=\'/redirwoei/(\d+)\'>#',$result,$matches)!=0)
				{
					$loc_code=$matches[1];
				}
				elseif (preg_match('#-(\d+)/#',$GLOBALS['HTTP_DOWNLOAD_URL'],$matches)!=0)
				{
					$loc_code=$matches[1];
				} else return new ocp_tempcode();

				if (is_numeric($loc_code))
				{
					$GLOBALS['SITE_DB']->query_insert('cached_weather_codes',array(
						'w_string'=>$map['param'],
						'w_code'=>intval($loc_code),
					));
				}
			} else
			{
				$loc_code=strval($test);
			}
		}

		$temperature_unit=(array_key_exists('unit',$map) && ($map['unit']!=''))?$map['unit']:'c';

		if (is_numeric($loc_code))
		{
			$rss_url='http://weather.yahooapis.com/forecastrss?w='.urlencode($loc_code).'&u='.urlencode($temperature_unit);
		} else
		{
			$rss_url='http://weather.yahooapis.com/forecastrss?p='.urlencode($loc_code).'&u='.urlencode($temperature_unit);
		}

		$rss=new rss($rss_url);

		if (!is_null($rss->error))
		{
			$GLOBALS['DO_NOT_CACHE_THIS']=true;
			require_code('failure');
			relay_error_notification(do_lang('ERROR_HANDLING_RSS_FEED','',$rss->error),false,'error_occurred_weather');
			if (cron_installed())
			{
				if (!$GLOBALS['FORUM_DRIVER']->is_staff(get_member())) return new ocp_tempcode();
			}
			return do_template('INLINE_WIP_MESSAGE',array('MESSAGE'=>htmlentities($rss->error)));
		}

		foreach ($rss->gleamed_items as $item)
		{
			if (array_key_exists('title',$item))
				$title=$item['title'];

			if (array_key_exists('news',$item))
			{	
				$out=array();
				$content=$item['news'];
				if (preg_match('/<img src="(.*)"\/?'.'>/Usm',$item['news'],$out)!=0)
					$image = $out[1];
				else
					$image = '';
				if (preg_match('/Current Conditions:<\/b><br \/>(.*)<BR \/>/Uism',$item['news'],$out)!=0)
					$cur_conditions = $out[1];
				else
					$cur_conditions = '';
				if (preg_match('/Forecast:<\/b><BR \/>(.*)<br \/>/ism',$item['news'],$out)!=0)
					$forecast = $out[1];
				else
					$forecast = '';
			}
		}

		return do_template('BLOCK_SIDE_WEATHER',array('TITLE'=>$title,'LOC_CODE'=>$loc_code,'IMAGE'=>$image,'COND'=>$cur_conditions,'FORECAST'=>$forecast));
	}
}


