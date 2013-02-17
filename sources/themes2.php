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
 * @package		core_themeing
 */

/**
 * Edit a theme image.
 *
 * @param  SHORT_TEXT		The current theme image ID
 * @param  ID_TEXT			The theme the theme image is in
 * @param  LANGUAGE_NAME	The language the theme image is for (blank: all languages)
 * @param  SHORT_TEXT		The new theme image ID
 * @param  URLPATH			The URL to the theme image
 * @param  boolean			Whether to avoid cleanup, etc
 */
function actual_edit_theme_image($old_id,$theme,$lang,$id,$path,$quick=false)
{
	if ($old_id!=$id)
	{
		$where_map=array('theme'=>$theme,'id'=>$id);
		if (($lang!='') && (!is_null($lang))) $where_map['lang']=$lang;
		$test=$GLOBALS['SITE_DB']->query_value_null_ok('theme_images','id',$where_map);
		if (!is_null($test))
		{
			warn_exit(do_lang_tempcode('ALREADY_EXISTS',escape_html($id)));
		}
	}

	if (!$quick)
	{
		$old_url=find_theme_image($id,true,true,$theme,($lang=='')?NULL:$lang);

		$where_map=array('theme'=>$theme,'id'=>$id);
		if (($lang!='') && (!is_null($lang))) $where_map['lang']=$lang;
		$GLOBALS['SITE_DB']->query_delete('theme_images',$where_map);

		if (($old_url!=$path) && ($old_url!=''))
		{
			if (($theme=='default') || (strpos($old_url,'themes/default/')===false))
			{
				require_code('themes3');
				cleanup_theme_images($old_url);
			}
		}
	} else
	{
		$where_map=array('theme'=>$theme,'id'=>$id);
		if (($lang!='') && (!is_null($lang))) $where_map['lang']=$lang;
		$GLOBALS['SITE_DB']->query_delete('theme_images',$where_map);
	}

	if ($lang=='')
	{
		$langs=array_keys(find_all_langs());
	} else
	{
		$langs=array($lang);
	}
	foreach ($langs as $lang)
	{
		$GLOBALS['SITE_DB']->query_insert('theme_images',array('id'=>$id,'theme'=>$theme,'path'=>$path,'lang'=>$lang));
	}

	if (!$quick) log_it('EDIT_THEME_IMAGE',$id,$theme);
}

/**
 * Replace colour codes with references (helper callback function)
 *
 * @param  array	List of found regular expression matches (only index 0 relevant).
 * @return string	Replacement.
 */
function css_preg($matches)
{
	global $CSS_MATCHES;
	$ret=count($CSS_MATCHES);
	$CSS_MATCHES[]=$matches[0];

	return '<color-'.strval($ret).'>';
}

/**
 * Add a theme.
 *
 * @param  ID_TEXT		The theme name
 */
function actual_add_theme($name)
{
	$GLOBALS['NO_QUERY_LIMIT']=true;

	if ((file_exists(get_custom_file_base().'/themes/'.$name)) || ($name=='default'))
	{
		warn_exit(do_lang_tempcode('ALREADY_EXISTS',escape_html($name)));
	}

	require_code('abstract_file_manager');
	force_have_afm_details();

	// Create directories
	$dir_list=array('','images','images/logo','images_custom','templates','templates_custom','templates_cached','css','css_custom');
	$langs=find_all_langs(true);
	foreach (array_keys($langs) as $lang)
		$dir_list[]='templates_cached/'.$lang;
	$dir_list_access=array('','images','images_custom','css');
	foreach ($dir_list as $dir)
	{
		$path='themes/'.$name.'/'.$dir;
		afm_make_directory($path,true);
		if (!in_array($dir,$dir_list_access))
		{
//			$path='themes/'.$name.'/'.$dir.'/.htaccess';
//			afm_copy('themes/default/templates_cached/.htaccess',$path,false);
		}
		$path='themes/'.$name.'/'.(($dir=='')?'':($dir.'/')).'index.html';
		if (file_exists(get_file_base().'/themes/default/templates_cached/index.html'))
			afm_copy('themes/default/templates_cached/index.html',$path,false);
	}
	afm_copy('themes/default/theme.ini','themes/'.$name.'/theme.ini',true);
	/*$_dir=opendir(get_custom_file_base().'/themes/default/css');
	while (false!==($file=readdir($_dir)))
	{
		if (strtolower(substr($file,-4,4))=='.css')
		{
			$path='themes/'.$name.'/css_custom/'.$file;
			$new_css_file="@import url(../../../default/css/$file);\n\n".file_get_contents(get_custom_file_base().'/themes/default/css/'.$file,FILE_TEXT);
			afm_make_file($path,$new_css_file,false);
		}
	}
	closedir($_dir);*/

	// Copy image references from default
	$start=0;
	do
	{
		$theme_images=$GLOBALS['SITE_DB']->query_select('theme_images',array('*'),array('theme'=>'default'),'',100,$start);
		foreach ($theme_images as $theme_image)
		{
			$test=$GLOBALS['SITE_DB']->query_value_null_ok('theme_images','id',array('theme'=>$name,'id'=>$theme_image['id'],'lang'=>$theme_image['lang']));
			if (is_null($test))
				$GLOBALS['SITE_DB']->query_insert('theme_images',array('id'=>$theme_image['id'],'theme'=>$name,'path'=>$theme_image['path'],'lang'=>$theme_image['lang']));
		}
		$start+=100;
	}
	while (count($theme_images)==100);

	log_it('ADD_THEME',$name);
}

