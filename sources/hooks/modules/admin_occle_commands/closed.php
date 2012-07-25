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
 * @package		occle
 */

class Hook_closed
{
	/**
	 * Standard modular run function for OcCLE hooks.
	 *
	 * @param  array	The options with which the command was called
	 * @param  array	The parameters with which the command was called
	 * @param  array	A reference to the OcCLE filesystem object
	 * @return array	Array of stdcommand, stdhtml, stdout, and stderr responses
	 */
	function run($options,$parameters,&$occle_fs)
	{
		require_code('config2');

		if ((array_key_exists('h',$options)) || (array_key_exists('help',$options))) return array('',do_command_help('closed',array('h','o','c'),array(true)),'','');
		else
		{
			if ((array_key_exists('o',$options)) || (array_key_exists('open',$options)))
			{
				set_option('site_closed','0');
			}
			if ((array_key_exists('c',$options)) || (array_key_exists('close',$options)))
			{
				if (!array_key_exists(0,$parameters)) return array('','','',do_lang('MISSING_PARAM','1','closed'));
				set_option('site_closed','1');
				set_option('closed',$parameters[0]);
			}
			return array('','',do_lang('SUCCESS'),'');
		}
	}

}

