<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_alertcommited extends DokuWiki_Action_Plugin {
        
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_hook_header');
 	}
    
	function _hook_header(&$event, $param) {
        global $conf;
        // TODO: Read from plugin config
        $gitStatusUrl = '/master/doku.php?id=wiki:git:localstatus';  // /master/doku.php?id=wiki:git:localstatus
        
        $repo = new GitRepo(DOKU_INC);
        $show = $repo->ChangesAwaitingApproval();
        
        if ($show)
            msg('Changes waiting to be approved. <a href="'.$gitStatusUrl.'">click here to view changes.</a>');		
	}


            
}
