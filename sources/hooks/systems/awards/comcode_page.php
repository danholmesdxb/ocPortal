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
 * @package		core_comcode_pages
 */

class Hook_awards_comcode_page
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
		$info['table']='comcode_pages';
		$info['date_field']='p_add_date';
		$info['id_field']=array('the_zone','the_page');
		$info['add_url']=(has_submit_permission('high',get_member(),get_ip_address(),'cms_comcode_pages'))?build_url(array('page'=>'cms_comcode_pages','type'=>'ed'),get_module_zone('cms_comcode_pages')):new ocp_tempcode();
		$info['category_field']=array('the_zone','the_page');
		$info['category_type']='!';
		$info['submitter_field']='p_submitter';
		$info['id_is_string']=true;
		require_lang('zones');
		$info['title']=do_lang_tempcode('COMCODE_PAGES');
		$info['validated_field']='p_validated';
		$info['category_is_string']=true;
		$info['archive_url']=build_url(array('page'=>'sitemap'),(!is_null($zone))?$zone:get_page_zone('sitemap'));
		$info['cms_page']='cms_comcode_pages';

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
		unset($zone); // Meaningless here

		require_code('zones2');

		return render_comcode_page_box($row,$give_context,$include_breadcrumbs,$root,$guid);
	}

}


