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


class Hook_do_next_menus_core
{

	/**
	 * Standard modular run function for do_next_menu hooks. They find links to put on standard navigation menus of the system.
	 *
	 * @return array			Array of links and where to show
	 */
	function run()
	{
		return array(
			array('','zones',array('admin',array('type'=>'structure'),get_module_zone('admin')),do_lang_tempcode('STRUCTURE'),('DOC_STRUCTURE')),
			array('','view_this',array('admin',array('type'=>'usage'),get_module_zone('admin')),do_lang_tempcode('USAGE'),('DOC_USAGE')),
			array('','manage_themes',array('admin',array('type'=>'style'),get_module_zone('admin')),do_lang_tempcode('STYLE'),('DOC_STYLE')),
			array('','config',array('admin',array('type'=>'setup'),get_module_zone('admin')),do_lang_tempcode('SETUP'),('DOC_SETUP')),
			array('','cleanup',array('admin',array('type'=>'tools'),get_module_zone('admin')),do_lang_tempcode('TOOLS'),('DOC_TOOLS')),
			array('','permissionstree',array('admin',array('type'=>'security'),get_module_zone('admin')),do_lang_tempcode('SECURITY_GROUP_SETUP'),('DOC_SECURITY')),
			array('','cms_home',array('cms',array('type'=>'cms'),get_module_zone('cms')),do_lang_tempcode('CMS'),('DOC_CMS')),

			(has_privilege(get_member(),'edit_highrange_content')||has_privilege(get_member(),'edit_own_highrange_content'))?array('cms','comcode_page_edit',array('cms_comcode_pages',array('type'=>'misc'),get_module_zone('cms_comcode_pages')),do_lang_tempcode('ITEMS_HERE',do_lang_tempcode('_COMCODE_PAGES'),make_string_tempcode(escape_html(integer_format($GLOBALS['SITE_DB']->query_value_if_there('SELECT COUNT(DISTINCT the_zone,the_page) FROM '.get_table_prefix().'comcode_pages WHERE '.db_string_not_equal_to('the_zone','!')))))),('DOC_COMCODE_PAGE_EDIT')):NULL,

			array('structure','zones',array('admin_zones',array('type'=>'misc'),get_module_zone('admin_zones')),do_lang_tempcode('ZONES'),('DOC_ZONES')),
			array('structure','zone_editor',array('admin_zones',array('type'=>'editor'),get_module_zone('admin_zones')),do_lang_tempcode('ZONE_EDITOR'),('DOC_ZONE_EDITOR')),
			array('structure','menus',array('admin_menus',array('type'=>'misc'),get_module_zone('admin_menus')),do_lang_tempcode('MENU_MANAGEMENT'),('DOC_MENUS')),
			addon_installed('page_management')?array('structure','sitetree',array('admin_sitetree',array('type'=>'site_tree'),get_module_zone('admin_sitetree')),do_lang_tempcode('SITE_TREE_EDITOR'),('DOC_SITE_TREE_EDITOR')):NULL,
			addon_installed('redirects_editor')?array('structure','redirect',array('admin_redirects',array('type'=>'misc'),get_module_zone('admin_redirects')),do_lang_tempcode('REDIRECTS'),('DOC_REDIRECTS')):NULL,
			addon_installed('page_management')?array('structure','pagewizard',array('admin_sitetree',array('type'=>'pagewizard'),get_module_zone('admin_sitetree')),do_lang_tempcode('PAGE_WIZARD'),('DOC_PAGE_WIZARD')):NULL,
			addon_installed('breadcrumbs')?array('structure','xml',array('admin_config',array('type'=>'xml_breadcrumbs'),get_module_zone('admin_config')),do_lang_tempcode('BREADCRUMB_OVERRIDES'),('DOC_BREADCRUMB_OVERRIDES')):NULL,
			array('structure','addons',array('admin_addons',array('type'=>'misc'),get_module_zone('admin_addons')),do_lang_tempcode('ADDONS'),('DOC_ADDONS')),

			(get_forum_type()!='ocf' || !addon_installed('ocf_cpfs'))?NULL:array('usage','customprofilefields',array('admin_ocf_customprofilefields',array('type'=>'stats'),get_module_zone('admin_ocf_customprofilefields')),do_lang_tempcode('CUSTOM_PROFILE_FIELD_STATS'),('DOC_CUSTOM_PROFILE_FIELDS_STATS')),
			addon_installed('errorlog')?array('usage','errorlog',array('admin_errorlog',array(),get_module_zone('admin_errorlog')),do_lang_tempcode('ERROR_LOG'),('DOC_ERROR_LOG')):NULL,
			addon_installed('actionlog')?array('usage','actionlog',array('admin_actionlog',array('type'=>'misc'),get_module_zone('admin_actionlog')),do_lang_tempcode('VIEW_ACTION_LOGS'),('DOC_ACTION_LOG')):NULL,
			addon_installed('securitylogging')?array('usage','securitylog',array('admin_security',array('type'=>'misc'),get_module_zone('admin_security')),do_lang_tempcode('SECURITY_LOGGING'),('DOC_SECURITY_LOGGING')):NULL,
			(get_option('mail_queue_debug')!=='1')?NULL:array('usage','email',array('admin_emaillog',array('type'=>'misc'),get_module_zone('admin_emaillog')),do_lang_tempcode('EMAIL_QUEUE'),('DOC_EMAIL_QUEUE')),

			array('style','manage_themes',array('admin_themes',array('type'=>'misc'),get_module_zone('admin_themes')),do_lang_tempcode('THEMES'),('DOC_THEMES')),
			(get_forum_type()!='ocf')?NULL:array('style','emoticons',array('admin_ocf_emoticons',array('type'=>'misc'),get_module_zone('admin_ocf_emoticons')),do_lang_tempcode('EMOTICONS'),('DOC_EMOTICONS')),

			array('setup','config',array('admin_config',array('type'=>'misc'),get_module_zone('admin_config')),do_lang_tempcode('CONFIGURATION'),('DOC_CONFIGURATION')),
			addon_installed('awards')?array('setup','awards',array('admin_awards',array('type'=>'misc'),get_module_zone('admin_awards')),do_lang_tempcode('AWARDS'),('DOC_AWARDS')):NULL,
			(get_forum_type()=='ocf' || !addon_installed('welcome_emails'))?/*Is on members menu*/NULL:array('setup','welcome_emails',array('admin_ocf_welcome_emails',array('type'=>'misc'),get_module_zone('admin_ocf_welcome_emails')),do_lang_tempcode('WELCOME_EMAILS'),('DOC_WELCOME_EMAILS')),
			((get_forum_type()=='ocf')?/*Is on members menu*/ || (addon_installed('securitylogging')))?NULL:array('tools','investigateuser',array('admin_lookup',array(),get_module_zone('admin_lookup')),do_lang_tempcode('INVESTIGATE_USER'),('DOC_INVESTIGATE_USER')),
			addon_installed('xml_fields')?array('setup','xml',array('admin_config',array('type'=>'xml_fields'),get_module_zone('admin_config')),do_lang_tempcode('FIELD_FILTERS'),('DOC_FIELD_FILTERS')):NULL,

			(get_forum_type()!='ocf')?NULL:array('tools','editmember',array('admin_ocf_join',array('type'=>'menu'),get_module_zone('admin_ocf_join')),do_lang_tempcode('MEMBERS'),('DOC_MEMBERS')),

			//((get_forum_type()!='ocf')||(!has_privilege(get_member(),'control_usergroups')))?NULL:array('tools','usergroups',array('groups',array('type'=>'misc'),get_module_zone('groups'),do_lang_tempcode('SWITCH_ZONE_WARNING')),do_lang_tempcode('SECONDARY_GROUP_MEMBERSHIP'),('DOC_SECONDARY_GROUP_MEMBERSHIP')),
			array('tools','cleanup',array('admin_cleanup',array('type'=>'misc'),get_module_zone('admin_cleanup')),do_lang_tempcode('CLEANUP_TOOLS'),('DOC_CLEANUP_TOOLS')),

			array('security','permissionstree',array('admin_permissions',array('type'=>'misc'),get_module_zone('admin_permissions')),do_lang_tempcode('PERMISSIONS_TREE'),('DOC_PERMISSIONS_TREE')),
			addon_installed('match_key_permissions')?array('security','matchkeysecurity',array('admin_permissions',array('type'=>'keys'),get_module_zone('admin_permissions')),do_lang_tempcode('PAGE_MATCH_KEY_ACCESS'),('DOC_PAGE_MATCH_KEY_ACCESS')):NULL,
			//array('security','sitetree',array('admin_permissions',array('type'=>'page'),get_module_zone('admin_permissions')),do_lang_tempcode('PAGE_ACCESS'), ('DOC_PAGE_PERMISSIONS')),  // Disabled as not needed - but tree permission editor will redirect to it if no javascript available
			addon_installed('securitylogging')?array('security','ipban',array('admin_ipban',array('type'=>'misc'),get_module_zone('admin_ipban')),do_lang_tempcode('BANNED_ADDRESSES'),('DOC_IPBAN')):NULL,
			array('security','privileges',array('admin_permissions',array('type'=>'privileges'),get_module_zone('admin_permissions')),do_lang_tempcode('GLOBAL_PRIVILEGES'),('DOC_PRIVILEGES')),
			(get_forum_type()!='ocf')?NULL:array('security','usergroups',array('admin_ocf_groups',array('type'=>'misc'),get_module_zone('admin_ocf_groups')),do_lang_tempcode('USERGROUPS'),('DOC_GROUPS')),
			(get_forum_type()=='ocf')?NULL:array('security','usergroups',array('admin_permissions',array('type'=>'absorb'),get_module_zone('admin_security')),do_lang_tempcode('ABSORB_PERMISSIONS'),('DOC_ABSORB_PERMISSIONS')),

			(is_null(get_value('brand_base_url')))?array('tools','cleanup',array('admin_config',array('type'=>'upgrader'),get_module_zone('admin_config')),do_lang_tempcode('FU_UPGRADER_TITLE'),('FU_UPGRADER_INTRO')):NULL,
			(addon_installed('syndication'))?array('tools','cleanup',array('admin_config',array('type'=>'backend'),get_module_zone('admin_config')),do_lang_tempcode('FEEDS'),('OPML_INDEX_DESCRIPTION')):NULL,
			(addon_installed('code_editor'))?array('tools','cleanup',array('admin_config',array('type'=>'code_editor'),get_module_zone('admin_config')),do_lang_tempcode('CODE_EDITOR'),('DOC_CODE_EDITOR')):NULL,
		);
	}

}


