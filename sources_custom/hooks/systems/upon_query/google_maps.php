<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.

*/

class upon_query_google_maps
{

	function run($ob,$query,$max,$start,$fail_ok,$get_insert_id,$ret)
	{
		if (preg_match($query,'^DELETE FROM '.get_table_prefix().'cache WHERE .*main_cc_embed')!=0) // If main_cc_embed being decached
		{
			decache('main_google_map'); // decache map block too
		}
	}
}
