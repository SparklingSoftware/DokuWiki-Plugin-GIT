<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'git/lib/Git.php');

class helper_plugin_git extends DokuWiki_Plugin {

    function getMethods(){
        $result = array();
        $result[] = array(
          'name'   => 'cloneRepo',
          'desc'   => 'Creates a new clone of a repository',
          'params' => array(
            'destination' => 'string'),
          'return' => array('result' => 'array'),
        );

        // and more supported methods...
        return $result;
    }

    
    function cloneRepo($destination) {
        global $conf;
        
        //$origin = $conf['plugin']['git']['origin'];
        $origin = '"E:\Stephan\ALM Community\Technical\WebSites\InstantALM-dev\origin"';
        
        try
        {
            msg('Cloning from: '.$origin.' to: '.$destination);        
            $repo = new GitRepo($destination, true, false);
            msg($repo->get_repo_path());
            $repo->clone_from($origin);
            //$repo->checkout('');

        }
        catch (Exception $e)
        {
            msg($e->getMessage());
        }
    }
}
