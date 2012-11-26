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
class syntax_plugin_git_localstatus extends DokuWiki_Syntax_Plugin {
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
        $this->Lexer->addSpecialPattern('~~GITLocalStatus~~',$mode,'plugin_git_localstatus');
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
                $porcelain_status = $repo->get_status();
                if ($porcelain_status === "") {
                    if ($repo->ChangesAwaitingApproval())
                    {
                        $this->RenderAwaitingApproval($renderer, $repo);
                        return;
                    }                    
                    
                    $renderer->doc .= "No changes made in this workspace";
                }
                else {
                    $this->RenderAwaitingCommit($porcelain_status, $renderer, $repo);
                }
                
            }
            catch(Exception $e)
            {
                $renderer->doc .= 'Exception happened:<br/>';
                $renderer->doc .= $e->getMessage();
            }
 
            return true;
        }
        return false;
    }

    function RenderAwaitingCommit($status, &$renderer, &$repo)
    {
        $renderer->doc .= "<h3>Please provide a detailed summary of the changes to be submitted:</h3>";
        $renderer->doc .= '<form method="post">';
        $renderer->doc .= '  <textarea name="CommitMessage" style="width: 800px; height: 80px;" ></textarea></br>';
        $renderer->doc .= '  <input type="submit" name="cmd[commit]"  value="Submit for approval" />';
        $renderer->doc .= '</form>';
        $renderer->doc .= '  (Please note that submitting the changes for approval will make this workspace read-only)';                
        $renderer->doc .= '<br/><br/>';                
        
        $renderer->doc .= "<h3>Changes made in this workspace::</h3>";
        $renderer->doc .= "<table><tr><th>What happened</th><th>Wiki page</th><th>Changes</th></tr>";
        $files = explode("\n", $status);                   
        foreach ($files as $file)
        {                
            $renderer->doc .= "<tr><td>";
            if ($file === "") continue;
            
            $change = substr($file, 0, 2);
            switch($change)
            {
                case "??":
                    $renderer->doc .= "Added:";
                    break;
                case "M ":
                    $renderer->doc .= "Modified:";
                    break;
                case " M":
                    $renderer->doc .= "Modified:";
                    break;
                case "D ":
                    $renderer->doc .= "Removed:";
                    break;
                case " D":
                    $renderer->doc .= "Removed:";
                    break;
            }
            $renderer->doc .= "</td><td>";
            $file = substr($file, 2);
            $id = 'todo';
            $renderer->doc .=  '<a href="'.wl($id).'">'.$id.'</a>';
            $renderer->doc .= "</td><td>";
            $renderer->doc .= '   <form method="post">';
            $renderer->doc .= '      <input type="hidden" name="filename"  value="'.$file.'" />';
            $renderer->doc .= '      <input type="submit" value="View Changes" />';
            $renderer->doc .= '   </form>';
            $renderer->doc .= "</td>";
            $renderer->doc .= "<tr>";
        }
        $renderer->doc .= "</table>";

        $fileForDiff = trim($_REQUEST['filename']);                
        if ($fileForDiff !== '')
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
    
    function RenderAwaitingApproval(&$renderer, &$repo)
    {
        $log = $repo->get_log('-1');
        $commits = $repo->get_commits($log);

        $renderer->doc .= '<h2>Commit message:</h2>';        
        $message = $commits[0]['message'];
        $renderer->doc .= $message.'<br/>';        

        $renderer->doc .= "<div class=\"commit_div\" id='".$commit['hash']."' style=\"".$divVisibility." width: 100%;height: 175px;overflow:-moz-scrollbars-vertical;overflow-y:auto;\">";
        $hash = $commits[0]['hash'];
        $files = explode("\n", $repo->get_files_by_commit($hash));                   

        $renderer->doc .= "<table><tr><th>What happened</th><th>Wiki page</th><th>Changes</th></tr>";
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
            $id = 'todo';
            $renderer->doc .=  '<a href="'.wl($id).'">'.$id.'</a>';
            $renderer->doc .= "</td><td>";
            $renderer->doc .= '   <form method="post">';
            $renderer->doc .= '      <input type="hidden" name="filename"  value="'.$file.'" />';
            $renderer->doc .= '      <input type="hidden" name="hash"  value="'.$hash.'" />';                        
            $renderer->doc .= '      <input type="submit" value="View Changes" />';
            $renderer->doc .= '   </form>';
            $renderer->doc .= "</td>";
            $renderer->doc .= "<tr>";
        }
        $renderer->doc .= "</table>";
        $renderer->doc .= "</div>\n";        

        $fileForDiff = trim($_REQUEST['filename']);                
        if ($fileForDiff !== '')
        {                    
            // Get left text (Current)
            $left_filename = DOKU_INC.$fileForDiff;
            $left_filename = str_replace("/", "\\", $left_filename);
            $renderer->doc .= '<h2>Changes to: '.$fileForDiff.'</h2>';
            $l_text = $this->getFileContents($left_filename);
            
            // Get right text (Latest in GIT)
            $r_text = $repo->getFile($fileForDiff, 'HEAD~1');
            
            // Show diff
            $df = new Diff(explode("\n",htmlspecialchars($l_text)), explode("\n",htmlspecialchars($r_text)));
            $tdf = new TableDiffFormatter();                
            $renderer->doc .= '<div class="table">';
            $renderer->doc .= '<table class="diff diff_inline">';
            $renderer->doc .= $tdf->format($df);
            $renderer->doc .= '</table>';
            $renderer->doc .= '</div>';
        }                          

        $isAdmin = $this->isCurrentUserAnAdmin();
        if ($isAdmin)
        {
            $renderer->doc .= '<h2>TODO: Only show the bit below if you are an admin</h2>';        
            $renderer->doc .= '<form method="post">';
            $renderer->doc .= '   <input type="submit" name="cmd[revert commit]" value="Reject and revert Commit" />';
            $renderer->doc .= '   <input type="submit" name="cmd[push]" value="Push to live!" />';
            $renderer->doc .= '</form>';
        }
    }
    
    function isCurrentUserAnAdmin()
    {
        global $INFO;
        $grps=array();
        if (is_array($INFO['userinfo'])) {
            foreach($INFO['userinfo']['grps'] as $val) {
                $grps[]="@" . $val;
            }
        }

        
        return in_array("@admin", $grps);
    }
    
    function getFileContents($filename)
    {
        if (file_exists($filename) === false) return "Page does not exist in the wiki";
        
        // get contents of a file into a string
        $handle = fopen($filename, "r");
        $contents = fread($handle, filesize($filename));
        fclose($handle);
        
        return $contents;
    }
    
}
 
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
?>