/**
 * Add a theme image.
 *
 * @param  ID_TEXT			The theme the theme image is in
 * @param  LANGUAGE_NAME	The language the theme image is for
 * @param  SHORT_TEXT		The theme image ID
 * @param  URLPATH			The URL to the theme image
 * @param  boolean			Whether to allow failure without bombing out
 */
function actual_add_theme_image($theme,$lang,$id,$path,$fail_ok=false)
{
	$test=$GLOBALS['SITE_DB']->query_value_null_ok('theme_images','id',array('id'=>$id,'theme'=>$theme,'lang'=>$lang));
	if (!is_null($test))
	{
		if ($fail_ok) return;
		warn_exit(do_lang_tempcode('ALREADY_EXISTS',escape_html($id)));
	}

	$GLOBALS['SITE_DB']->query_insert('theme_images',array('id'=>$id,'theme'=>$theme,'path'=>$path,'lang'=>$lang));

	log_it('ADD_THEME_IMAGE',$id,$theme);

	persistant_cache_delete('THEME_IMAGES');
}

/**
 * A theme image has been passed through by POST, either as a file (a new theme image), or as a reference to an existing one. Get the image code from the POST data.
 *
 * @param  ID_TEXT		The type of theme image
 * @param  boolean		Allow no code to be given
 * @param  ID_TEXT		Form field for uploading
 * @param  ID_TEXT		Form field for choosing
 * @param  ?object		Database connection (NULL: site database)
 * @return ID_TEXT		The (possibly randomised) theme image code
 */
function get_theme_img_code($type,$allow_skip=false,$field_file='file',$field_choose='theme_img_code',$db=NULL)
{
	if (is_null($db)) $db=$GLOBALS['SITE_DB'];

	// TODO: Image won't upload to central site. So perhaps we should not allow uploads if not editing on central site.

	if ((substr($type,0,4)=='ocf_') && (file_exists(get_file_base().'/themes/default/images/avatars/index.html'))) // Allow debranding of theme img dirs
	{
		$type=substr($type,4);
	}

	require_code('uploads');
	if ((is_swf_upload()) || (((array_key_exists($field_file,$_FILES)) && (is_uploaded_file($_FILES[$field_file]['tmp_name'])))))
	{
		$urls=get_url('',$field_file,'themes/default/images_custom',0,OCP_UPLOAD_IMAGE,false);

		$theme_img_code=$type.'/'.uniqid('');

		$db->query_insert('theme_images',array('id'=>$theme_img_code,'theme'=>'default','path'=>$urls[0],'lang'=>get_site_default_lang()));

		persistant_cache_delete('THEME_IMAGES');
	} else
	{
		$theme_img_code=post_param($field_choose,'');

		if ($theme_img_code=='')
		{
			if ($allow_skip) return '';
			warn_exit(do_lang_tempcode('IMPROPERLY_FILLED_IN_UPLOAD'));
		}
	}
	return $theme_img_code;
}

/**
 * Recursively find theme images under the specified details. Does not find custom theme images, as it doesn't check the DB.
 *
 * @param  ID_TEXT		The theme
 * @param  string			The subdirectory to search under
 * @param  array			A map (lang=>1) of the languages in the system, so the codes may be filtered out of the image codes in our result list
 * @return array			A map, theme-image-code=>URL
 */
function find_images_do_dir($theme,$subdir,$langs)
{
	$full=(($theme=='default')?get_file_base():get_custom_file_base()).'/themes/'.filter_naughty($theme).'/'.filter_naughty($subdir);
	$out=array();

	$_dir=@opendir($full);
	if ($_dir!==false)
	{
		while (false!==($file=readdir($_dir)))
		{
			if (($file!='.') && ($file!='..'))
			{
				if (is_dir($full.$file))
				{
					$out=array_merge($out,find_images_do_dir($theme,$subdir.$file.'/',$langs));
				} else
				{
					$ext=substr($file,-4);
					if (($ext=='.png') || ($ext=='.gif') || ($ext=='.jpg') || ($ext=='jpeg'))
					{
						$_file=explode('.',$file);
						$_subdir=$subdir;
						foreach (array_keys($langs) as $lang)
						{
							$_subdir=str_replace('/'.$lang.'/','/',$_subdir);
						}
						$_subdir=preg_replace('#(^|/)images(\_custom)?/#','',$_subdir);
						$out[$_subdir.$_file[0]]='themes/'.rawurlencode($theme).'/'.$subdir.rawurlencode($file);
					}
				}
			}
		}

		closedir($_dir);
	}

	return $out;
}

/**
 * Get all the image IDs (both already known, and those uncached) of a certain type (i.e. under a subdirectory).
 *
 * @param  ID_TEXT		The type of image (e.g. 'ocf_emoticons')
 * @param  boolean		Whether to search recursively; i.e. in subdirectories of the type subdirectory
 * @param  ?object		The database connection to work over (NULL: site db)
 * @param  ?ID_TEXT		The theme to search in, in addition to the default theme (NULL: current theme)
 * @param  boolean		Whether to only return directories (advanced option, rarely used)
 * @param  boolean		Whether to only return from the database (advanced option, rarely used)
 * @return array			The list of image IDs
 */
function get_all_image_ids_type($type,$recurse=false,$db=NULL,$theme=NULL,$dirs_only=false,$db_only=false)
{
	if (is_null($db)) $db=$GLOBALS['SITE_DB'];
	if (is_null($theme)) $theme=$GLOBALS['FORUM_DRIVER']->get_theme();

	if ((substr($type,0,4)=='ocf_') && (file_exists(get_file_base().'/themes/default/images/avatars/index.html'))) // Allow debranding of theme img dirs
	{
		$type=substr($type,4);
	}

	if (substr($type,-1)=='/') $type=substr($type,0,strlen($type)-1);

	$ids=array();

	if ((!$db_only) && (($db->connection_write==$GLOBALS['SITE_DB']->connection_write) || ($dirs_only) || (get_db_forums()==get_db_site())))
	{
		_get_all_image_ids_type($ids,get_file_base().'/themes/default/images/'.(($type=='')?'':($type.'/')),$type,$recurse,$dirs_only);
		_get_all_image_ids_type($ids,get_file_base().'/themes/default/images/'.get_site_default_lang().'/'.(($type=='')?'':($type.'/')),$type,$recurse,$dirs_only);
		if ($theme!='default')
		{
			_get_all_image_ids_type($ids,get_custom_file_base().'/themes/'.$theme.'/images/'.(($type=='')?'':($type.'/')),$type,$recurse,$dirs_only);
			_get_all_image_ids_type($ids,get_custom_file_base().'/themes/'.$theme.'/images/'.get_site_default_lang().'/'.(($type=='')?'':($type.'/')),$type,$recurse,$dirs_only);
		}
		_get_all_image_ids_type($ids,get_file_base().'/themes/default/images_custom/'.(($type=='')?'':($type.'/')),$type,$recurse,$dirs_only);
		_get_all_image_ids_type($ids,get_file_base().'/themes/default/images_custom/'.get_site_default_lang().'/'.(($type=='')?'':($type.'/')),$type,$recurse,$dirs_only);
		if ($theme!='default')
		{
			_get_all_image_ids_type($ids,get_custom_file_base().'/themes/'.$theme.'/images_custom/'.(($type=='')?'':($type.'/')),$type,$recurse,$dirs_only);
			_get_all_image_ids_type($ids,get_custom_file_base().'/themes/'.$theme.'/images_custom/'.get_site_default_lang().'/'.(($type=='')?'':($type.'/')),$type,$recurse,$dirs_only);
		}
	}

	if (!$dirs_only)
	{
		$query='SELECT DISTINCT id,path FROM '.$db->get_table_prefix().'theme_images WHERE ';
		if (!$db_only)
			$query.='path NOT LIKE \''.db_encode_like('themes/default/images/%').'\' AND '.db_string_not_equal_to('path','themes/default/images/blank.gif').' AND ';
		$query.='('.db_string_equal_to('theme',$theme).' OR '.db_string_equal_to('theme','default').') AND id LIKE \''.db_encode_like($type.'%').'\' ORDER BY path';
		$rows=$db->query($query);
		foreach ($rows as $row)
		{
			if ($row['path']=='') continue;

			if ((url_is_local($row['path'])) && (!file_exists(((substr($row['path'],0,15)=='themes/default/')?get_file_base():get_custom_file_base()).'/'.rawurldecode($row['path'])))) continue;
			if ($row['path']!='themes/default/images/blank.gif') // We sometimes associate to blank.gif to essentially delete images so they can never be found again
			{
				$ids[]=$row['id'];
			} else
			{
				$key=array_search($row['id'],$ids);
				if (is_integer($key)) unset($ids[$key]);
			}
		}
	}
	sort($ids);

	return array_unique($ids);
}

/**
 * Get all the image IDs (both already known, and those uncached) of a certain type (i.e. under a subdirectory).
 *
 * @param  array			The list of image IDs found so far. This list will be appended as we proceed
 * @param  ID_TEXT		The specific theme image subdirectory we are currently looking under
 * @param  ID_TEXT		The type of image (e.g. 'ocf_emoticons')
 * @param  boolean		Whether to search recursively; i.e. in subdirectories of the type subdirectory
 * @param  boolean		Whether to only return directories (advanced option, rarely used)
 */
function _get_all_image_ids_type(&$ids,$dir,$type,$recurse,$dirs_only=false)
{
	require_code('images');

	$_dir=@opendir($dir);
	if ($_dir!==false)
	{
		while (false!==($file=readdir($_dir)))
		{
			if (!should_ignore_file($file,IGNORE_ACCESS_CONTROLLERS))
			{
				if (!is_dir($dir.'/'.$file))
				{
					if (!$dirs_only)
					{
						$dot_pos=strrpos($file,'.');
						if ($dot_pos===false) $dot_pos=strlen($file);
						if (is_image($file)) $ids[]=$type.(($type!='')?'/':'').substr($file,0,$dot_pos);
					}
				}
				elseif (($recurse) && ((strlen($file)!=2) || (strtoupper($file)!=$file)))
				{
					if ($dirs_only) $ids[]=$type.(($type!='')?'/':'').$file;
					_get_all_image_ids_type($ids,$dir.(($dir!='')?'/':'').$file,$type.(($type!='')?'/':'').$file,true,$dirs_only);
				}
			}
		}
		closedir($_dir);
	}
}

/**
 * Get tempcode for a radio list to choose an image from the image FILES in the theme.
 *
 * @param  string			The currently selected image path (blank for none)
 * @param  URLPATH		The base-URL to where we are searching for images
 * @param  PATH			The base-path to where we are searching for images
 * @return tempcode		The generated tempcode
 */
function combo_get_image_paths($selected_path,$base_url,$base_path)
{
	$out=new ocp_tempcode();

	$paths=get_image_paths($base_url,$base_path);
	$i=0;
	foreach ($paths as $pretty=>$url)
	{
		$checked=(($url==$selected_path) || (($selected_path=='') && ($i==0)));
		$out->attach(do_template('FORM_SCREEN_INPUT_RADIO_LIST_ENTRY_PICTURE',array('_GUID'=>'d2ff01291e5f0c0e4cf4ee5b6061593c','CHECKED'=>$checked,'NAME'=>'path','VALUE'=>$url,'URL'=>$url,'PRETTY'=>$pretty)));
		$i++;
	}

	return $out;
}

/**
 * Search under a base path for image FILE URLs (not actually paths as function name would suggest).
 *
 * @param  URLPATH		The base-URL to where we are searching for images
 * @param  PATH			The base-path to where we are searching for images
 * @return array			path->url map of found images
 */
