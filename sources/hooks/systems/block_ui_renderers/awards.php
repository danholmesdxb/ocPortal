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
 * @package		awards
 */

class Hook_block_ui_renderers_awards
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
		if ($block.':'.$parameter=='main_awards:param') // special case for awards
		{
			$list=new ocp_tempcode();
			$rows=$GLOBALS['SITE_DB']->query_select('award_types',array('id','a_title'));
			foreach ($rows as $row)
			{
				$list->attach(form_input_list_entry(strval($row['id']),$has_default && strval($row['id'])==$default,get_translated_text($row['a_title'])));
			}
			return form_input_list(titleify($parameter),escape_html($description),$parameter,$list,NULL,false,false);
		}
		return NULL;
	}

}
