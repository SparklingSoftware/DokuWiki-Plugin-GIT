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
                $waiting_to_commit = $repo->get_status();

                if ($waiting_to_commit !== "") 
                {
                    // There are changes waiting to be committed to the local repo
                    $this->renderCommitMessage($renderer);
                    $files = explode("\n", $waiting_to_commit);                   
                    $this->renderChangesMade($renderer, $files, $repo);
                    return;
                }
                
                // No local changes to be committed. Are we ready to push up?
                if ($repo->ChangesAwaitingApproval())
                {
                    // Get the files from the latest commit. There can only be one commit as the Wiki will become read-only after a commit
                    $log = $repo->get_log('-1');
                    $commits = $repo->get_commits($log);
                    $hash = $commits[0]['hash'];
                    $files = explode("\n", $repo->get_files_by_commit($hash));                   

                    $renderer->doc .= '<h3>Commit message:</h3>';        
                    $renderer->doc .= $message.'<textarea>';        
                    $renderer->doc .= $commits[0]['message'];
                    $renderer->doc .= '</textarea><br/>';        

                    // Show the approval sections
                    $this->renderChangesMade($renderer, $files, $repo);
                    $this->renderAdminApproval($renderer);
                    return;
                }                    

                // None of the above, so there is nothing to do... bugger...
                $renderer->doc .= "No changes made in this workspace";
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
    
    function renderCommitMessage(&$renderer)
    {
        $renderer->doc .= "<h3>Please provide a detailed summary of the changes to be submitted:</h3>";
        $renderer->doc .= '<form method="post">';
        $renderer->doc .= '  <textarea name="CommitMessage" style="width: 800px; height: 80px;" ></textarea></br>';
        $renderer->doc .= '  <input type="submit" name="cmd[commit]"  value="Submit for approval" />';
        $renderer->doc .= '</form>';
        $renderer->doc .= '  (Please note that submitting the changes for approval will make this workspace read-only)';                
        $renderer->doc .= '<br/><br/>';                
    }
    
    function renderChangesMade(&$renderer, &$files, &$repo)
    {
        global $INFO;
        global $ID;
        global $conf;
        
        $renderer->doc .= "<h3>Changes made in this workspace::</h3>";
        $renderer->doc .= "<table><tr><th>What happened</th><th>Wiki page</th><th>Changes</th></tr>";
        foreach ($files as $file)
        {               
            if ($file === "") continue;

            //            $skipNonWikiPages = $conf['plugin']['git']['HideNonWikiPages'];
            //$skipNonWikiPages = true;
            //if (($skipNonWikiPages === true) && (strpos($file, 'data/pages') === false))
            //    continue;

            $renderer->doc .= "<tr><td>";
            
            $change = substr($file, 0, 2);
            if (strpos($change, '?') !== false)
                $renderer->doc .= "Added:";
            else if (strpos($change, 'M') !== false)
                $renderer->doc .= "Modified:";
            else if (strpos($change, 'A') !== false)
                $renderer->doc .= "Added:";
            else if (strpos($change, 'D') !== false)
                $renderer->doc .= "Removed:";
            else if (strpos($change, 'R') !== false)
                $renderer->doc .= "Removed:";
            
            $renderer->doc .= "</td><td>";
            $file = trim(substr($file, 2));
            $page = $this->getPageFromFile($file);            
            $renderer->doc .=  '<a href="http://localhost:8002/doku.php?id='.$page.'">'.$page.'</a>';
            $renderer->doc .= "</td><td>";
            // Is this a wiki page
            if (strpos($file, 'data/page') !== false)
            {                
                // only show diff buttens for wiki pages...
                $renderer->doc .= '   <form method="post">';
                $renderer->doc .= '      <input type="hidden" name="filename"  value="'.$file.'" />';
                $renderer->doc .= '      <input type="submit" value="View Changes" />';
                $renderer->doc .= '   </form>';
            }
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

    function renderAdminApproval(&$renderer)
    {
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
    
    function getPageFromFile($file)
    {
        // If it's not a wiki page, just return the normal filename
        if (strpos($file, 'data/pages') === false) return $file;

        // Replace all sorts of stuff so it makes sense to non-technical users.
        $page = str_replace('data/pages/', ':', $file);
        $page = str_replace('.txt', '', $page);
        $page = str_replace('/', ':', $page);
        $page = trim($page);
        
        return $page;
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
