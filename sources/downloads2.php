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
 * @package		downloads
 */

/*EXTRA FUNCTIONS: shell_exec*/

/**
 * Standard code module initialisation function.
 */
function init__downloads2()
{
	global $PT_PAIR_CACHE;
	$PT_PAIR_CACHE=array();
}

/**
 * Farm out the files for downloads.
 */
function dload_script()
{
	// Closed site
	$site_closed=get_option('site_closed');
	if (($site_closed=='1') && (!has_specific_permission(get_member(),'access_closed_site')) && (!$GLOBALS['IS_ACTUALLY_ADMIN']))
	{
		header('Content-Type: text/plain');
		@exit(get_option('closed'));
	}

	global $SITE_INFO;
	if ((!is_guest()) || (!isset($SITE_INFO['any_guest_cached_too'])) || ($SITE_INFO['any_guest_cached_too']=='0'))
	{
		if ((get_param('for_session','-1')!=md5(strval(get_session_id()))) && (get_option('anti_leech')=='1') && (ocp_srv('HTTP_REFERER')!=''))
			warn_exit(do_lang_tempcode('LEECH_BLOCK'));
	}

	require_lang('downloads');

	$id=get_param_integer('id',0);

	// Lookup
	$rows=$GLOBALS['SITE_DB']->query_select('download_downloads',array('*'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$rows[0];

	// Permission
	if (!has_category_access(get_member(),'downloads',strval($myrow['category_id'])))
		access_denied('CATEGORY_ACCESS');

	// Cost?
	$got_before=$GLOBALS['SITE_DB']->query_value_null_ok('download_logging','the_user',array('the_user'=>get_member(),'id'=>$id));
	if (addon_installed('points'))
	{
		if ($myrow['download_cost']>0)
		{
			require_code('points2');

			$member=get_member();
			if (is_guest($member))
				access_denied('NOT_AS_GUEST');

			// Check they haven't downloaded this before (they only get charged once - maybe they are resuming)
			if (is_null($got_before))
			{
				$cost=$myrow['download_cost'];

				$member=get_member();
				if (is_guest($member))
					access_denied('NOT_AS_GUEST');

				$dif=$cost-available_points($member);
				if (($dif>0) && (!has_specific_permission(get_member(),'have_negative_gift_points')))
					warn_exit(do_lang_tempcode('LACKING_POINTS',integer_format($dif)));
				require_code('points2');
				charge_member($member,$cost,do_lang('DOWNLOADED_THIS',get_translated_text($myrow['name'])));

				if ($myrow['download_submitter_gets_points']==1)
				{
					system_gift_transfer(do_lang('THEY_DOWNLOADED_THIS',get_translated_text($myrow['name'])),$cost,$myrow['submitter']);
				}
			}
		}
	}

	// Filename
	$full=$myrow['url'];
	$breakdown=@pathinfo($full) OR warn_exit(do_lang_tempcode('HTTP_DOWNLOAD_NO_SERVER',$full));
//	$filename=$breakdown['basename'];
	if (!array_key_exists('extension',$breakdown)) $extension=''; else $extension=strtolower($breakdown['extension']);
	if (url_is_local($full)) $_full=get_custom_file_base().'/'.rawurldecode(/*filter_naughty*/($full)); else $_full=rawurldecode($full);

	// Is it non-local? If so, redirect
	if ((!url_is_local($full)) || (!file_exists(get_file_base().'/'.rawurldecode(filter_naughty($full)))))
	{
		if (url_is_local($full)) $full=get_custom_base_url().'/'.$full;
		if ((strpos($full,chr(10))!==false) || (strpos($full,chr(13))!==false))
			log_hack_attack_and_exit('HEADER_SPLIT_HACK');
		header('Location: '.$full);
		log_download($id,0,!is_null($got_before)); // Bandwidth used is 0 for an external download
		return;
	}

	// Some basic security: don't fopen php files
	if ($extension=='php') log_hack_attack_and_exit('PHP_DOWNLOAD_INNOCENT',integer_format($id));

	// Size, bandwidth, logging
	$size=filesize($_full);
	if (is_null($got_before))
	{
		$bandwidth=$GLOBALS['SITE_DB']->query_value_null_ok_full('SELECT SUM(file_size) AS answer FROM '.get_table_prefix().'download_logging l LEFT JOIN '.get_table_prefix().'download_downloads d ON l.id=d.id WHERE date_and_time>'.strval(time()-24*60*60*32));
		if ((($bandwidth+floatval($size))>(floatval(get_option('maximum_download'))*1024*1024*1024)) && (!has_specific_permission(get_member(),'bypass_bandwidth_restriction')))
			warn_exit(do_lang_tempcode('TOO_MUCH_DOWNLOAD'));

		require_code('files2');
		check_shared_bandwidth_usage($size);
	}
	log_download($id,$size,!is_null($got_before));

	// Send header
	if ((strpos($myrow['original_filename'],chr(10))!==false) || (strpos($myrow['original_filename'],chr(13))!==false))
		log_hack_attack_and_exit('HEADER_SPLIT_HACK');
	header('Content-Type: application/octet-stream'.'; authoritative=true;');
	if (get_option('immediate_downloads')=='1')
	{
		require_code('mime_types');
		header('Content-Type: '.get_mime_type(get_file_extension($myrow['original_filename'])).'; authoritative=true;');
		header('Content-Disposition: filename="'.str_replace(chr(13),'',str_replace(chr(10),'',addslashes($myrow['original_filename']))).'"');
	} else
	{
		if (strstr(ocp_srv('HTTP_USER_AGENT'),'MSIE')!==false)
			header('Content-Disposition: filename="'.str_replace(chr(13),'',str_replace(chr(10),'',addslashes($myrow['original_filename']))).'"');
		else
			header('Content-Disposition: attachment; filename="'.str_replace(chr(13),'',str_replace(chr(10),'',addslashes($myrow['original_filename']))).'"');
	}
	header('Accept-Ranges: bytes');

	// Caching
	header("Pragma: private");
	header("Cache-Control: private");
	header('Expires: '.gmdate('D, d M Y H:i:s',time()+60*60*24*365).' GMT');
	$time=is_null($myrow['edit_date'])?$myrow['add_date']:$myrow['edit_date'];
	$time=max($time,filemtime($_full));
	header('Last-Modified: '.gmdate('D, d M Y H:i:s',$time).' GMT');

	// Default to no resume
	$from=0;
	$new_length=$size;

	@ini_set('zlib.output_compression','Off');

	// They're trying to resume (so update our range)
	$httprange=ocp_srv('HTTP_RANGE');
	if (strlen($httprange)>0)
	{
		$_range=explode('=',ocp_srv('HTTP_RANGE'));
		if (count($_range)==2)
		{
			if (strpos($_range[0],'-')===false) $_range=array_reverse($_range);
			$range=$_range[0];
			if (substr($range,0,1)=='-') $range=strval($size-intval(substr($range,1))-1).$range;
			if (substr($range,-1,1)=='-') $range.=strval($size-1);
			$bits=explode('-',$range);
			if (count($bits)==2)
			{
				list($from,$to)=array_map('intval',$bits);
				if (($to-$from!=0) || ($from==0)) // Workaround to weird behaviour on Chrome
				{
					$new_length=$to-$from+1;

					header('HTTP/1.1 206 Partial Content');
					header('Content-Range: bytes '.$range.'/'.strval($size));
				} else
				{
					$from=0;
				}
			}
		}
	}
	header('Content-Length: '.strval($new_length));
	if (function_exists('set_time_limit')) @set_time_limit(0);
	error_reporting(0);

	// Send actual data
	$myfile=fopen($_full,'rb');
	fseek($myfile,$from);
	/*if ($size==$new_length)		Uses a lot of memory :S
	{
		fpassthru($myfile);
	} else*/
	{
		$i=0;
		flush(); // Works around weird PHP bug that sends data before headers, on some PHP versions
		while ($i<$new_length)
		{
			$content=fread($myfile,min($new_length-$i,1048576));
			echo $content;
			$len=strlen($content);
			if ($len==0) break;
			$i+=$len;
		}
		fclose($myfile);
	}

	/*

	Security note... at the download adding/editing stage, we ensured that
	only files accessible to the web server (in raw form) could end up in
	our database.
	Therefore we did not check here that our file was accessible in raw
	form.

	*/
}

/**
 * Add a download category
 *
 * @param  SHORT_TEXT	The name of the download category
 * @param  AUTO_LINK		The parent download category
 * @param  LONG_TEXT		A description
 * @param  LONG_TEXT		Hidden notes pertaining to this download category
 * @param  URLPATH		The representative image for the category (blank: none)
 * @param  ?AUTO_LINK	Force an ID (NULL: don't force an ID)
 * @return AUTO_LINK		The ID of the newly added download category
 */
function add_download_category($category,$parent_id,$description,$notes,$rep_image='',$id=NULL)
{
	$map=array('rep_image'=>$rep_image,'add_date'=>time(),'notes'=>$notes,'category'=>insert_lang($category,2),'parent_id'=>$parent_id,'description'=>insert_lang_comcode($description,2));
	if (!is_null($id)) $map['id']=$id;
	$id=$GLOBALS['SITE_DB']->query_insert('download_categories',$map,true);

	log_it('ADD_DOWNLOAD_CATEGORY',strval($id),$category);

	require_code('seo2');
	seo_meta_set_for_implicit('downloads_category',strval($id),array($category,$description),$description);

	decache('main_download_category');

	if (!is_null($parent_id))
	{
		require_code('notifications2');
		copy_notifications_to_new_child('download',strval($parent_id),strval($id));
	}

	return $id;
}

/**
 * Edit the given download category with the new details given
 *
 * @param  SHORT_TEXT	The name of the download category
 * @param  AUTO_LINK		The parent download category
 * @param  LONG_TEXT		A description
 * @param  AUTO_LINK		The ID of the category being edited
 * @param  LONG_TEXT		Hidden notes pertaining to this download category
 * @param  URLPATH		The representative image for the category (blank: none)
 * @param  ?SHORT_TEXT	Meta keywords for this resource (NULL: do not edit)
 * @param  ?LONG_TEXT	Meta description for this resource (NULL: do not edit)
 */
function edit_download_category($category,$parent_id,$description,$category_id,$notes,$rep_image,$meta_keywords,$meta_description)
{
	$under_category_id=$parent_id;
	while ((!is_null($under_category_id)) && ($under_category_id!=INTEGER_MAGIC_NULL))
	{
		if ($category_id==$under_category_id) warn_exit(do_lang_tempcode('OWN_PARENT_ERROR'));
		$under_category_id=$GLOBALS['SITE_DB']->query_value('download_categories','parent_id',array('id'=>$under_category_id));
	}

	require_code('urls2');
	suggest_new_idmoniker_for('downloads','misc',strval($category_id),$category);

	$rows=$GLOBALS['SITE_DB']->query_select('download_categories',array('category','description'),array('id'=>$category_id),'',1);
	if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$_category=$rows[0]['category'];
	$_description=$rows[0]['description'];

	$map=array('notes'=>$notes,'category'=>lang_remap($_category,$category),'parent_id'=>$parent_id,'description'=>lang_remap_comcode($_description,$description));
	if (!is_null($rep_image))
	{
		$map['rep_image']=$rep_image;
		require_code('files2');
		delete_upload('uploads/grepimages','download_categories','rep_image','id',$category_id,$rep_image);
	}
	$GLOBALS['SITE_DB']->query_update('download_categories',$map,array('id'=>$category_id),'',1);

	log_it('EDIT_DOWNLOAD_CATEGORY',strval($category_id),$category);

	require_code('seo2');
	seo_meta_set_for_explicit('downloads_category',strval($category_id),$meta_keywords,$meta_description);

	decache('main_download_category');
}

/**
 * Delete a download category.
 *
 * @param  AUTO_LINK		The download category to delete
 */
function delete_download_category($category_id)
{
	$root_category=$GLOBALS['SITE_DB']->query_value('download_categories','MIN(id)');
	if ($category_id==$root_category) warn_exit(do_lang_tempcode('NO_DELETE_ROOT'));

	$rows=$GLOBALS['SITE_DB']->query_select('download_categories',array('category','description','parent_id'),array('id'=>$category_id),'',1);
	if (!array_key_exists(0,$rows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$category=$rows[0]['category'];
	$description=$rows[0]['description'];

	require_code('files2');
	delete_upload('uploads/grepimages','download_categories','rep_image','id',$category_id);

	log_it('DELETE_DOWNLOAD_CATEGORY',strval($category_id),get_translated_text($category));

	$GLOBALS['SITE_DB']->query_delete('download_categories',array('id'=>$category_id),'',1);
	$GLOBALS['SITE_DB']->query_update('download_downloads',array('category_id'=>$rows[0]['parent_id']),array('category_id'=>$category_id));
	$GLOBALS['SITE_DB']->query_update('download_categories',array('parent_id'=>$rows[0]['parent_id']),array('parent_id'=>$category_id));

	delete_lang($category);
	delete_lang($description);

	require_code('seo2');
	seo_meta_erase_storage('downloads_category',strval($category_id));

	$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>'downloads','category_name'=>strval($category_id)));
	$GLOBALS['SITE_DB']->query_delete('gsp',array('module_the_name'=>'downloads','category_name'=>strval($category_id)));

	decache('main_download_category');
}

/**
 * Create a data-mash from the file at a URL. This is data useful for the search engine.
 *
 * @param  URLPATH			The URL to make a data-mash of, or a filename if $data isn't blank
 * @param  ?string			Data (NULL: use URL)
 * @param  ?ID_TEXT			File extension (NULL: get from URL)
 * @param  boolean			Whether a direct file path was given instead of a URL
 * @return LONG_TEXT			The data-mash
 */
function create_data_mash($url,$data=NULL,$extension=NULL,$direct_path=false)
{
	if (function_exists('set_time_limit')) @set_time_limit(300);

	if (get_value('no_dload_search_index')==='1') return '';

	if (running_script('stress_test_loader')) return '';

	if ((function_exists('memory_get_usage')) && (ini_get('memory_usage')=='8M'))
		return ''; // Some cowardice... don't want to tempt fate

	if (is_null($extension))
		$extension=get_file_extension($url);

	$tmp_file=NULL;

	if (is_null($data))
	{
		if (($direct_path) || (url_is_local($url)))
		{
			$actual_path=$direct_path?$url:get_custom_file_base().'/'.rawurldecode($url);

			if (file_exists($actual_path))
			{
				switch ($extension)
				{
					case 'zip':
					case 'odt':
					case 'odp':
					case 'docx':
					case 'tar':
					case 'gz':
						if (filesize($actual_path)>1024*1024*3) return '';
						break;
				}

				$tmp_file=$actual_path;
				if (filesize($actual_path)>1024*1024*3)
				{
					$myfile=fopen($actual_path,'rb');
					$data='';
					for ($i=0;$i<384;$i++)
						$data.=fread($myfile,8192);
					fclose($myfile);
				} else
				{
					$data=file_get_contents($actual_path);
				}
			} else
			{
				$data='';
			}
		} else
		{
			switch ($extension)
			{
				case 'txt':
				case '1st':
				case 'rtf':
				case 'pdf':
				case 'htm':
				case 'html':
				case 'xml':
				case 'doc':
				case 'xls':
					break; // Continue through to download good stuff

				default:
					return ''; // Don't download, it's not worth it
					break;
			}

			$data=http_download_file($url,3*1024*1024,false); // 3MB is enough
			if (is_null($data)) return '';
		}
	}

	$mash='';

	switch ($extension)
	{
		case 'zip':
		case 'odt':
		case 'odp':
		case 'docx':
			require_code('m_zip');
			$tmp_file=ocp_tempnam('dcdm_');
			$myfile2=fopen($tmp_file,'wb');
			fwrite($myfile2,$data);
			fclose($myfile2);
			$myfile_zip=@zip_open($tmp_file);
			if (!is_integer($myfile_zip))
			{
				while (($entry=(@zip_read($myfile_zip)))!==false) // Temporary file may be cleaned up before this can complete, hence @
				{
					$entry_name=@zip_entry_name($entry);
					$mash.=' '.$entry_name;
					if (substr($entry_name,-1)!='/')
					{
						$_entry=@zip_entry_open($myfile_zip,$entry);
						if ($_entry!==false)
						{
							$file_data='';
							while (true)
							{
								$it=@zip_entry_read($entry,1024);
								if (($it===false) || ($it=='')) break;
								$file_data.=$it;
								if (strlen($file_data)>=3*1024*1024) break; // 3MB is enough
							}
							@zip_entry_close($entry);
							$mash.=' '.create_data_mash($entry_name,$file_data);
							if (strlen($mash)>=3*1024*1024) break; // 3MB is enough
						}
					}
				}
				@zip_close($myfile_zip);
			}
			@unlink($tmp_file);
			break;
		case 'tar':
			require_code('tar');
			$tmp_file=ocp_tempnam('dcdm_');
			$myfile=fopen($tmp_file,'wb');
			fwrite($myfile,$data);
			fclose($myfile);
			$myfile_tar=tar_open($tmp_file,'rb');
			if ($myfile_tar!==false)
			{
				$directory=tar_get_directory($myfile_tar);
				foreach ($directory as $entry)
				{
					$entry_name=$entry['path'];
					$mash.=' '.$entry_name;
					if ($entry['size']>=3*1024*1024) continue; // 3MB is enough
					$_entrya=tar_get_file($myfile_tar,$entry['path']);
					if (!is_null($_entrya))
					{
						$mash.=' '.create_data_mash($entry_name,$_entrya['data']);
						if (strlen($mash)>=3*1024*1024) break; // 3MB is enough
					}
				}
				tar_close($myfile_tar);
			}
			@unlink($tmp_file);
			break;
		case 'gz':
			if (function_exists('gzopen'))
			{
				if (function_exists('gzeof'))
				{
					if (function_exists('gzread'))
					{
						$tmp_file=ocp_tempnam('dcdm_');
						$myfile=fopen($tmp_file,'wb');
						fwrite($myfile,$data);
						fclose($myfile);
						$myfile=gzopen($tmp_file,'rb');
						if ($myfile!==false)
						{
							$file_data='';
							while (!gzeof($myfile))
							{
								$it=gzread($myfile,1024);
								$file_data.=$it;
								if (strlen($file_data)>=3*1024*1024) break; // 3MB is enough
							}
							$mash=' '.create_data_mash(preg_replace('#\.gz#i','',$url),$file_data);
						}
						@unlink($tmp_file);
					}
				}
			}
			break;
		case 'txt':
		case '1st':
			$mash.=$data;
			break;
		case 'rtf':
			$len=strlen($data);
			$skipping_section_depth=0;
			$escape=false;
			for ($i=0;$i<$len;$i++)
			{
				$byte=$data[$i];
				if ((!$escape) && ($byte=="\\"))
				{
					$escape=true;
				}
				elseif ((!$escape) && ($byte=='{'))
				{
					if ($skipping_section_depth!=0) $skipping_section_depth++;
				}
				elseif ((!$escape) && ($byte=='}'))
				{
					if ($skipping_section_depth!=0) $skipping_section_depth--;
				}
				elseif (($escape) && ($byte!='{') && ($byte!="\\") && ($byte!='}'))
				{
					$end_pos_1=strpos($data,"\\",$i+1);
					if ($end_pos_1===false) $end_pos_1=$len;
					$end_pos_2=strpos($data,chr(10),$i+1);
					if ($end_pos_2===false) $end_pos_2=$len;
					$end_pos_3=strpos($data,' ',$i+1);
					if ($end_pos_3===false) $end_pos_3=$len;
					$end_pos_4=strpos($data,"\t",$i+1);
					if ($end_pos_4===false) $end_pos_4=$len;
					$end_pos_5=strpos($data,'{',$i+1);
					if ($end_pos_5===false) $end_pos_5=$len;
					$end_pos_6=strpos($data,'}',$i+1);
					if ($end_pos_6===false) $end_pos_6=$len;
					$end_pos=min($end_pos_1,$end_pos_2,$end_pos_3,$end_pos_4,$end_pos_5,$end_pos_6);
					$tag=substr($data,$i,$end_pos-$i);
					$tag=preg_replace('#[\-0-9]*#','',$tag);
					if (($skipping_section_depth==0) && (($tag=='pgdsc') || ($tag=='comment') || ($tag=='object') || ($tag=='pict') || ($tag=='stylesheet') || ($tag=='fonttbl')))
					{
						$skipping_section_depth=1;
					}
					if ($tag=='par') $mash.=chr(10);
					$i=$end_pos-1;
					$escape=false;
				}
				elseif ($skipping_section_depth==0)
				{
					if (($byte!=chr(13)) && ($byte!=chr(10))) $mash.=$byte;
					$escape=false;
				} else $escape=false;
			}
			break;
		case 'pdf':
			if ((ini_get('safe_mode')!='1') && (strpos(@ini_get('disable_functions'),'shell_exec')===false) && (!is_null($tmp_file)))
			{
				$enc=(get_charset()=='utf-8')?' -enc UTF-8':'';
				$path='pdftohtml -i -noframes -stdout -hidden'.$enc.' -q -xml '.@escapeshellarg($tmp_file);
				if (strpos(strtolower(PHP_OS),'win')!==false)
					if (file_exists(get_file_base().'/data_custom/pdftohtml.exe')) $path='"'.get_file_base().DIRECTORY_SEPARATOR.'data_custom'.DIRECTORY_SEPARATOR.'"'.$path;
				$tmp_file_2=ocp_tempnam('pdfxml_');
				@shell_exec($path.' > '.$tmp_file_2);
				$mash=create_data_mash($tmp_file_2,NULL,'xml',true);
				@unlink($tmp_file_2);
			}
			break;
		case 'htm':
		case 'html':
			$head_patterns=array('#<\s*script.*<\s*/\s*script\s*>#misU','#<\s*link[^<>]*>#misU','#<\s*style.*<\s*/\s*style\s*>#misU');
			foreach ($head_patterns as $pattern)
			{
				$data=preg_replace($pattern,'',$data);
			}
		case 'xml':
			$mash=str_replace('&apos;','\'',str_replace(' false ',' ',str_replace(' true ',' ',@html_entity_decode(preg_replace('#\<[^\<\>]*\>#',' ',$data),ENT_QUOTES,get_charset()))));
			$mash=preg_replace('#Error : Bad \w+#','',$mash);
			break;
		case 'xls':
		case 'doc':
		case 'ppt':
		case 'hlp':
//		default: // Binary formats are complex to parse, but whatsmore, as textual tagging isn't used, extraction can be done automatically as all identified text is good.
			// Strip out interleaved nulls because they are used in wide-chars, obscuring the data
			$sstring_regexp='[a-zA-Z0-9\'\-\x91\x92\x93\x94]';
			$data=preg_replace('#('.$sstring_regexp.')\x00('.$sstring_regexp.')\x00#','${1}${2}',$data);

			// Now try and extract strings
			$matches=array();
			$count=preg_match_all('#([a-zA-Z0-9\'\-\x91\x92\x93\x94\s]+)#',$data,$matches);
			$min_length=10;
			if ($extension=='xls') $min_length=4;
			for ($i=0;$i<$count;$i++)
			{
				$x=$matches[1][$i];
				if ((strlen($x)>$min_length) && ($x!=strtoupper($x)) && ($x!='Microsoft Word Document') && ($x!='WordDocument') && ($x!='SummaryInformation') && ($x!='DocumentSummaryInformation'))
					$mash.=' '.$matches[1][$i];
			}
			break;
	}

	if (strlen($mash)>1024*1024*3) $mash=substr($mash,0,1024*1024*3);
	$mash=preg_replace('# +#',' ',preg_replace('#[^\w\d-\-\']#',' ',$mash));
	if (strlen($mash)>intval(1024*1024*1*0.4)) $mash=substr($mash,0,intval(1024*1024*0.4));

	return $mash;
}

/**
 * Add a download.
 *
 * @param  AUTO_LINK			The ID of the category the download is to be in
 * @param  SHORT_TEXT		The name of the download
 * @param  URLPATH			The URL to the download
 * @param  LONG_TEXT			The description of the download
 * @param  ID_TEXT			The author of the download (not necessarily same as the submitter)
 * @param  LONG_TEXT			The comments for the download
 * @param  ?AUTO_LINK		The out-mode-id (the ID of a download that this download is an old version of). Often people wonder why this is specified with the old version, and not the opposite with the new version - it is because statistically, we perceive more chance of downloads merging than splitting (NULL: none)
 * @param  BINARY				Whether the download has been validated
 * @param  BINARY				Whether the download may be rated
 * @param  SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY				Whether the download may be trackbacked
 * @param  LONG_TEXT			Hidden notes pertaining to the download
 * @param  SHORT_TEXT		The downloads original filename (the URL may be obfuscated)
 * @param  integer			The file size of the download (we can't really detect this in real-time for remote URLs)
 * @param  integer			The cost of the download that members will have to pay to get it
 * @param  BINARY				Whether the submitter gets the points for the download (they are selling it) (otherwise they are just thrown out, which is an alternative model - one of enforcing community point building)
 * @param  ?AUTO_LINK		The licence to use (NULL: none)
 * @param  ?TIME				The add date for the download (NULL: now)
 * @param  integer			The number of downloads that this download has had
 * @param  integer			The number of views that this download has had
 * @param  ?MEMBER			The submitter (NULL: current user)
 * @param  ?TIME				The edit date (NULL: never)
 * @param  ?AUTO_LINK		Force an ID (NULL: don't force an ID)
 * @return AUTO_LINK			The ID of the newly added download
 */
function add_download($category_id,$name,$url,$description,$author,$comments,$out_mode_id,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$original_filename,$file_size,$cost,$submitter_gets_points,$licence=NULL,$add_date=NULL,$num_downloads=0,$views=0,$submitter=NULL,$edit_date=NULL,$id=NULL)
{
	if (is_null($add_date)) $add_date=time();
	if (is_null($submitter)) $submitter=get_member();
	if (($file_size==0) || (url_is_local($url)))
	{
		if (url_is_local($url))
		{
			$file_size=@filesize(get_custom_file_base().'/'.rawurldecode($url)) OR $file_size=NULL;
		} else
		{
			$file_size=@filesize($url) OR $file_size=NULL;
		}
	}
	$met=@ini_get('max_execution_time');
	$data_mash=($url=='')?'':create_data_mash($url,NULL,get_file_extension($original_filename));
	if (function_exists('set_time_limit')) @set_time_limit($met);
	if (!addon_installed('unvalidated')) $validated=1;
	$map=array('download_data_mash'=>$data_mash,'download_licence'=>$licence,'rep_image'=>'','edit_date'=>$edit_date,'download_submitter_gets_points'=>$submitter_gets_points,'download_cost'=>$cost,'original_filename'=>$original_filename,'download_views'=>$views,'allow_rating'=>$allow_rating,'allow_comments'=>$allow_comments,'allow_trackbacks'=>$allow_trackbacks,'notes'=>$notes,'submitter'=>$submitter,'default_pic'=>1,'num_downloads'=>$num_downloads,'out_mode_id'=>$out_mode_id,'category_id'=>$category_id,'name'=>insert_lang($name,2),'url'=>$url,'description'=>insert_lang_comcode($description,3),'author'=>$author,'comments'=>insert_lang_comcode($comments,3),'validated'=>$validated,'add_date'=>$add_date,'file_size'=>$file_size);
	if (!is_null($id)) $map['id']=$id;
	$id=$GLOBALS['SITE_DB']->query_insert('download_downloads',$map,true);

	require_code('seo2');
	seo_meta_set_for_implicit('downloads_download',strval($id),array($name,$description,$comments),$description);

	// Make its gallery
	if ((addon_installed('galleries')) && (!running_script('stress_test_loader')))
	{
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('galleries','name',array('name'=>'download_'.strval($id)));
		if (is_null($test))
		{
			require_code('galleries2');
			$download_gallery_root=get_option('download_gallery_root');
			if (is_null($download_gallery_root)) $download_gallery_root='root';
			add_gallery('download_'.strval($id),do_lang('GALLERY_FOR_DOWNLOAD',$name),'','','',$download_gallery_root);
			set_download_gallery_permissions($id,$submitter);
		}
	}

	// Stat
	update_stat('num_archive_downloads',1);
	if ($file_size>0) update_stat('archive_size',$file_size);

	if ($validated==1)
	{
		require_lang('downloads');
		require_code('notifications');
		$subject=do_lang('DOWNLOAD_NOTIFICATION_MAIL_SUBJECT',get_site_name(),$name);
		$self_url=build_url(array('page'=>'downloads','type'=>'entry','id'=>$id),get_module_zone('downloads'),NULL,false,false,true);
		$mail=do_lang('DOWNLOAD_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape($name),array(comcode_escape($self_url->evaluate())));
		dispatch_notification('download',strval($category_id),$subject,$mail);
	}

	log_it('ADD_DOWNLOAD',strval($id),$name);

	decache('main_recent_downloads');
	decache('main_top_downloads');
	decache('main_download_category');
	decache('main_download_tease');

	return $id;
}

/**
 * Set the permissions for a download gallery.
 *
 * @param  ?AUTO_LINK		The ID of the download (NULL: lookup from download)
 * @param  ?MEMBER			The submitter (NULL: work out automatically)
 */
function set_download_gallery_permissions($id,$submitter=NULL)
{
	if (is_null($submitter)) $submitter=$GLOBALS['SITE_DB']->query_value('download_downloads','submitter',array('id'=>$id));

	$download_gallery_root=get_option('download_gallery_root');
	if (is_null($download_gallery_root)) $download_gallery_root='root';

	// Copy through requisite permissions
	// TODO: This code will need updating in v10
	$GLOBALS['SITE_DB']->query_delete('group_category_access',array('module_the_name'=>'galleries','category_name'=>'download_'.strval($id)));
	$perms=$GLOBALS['SITE_DB']->query_select('group_category_access',array('*'),array('module_the_name'=>'galleries','category_name'=>$download_gallery_root));
	foreach ($perms as $perm)
	{
		$perm['category_name']='download_'.strval($id);
		$GLOBALS['SITE_DB']->query_insert('group_category_access',$perm);
	}
	$GLOBALS['SITE_DB']->query_delete('gsp',array('module_the_name'=>'galleries','category_name'=>'download_'.strval($id)));
	$perms=$GLOBALS['SITE_DB']->query_select('gsp',array('*'),array('module_the_name'=>'galleries','category_name'=>$download_gallery_root));
	foreach ($perms as $perm)
	{
		$perm['category_name']='download_'.strval($id);
		$GLOBALS['SITE_DB']->query_insert('gsp',$perm);
	}
	// If they were able to submit the download, they should be able to submit extra images
	$GLOBALS['SITE_DB']->query_delete('msp',array('module_the_name'=>'galleries','category_name'=>'download_'.strval($id)));
	foreach (array('submit_midrange_content') as $privilege)
	{
		$GLOBALS['SITE_DB']->query_insert('msp',array('active_until'=>2147483647/*FUDGEFUDGE*/,'member_id'=>$submitter,'specific_permission'=>$privilege,'the_page'=>'','module_the_name'=>'galleries','category_name'=>'download_'.strval($id),'the_value'=>'1'));
	}
}

/**
 * Edit a download.
 *
 * @param  AUTO_LINK			The ID of the download to edit
 * @param  AUTO_LINK			The ID of the category the download is to be in
 * @param  SHORT_TEXT		The name of the download
 * @param  URLPATH			The URL to the download
 * @param  LONG_TEXT			The description of the download
 * @param  ID_TEXT			The author of the download (not necessarily same as the submitter)
 * @param  LONG_TEXT			The comments for the download
 * @param  ?AUTO_LINK		The out-mode-id (the ID of a download that this download is an old version of). Often people wonder why this is specified with the old version, and not the opposite with the new version - it is because statistically, we perceive more chance of downloads merging than splitting (NULL: none)
 * @param  integer			The ordered number of the gallery image to use as the download representative image
 * @param  BINARY				Whether the download has been validated
 * @param  BINARY				Whether the download may be rated
 * @param  SHORT_INTEGER	Whether comments are allowed (0=no, 1=yes, 2=review style)
 * @param  BINARY				Whether the download may be trackbacked
 * @param  LONG_TEXT			Hidden notes pertaining to the download
 * @param  SHORT_TEXT		The downloads original filename (the URL may be obfuscated)
 * @param  integer			The file size of the download (we can't really detect this in real-time for remote URLs)
 * @param  integer			The cost of the download that members will have to pay to get it
 * @param  BINARY				Whether the submitter gets the points for the download (they are selling it) (otherwise they are just thrown out, which is an alternative model - one of enforcing community point building)
 * @param  ?AUTO_LINK		The licence to use (NULL: none)
 * @param  SHORT_TEXT		Meta keywords
 * @param  LONG_TEXT			Meta description
 */
function edit_download($id,$category_id,$name,$url,$description,$author,$comments,$out_mode_id,$default_pic,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes,$original_filename,$file_size,$cost,$submitter_gets_points,$licence,$meta_keywords,$meta_description)
{
	require_code('urls2');
	suggest_new_idmoniker_for('downloads','view',strval($id),$name);

	if (($file_size==0) || (url_is_local($url)))
	{
		if (url_is_local($url))
		{
			$file_size=filesize(get_custom_file_base().'/'.rawurldecode($url));
		} else
		{
			$file_size=@filesize($url) OR $file_size=NULL;
		}
	}

	$myrows=$GLOBALS['SITE_DB']->query_select('download_downloads',array('name','description','comments'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$myrows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$myrows[0];

	require_code('seo2');
	seo_meta_set_for_explicit('downloads_download',strval($id),$meta_keywords,$meta_description);

	require_code('files2');
	delete_upload('uploads/downloads','download_downloads','url','id',$id,$url);

	$met=@ini_get('max_execution_time');
	$data_mash=create_data_mash($url,NULL,get_file_extension($original_filename));
	if (function_exists('set_time_limit')) @set_time_limit($met);

	if (!addon_installed('unvalidated')) $validated=1;

	require_code('submit');
	$just_validated=(!content_validated('download',strval($id))) && ($validated==1);
	if ($just_validated)
	{
		send_content_validated_notification('download',strval($id));
	}

	$map=array('download_data_mash'=>$data_mash,'download_licence'=>$licence,'original_filename'=>$original_filename,'download_submitter_gets_points'=>$submitter_gets_points,'download_cost'=>$cost,'edit_date'=>time(),'file_size'=>$file_size,'allow_rating'=>$allow_rating,'allow_comments'=>$allow_comments,'allow_trackbacks'=>$allow_trackbacks,'notes'=>$notes,'name'=>lang_remap($myrow['name'],$name),'description'=>lang_remap_comcode($myrow['description'],$description),'comments'=>lang_remap_comcode($myrow['comments'],$comments),'validated'=>$validated,'category_id'=>$category_id,'url'=>$url,'author'=>$author,'default_pic'=>$default_pic,'out_mode_id'=>$out_mode_id);
	$GLOBALS['SITE_DB']->query_update('download_downloads',$map,array('id'=>$id),'',1);

	$self_url=build_url(array('page'=>'downloads','type'=>'entry','id'=>$id),get_module_zone('downloads'),NULL,false,false,true);

	if ($just_validated)
	{
		require_lang('downloads');
		require_code('notifications');
		$subject=do_lang('DOWNLOAD_NOTIFICATION_MAIL_SUBJECT',get_site_name(),$name);
		$mail=do_lang('DOWNLOAD_NOTIFICATION_MAIL',comcode_escape(get_site_name()),comcode_escape($name),array(comcode_escape($self_url->evaluate())));
		dispatch_notification('download',strval($category_id),$subject,$mail);
	}

	log_it('EDIT_DOWNLOAD',strval($id),get_translated_text($myrow['name']));

	if (addon_installed('galleries'))
	{
		// Change its gallery
		require_code('galleries2');
		$download_gallery_root=get_option('download_gallery_root');
		if (is_null($download_gallery_root)) $download_gallery_root='root';
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('galleries','parent_id',array('name'=>'download_'.strval($id)));
		if (!is_null($test))
			edit_gallery('download_'.strval($id),'download_'.strval($id),do_lang('GALLERY_FOR_DOWNLOAD',$name),'','','',$download_gallery_root);
	}

	decache('main_recent_downloads');
	decache('main_top_downloads');
	decache('main_download_category');
	decache('main_download_tease');

	require_code('feedback');
	update_spacer_post($allow_comments!=0,'downloads',strval($id),$self_url,$name,get_value('comment_forum__downloads'));
}

/**
 * Delete a download.
 *
 * @param  AUTO_LINK		The ID of the download to delete
 * @param  boolean		Whether to leave the actual file behind
 */
function delete_download($id,$leave=false)
{
	$myrows=$GLOBALS['SITE_DB']->query_select('download_downloads',array('name','description','comments'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$myrows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$myrows[0];

	log_it('DELETE_DOWNLOAD',strval($id),get_translated_text($myrow['name']));
	delete_lang($myrow['name']);
	delete_lang($myrow['description']);
	delete_lang($myrow['comments']);

	require_code('seo2');
	seo_meta_erase_storage('downloads_download',strval($id));

	if (!$leave)
	{
		require_code('files2');
		delete_upload('uploads/downloads','download_downloads','url','id',$id);
	}

	// Delete from database
	$GLOBALS['SITE_DB']->query_delete('download_downloads',array('id'=>$id),'',1);
	$GLOBALS['SITE_DB']->query_delete('download_logging',array('id'=>$id));
	$GLOBALS['SITE_DB']->query_delete('rating',array('rating_for_type'=>'downloads','rating_for_id'=>$id));
	$GLOBALS['SITE_DB']->query_delete('trackbacks',array('trackback_for_type'=>'downloads','trackback_for_id'=>$id));

	$GLOBALS['SITE_DB']->query_update('download_downloads',array('out_mode_id'=>NULL),array('out_mode_id'=>$id),'',1);

	if (addon_installed('galleries'))
	{
		// Delete gallery
		$name='download_'.strval($id);
		require_code('galleries2');
		$test=$GLOBALS['SITE_DB']->query_value('galleries','parent_id',array('name'=>'download_'.strval($id)));
		if (!is_null($test))
			delete_gallery($name);
	}

	decache('main_recent_downloads');
	decache('main_top_downloads');
	decache('main_download_category');
	decache('main_download_tease');
}

/**
 * Add a download licence.
 *
 * @param  SHORT_TEXT	The title of the download licence
 * @param  LONG_TEXT		The text of the download licence
 * @return AUTO_LINK		The ID of the new download licence
 */
function add_download_licence($title,$text)
{
	$id=$GLOBALS['SITE_DB']->query_insert('download_licences',array('l_title'=>$title,'l_text'=>$text),true);

	log_it('ADD_DOWNLOAD_LICENCE',strval($id),$title);
	return $id;
}

/**
 * Edit a download licence.
 *
 * @param  AUTO_LINK		The ID of the download licence to edit
 * @param  SHORT_TEXT	The title of the download licence
 * @param  LONG_TEXT		The text of the download licence
 */
function edit_download_licence($id,$title,$text)
{
	$GLOBALS['SITE_DB']->query_update('download_licences',array('l_title'=>$title,'l_text'=>$text),array('id'=>$id),'',1);
	log_it('EDIT_DOWNLOAD_LICENCE',strval($id),$title);
}

/**
 * Delete a download licence.
 *
 * @param  AUTO_LINK		The ID of the download licence to delete
 */
function delete_download_licence($id)
{
	$myrows=$GLOBALS['SITE_DB']->query_select('download_licences',array('l_title'),array('id'=>$id),'',1);
	if (!array_key_exists(0,$myrows)) warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
	$myrow=$myrows[0];

	$GLOBALS['SITE_DB']->query_delete('download_licences',array('id'=>$id),'',1);
	log_it('DELETE_DOWNLOAD_LICENCE',strval($id),$myrow['l_title']);

	$GLOBALS['SITE_DB']->query_update('download_downloads',array('download_licence'=>NULL),array('download_licence'=>$id));
}

/**
 * Log a file download, update the downloads counter and the download bandwidth counter.
 *
 * @param  AUTO_LINK		The ID of the download being downloaded
 * @param  integer		The size of the download (if zero, no bandwidth will be done - zero implies either an empty file, or a remote file that doesn't affect our bandwidth)
 * @param  boolean		Whether the download has been downloaded before
 */
function log_download($id,$size,$got_before)
{
	// Log
	if (!$got_before)
		$GLOBALS['SITE_DB']->query_insert('download_logging',array('id'=>$id,'the_user'=>get_member(),'ip'=>get_ip_address(),'date_and_time'=>time()),false,true); // Suppress errors in case of race condition

	// Update download count
	$GLOBALS['SITE_DB']->query('UPDATE '.get_table_prefix().'download_downloads SET num_downloads=(num_downloads+1) WHERE id='.strval((integer)$id),1,NULL,true);

	// Update stats
	$GLOBALS['SITE_DB']->query('UPDATE '.get_table_prefix().'values SET the_value=(the_value+1) WHERE the_name=\'num_downloads_downloaded\'',1,NULL,true);
	if ($size!=0) $GLOBALS['SITE_DB']->query('UPDATE '.get_table_prefix().'values SET the_value=(the_value+'.strval((integer)$size).') WHERE the_name=\'download_bandwidth\'',1,NULL,true);
}


