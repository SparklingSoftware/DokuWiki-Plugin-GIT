<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_merge extends DokuWiki_Action_Plugin {
    
    var $helper = null;

    function action_plugin_git_merge (){
        $this->helper =& plugin_load('helper', 'git');
        if(!$this->helper) msg('Loading the git helper failed.',-1);
    }

	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_handle');
    }
    
	function _handle(&$event, $param) {

        if ($_REQUEST['cmd'] === null) return;
        
        // verify valid values
        switch (key($_REQUEST['cmd'])) {
            case 'merge' : $this->pull(); break;
            case 'ignore' : $this->ignore(); break;
        }   
  	}       
    
    function pull()
    {
        try {
            global $conf;
            $this->getConf('');

            $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
            $datapath = $conf['savedir'];    
            
            $repo = new GitRepo($datapath);
            $repo->git_path = $git_exe_path;   
            $repo->pull('origin', 'master');
            
            if(!$this->helper) {
                msg('GIT helper is null in the merge.php file');
                return;
            }
            $this->helper->resetGitStatusCache('upstream');
            $this->helper->rebuild_data_plugin_data();
        }
        catch(Exception $e)
        {
            msg($e->getMessage());
            return false;
        }
    }

    function ignore()
    {
        $hash = $_REQUEST['hash'];        
        //$this->output('Ignoring: '.$hash);
    }
}