<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_alertcommited extends DokuWiki_Action_Plugin {

    var $helper = null;

    function action_plugin_git_alertcommited (){
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
        global $conf, $INFO;
        $this->getConf('');

        $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
        $gitLocalStatusUrl = wl($conf['plugin']['git']['local_status_page'],'',true);
        $datapath = $conf['savedir'];    

        $currentURL = $this->current_url();
        if ($gitLocalStatusUrl === $currentURL) return;  // Skip local GIT status page, no notification needed when the user is looking at the details.
        if (strpos(strtolower($currentURL), strtolower('mediamanager.php')) > 0) return;  // Skip media manager page as well
        
        $changesAwaiting = $this->helper->readLocalChangesAwaitingFromCache();

        if ($changesAwaiting) {
            msg('Changes waiting to be approved. <a href="'.$gitLocalStatusUrl.'">click here to view changes.</a>');		
        }
	}    
    

}
