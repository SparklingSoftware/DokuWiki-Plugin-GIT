<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'action.php';

class action_plugin_git_javascript extends DokuWiki_Action_Plugin {
        
	function register(&$controller) {
		$controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, '_hook_header');
 	}

    function git_commit_select()
    {
		$script = '<script type="text/javascript">
            function ChangeGitCommit() {
                var hash = jQuery("#git_commit").val();
                jQuery(".commit_div").hide("fast"); 
                jQuery("#" + hash).show("fast"); 

                jQuery("#diff_table").hide("fast"); 
            } </script>';            
        return $script;
    }
    
	function _hook_header(&$event, $param) {

		$data = $this->git_commit_select();
        ptln($data);
        
		//$event->data['script'][] = array(
		//	'type' => 'text/javascript',
		//	'charset' => 'utf-8',
		//	'_data' => $data,
		//);
	}
}
