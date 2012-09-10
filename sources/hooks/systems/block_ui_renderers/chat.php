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

class Hook_block_ui_renderers_chat
{

	/**
	 * See if a particular block parameter's UI input can be rendered by this.
	 *
	 * @param  ID_TEXT		The block
	 * @param  ID_TEXT		The parameter of the block
	 * @param  boolean		Whether there is a default value for the field, due to this being an edit
	 * @param  string			Default value for field
	 * @param  tempcode		Field description
	 * @return ?tempcode		Rendered field (NULL: not handled).
	 */
	function render_block_ui($block,$parameter,$has_default,$default,$description)
	{
		if ($block.':'.$parameter=='side_shoutbox:param') // special case for chat rooms
		{
			$list=new ocp_tempcode();
			$rows=$GLOBALS['SITE_DB']->query_select('chat_rooms',array('id','room_name'),array('is_im'=>0),'',100/*In case insane number*/);
			foreach ($rows as $row)
			{
				$list->attach(form_input_list_entry(strval($row['id']),$has_default && strval($row['id'])==$default,$row['room_name']));
			}
			return form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false);
		}
		return NULL;
	}

}
