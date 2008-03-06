<?php
/*
Plugin Name:	EHT Graphviz
Plugin URI:		http://ociotec.com/index.php/2008/02/25/eht-graphviz-plugin-para-wordpress/
Description:	This plugin generates images form Graphviz language source code inserted into your pages/posts.
Author:			Emilio Gonz&aacute;lez Monta&ntilde;a
Version:		0.2
Author URI:		http://ociotec.com/

History:		0.1		First release.
				0.2		Error fixing.

Setup:
	1) Install the plugin.
	2) Go to the admin menus, and in "Options" panel, select "EHT Graphviz".
	4) Configure the plugin if you need.
	5) Insert the plugin tags where you need it (see below the plugin sintax).

Plugin sintax:

[graphviz name={1}]
{2}
[/graphviz]

Where:
   {1} name of the image to generate (under the configured Graphviz output folder].
   {2} this is the Graphviz code to compile

Example:

[graphviz name=Graph01]
   main -> parse -> execute;
   main -> init;
   main -> cleanup;
   execute -> make_string;
   execute -> printf
   init -> make_string;
   main -> printf;
   execute -> compare;
[/graphviz]

*/

define ("EHT_GRAPHVIZ_SESSION_DOMAIN", "eht-graphviz");
define ("EHT_GRAPHVIZ_PLUGIN_URL_BASE", get_option ("siteurl") . "/wp-content/plugins/eht-graphviz/");
define ("EHT_GRAPHVIZ_PLUGIN_URL_BASE_IMAGES", EHT_PHOTOS_PLUGIN_URL_BASE . "images/");
define ("EHT_GRAPHVIZ_PLUGIN_PATH_BASE", $_SERVER["DOCUMENT_ROOT"] . "/wp-content/plugins/eht-graphviz/");
define ("EHT_GRAPHVIZ_PLUGIN_PATH_BASE_IMAGES", EHT_PHOTOS_PLUGIN_PATH_BASE . "images/");
define ("EHT_GRAPHVIZ_PLUGIN_VERSION", "0.2");
define ("EHT_GRAPHVIZ_PLUGIN_DESCRIPTION", "Plugin <a href=\"http://ociotec.com/index.php/2008/02/25/eht-graphviz-plugin-para-wordpress/\" target=\"_blank\">EHT Graphviz v" . EHT_GRAPHVIZ_PLUGIN_VERSION . "</a> - Created by <a href=\"http://ociotec.com\" target=\"_blank\">Emilio Gonz&aacute;lez Monta&ntilde;a</a>");
define ("EHT_GRAPHVIZ_PLUGIN_SHORT_DESCRIPTION", "<a href=\"http://ociotec.com/index.php/2008/02/25/eht-graphviz-plugin-para-wordpress/\" target=\"_blank\">&copy;</a>");
define ("EHT_GRAPHVIZ_OPTION_PATH", "eht-graphviz-option-path-images");
define ("EHT_GRAPHVIZ_FIELD_ACTION", "eht-graphviz-field-action");
define ("EHT_GRAPHVIZ_ACTION_UPDATE", "Update");
define ("EHT_GRAPHVIZ_ACTION_RESET", "Reset");
define ("EHT_GRAPHVIZ_SLASH", strstr (PHP_OS, "WIN") ? "\\" : "/");
define ("EHT_GRAPHIZ_IMAGE_EXTENSION", "png");
define ("EHT_GRAPHIZ_DOT_EXTENSION", "dot");

require_once ("Admin.php");

add_filter ("the_content", "EHTGraphvizFilterTheContent");

