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

class Hook_addon_registry_awards
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
		return 'Pick out content for featuring.';
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
			'previously_in_addon'=>array(
				'awards'
			)
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
			'sources/hooks/systems/addon_registry/awards.php',
			'AWARDED_CONTENT.tpl',
			'BLOCK_MAIN_AWARDS.tpl',
			'adminzone/pages/modules/admin_awards.php',
			'sources/blocks/main_awards.php',
			'sources/awards.php',
			'sources/awards2.php',
			'site/pages/modules/awards.php',
			'lang/EN/awards.ini',
			'themes/default/images/pagepics/awards.png',
			'themes/default/images/bigicons/awards.png',
			'sources/hooks/blocks/main_staff_checklist/awards.php',
			'awards.css',
			'themes/default/images/awarded.png',
			'sources/hooks/modules/admin_import_types/awards.php',
			'sources/hooks/systems/block_ui_renderers/awards.php',
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
			'BLOCK_MAIN_AWARDS.tpl'=>'block_main_awards',
			'AWARDED_CONTENT.tpl'=>'awarded_content'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__block_main_awards()
	{
		return array(
			lorem_globalise(do_lorem_template('BLOCK_MAIN_AWARDS', array(
				'TITLE'=>lorem_word(),
				'TYPE'=>lorem_word(),
				'DESCRIPTION'=>lorem_paragraph_html(),
				'AWARDEE_PROFILE_URL'=>placeholder_url(),
				'AWARDEE'=>lorem_phrase(),
				'AWARDEE_USERNAME'=>lorem_word(),
				'RAW_AWARD_DATE'=>placeholder_time(),
				'AWARD_DATE'=>placeholder_time(),
				'CONTENT'=>lorem_phrase_html(),
				'SUBMIT_URL'=>placeholder_url(),
				'ARCHIVE_URL'=>placeholder_url()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__awarded_content()
	{
		return array(
			lorem_globalise(do_lorem_template('AWARDED_CONTENT', array(
				'AWARDEE_PROFILE_URL'=>placeholder_url(),
				'AWARDEE'=>lorem_phrase(),
				'AWARDEE_USERNAME'=>lorem_word(),
				'RAW_AWARD_DATE'=>placeholder_time(),
				'AWARD_DATE'=>placeholder_time(),
				'CONTENT'=>lorem_phrase()
			)), NULL, '', true)
		);
	}
}
