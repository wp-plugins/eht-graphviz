<?php

add_action ("admin_menu", "EHTGraphvizAdminAddPages");

require_once (ABSPATH . "wp-admin/includes/upgrade.php");

function EHTGraphvizAdminAddPages ()
{
	if (function_exists ("add_options_page"))
	{
		add_options_page ('EHT Graphviz', 'EHT Graphviz', 8, 'eht-graphviz-options', 'EHTGraphvizAdminOptions');
	}
}

function EHTGraphvizAdminOptions ()
{
	global $wpdb;
	
	$action = $_REQUEST[EHT_GRAPHVIZ_FIELD_ACTION];
	if ($action == EHT_GRAPHVIZ_ACTION_UPDATE)
	{
		$optionPath = $_REQUEST[EHT_GRAPHVIZ_OPTION_PATH];
		EHTGraphvizQuitSlashes ($optionPath);
	}
	else
	{
		$optionPath = get_option (EHT_GRAPHVIZ_OPTION_PATH);
	}

	$firstUse = ($optionPath == "");
	
	if ($action == EHT_GRAPHVIZ_ACTION_UPDATE)
	{
        update_option (EHT_GRAPHVIZ_OPTION_PATH, $optionPath);
        echo "<div class=\"updated\">The options have been updated.</div>\n";
	}

	echo "<div class=\"wrap\">\n" .
		 "<h2>EHT Graphviz</h2>\n" .
		 "<form method=\"post\" action=\"" . str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) . "\">\n" .
		 "<p>Relative path (from web root) to Graphviz generated images:<br>\n" .
		 "<input type=\"text\" name=\"" . EHT_GRAPHVIZ_OPTION_PATH . "\" value=\"$optionPath\"></p>\n" .
		 "<p class=\"submit\">\n" .
		 "<input type=\"submit\" name=\"" . EHT_GRAPHVIZ_FIELD_ACTION . "\" value=\"" . EHT_GRAPHVIZ_ACTION_UPDATE . "\" default>\n" .
		 "</p>\n" .
		 "</form>\n" .
		 "</div>\n" .
		 "<p align=\"center\">" . EHT_GRAPHVIZ_PLUGIN_DESCRIPTION . "</p>\n";
}

?>