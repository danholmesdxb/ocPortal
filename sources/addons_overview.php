<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_addon_management
 */

/**
 * Get an addon icon.
 *
 * @param  ID_TEXT		The name of the addon
 * @return URLPATH		Addon icon (blank: could not find one)
 */
function find_addon_icon($hook)
{
	if ($hook=='') return '';

	static $addon_icons_cache=array();
	if (isset($addon_icons_cache[$hook])) return $addon_icons_cache[$hook];

	$path=get_custom_file_base().'/sources/hooks/systems/addon_registry/'.filter_naughty_harsh($hook).'.php';
	if (!file_exists($path))
	{
		$path=get_file_base().'/sources/hooks/systems/addon_registry/'.filter_naughty_harsh($hook).'.php';
	}
	if (!file_exists($path)) return '';
	$hook_file=file_get_contents($path);

	$matches=array();
	if (preg_match('#function get_file_list\(\)\s*\{([^\}]*)\}#',$hook_file,$matches)!=0)
	{
		if (!defined('HIPHOP_PHP'))
		{
			$addon_files=eval($matches[1]);
		} else
		{
			require_code('hooks/systems/addon_registry/'.$addon_name);
			$hook_ob=object_factory('Hook_addon_registry_'.$addon_name);
			$addon_files=$hook_ob->get_file_list();
		}
		foreach ($addon_files as $file)
		{
			if (substr($file,0,31)=='themes/default/images/bigicons/')
			{
				$addon_icons_cache[$hook]=find_theme_image('bigicons/'.basename($file,'.png'),false,true);
				return $addon_icons_cache[$hook];
			}
		}
	}

	$addon_icons_cache[$hook]='';
	return '';
}
