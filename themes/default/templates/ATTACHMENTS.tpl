{+START,IF,{$ADDON_INSTALLED,filedump}}{+START,IF,{$HAS_ACTUAL_PAGE_ACCESS,filedump}}
	<p>
		{!ADD_ATTACHMENTS_MEDIA_LIBRARY,{POSTING_FIELD_NAME*;}}
	</p>
{+END}{+END}

{$SET,IMAGE_TYPES,{IMAGE_TYPES}}

<script type="text/javascript">// <![CDATA[
	var attachment_template='{ATTACHMENT_TEMPLATE;^/}';
//]]></script>

<div id="attachment_store">
	{ATTACHMENTS}
</div>

{+START,IF,{$JS_ON}}
	<script type="text/javascript">// <![CDATA[
		var max_attachments={MAX_ATTACHMENTS#};
		var num_attachments={NUM_ATTACHMENTS#};
	//]]></script>
{+END}

<p>
	{!comcode:DELETE_ATTACHMENTS,<a rel="nofollow" target="_blank" href="{$FIND_SCRIPT*,attachment_popup}?field_name={POSTING_FIELD_NAME*}{$KEEP*;,0,1}" onclick="window.faux_open(maintain_theme_in_link(this.href)\,'site_attachment_chooser'\,'width=550\,height=600\,status=no\,resizable=yes\,scrollbars=yes'); return false;">{!comcode:ATTACHMENT_POPUP}</a>}
</p>
