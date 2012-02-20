{+START,IF_PASSED,NOTIFICATIONS_TYPE}
	{$SET,NOTIFICATIONS_TYPE,{NOTIFICATIONS_TYPE}}
{+END}

{+START,IF_NON_PASSED,NOTIFICATIONS_TYPE}
	{$SET,NOTIFICATIONS_TYPE,{$PAGE}}
{+END}

{+START,IF,{$NOT,{$IS_GUEST}}}
	{+START,IF,{$NOT,{$NOTIFICATIONS_ENABLED,{NOTIFICATIONS_ID},{$GET,NOTIFICATIONS_TYPE}}}}
		<a rel="enable-notifications" href="{$PAGE_LINK*,{NOTIFICATIONS_PAGELINK}:redirect={$SELF_URL*&,1}}"><img class="button_page page_icon" src="{$IMG*,page/enable_notifications}" title="" alt="{!ENABLE_NOTIFICATIONS}" /></a>
	{+END}

	{+START,IF,{$NOTIFICATIONS_ENABLED,{NOTIFICATIONS_ID},{$GET,NOTIFICATIONS_TYPE}}}
		<a rel="disable-notifications" href="{$PAGE_LINK*,{NOTIFICATIONS_PAGELINK}:redirect={$SELF_URL*&,1}}"><img class="button_page page_icon" src="{$IMG*,page/disable_notifications}" title="" alt="{!DISABLE_NOTIFICATIONS}" /></a>
	{+END}
{+END}
