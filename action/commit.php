<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';
require_once(DOKU_PLUGIN.'git/lib/Git.php');


class action_plugin_git_commit extends DokuWiki_Action_Plugin {
    
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_handle');
    }
    
	function _handle(&$event, $param) {

        // verify valid values
        switch (key($_REQUEST['cmd'])) {
            case 'commit' : 
                $this->commit(); 
                $this->makeReadOnly();
                break;
        }   
  	}       
    
    function commit()
    {
        $msg = $_REQUEST['CommitMessage'];

        try
        {
            $path = DOKU_INC;
            $repo = new GitRepo($path);
            $result = $repo->commit($msg);
            msg($result);
        }
        catch(Exception $e)
        {
            msg($e->getMessage());
        }
    }
    
    function makeReadOnly()
    {
        global $config_cascade;
        
        $AUTH_ACL = file($config_cascade['acl']['default']);

        $lines = array();
        foreach($AUTH_ACL as $line){
            if(strpos(strtolower($line), strtolower('@USER')) === FALSE)
            {
                $lines[] = $line;
                continue;
            }

            // Whatever the setting is, reset it to 1 (Read)
            $replaced = $line;
            for ($i = 2; $i <= 255; $i++) {
                $replaced = str_replace((string)$i, '1', $replaced);                
            }
            
            $lines[] = $replaced;
        }

        // save it
        io_saveFile($config_cascade['acl']['default'], join('',$lines));
    }
}