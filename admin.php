<?php
/**
 * DokuWiki Plugin git (Admin Component)
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'admin.php');

class admin_plugin_git extends DokuWiki_Admin_Plugin {

    function getInfo() {
        return confToHash(dirname(__FILE__).'plugin.info.txt');
    }

    function getMenuSort() { return 1; }
    function forAdminOnly() { return true; }

    function handle() {
    
    }

    function html() {

       echo '<h2>Short:</h2><p>The GIT plugin already refreshes all Data-Plugin data. You can refresh as often as you like!</p>';

       echo '<h2>Long:</h2><p>The data plugin allows some really nice useability features, like tag clouds and applying the user selection of an item in that tag-cloud to a table as a filter.';
       echo 'The GIT plugin honers that and will refresh the entries when merging changes from other branches. However... When merging in changes outside of the wiki, the data doesnt get updates. Therefore the GIT plugin allows you to refresh the data with the click of a button!';
       echo 'This is also to cover up any bugs in the plugin that prevent the refresh to happen as it should :-)';
       
       echo '<form method="post">';
       echo '  <input type="submit" name="cmd[refresh_data_plugin_data]"  value="Refresh Data plugin data" />';
       echo '</form><br/>';
       
  }

}

// vim:ts=4:sw=4:et:
