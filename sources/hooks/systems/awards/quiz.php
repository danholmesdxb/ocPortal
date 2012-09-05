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
 * @package		quizzes
 */

class Hook_awards_quiz
{

	/**
	 * Standard modular info function for award hooks. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
	 *
	 * @param  ?ID_TEXT	The zone to link through to (NULL: autodetect).
	 * @return ?array		Map of award content-type info (NULL: disabled).
	 */
	function info($zone=NULL)
	{
		$info=array();
		$info['connection']=$GLOBALS['SITE_DB'];
		$info['table']='quizzes';
		$info['date_field']='q_add_date';
		$info['id_field']='id';
		$info['add_url']=(has_submit_permission('high',get_member(),get_ip_address(),'cms_quiz'))?build_url(array('page'=>'cms_quiz','type'=>'ad'),get_module_zone('cms_quiz')):new ocp_tempcode();
		$info['category_field']='q_type';
		$info['parent_spec__table_name']=NULL;
		$info['parent_spec__parent_name']=NULL;
		$info['parent_spec__field_name']=NULL;
		$info['parent_field_name']=NULL;
		$info['submitter_field']='q_submitter';
		$info['id_is_string']=false;
		require_lang('quiz');
		$info['title']=do_lang_tempcode('QUIZZES');
		$info['validated_field']='q_validated';
		$info['category_is_string']=true;
		$info['archive_url']=build_url(array('page'=>'quiz'),(!is_null($zone))?$zone:get_module_zone('quiz'));
		$info['cms_page']='cms_quiz';
		$info['supports_custom_fields']=true;

		return $info;
	}

	/**
	 * Standard modular run function for award hooks. Renders a content box for an award/randomisation.
	 *
	 * @param  array		The database row for the content
	 * @param  ID_TEXT	The zone to display in
	 * @param  boolean	Whether to include context (i.e. say WHAT this is, not just show the actual content)
	 * @param  boolean	Whether to include breadcrumbs (if there are any)
	 * @param  ?ID_TEXT	Virtual root to use (NULL: none)
	 * @param  boolean	Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
	 * @param  ID_TEXT	Overridden GUID to send to templates (blank: none)
	 * @return tempcode	Results
	 */
	function run($row,$zone,$give_context=true,$include_breadcrumbs=true,$root=NULL,$attach_to_url_filter=false,$guid='')
	{
		require_code('quiz');

		return render_quiz_box($row,$zone,$give_context,$guid);
	}

}


