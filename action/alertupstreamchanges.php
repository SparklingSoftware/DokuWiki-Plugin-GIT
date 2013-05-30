<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');

class action_plugin_git_alertupstreamchanges extends DokuWiki_Action_Plugin {

    var $helper = null;

    function action_plugin_git_alertupstreamchanges (){
        $this->helper =& plugin_load('helper', 'git');
        if(!$this->helper) msg('Loading the git helper in the git_alertupstreamchanges class failed.', -1);
    }
    
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handler');
 	}
    
    function current_url(){
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        return $pageURL;        
    }
    
	function handler(&$event, $param) {
        global $conf, $ID;
        $this->getConf('');

        $gitRemoteStatusUrl = DOKU_URL.'doku.php?id='.$conf['plugin']['git']['origin_status_page'];
        
        $currentURL = $this->current_url();
        if ($gitRemoteStatusUrl === wl($ID,'',true)) return;  // Skip remote GIT status page, no notification needed when the user is looking at the details.
        if (strpos(strtolower($currentURL), strtolower('mediamanager.php')) > 0) return;  // Skip media manager page as well
        
        if ($this->helper->CheckForUpstreamUpdates())
            msg('Other improvements have been approved. <a href="'.$gitRemoteStatusUrl.'">click here to merge changes into this workspace.</a>');		
	}


   
        
}