function get_image_paths($base_url,$base_path)
{
	$out=array();

	require_code('images');

	$handle=@opendir($base_path);
	if ($handle!==false)
	{
		while (false!==($file=readdir($handle)))
		{
			if (!should_ignore_file($file,IGNORE_ACCESS_CONTROLLERS))
			{
				$this_path=$base_path.$file;
				if (is_file($this_path))
				{
					if (is_image($file))
					{
						$this_url=$base_url.rawurlencode($file);
						$out[$this_path]=$this_url;
					}
				}
				elseif ((strlen($file)!=2) || (strtoupper($file)!=$file))
				{
					$out=array_merge($out,get_image_paths($base_url.$file.'/',$base_path.$file.'/'));
				}
			}
		}
		closedir($handle);
	}

	return $out;
}

/**
 * Get all the themes image codes. THIS DOES NOT SEARCH THE DB - DO NOT USE UNLESS IT'S ON A PURE PACKAGED THEME
 *
 * @param  PATH			The base-path to where we are searching for images
 * @param  PATH			The path to search under, relative to the base-path. This is not the same as the base-path, as we are cropping paths to the base-path
 * @param  boolean		Whether to search recursively from the given directory
 * @return array			A list of image codes
 */
function get_all_image_codes($base_path,$search_under,$recurse=true)
{
	$out=array();

	require_code('images');

	if (!file_exists($base_path.'/'.$search_under)) return array();
	$handle=@opendir($base_path.'/'.$search_under);
	if ($handle!==false)
	{
		while (false!==($file=readdir($handle)))
		{
			if (!should_ignore_file($file,IGNORE_ACCESS_CONTROLLERS))
			{
				$full_path=$base_path.'/'.$search_under.'/'.$file;
				if (is_file($full_path))
				{
					if (is_image($file))
					{
						$dot_pos=strrpos($file,'.');
						if ($dot_pos===false) $dot_pos=strlen($file);
						$_file=substr($file,0,$dot_pos);
						$short_path=($search_under=='')?$_file:($search_under.'/'.$_file);
						$out[$short_path]=1;
					}
				}
				elseif ((strlen($file)!=2) || (strtoupper($file)!=$file))
				{
					if ($recurse) $out+=get_all_image_codes($base_path,$search_under.'/'.$file);
				}
			}
		}
		closedir($handle);
	}

	return $out;
}

/**
 * Get tempcode for a dropdown to choose a theme from the themes present.
 *
 * @param  ?ID_TEXT		The currently selected image ID (NULL: none selected)
 * @param  ?string		An SQL where clause (including the WHERE), that filters the query somehow (NULL: none)
 * @param  boolean		Whether to show IDs as the list entry captions, rather than paths
 * @param  boolean		Whether to include images not yet used (i.e not in theme_images map yet)
 * @return tempcode		Tempcode for a list selection of theme images
 * @param  string			Only include images under this path. Including a trailing slash unless you specifically want to filter allowing filename stubs as well as paths (blank: no limitation)
 */
function nice_get_theme_images($it=NULL,$filter=NULL,$do_id=false,$include_all=false,$under='')
{
	$out=new ocp_tempcode();
	if (!$include_all)
	{
		$rows=$GLOBALS['SITE_DB']->query('SELECT id,path FROM '.get_table_prefix().'theme_images WHERE '.db_string_equal_to('theme',$GLOBALS['FORUM_DRIVER']->get_theme()).' '.$filter.' ORDER BY path');
		foreach ($rows as $myrow)
		{
			$id=$myrow['id'];

			if (substr($id,0,strlen($under))!=$under) continue;

			$selected=($id==$it);

			$out->attach(form_input_list_entry($id,$selected,($do_id)?$id:$myrow['path']));
		}
	} else
	{
		$rows=get_all_image_ids_type($under,true);
		foreach ($rows as $id)
		{
			if (substr($id,0,strlen($under))!=$under) continue;

			$selected=($id==$it);

			$out->attach(form_input_list_entry($id,$selected));
		}
	}

	return $out;
}

/**
 * Get a UI list for choosing a theme.
 *
 * @param  ?ID_TEXT		The theme to select by default (NULL: no specific default)
 * @param  boolean		Whether to skip the 'rely on forums' entry
 * @param  boolean		Whether to forget about permissions for this list
 * @param  ID_TEXT		The language string to use for the default answer
 * @return tempcode		The list
 */
