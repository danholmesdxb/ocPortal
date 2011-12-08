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
 * @package		ocf_cpfs
 */

class Hook_Profiles_Tabs_Edit_privacy
{

	/**
	 * Find whether this hook is active.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return boolean		Whether this hook is active
	 */
	function is_active($member_id_of,$member_id_viewing)
	{
		return (($member_id_of==$member_id_viewing) || (has_specific_permission($member_id_viewing,'assume_any_member')) || (has_specific_permission($member_id_viewing,'member_maintenance')));
	}

	/**
	 * Standard modular render function for profile tabs edit hooks.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return array			A tuple: The tab title, the tab body text (may be blank), the tab fields, extra Javascript (may be blank) the suggested tab order, the hidden fields
	 */
	function render_tab($member_id_of,$member_id_viewing)
	{
		$title=do_lang_tempcode('PRIVACY');

		$order=60;

		require_lang('ocf_privacy');

		// Actualiser
		$_cpf_fields=post_param('cpf_fields',NULL);
		if ($_cpf_fields!==NULL)
		{
			$cpf_fields=explode(',', $_cpf_fields);

			foreach ($cpf_fields as $_field_id)
			{
				$field_id=intval($_field_id);

				$_guests_view=post_param('guests_'.strval($field_id),NULL);
				$_members_view=post_param('members_'.strval($field_id),NULL);
				$_friends_view=post_param('friends_'.strval($field_id),NULL);
				$_groups_view=post_param('groups_'.strval($field_id),NULL);

				$guests_view=(!is_null($_guests_view))?1:0;
				$members_view=(!is_null($_members_view))?1:0;
				$friends_view=(!is_null($_friends_view))?1:0;
				$groups_view=(!is_null($_groups_view))?$_groups_view:'';

				$cpf_permissions=$GLOBALS['FORUM_DB']->query_select('f_member_cpf_perms',array('*'),array('member_id'=>$member_id_of, 'field_id'=>$field_id));

				//if there are permissions saved already
				if (array_key_exists(0,$cpf_permissions) && $cpf_permissions[0]['field_id']==$field_id)
				{
					$GLOBALS['FORUM_DB']->query_update('f_member_cpf_perms',array('guest_view'=>$guests_view,'member_view'=>$members_view,'friend_view'=>$friends_view,'group_view'=>$groups_view),array('member_id'=>$member_id_of, 'field_id'=>$field_id),'',1);
				} else
				{
					//insert the custom permissions the user chose
					$GLOBALS['FORUM_DB']->query_insert('f_member_cpf_perms',array('guest_view'=>$guests_view,'member_view'=>$members_view,'friend_view'=>$friends_view,'group_view'=>$groups_view,'member_id'=>$member_id_of, 'field_id'=>$field_id));
				}
			}

			attach_message(do_lang_tempcode('SUCCESS_SAVE'),'inform');
		}

		// UI fields

		$member_cpfs=ocf_get_custom_fields_member($member_id_of);

		require_javascript('javascript_multi');

		$fields=new ocp_tempcode();
		require_code('form_templates');
		require_code('themes2');

		$tmp_groups=$GLOBALS['OCF_DRIVER']->get_usergroup_list(true);

		$cpf_ids=array();
		foreach ($member_cpfs as $cpf_id => $cpf)
		{
			if ((preg_replace('#^((\s)|(<br\s*/?'.'>)|(&nbsp;))*#','',$cpf) === '') && (count($member_cpfs)>15)) continue;

			$cpf_ids[]=$cpf_id; //id of the CPF

			$cpf_data=$GLOBALS['FORUM_DB']->query_select('f_custom_fields',array('*'),array('id'=>$cpf_id));
			if ($cpf_data[0]['cf_public_view']==0) continue;

			$cpf_permissions=$GLOBALS['FORUM_DB']->query_select('f_member_cpf_perms',array('*'),array('member_id'=>$member_id_of,'field_id'=>$cpf_id));

			if (!array_key_exists(0,$cpf_permissions))
			{
				$view_by_guests=true;

				$view_by_members=true;

				$view_by_friends=true;

				$view_by_groups=array();
				foreach ($tmp_groups as $gr_key => $group)
				{
					$view_by_groups[]=$gr_key;
				}
			} else
			{
				$view_by_guests=($cpf_permissions[0]['guest_view']==1);
				$view_by_members=($cpf_permissions[0]['member_view']==1);
				$view_by_friends=($cpf_permissions[0]['friend_view']==1);
				$view_by_groups=(strlen($cpf_permissions[0]['group_view'])>0)?explode(',',$cpf_permissions[0]['group_view']):array();
			}

			//CPF name
			$cpf_title=get_translated_text($cpf_data[0]['cf_name']);
			if (substr($cpf_title,0,4)=='ocp_')
			{
				$_cpf_title=do_lang('SPECIAL_CPF__'.$cpf_title,NULL,NULL,NULL,NULL,false);
				if (!is_null($_cpf_title)) $cpf_title=$_cpf_title;
			}

			$fields->attach(do_template('FORM_SCREEN_FIELD_SPACER',array('TITLE'=>$cpf_title)));

			$fields->attach(form_input_tick(do_lang_tempcode('GUESTS'),do_lang_tempcode('DESCRIPTION_VISIBLE_TO_GUESTS'),'guests_'.strval($cpf_id),$view_by_guests));
			$fields->attach(form_input_tick(do_lang_tempcode('MEMBERS'),do_lang_tempcode('DESCRIPTION_VISIBLE_TO_MEMBERS'),'members_'.strval($cpf_id),$view_by_members));
			$fields->attach(form_input_tick(do_lang_tempcode('FRIENDS'),do_lang_tempcode('DESCRIPTION_VISIBLE_TO_FRIENDS'),'friends_'.strval($cpf_id),$view_by_friends));

			$groups=new ocp_tempcode();
			foreach ($tmp_groups as $gr_key=>$group)
			{
				if ($group==get_option('probation_usergroup')) continue;

				$current_group_view=(in_array($gr_key,$view_by_groups));
				$groups->attach(form_input_list_entry(strval($gr_key),$current_group_view,$group,false,false));
			}

			$fields->attach(form_input_multi_list(do_lang_tempcode('GROUPS'),do_lang_tempcode('DESCRIPTION_VISIBLE_TO_GROUPS'),'groups_'.strval($cpf_id),$groups));
		}

		$cpfs_hidden=form_input_hidden('cpf_fields',implode(',', $cpf_ids));

		$text=do_template('OCF_CPF_PERMISSIONS_TAB',array('_GUID'=>'dbdac6ca3bc752b54d2a24a4c6e69c7c','FIELDS'=>$fields));

		$javascript='';

		return array($title,$fields,$text,$javascript,$order,$cpfs_hidden);
	}

}

