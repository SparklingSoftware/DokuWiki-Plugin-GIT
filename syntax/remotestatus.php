<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@SparklingSoftware.com.au>
 */
 
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_PLUGIN.'git/lib/Git.php');
require_once(DOKU_INC.'/inc/DifferenceEngine.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_git_remotestatus extends DokuWiki_Syntax_Plugin {

    var $helper = null;

    function syntax_plugin_git_remotestatus (){
        $this->helper =& plugin_load('helper', 'git');
        if(!$this->helper) msg('Loading the git helper in the git_localstatus class failed.', -1);
    }

    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Stephan Dekker',
            'email'  => 'Stephan@SparklingSoftware.com.au',
            'date'   => @file_get_contents(dirname(__FILE__) . '/VERSION'),
            'name'   => 'Git Remote Status Plugin',
            'desc'   => 'xml',
            'url'    => 'http://dokuwiki.org/plugin:git',
        );
    }
 
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }
 
    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'normal';
    }
 
    /**
     * Where to sort in?
     */
    function getSort(){
        return 990;     //was 990
    }
 
 
    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('~~GITRemoteStatus~~',$mode,'plugin_git_remotestatus');
    }
 
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        $match_array = array();
        $match = substr($match,11,-2); //strip ~~REVISIONS: from start and ~~ from end
        // Wolfgang 2007-08-29 suggests commenting out the next line
        // $match = strtolower($match);
        //create array, using ! as separator
        $match_array = explode("!", $match);
        // $match_array[0] will be all, or syntax error
        // this return value appears in render() as the $data param there
        return $match_array;
    } 
    
    
    
    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        global $INFO, $conf;

        if($format == 'xhtml'){

            try
            {
                // If not logged in, go bugger off...
                if (is_array($INFO['userinfo']) === false)
                {
                    $renderer->doc .= "<br/><br/>You need to be logged in to view this page. Please login.";
                    return;
                }
                
                // Get GIT commits
                global $conf;
                $this->getConf('');

                $git_exe_path = $conf['plugin']['git']['git_exe_path'];        
                $datapath = $conf['savedir'];    
                
                $repo = new GitRepo($datapath);
                $repo->git_path = $git_exe_path;  
                $repo->fetch();
                $log = $repo->get_log();
                if ($log === "")
                {
                    $renderer->doc .= "There are no upstream updates for the current workspace. It's up to date!";
                    return true;
                }

                $waiting_to_commit = $repo->get_status();
                if ($waiting_to_commit !== "") 
                { 
                    $gitLocalStatusUrl = wl($conf['plugin']['git']['local_status_page'],'',true);
                    $renderer->doc .= 'Please commit local changes before you can merge upstream. <a href="'.$gitLocalStatusUrl.'">Local Changes<a/>';
                    return true;
                }

                $commits = $repo->get_commits($log);
                
                // Render select box
                $renderer->doc .= "Select a commit from upstream to view the changes contained in each: <br/>";
                $this->helper->render_commit_selector($renderer, $commits);                                
                $this->helper->render_changed_files_table($renderer, $commits, $repo);                    
                $this->helper->renderChangesMade($renderer, $repo, 'Merge upstream');                
                                               
                $renderer->doc .= '<form method="post">';
                $renderer->doc .= '  <input type="submit" name="cmd[ignore]"  value="Ignore this commit" />';
                $renderer->doc .= '  <input type="submit" name="cmd[merge]"  value="Merge All" />';
                $renderer->doc .= '</form>';
                $renderer->doc .= '<br/>';

            }
            catch(Exception $e)
            {
                $renderer->doc .= $e->getMessage();
            }
 
            return true;
        }
        return false;
    }
   
    //function render_commit_selector($renderer, $commits)
    //{
    //    $renderer->doc .= "<select id='git_commit' width=\"800\" style=\"width: 800px\" onchange='ChangeGitCommit();'>";
    //    $index = 1;
    //    foreach($commits as $commit)
    //    {
    //        $renderer->doc .= "<option value=\"".$commit['hash']."\">".$index." - ".$commit['message']."</option>";
    //        $index++;
    //    }
    //    $renderer->doc .= '</select>';    
    //}
    
    //function render_changed_files_table($renderer, $commits, $repo)
    //{
    //    $renderer->doc .= '<h3>This is the content of the selected commit:</h3>';
    //    $divVisibility = ""; // Make the first div visible as thats the first item in the select box
    //    foreach($commits as $commit)
    //    {
    //        $renderer->doc .= "<div class=\"commit_div\" id='".$commit['hash']."' style=\"".$divVisibility." width: 100%;height: 175px;overflow:-moz-scrollbars-vertical;overflow-y:auto;\">";
    //        $hash = $commit['hash'];
    //        $files = explode("\n", $repo->get_files_by_commit($hash));                   

    //        $renderer->doc .= "<table><tr><th>What happened</th><th>File</th><th>link</th></tr>";
    //        foreach ($files as $file)
    //        {                
    //            if ($file === "") continue;

    //            $renderer->doc .= "<tr><td>";
                
    //            $change = substr($file, 0, 1);
    //            switch($change)
    //            {
    //                case "A":
    //                    $renderer->doc .= "Added:";
    //                    break;
    //                case "M":
    //                    $renderer->doc .= "Modified:";
    //                    break;
    //                case "R":
    //                    $renderer->doc .= "Removed:";
    //                    break;
    //            }
    //            $renderer->doc .= "</td><td>";
    //            $file = substr($file, 2);
    //            $renderer->doc .= $file;
    //            $renderer->doc .= "</td><td>";
    //            $renderer->doc .= '   <form method="post">';
    //            $renderer->doc .= '      <input type="hidden" name="filename"  value="'.$file.'" />';
    //            $renderer->doc .= '      <input type="hidden" name="hash"  value="'.$commit['hash'].'" />';                        
    //            $renderer->doc .= '      <input type="submit" value="View Changes" />';
    //            $renderer->doc .= '   </form>';
    //            $renderer->doc .= "</td>";
    //            $renderer->doc .= "</tr>";
    //        }
    //        $renderer->doc .= "</table>";
    //        $renderer->doc .= "</div>\n";
    //        $divVisibility = " display:none;";
    //    }       
    //}
    
    function getFileContents($filename)
    {
        // get contents of a file into a string
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        fclose($handle);

        return $contents;
    }

}
 
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
