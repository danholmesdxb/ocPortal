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
 * @package		core_ocf
 */

class Hook_Profiles_Tabs_edit
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
		if (is_guest($member_id_viewing)) return false;
		
		if (!(($member_id_of==$member_id_viewing) || (has_specific_permission($member_id_viewing,'assume_any_member')))) return false;

		$hooks=find_all_hooks('systems','profiles_tabs_edit');
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/profiles_tabs_edit/'.$hook);
			$ob=object_factory('Hook_Profiles_Tabs_Edit_'.$hook);
			if ($ob->is_active($member_id_of,$member_id_viewing)) return true;
		}
		
		return false;
	}

	/**
	 * Standard modular render function for profile tab hooks.
	 *
	 * @param  MEMBER			The ID of the member who is being viewed
	 * @param  MEMBER			The ID of the member who is doing the viewing
	 * @return array			A triple: The tab title, the tab contents, the suggested tab order
	 */
	function render_tab($member_id_of,$member_id_viewing)
	{
		$title=do_lang_tempcode('EDIT_EM');

		$order=200;

		$tabs=array();

		$hooks=find_all_hooks('systems','profiles_tabs_edit');
		foreach (array_keys($hooks) as $hook)
		{
			require_code('hooks/systems/profiles_tabs_edit/'.$hook);
			$ob=object_factory('Hook_Profiles_Tabs_Edit_'.$hook);
			if ($ob->is_active($member_id_of,$member_id_viewing))
			{
				$tabs[]=$ob->render_tab($member_id_of,$member_id_viewing);
			}
		}

		global $M_SORT_KEY;
		$M_SORT_KEY=4;
		usort($tabs,'multi_sort');

		$javascript='';
		
		$hidden=new ocp_tempcode();

		// Session ID check, if saving
		if ((count($_POST)!=0) && (count($tabs)!=0))
		{
			global $SESSION_CONFIRMED;
			if ($SESSION_CONFIRMED==0)
			{
				access_denied('SESSION','',true);
			}
		}

		$_tabs=array();
		foreach ($tabs as $i=>$tab)
		{
			$javascript.=$tab[3];

			if (isset($tab[5])) $hidden->attach($tab[5]);
			$_tabs[]=array('TAB_TITLE'=>$tab[0],'TAB_FIELDS'=>$tab[1],'TAB_TEXT'=>$tab[2],'TAB_FIRST'=>$i==0,'TAB_LAST'=>!array_key_exists($i+1,$tabs));
		}
		$url=build_url(array('page'=>'_SELF'),'_SELF',NULL,true,false,false,'tab__edit');

		$content=do_template('OCF_MEMBER_PROFILE_EDIT',array('JAVASCRIPT'=>$javascript,'HIDDEN'=>$hidden,'URL'=>$url,'SUBMIT_NAME'=>do_lang_tempcode('SAVE'),'AUTOCOMPLETE'=>false,'SKIP_VALIDATION'=>true,'TABS'=>$_tabs));

		return array($title,$content,$order);
	}

}

