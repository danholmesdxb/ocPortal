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
 * @package		ocf_forum
 */

class Hook_content_meta_aware_topic
{

	/**
	 * Standard modular info function for content_meta_aware hooks. Allows progmattic identification of ocPortal entity model (along with db_meta table contents).
	 *
	 * @return ?array	Map of award content-type info (NULL: disabled).
	 */
	function info()
	{
		return array(
			'table'=>'f_topics',
			'id_field'=>'id',
			'id_field_numeric'=>true,
			'parent_category_field'=>'t_forum_id',
			'parent_category_meta_aware_type'=>'forum',
			'title_field'=>'t_cache_first_title',
			'title_field_dereference'=>false,

			'is_category'=>false,
			'is_entry'=>true,
			'seo_type_code'=>'topic',
			'feedback_type_code'=>NULL,
			'permissions_type_code'=>'forums', // NULL if has no permissions
			'view_pagelink_pattern'=>'_SEARCH:topicview:misc:_WILD',
			'edit_pagelink_pattern'=>'_SEARCH:topics:edit_topic:_WILD',
			'view_category_pagelink_pattern'=>'_SEARCH:forumview:misc:_WILD',
			'support_url_monikers'=>true,
			'search_hook'=>'ocf_posts',
			'views_field'=>'t_num_views',
			'submitter_field'=>'t_cache_first_member_id',
			'add_time_field'=>'t_cache_first_time',
			'edit_time_field'=>'t_cache_last_time',
			'validated_field'=>'t_validated',
			
			'addon_name'=>'ocf_forum',
			
			'module'=>'forumview',
		);
	}
	
}