function nice_get_themes($theme=NULL,$no_rely=false,$show_everything=false,$default_message_string='RELY_FORUMS')
{
	if (!$no_rely) $entries=form_input_list_entry('-1',false,do_lang_tempcode($default_message_string)); else $entries=new ocp_tempcode();
	$themes=find_all_themes();
	foreach ($themes as $_theme=>$title)
	{
		if (/*($_theme=='default') || */ ($show_everything) || (has_category_access(get_member(),'theme',$_theme)))
		{
			$selected=($theme==$_theme);
			$entries->attach(form_input_list_entry($_theme,$selected,$title));
		}
	}
	if ($entries->is_empty())
	{
		$entries->attach(form_input_list_entry('default',false,$themes['default']));
	}
	return $entries;
}

/**
 * Get an array listing all the themes present.
 *
 * @param  boolean		Whether to gather full details for each theme
 * @return array			A map of all themes (name=>title) OR if requested a map of theme name to full theme details
 */
function find_all_themes($full_details=false)
{
	if ($GLOBALS['IN_MINIKERNEL_VERSION']==1) return $full_details?array('default'=>array()):array('default'=>do_lang('DEFAULT'));

	require_code('files');

	$themes=array();
	$_dir=opendir(get_file_base().'/themes/');
	while (false!==($file=readdir($_dir)))
	{
		$ini_file=get_file_base().'/themes/'.$file.'/theme.ini';
		if ((strpos($file,'.')===false) && (is_dir(get_file_base().'/themes/'.$file)) && (file_exists($ini_file)))
		{
			$details=better_parse_ini_file($ini_file);
			if (!array_key_exists('title',$details)) $details['title']='?';
			if (!array_key_exists('description',$details)) $details['description']='?';
			if (!array_key_exists('author',$details)) $details['author']='?';
			$themes[$file]=$full_details?$details:$details['title'];
		}
	}
	closedir($_dir);
	if (get_custom_file_base()!=get_file_base())
	{
		$_dir=opendir(get_custom_file_base().'/themes/');
		while (false!==($file=readdir($_dir)))
		{
			$ini_file=get_custom_file_base().'/themes/'.$file.'/theme.ini';
			if ((strpos($file,'.')===false) && (is_dir(get_custom_file_base().'/themes/'.$file)) && (file_exists($ini_file)))
			{
				$details=better_parse_ini_file($ini_file);
				if (!array_key_exists('title',$details)) $details['title']='?';
				if (!array_key_exists('description',$details)) $details['description']='?';
				if (!array_key_exists('author',$details)) $details['author']='?';
				$themes[$file]=$full_details?$details:$details['title'];
			}
		}
		closedir($_dir);
	}
	if (!array_key_exists('default',$themes))
	{
		$details=better_parse_ini_file(get_file_base().'/themes/default/theme.ini');
		if (!array_key_exists('title',$details)) $details['title']='?';
		if (!array_key_exists('description',$details)) $details['description']='?';
		if (!array_key_exists('author',$details)) $details['author']='?';
		$themes['default']=$full_details?$details:$details['title'];
	}
	return $themes;
}

/**
 * Delete a theme image used for a resource that was added, but only if the theme image is now unused.
 *
 * @param  ?ID_TEXT		The new theme image (NULL: no new one)
 * @param  ID_TEXT		The old theme image we might be tidying up
 * @param  ID_TEXT		Table to check against
 * @param  ID_TEXT		Field in table
 * @param  ?object		Database connection to check against (NULL: site database)
 */
function tidy_theme_img_code($new,$old,$table,$field,$db=NULL)
{
	if ($new===$old) return; // Still being used

	$path=find_theme_image($old,true,true);
	if ((is_null($path)) || ($path=='')) return;

	if ((strpos($path,'/images_custom/')!==false) && ($GLOBALS['SITE_DB']->query_value('theme_images','COUNT(DISTINCT id)',array('path'=>$path))==1))
	{
		if (is_null($db)) $db=$GLOBALS['SITE_DB'];
		$count=$db->query_value($table,'COUNT(*)',array($field=>$old));
		if ($count==0)
		{
			@unlink(get_custom_file_base().'/'.$path);
			$GLOBALS['SITE_DB']->query_delete('theme_images',array('id'=>$old));
		}
	}
}
