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
 * @package		iotds
 */

/**
 * Add an IOTD to the database and return the ID of the new entry.
 *
 * @param  URLPATH			The URL to the IOTD image
 * @param  SHORT_TEXT		The IOTD title
 * @param  LONG_TEXT			The IOTD caption
 * @param  URLPATH			The URL to the IOTD thumbnail image
 * @param  BINARY				Whether the IOTD is currently in use (note: setting this to 1 will not actually set the IOTD, and if it is 1, then the IOTD must be explicitly set only to this)
 * @param  BINARY				Whether the IOTD may be rated
 * @param  SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY				Whether the IOTD may be trackbacked
 * @param  LONG_TEXT			Notes for the IOTD
 * @param  ?TIME				The time of submission (NULL: now)
 * @param  ?MEMBER			The IOTD submitter (NULL: current member)
 * @param  BINARY				Whether the IOTD has been used before
 * @param  ?TIME				The time the IOTD was used (NULL: never)
 * @param  integer			The number of views had
 * @param  ?TIME				The edit date (NULL: never)
 * @return AUTO_LINK			The ID of the IOTD just added
 */
function add_iotd($url,$title,$caption,$thumb_url,$current,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$time=NULL,$submitter=NULL,$used=0,$use_time=NULL,$views=0,$edit_date=NULL)
{
	if (is_null($time)) $time=time();
	if (is_null($submitter)) $submitter=get_member();

	$id=$GLOBALS['SITE_DB']->query_insert('iotd',array('i_title'=>insert_lang_comcode($title,2),'add_date'=>time(),'edit_date'=>$edit_date,'iotd_views'=>$views,'allow_rating'=>$allow_rating,'allow_comments'=>$allow_comments,'allow_trackbacks'=>$allow_trackbacks,'notes'=>$notes,'date_and_time'=>$use_time,'used'=>$used,'url'=>$url,'caption'=>insert_lang_comcode($caption,2),'thumb_url'=>$thumb_url,'submitter'=>$submitter,'is_current'=>$current),true);

	log_it('ADD_IOTD',strval($id),$caption);

	return $id;
}

/**
 * Edit an IOTD.
 *
 * @param  AUTO_LINK			The ID of the IOTD to edit
 * @param  SHORT_TEXT		The IOTD title
 * @param  LONG_TEXT			The IOTD caption
 * @param  URLPATH			The URL to the IOTD image
 * @param  URLPATH			The URL to the IOTD thumbnail image
 * @param  BINARY				Whether the IOTD may be rated
 * @param  SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY				Whether the IOTD may be trackbacked
 * @param  LONG_TEXT			Notes for the IOTD
 */
function edit_iotd($id,$title,$caption,$thumb_url,$url,$allow_rating,$allow_comments,$allow_trackbacks,$notes)
{
	$_caption=$GLOBALS['SITE_DB']->query_value('iotd','caption',array('id'=>$id));
	$_title=$GLOBALS['SITE_DB']->query_value('iotd','i_title',array('id'=>$id));

	require_code('files2');
	delete_upload('uploads/iotds','iotd','url','id',$id,$url);
	delete_upload('uploads/iotds_thumbs','iotd','thumb_url','id',$id,$thumb_url);

	$GLOBALS['SITE_DB']->query_update('iotd',array('i_title'=>lang_remap_comcode($_title,$title),'edit_date'=>time(),'allow_rating'=>$allow_rating,'allow_comments'=>$allow_comments,'allow_trackbacks'=>$allow_trackbacks,'notes'=>$notes,'caption'=>lang_remap_comcode($_caption,$caption),'thumb_url'=>$thumb_url,'url'=>$url),array('id'=>$id),'',1);

	require_code('urls2');
	suggest_new_idmoniker_for('iotds','view',strval($id),$title);

	log_it('EDIT_IOTD',strval($id),get_translated_text($_caption));

	decache('main_iotd');

	update_spacer_post($allow_comments!=0,'videos',strval($id),build_url(array('page'=>'iotds','type'=>'view','id'=>$id),get_module_zone('iotds'),NULL,false,false,true),$title,get_value('comment_forum__iotds'));
}

/**
 * Delete an IOTD.
 *
 * @param  AUTO_LINK		The ID of the IOTD to delete
 */
function delete_iotd($id)
{
	$caption=$GLOBALS['SITE_DB']->query_value('iotd','caption',array('id'=>$id));
	$title=$GLOBALS['SITE_DB']->query_value('iotd','i_title',array('id'=>$id));

	log_it('DELETE_IOTD',strval($id),get_translated_text($caption));

	delete_lang($caption);
	delete_lang($title);

	require_code('files2');
	delete_upload('uploads/iotds','iotd','url','id',$id);
	delete_upload('uploads/iotds_thumbs','iotd','thumb_url','id',$id);

	// Delete from the database
	$GLOBALS['SITE_DB']->query_delete('iotd',array('id'=>$id),'',1);
	$GLOBALS['SITE_DB']->query_delete('rating',array('rating_for_type'=>'iotds','rating_for_id'=>$id));
	$GLOBALS['SITE_DB']->query_delete('trackbacks',array('trackback_for_type'=>'iotds','trackback_for_id'=>$id));

	decache('main_iotd');
}

/**
 * Set the IOTD.
 *
 * @param  AUTO_LINK		The IOTD ID to set
 */
function set_iotd($id)
{
	$rows=$GLOBALS['SITE_DB']->query_select('iotd',array('*'),array('id'=>$id),'',1);
	$title=get_translated_text($rows[0]['i_title']);
	$submitter=$rows[0]['submitter'];

	log_it('CHOOSE_IOTD',strval($id),$title);
	if (has_actual_page_access($GLOBALS['FORUM_DRIVER']->get_guest_id(),'iotds'))
		syndicate_described_activity('iotds:CHOOSE_IOTD',$title,'','','_SEARCH:iotds:view:'.strval($id),'','','iotds');

	if ((!is_guest($submitter)) && (addon_installed('points')))
	{
		require_code('points2');
		$_points_chosen=get_option('points_CHOOSE_IOTD');
		if (is_null($_points_chosen)) $points_chosen=35; else $points_chosen=intval($_points_chosen);
		if ($points_chosen!=0)
			system_gift_transfer(do_lang('IOTD'),$points_chosen,$submitter);
	}

	// Turn all others off
	$GLOBALS['SITE_DB']->query_update('iotd',array('is_current'=>0),array('is_current'=>1));

	// Turn ours on
	$GLOBALS['SITE_DB']->query_update('iotd',array('is_current'=>1,'used'=>1,'date_and_time'=>time()),array('id'=>$id),'',1);

	decache('main_iotd');
}