function EHTGraphvizFilterTheContent ($content)
{
	global $goodPath;
	
	$optionPath = get_option (EHT_GRAPHVIZ_OPTION_PATH);
	
	$newContent = "";
	$oldContent = $content;
	while (($start = strpos ($oldContent, "[graphviz")) !== false)
	{
		$newContent .= substr ($oldContent, 0, $start);
		$oldContent = substr ($oldContent, $start + strlen ("[graphviz"));
		if (($end = strpos ($oldContent, "[/graphviz]")) !== false)
		{
			$tagCode = substr ($oldContent, 0, $end);
			if ((($startName = strpos ($tagCode, "name=")) !== false) &&
				(($endName = strpos ($tagCode, "]")) !== false))
			{
				$name = substr ($tagCode, $startName + strlen ("name="), $endName - $startName - strlen ("name="));
				$code = substr ($tagCode, $endName + strlen ("]"));

				$code = str_replace ("<br />", "", $code);
				$code = str_replace ("&#8220;", "\"", $code);
				$code = str_replace ("&#8221;", "\"", $code);
				$code = "digraph $name {" . $code . "}\n";
				
				$goodUrl = get_option ("siteurl");
				EHTGraphvizQuitSlashes ($goodUrl, true);
				$goodPath = $_SERVER["DOCUMENT_ROOT"];
				EHTGraphvizQuitSlashes ($goodPath, true);
				
				$urlImage = $goodUrl . EHT_GRAPHVIZ_SLASH . 
							$optionPath . EHT_GRAPHVIZ_SLASH .
							$name . "." . EHT_GRAPHIZ_IMAGE_EXTENSION;
				$pathImage = $goodPath . EHT_GRAPHVIZ_SLASH . 
							 $optionPath . EHT_GRAPHVIZ_SLASH .
							 $name . "." . EHT_GRAPHIZ_IMAGE_EXTENSION;
				$pathDot = $goodPath . EHT_GRAPHVIZ_SLASH . 
						   $optionPath . EHT_GRAPHVIZ_SLASH .
						   $name . "." . EHT_GRAPHIZ_DOT_EXTENSION;
							 
				if (!EHTGraphvizGenerate ($code, $pathDot, $pathImage, $message))
				{
					$code = str_replace ("\n", "\n<br />", $code);
					$newContent .= "Fail to generate the Graphviz image: \"$message\"<br>\n" .
								   "<code>$code</code><br>\n";
				}
				else
				{
					$newContent .= "<img src=\"$urlImage\" tittle=\"$name\"><br>\n";
				}
				$newContent .= "<small>$name " . EHT_GRAPHVIZ_PLUGIN_SHORT_DESCRIPTION . "</small>\n";
			}
			$oldContent = substr ($oldContent, $end + strlen ("[/graphviz]"));
		}
	}
	$content = $newContent . $oldContent;
	
	return ($content);
}

function EHTGraphvizGenerate ($code,
							  $pathDot,
							  $pathImage,
							  &$message)
{
	$ok = false;
	$message = "";
	if (!($file = fopen ($pathDot, "w")))
	{
		$message .= "Fail to open the dot file \"pathDot\"";
	}
	else if (!fwrite ($file, $code))
	{
		$message .= "Fail to write the code into the dot file";
	}
	else if (!fclose ($file))
	{
		$message .= "Fail to close the dot file";
	}
	else
	{
		exec ("dot -T" . EHT_GRAPHIZ_IMAGE_EXTENSION . " \"$pathDot\" -o \"$pathImage\"", $lines, $result);
		if ($result != 0)
		{
			$message .= "Fail to generate the dot file";
		}	
		else
		{
			$ok = true;
		}
	}	
	
	return ($ok);
}

function EHTGraphvizQuitSlashes (&$path,
								 $onlyEnd = false)
{
	$size = strlen ($path);
	if ($size > 0)
	{
		if ((!$onlyEnd) && ($path[0] == EHT_GRAPHVIZ_SLASH))
		{
			$path = substr ($path, 1);
			$size--;
		}
		if ($path[$size - 1] == EHT_GRAPHVIZ_SLASH)
		{
			$path = substr ($path, 0, ($size - 1));
		}
	}
	
	return ($path);
}

?>