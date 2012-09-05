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
 * @package		authors
 */

class Hook_awards_author
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
		$info['table']='authors';
		$info['date_field']=NULL;
		$info['id_field']='author';
		$info['add_url']=(has_submit_permission('mid',get_member(),get_ip_address(),'cms_authors'))?build_url(array('page'=>'cms_authors','type'=>'_ad'),get_module_zone('cms_authors')):new ocp_tempcode();
		$info['category_field']=NULL;
		$info['submitter_field']='forum_handle';
		$info['id_is_string']=true;
		require_lang('authors');
		$info['title']=do_lang_tempcode('AUTHORS');
		$info['category_is_string']=true;
		$info['archive_url']=build_url(array('page'=>'authors'),(!is_null($zone))?$zone:get_module_zone('authors'));
		$info['cms_page']='cms_authors';
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
		require_code('authors');

		return render_author_box($row,$zone,$give_context,$guid);
	}

}


