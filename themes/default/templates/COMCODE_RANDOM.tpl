{$SET,RAND_ID_RANDOM,rand{$RAND}}

<div id="comcoderandom{$GET%,RAND_ID_RANDOM}">&nbsp;</div>
<script type="text/javascript">// <![CDATA[
	var parts={};
	{PARTS}

	var use='',rand=window.parseInt(Math.random()*{MAX%});
	for (var i in parts)
	{
		use=parts[i];
		if (i>rand) break;
	}
	var comcoderandom=document.getElementById('comcoderandom{$GET%,RAND_ID_RANDOM}');
	setInnerHTML(comcoderandom,use);
//]]></script>
<noscript>
	{FULL*}
</noscript>

