<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_push extends DokuWiki_Action_Plugin {
    
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_handle');
    }
    
	function _handle(&$event, $param) {

        // verify valid values
        switch (key($_REQUEST['cmd'])) {
            case 'push' : $this->push(); break;
                }   
  	}       
    
    function push()
    {
        $repo = new GitRepo(DOKU_INC);
        $result = $repo->push();        
    }

}