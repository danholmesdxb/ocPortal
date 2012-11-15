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

class Block_main_include_module
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
		$info['version']=1;
		$info['locked']=false;
		$info['parameters']=array('param','strip_title','only_if_permissions','leave_page_and_zone','merge_parameters');
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
		// Settings
		$strip_title=array_key_exists('strip_title',$map)?intval($map['strip_title']):1;
		$only_if_permissions=array_key_exists('only_if_permissions',$map)?intval($map['only_if_permissions']):1;
		$leave_page_and_zone=array_key_exists('leave_page_and_zone',$map)?($map['leave_page_and_zone']=='1'):false;
		$merge_parameters=array_key_exists('merge_parameters',$map)?($map['merge_parameters']=='1'):false;

		// Find out what we're virtualising
		$param=array_key_exists('param',$map)?$map['param']:'';
		if ($param=='') return new ocp_tempcode();
		list($zone,$attributes,)=page_link_decode($param);
		if (!array_key_exists('page',$attributes)) return new ocp_tempcode();
		if ($zone=='_SEARCH') $zone=get_page_zone($attributes['page'],false);
		elseif ($zone=='_SELF') $zone=get_zone_name();
		if (is_null($zone)) return new ocp_tempcode();
		if ($merge_parameters) $attributes+=$_GET; // Remember that PHP does not overwrite using the '+' operator (as unintuitive as this is!)

		// Check permissions
		if (($only_if_permissions==1) && (!has_actual_page_access(get_member(),$attributes['page'],$zone)))
			return new ocp_tempcode();

		// Setup virtual environment
		global $SKIP_TITLING;
		if ($strip_title==1)
		{
			$prior_skip_titling=$SKIP_TITLING;
			$SKIP_TITLING=true;
		}
		$new_zone=$leave_page_and_zone?get_zone_name():$zone;
		list($old_get,$old_zone,$old_current_script)=set_execution_context(
			($leave_page_and_zone?array('page'=>$attributes['page']):array())+$attributes,
			$new_zone
		);
		global $IS_VIRTUALISED_REQUEST;
		$IS_VIRTUALISED_REQUEST=true;
		push_output_state();

		// Do it!
		$ret=request_page($attributes['page'],false,$zone,NULL,true);
		$ret->handle_symbol_preprocessing();

		// Get things back to prior state
		set_execution_context(
			$old_get,
			$old_zone,
			$old_current_script,
			false
		);
		restore_output_state();
		$IS_VIRTUALISED_REQUEST=false;
		if ($strip_title==1)
		{
			$SKIP_TITLING=$prior_skip_titling;
		}

		return $ret;
	}

}


