<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_alertupstreamchanges extends DokuWiki_Action_Plugin {
        
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_hook_header');
 	}
    
	function _hook_header(&$event, $param) {
        global $conf;

        $gitStatusUrl = '/master/doku.php?id=wiki:git:masterstatus';
        
        if ($this->CheckForUpdates())
            msg('Updates available from master. <a href="'.$gitStatusUrl.'">click here to merge changes into this workspace.</a>');		
	}

    function CheckForUpdates() {
        $hasCacheTimedOut = false;
                
        $updatesAvailable = false;
        if ($hasCacheTimedOut)
        {
            $repo = new GitRepo(DOKU_INC);
            $repo->fetch();
            $log = $repo->get_log();
                        
            if ($log === "") $updatesAvailable = true;
            return $updatesAvailable;
        }
        
        $updatesAvailable = $this->readUpdateStatusFromCache();
    }
    
    function readUpdateStatusFromCache() {
        return true;
    }
        
}
