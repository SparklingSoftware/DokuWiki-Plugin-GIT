<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_push extends DokuWiki_Action_Plugin {

    var $helper = null;
    
    function action_plugin_git_push(){  
        $this->helper =& plugin_load('helper', 'git');
        if (is_null($this->helper)) {
            msg('The GIT plugin could not load its helper class', -1);
            return false;
        } 
    }
        
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_handle');
    }
        
	function _handle(&$event, $param) {

        if ($_REQUEST['cmd'] === null) return;
        
        // verify valid values
        switch (key($_REQUEST['cmd'])) {
            case 'push' : 
                $this->push(); 
                $this->helper->changeReadOnly(false);
                $this->redirect();
                break;
       }   
  	}       

    function redirect()
    {
            global $conf;
            $this->getConf('');
            $local_status_page = $conf['plugin']['git']['local_status_page'];        

           header( 'Location:doku.php?id='.$local_status_page );
    }
    
    function push()
    {
        try {
            global $conf;
            $this->getConf('');

            $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
            $datapath = $conf['savedir'];    
            
            $repo = new GitRepo($datapath);
            $repo->git_path = $git_exe_path;   
            $result = $repo->push();        
        }
        catch(Exception $e)
        {
            msg($e->getMessage());
            return false;
        }
    }
    


}