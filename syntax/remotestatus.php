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
                // Get GIT commits
                $repo = new GitRepo(DOKU_INC);
                $repo->fetch();
                $log = $repo->get_log();
                if ($log === "")
                {
                    $renderer->doc .= "Current wiki is up to date with Master.";
                    return true;
                }
                $commits = $repo->get_commits($log);
                
                // Render select box
                $renderer->doc .= "Select something from the Master to merge into the local instance: <br/>";
                $renderer->doc .= "<select id='git_commit' width=\"300\" style=\"width: 300px\" onchange='ChangeGitCommit();'>";
                $index = 1;
                foreach($commits as $commit)
                {
                    $renderer->doc .= "<option value=\"".$commit['hash']."\">".$index." - ".$commit['message']."</option>";
                    $index++;
                }
                $renderer->doc .= '</select>';
                $renderer->doc .= '<form method="post">';
                $renderer->doc .= '  <input type="hidden"  name="hash"  value="'.$commits[0]['hash'].'" />';
                $renderer->doc .= '  <input type="submit" name="cmd[merge]"  value="Merge" />';
                $renderer->doc .= '</form>';
                $renderer->doc .= '<br/>';
                
                $renderer->doc .= '<h3>This is the content of the selected change set:</h3>';
                $divVisibility = ""; // Make the first div visible as thats the first item in the select box
                foreach($commits as $commit)
                {
                    $renderer->doc .= "<div class=\"commit_div\" id='".$commit['hash']."' style=\"".$divVisibility." width: 100%;height: 175px;overflow:-moz-scrollbars-vertical;overflow-y:auto;\">";
                    $hash = $commit['hash'];
                    $files = explode("\n", $repo->get_files_by_commit($hash));                   

                    $renderer->doc .= "<table><tr><th>What happened</th><th>File</th><th>link</th></tr>";
                    foreach ($files as $file)
                    {                
                        $renderer->doc .= "<tr><td>";
                        if ($file === "") continue;
                        
                        $change = substr($file, 0, 1);
                        switch($change)
                        {
                           case "A":
                               $renderer->doc .= "Added:";
                               break;
                           case "M":
                               $renderer->doc .= "Modified:";
                               break;
                           case "R":
                               $renderer->doc .= "Removed:";
                               break;
                        }
                        $renderer->doc .= "</td><td>";
                        $file = substr($file, 2);
                        $renderer->doc .= $file;
                        $renderer->doc .= "</td><td>";
                        $renderer->doc .= '   <form method="post">';
                        $renderer->doc .= '      <input type="hidden" name="filename"  value="'.$file.'" />';
                        $renderer->doc .= '      <input type="hidden" name="hash"  value="'.$commit['hash'].'" />';                        
                        $renderer->doc .= '      <input type="submit" value="View Changes" />';
                        $renderer->doc .= '   </form>';
                        $renderer->doc .= "</td>";
                        $renderer->doc .= "<tr>";
                    }
                    $renderer->doc .= "</table>";
                    $renderer->doc .= "</div>\n";
                    $divVisibility = " display:none;";
                }       
                               
                $fileForDiff = trim($_REQUEST['filename']);                
                $hashForDiff = trim($_REQUEST['hash']);                
                if ($fileForDiff !== '' && $hashForDiff !== '')
                {                    
                    // Get left text (Current)
                    $left_filename = DOKU_INC.$fileForDiff;
                    $left_filename = str_replace("/", "\\", $left_filename);
                    $renderer->doc .= '<h2>Changes to: '.$fileForDiff.'</h2>';
                    $l_text = $this->getFileContents($left_filename);
                    
                    // Get right text (Latest in GIT)
                    $r_text = $repo->getFile($fileForDiff, 'HEAD');
                    
                    // Show diff
                    $df = new Diff(explode("\n",htmlspecialchars($l_text)), explode("\n",htmlspecialchars($r_text)));
                    $tdf = new TableDiffFormatter();                
                    $renderer->doc .= '<div class="table">';
                    $renderer->doc .= '<table class="diff diff_inline">';
                    $renderer->doc .= $tdf->format($df);
                    $renderer->doc .= '</table>';
                    $renderer->doc .= '</div>';
                }                          
            }
            catch(Exception $e)
            {
                $renderer->doc .= $e->getMessage();
            }
 
            return true;
        }
        return false;
    }
    
    
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
