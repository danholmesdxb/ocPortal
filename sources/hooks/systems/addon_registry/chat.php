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
 * @package		chat
 */

class Hook_addon_registry_chat
{

	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Chat rooms and instant messaging.';
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(),
			'recommends'=>array(),
			'conflicts_with'=>array(),
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(

			'sources/hooks/systems/notifications/im_invited.php',
			'sources/hooks/systems/notifications/new_buddy.php',
			'sources/hooks/systems/notifications/member_entered_chatroom.php',
			'sources/hooks/systems/notifications/ocf_friend_birthday.php',
			'sources/hooks/systems/config_default/chat_default_post_colour.php',
			'sources/hooks/systems/config_default/chat_default_post_font.php',
			'sources/hooks/systems/config_default/chat_flood_timelimit.php',
			'sources/hooks/systems/config_default/chat_private_room_deletion_time.php',
			'sources/hooks/systems/config_default/chat_show_stats_count_messages.php',
			'sources/hooks/systems/config_default/chat_show_stats_count_rooms.php',
			'sources/hooks/systems/config_default/chat_show_stats_count_users.php',
			'sources/hooks/systems/config_default/group_private_chatrooms.php',
			'sources/hooks/systems/config_default/points_chat.php',
			'sources/hooks/systems/config_default/sitewide_im.php',
			'sources/hooks/systems/config_default/username_click_im.php',
			'sources/hooks/systems/realtime_rain/chat.php',
			'sources/hooks/systems/symbols/CHAT_IM.php',
			'sources/hooks/systems/profiles_tabs/friends.php',
			'uploads/personal_sound_effects/index.html',
			'uploads/personal_sound_effects/.htaccess',
			'data/sounds/contact_off.mp3',
			'data/sounds/contact_on.mp3',
			'data/sounds/error.mp3',
			'data/sounds/invited.mp3',
			'data/sounds/message_background.mp3',
			'data/sounds/message_initial.mp3',
			'data/sounds/message_received.mp3',
			'data/sounds/message_sent.mp3',
			'data/sounds/you_connect.mp3',
			'sources/hooks/modules/chat_bots/default.php',
			'sources/hooks/modules/chat_bots/index.html',
			'CHAT_LOGS_SCREEN.tpl',
			'CHAT_SET_EFFECTS_SCREEN.tpl',
			'CHAT_SET_EFFECTS_SETTING_BLOCK.tpl',
			'CHAT_SITEWIDE_IM.tpl',
			'CHAT_SITEWIDE_IM_POPUP.tpl',
			'CHAT_SOUND.tpl',
			'CHAT_MODERATE_SCREEN.tpl',
			'sources/hooks/modules/admin_import_types/chat.php',
			'sources/hooks/modules/admin_setupwizard/chat.php',
			'sources/hooks/modules/admin_themewizard/chat.php',
			'sources/hooks/systems/content_meta_aware/chat.php',
			'sources/hooks/systems/addon_registry/chat.php',
			'sources/hooks/systems/ocf_cpf_filter/points_chat.php',
			'BLOCK_SIDE_SHOUTBOX_MESSAGE.tpl',
			'BLOCK_SIDE_SHOUTBOX.tpl',
			'BLOCK_SIDE_SHOUTBOX_IFRAME.tpl',
			'CHAT_SCREEN.tpl',
			'CHATCODE_EDITOR_BUTTON.tpl',
			'CHATCODE_EDITOR_MICRO_BUTTON.tpl',
			'CHAT_INVITE.tpl',
			'CHAT_MESSAGE.tpl',
			'CHAT_PRIVATE.tpl',
			'CHAT_STAFF_ACTIONS.tpl',
			'JAVASCRIPT_CHAT.tpl',
			'CHAT_BUDDIES_LIST_SCREEN.tpl',
			'CHAT_LOBBY_SCREEN.tpl',
			'CHAT_LOBBY_IM_AREA.tpl',
			'CHAT_LOBBY_IM_PARTICIPANT.tpl',
			'CHAT_ROOM_LINK.tpl',
			'sources/hooks/modules/chat_bots/.htaccess',
			'adminzone/pages/modules/admin_chat.php',
			'chat.css',
			'themes/default/images/bigicons/chatrooms.png',
			'themes/default/images/EN/chatcodeeditor/index.html',
			'themes/default/images/EN/chatcodeeditor/invite.png',
			'themes/default/images/EN/chatcodeeditor/new_room.png',
			'themes/default/images/EN/chatcodeeditor/private_message.png',
			'themes/default/images/pagepics/chatrooms.png',
			'cms/pages/modules/cms_chat.php',
			'data_custom/modules/chat/index.html',
			'data_custom/modules/chat/.htaccess',
			'lang/EN/chat.ini',
			'site/pages/comcode/EN/userguide_chatcode.txt',
			'site/pages/modules/chat.php',
			'sources/chat.php',
			'sources/chat_stats.php',
			'sources/chat_poller.php',
			'sources/chat2.php',
			'sources/hooks/blocks/side_stats/stats_chat.php',
			'sources/hooks/modules/admin_occle_commands/send_chatmessage.php',
			'sources/hooks/modules/admin_occle_commands/watch_chatroom.php',
			'sources/hooks/modules/admin_occle_notifications/chat.php',
			'sources/hooks/modules/members/chat.php',
			'sources/hooks/systems/do_next_menus/chat.php',
			'sources/hooks/systems/rss/chat.php',
			'site/dllogs.php',
			'data/shoutbox.php',
			'site/messages.php',
			'sources/blocks/side_shoutbox.php',
			'OCF_MEMBER_PROFILE_FRIENDS.tpl',
		);
	}


	/**
	* Get mapping between template names and the method of this class that can render a preview of them
	*
	* @return array			The mapping
	*/
	function tpl_previews()
	{
		return array(
				'CHAT_MODERATE_SCREEN.tpl'=>'administrative__chat_moderate_screen',
				'BLOCK_SIDE_SHOUTBOX_MESSAGE.tpl'=>'block_side_shoutbox',
				'BLOCK_SIDE_SHOUTBOX.tpl'=>'block_side_shoutbox',
				'CHAT_MESSAGE.tpl'=>'chat_message',
				'CHAT_LOGS_SCREEN.tpl'=>'chat_logs_screen',
				'CHAT_STAFF_ACTIONS.tpl'=>'chat_staff_actions',
				'CHAT_PRIVATE.tpl'=>'chat_private',
				'CHAT_INVITE.tpl'=>'chat_invite',
				'CHAT_SOUND.tpl'=>'chat_lobby_screen',
				'BLOCK_SIDE_SHOUTBOX_IFRAME.tpl'=>'block_side_shoutbox_iframe',
				'CHAT_LOBBY_IM_AREA.tpl'=>'chat_lobby_screen',
				'CHAT_SITEWIDE_IM_POPUP.tpl'=>'chat_sitewide_im_popup',
				'CHAT_LOBBY_IM_PARTICIPANT.tpl'=>'chat_lobby_screen',
				'CHAT_SITEWIDE_IM.tpl'=>'chat_sitewide_im',
				'CHAT_ROOM_LINK.tpl'=>'chat_lobby_screen',
				'CHAT_LOBBY_SCREEN.tpl'=>'chat_lobby_screen',
				'CHATCODE_EDITOR_BUTTON.tpl'=>'chat_screen',
				'CHATCODE_EDITOR_MICRO_BUTTON.tpl'=>'chat_screen',
				'COMCODE_EDITOR_MICRO_BUTTON.tpl'=>'chat_screen',
				'CHAT_SCREEN.tpl'=>'chat_screen',
				'CHAT_SET_EFFECTS_SETTING_BLOCK.tpl'=>'chat_set_effects_screen',
				'CHAT_SET_EFFECTS_SCREEN.tpl'=>'chat_set_effects_screen',
				'CHAT_BUDDIES_LIST_SCREEN.tpl'=>'chat_buddies_list_screen',
				'OCF_MEMBER_PROFILE_FRIENDS.tpl'=>'ocf_member_profile_friends',
				);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__ocf_member_profile_friends()
	{
		$tab_content=do_lorem_template('OCF_MEMBER_PROFILE_FRIENDS',array(
			'MEMBER_ID'=>placeholder_id(),
			'FRIENDS_A'=>array(),
			'FRIENDS_B'=>array(array('USERNAME'=>lorem_phrase(),'URL'=>placeholder_url(),'USERGROUP'=>lorem_phrase())),
			'ADD_FRIEND_URL'=>placeholder_url(),
			'REMOVE_FRIEND_URL'=>placeholder_url(),
			'ALL_BUDDIES_LINK'=>placeholder_url(),
			'BOX'=>lorem_paragraph(),
		));
		return array(
			lorem_globalise($tab_content,NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_buddies_list_screen()
	{
		require_css('ocf');

		$friend_map=array('USERGROUP'=>lorem_phrase(),'USERNAME'=>lorem_phrase(),'URL'=>placeholder_url(),'F_ID'=>placeholder_id(),'BOX'=>placeholder_table());
		$buddies=array();
		$buddies[]=$friend_map;

		return array(
			lorem_globalise(
				do_lorem_template('CHAT_BUDDIES_LIST_SCREEN',array(
					'TITLE'=>lorem_title(),
					'BUDDIES'=>$buddies,
					'RESULTS_BROWSER'=>lorem_phrase(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__chat_moderate_screen()
	{
		return array(
			lorem_globalise(
				do_lorem_template('CHAT_MODERATE_SCREEN',array(
					'URL'=>placeholder_url(),
					'TITLE'=>lorem_title(),
					'INTRODUCTION'=>lorem_phrase(),
					'CONTENT'=>placeholder_table(),
					'LINKS'=>placeholder_array(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__block_side_shoutbox()
	{
		$tpl=	do_lorem_template('BLOCK_SIDE_SHOUTBOX_MESSAGE',array(
					'USER'=>lorem_word(),
					'MESSAGE'=>lorem_phrase(),
					'TIME_RAW'=>placeholder_time(),
					'TIME'=>placeholder_time(),
						)
			);

		$tpl=do_lorem_template('BLOCK_SIDE_SHOUTBOX',array('MESSAGES'=>$tpl,'URL'=>placeholder_url()));

		return array(
			lorem_globalise(
			$tpl,NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_message()
	{
		require_lang('submitban');
		$chat_actions	=	do_lorem_template('CHAT_STAFF_ACTIONS',array(
									'CHAT_BAN_URL'=>placeholder_url(),
									'CHAT_UNBAN_URL'=>placeholder_url(),
									'EDIT_URL'=>placeholder_url(),
									'BAN_URL'=>placeholder_url(),
										)
								);

		return array(
			lorem_globalise(
				do_lorem_template('CHAT_MESSAGE',array(
					'SYSTEM_MESSAGE'=>lorem_phrase(),
					'STAFF'=>"1",
					'OLD_MESSAGES'=>lorem_phrase(),
					'AVATAR_URL'=>placeholder_avatar(),
					'STAFF_ACTIONS'=>$chat_actions,
					'USER'=>lorem_word(),
					'MESSAGE'=>lorem_phrase(),
					'TIME'=>placeholder_time(),
					'RAW_TIME'=>placeholder_time(),
					'FONT_COLOUR'=>'blue',
					'FONT_FACE'=>'Arial',
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_logs_screen()
	{
		return array(
			lorem_globalise(
				do_lorem_template('CHAT_LOGS_SCREEN',array(
					'TITLE'=>lorem_phrase(),
					'MESSAGES'=>lorem_phrase(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_private()
	{
		return array(
			lorem_globalise(
				do_lorem_template('CHAT_PRIVATE',array(
					'SYSTEM_MESSAGE'=>lorem_phrase(),
					'MESSAGE'=>lorem_phrase_html(),
					'USER'=>lorem_word(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_invite()
	{
		return array(
			lorem_globalise(
				do_lorem_template('CHAT_INVITE',array(
					'USERNAME'=>lorem_word(),
					'ROOM'=>lorem_phrase(),
					'LINK'=>placeholder_link(),
					'page'=>lorem_phrase(),
					'type'=>lorem_phrase(),
					'room_id'=>placeholder_number(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__block_side_shoutbox_iframe()
	{
		$tpl=	do_lorem_template('BLOCK_SIDE_SHOUTBOX_MESSAGE',array(
					'USER'=>lorem_word(),
					'MESSAGE'=>lorem_phrase(),
					'TIME_RAW'=>placeholder_time(),
					'TIME'=>placeholder_time(),
						)
			);

		$tpl=do_lorem_template('BLOCK_SIDE_SHOUTBOX',array('MESSAGES'=>$tpl,'URL'=>placeholder_url()));

		return array(
			lorem_globalise(
				do_lorem_template('BLOCK_SIDE_SHOUTBOX_IFRAME',array(
					'CONTENT'=>$tpl,
					'ROOM_ID'=>placeholder_id(),
					'NUM_MESSAGES'=>placeholder_number(),
					'LAST_MESSAGE_ID'=>placeholder_id(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_sitewide_im()
	{
		return array(
			lorem_globalise(
				do_lorem_template('CHAT_SITEWIDE_IM',array(
					'IM_AREA_TEMPLATE'=>lorem_phrase(),
					'IM_PARTICIPANT_TEMPLATE'=>lorem_phrase(),
					'CHAT_SOUND'=>lorem_phrase(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_lobby_screen()
	{
		$chat_sound = do_lorem_template('CHAT_SOUND',array('SOUND_EFFECTS'=>placeholder_array(),'KEY'=>lorem_word(),'VALUE'=>lorem_word_2()));

		$im_area_template=do_lorem_template('CHAT_LOBBY_IM_AREA',array('MESSAGES_PHP'=>find_script('messages'),'ROOM_ID'=>'__room_id__'));

		$im_participant_template=do_lorem_template('CHAT_LOBBY_IM_PARTICIPANT',array('PROFILE_URL'=>placeholder_url(),'ID'=>'__id__','ROOM_ID'=>'__room_id__','USERNAME'=>'__username__','ONLINE'=>'__online__','AVATAR_URL'=>'__avatar_url__','MAKE_BUDDY_URL'=>placeholder_url(),'BLOCK_MEMBER_URL'=>placeholder_url()));

		$fields=new ocp_tempcode();

		foreach (placeholder_array() as $key=>$room)
		{
			$users=array('1'=>'Guest','2'=>'admin');

			$usernames=new ocp_tempcode();

			foreach($users as $user)
				$usernames->attach(do_lorem_template('OCF_USER_MEMBER',array('PROFILE_URL'=>placeholder_url(),'USERNAME'=>$user,'COLOUR'=>'black','AT'=>lorem_phrase())));

			$room_link=do_lorem_template('CHAT_ROOM_LINK',array('PRIVATE'=>true,'ID'=>strval($key),'NAME'=>$room,'USERNAMES'=>$usernames,'URL'=>placeholder_url()));
			$fields->attach($room_link);
		}

		$buddies=array();
		foreach (placeholder_array() as $key=>$buddy)
			$buddies[]=array('DATE_AND_TIME_RAW'=>placeholder_time(),'DATE_AND_TIME'=>placeholder_time(),'MEMBER_PROFILE_LINK'=>placeholder_url(),'MEMBER_ID'=>strval($key),'USERNAME'=>lorem_word(),'ONLINE_TEXT'=>lorem_phrase());

		return array(
			lorem_globalise(
				do_lorem_template('CHAT_LOBBY_SCREEN',array(
					'MESSAGE'=>lorem_phrase(),
					'CHAT_SOUND'=>$chat_sound,
					'IM_PARTICIPANT_TEMPLATE'=>$im_participant_template,
					'IM_AREA_TEMPLATE'=>$im_area_template,
					'BUDDIES'=>$buddies,
					'CAN_IM'=>true,
					'ONLINE_URL'=>placeholder_url(),
					'URL_ADD_BUDDY'=>placeholder_url(),
					'URL_REMOVE_BUDDIES'=>placeholder_url(),
					'TITLE'=>lorem_title(),
					'ROOMS'=>$fields,
					'PRIVATE_ROOM'=>placeholder_link(),
					'ROOM_URL'=>placeholder_url(),
					'PASSWORD_HASH'=>placeholder_random(),
					'MOD_LINK'=>placeholder_link(),
					'BLOCKING_LINK'=>placeholder_link(),
					'SETEFFECTS_LINK'=>placeholder_link(),
					'ADD_ROOM_URL'=>placeholder_url(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_screen()
	{
		require_lang('comcode');
		require_javascript('javascript_chat');
		require_javascript('javascript_yahoo_2');

		$chat_sound = do_lorem_template('CHAT_SOUND',array('SOUND_EFFECTS'=>placeholder_array(),'KEY'=>lorem_word(),'VALUE'=>lorem_word_2()));

		$_buttons=array(
			'private_message',
			'invite',
			'new_room'
		);

		$buttons=new ocp_tempcode();
		foreach ($_buttons as $button) 		$buttons->attach(do_lorem_template('CHATCODE_EDITOR_BUTTON',array('TITLE'=>do_lang_tempcode('INPUT_CHATCODE_'.$button),'B'=>$button)));

		$micro_buttons=new ocp_tempcode();
		$_micro_buttons=array(
			'b',
			'i',
		);

		$micro_buttons->attach(do_lorem_template('CHATCODE_EDITOR_MICRO_BUTTON',array('TITLE'=>lorem_phrase(),'B'=>'new_room')));

		foreach ($_micro_buttons as $button)
			$micro_buttons->attach(do_lorem_template('COMCODE_EDITOR_MICRO_BUTTON',array('FIELD_NAME'=>'post','TITLE'=>do_lang_tempcode('INPUT_COMCODE_'.$button),'B'=>$button)));

		$users=array('1'=>'Guest','2'=>'admin');

		$usernames = new ocp_tempcode();
		foreach($users as $user)
			$usernames->attach(do_lorem_template('OCF_USER_MEMBER',array('PROFILE_URL'=>placeholder_url(),'USERNAME'=>$user,'COLOUR'=>'black','AT'=>lorem_phrase())));

		return array(
			lorem_globalise(
				do_lorem_template('CHAT_SCREEN',array(
					'CHATTERS'=>$usernames,
					'CHAT_SOUND'=>$chat_sound,
					'ROOM_ID'=>placeholder_number(),
					'DEBUG'=>'0',
					'MESSAGES_PHP'=>find_script('messages'),
					'CHATCODE_HELP'=>lorem_phrase(),
					'ROOM_NAME'=>lorem_word(),
					'MICRO_BUTTONS'=>$micro_buttons,
					'BUTTONS'=>$buttons,
					'YOUR_NAME'=>lorem_word(),
					'MESSAGES_LINK'=>placeholder_url(),
					'POSTING_URL'=>placeholder_url(),
					'OPTIONS_URL'=>placeholder_url(),
					'SUBMIT_VALUE'=>lorem_word(),
					'PASSWORD_HASH'=>placeholder_random(),
					'INTRODUCTION'=>'',
					'TITLE'=>lorem_title(),
					'CONTENT'=>lorem_phrase(),
					'LINKS'=>placeholder_array(),
					'TEXT_COLOUR_DEFAULT'=>lorem_word(),
					'FONT_NAME_DEFAULT'=>lorem_word(),
					'COMCODE_HELP'=>lorem_phrase(),
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_set_effects_screen()
	{
		require_javascript('javascript_validation');
		require_javascript('javascript_swfupload');

		$setting_blocks = new ocp_tempcode();
		foreach (placeholder_array() as $member=>$values)
		{
			$effects = array();
			foreach (placeholder_array() as $k=>$v)
			{
				$effects[] = array('KEY'=>strval($k),'VALUE'=>$v,'MEMBER_ID'=>"$member",'USERNAME'=>lorem_phrase(),'EFFECT_TITLE'=>lorem_word(),'EFFECT_SHORT'=>lorem_word_2(),'EFFECT'=>lorem_word());
			}
			$block = do_lorem_template('CHAT_SET_EFFECTS_SETTING_BLOCK',array('HAS_SOME'=>false,'EFFECTS'=>$effects,'LIBRARY'=>placeholder_array()));
			$setting_blocks->attach($block);
		}

		return array(
			lorem_globalise(
				do_lorem_template('CHAT_SET_EFFECTS_SCREEN',array(
					'TITLE'=>lorem_title(),
					'SUBMIT_NAME'=>lorem_word(),
					'HIDDEN'=>'',
					'POST_URL'=>placeholder_url(),
					'SETTING_BLOCKS'=>$setting_blocks,
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_staff_actions()
	{
		return array(
			lorem_globalise(
				do_lorem_template('CHAT_STAFF_ACTIONS',array(
									'CHAT_BAN_URL'=>placeholder_url(),
									'CHAT_UNBAN_URL'=>placeholder_url(),
									'EDIT_URL'=>placeholder_url(),
									'BAN_URL'=>placeholder_url(),
							)
				),NULL,'',true),
			);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__chat_sitewide_im_popup()
	{
		$im_area_template=do_lorem_template('CHAT_LOBBY_IM_AREA',array(
				'MESSAGES_PHP'=>find_script('messages'),
				'ROOM_ID'=>placeholder_id()
		));
		return array(
			lorem_globalise(
				do_lorem_template('CHAT_SITEWIDE_IM_POPUP',array('CONTENT'=>$im_area_template)
			),NULL,'',true),
		);
	}
}
