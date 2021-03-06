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
 * @package		ocf_forum
 */

class Hook_content_meta_aware_post
{

	/**
	 * Standard modular info function for content_meta_aware hooks. Allows progmattic identification of ocPortal entity model (along with db_meta table contents).
	 *
	 * @return ?array	Map of award content-type info (NULL: disabled).
	 */
	function info()
	{
		return array(
			'content_type_label'=>'ocf:FORUM_POST',

			'table'=>'f_posts',
			'id_field'=>'id',
			'id_field_numeric'=>true,
			'parent_category_field'=>'p_topic_id',
			'parent_category_meta_aware_type'=>'topic',
			'title_field'=>'p_title',
			'title_field_dereference'=>false,

			'is_category'=>false,
			'is_entry'=>true,
			'seo_type_code'=>NULL,
			'feedback_type_code'=>'post',
			'permissions_type_code'=>'forums',
			'view_pagelink_pattern'=>'_SEARCH:topicview:findpost:_WILD',
			'edit_pagelink_pattern'=>'_SEARCH:topics:edit_post:_WILD',
			'view_category_pagelink_pattern'=>'_SEARCH:topicview:misc:_WILD',
			'support_url_monikers'=>false,
			'search_hook'=>'ocf_posts',
			'views_field'=>NULL,
			'submitter_field'=>'p_poster',
			'add_time_field'=>'p_time',
			'edit_time_field'=>'p_last_edit_time',
			'validated_field'=>'p_validated',

			'addon_name'=>'ocf_forum',

			'module'=>'forumview',
		);
	}

}
