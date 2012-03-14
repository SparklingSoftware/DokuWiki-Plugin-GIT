<?php
/**
 * Plugin git: Allows GIT actions to be performed from DokuWiki
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Stephan Dekker <Stephan@StephanDekker.com>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'git/git.php');


/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_git extends DokuWiki_Admin_Plugin {

    var $status = 'Status Unknown';
    var $output = 'No actions performed yet...';
    var $commit_message = 'No Comment';
    var $git_repo_path = DOKU_INC;
    var $master_repo = '';
  
    /**
     * handle user request
     */
    function handle() {
 
      $this->status = $this->git_status();
      $this->commit_message = $_POST["CommitMessage"];
      $this->master_repo = $_POST["MasterRepo"];
    
      if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do

      $this->output = 'invalid';
      if (!checkSecurityToken()) return;
      if (!is_array($_REQUEST['cmd'])) return;
      
      // verify valid values
      switch (key($_REQUEST['cmd'])) {
        case 'commit' : 
          $this->output = $this->git_commit(); 
          $this->status = $this->git_status(); 
          break;
        case 'push' : 
          $this->output = $this->git_push(); 
          $this->status = $this->git_status(); 
          break;
      }      
    }
 
    /**
     * output appropriate html
     */
    function html() {
      ptln('<h1>'.$this->getLang('title').'</h1>');

      ptln('<p>'.$this->getLang('introduction').'</p>');
      
      ptln('<form action="'.wl($ID).'" method="post">');
      
      // output hidden values to ensure dokuwiki will return back to this plugin
      ptln('  <input type="hidden" name="do"   value="admin" />');
      ptln('  <input type="hidden" name="page" value="'.$this->getPluginName().'" />');
      formSecurityToken();

      ptln('<h1>Current status:</h1><br/><p>');
      echo 'I am: "'.shell_exec('whoami').'" <br/>';
      echo 'current status: <br/>'.$this->status;
      ptln('</p>');

      ptln('<h1>'.$this->getLang('title_commit').'</h1><br/><p>');
      ptln($this->getLang('lbl_commit_message').'<br/>');
      ptln('</p>');
      ptln('  <input type="text" size="50" name="CommitMessage" value="default" />');
      ptln('  <input type="submit" name="cmd[commit]"  value="'.$this->getLang('btn_commit').'" />');

      ptln('<h1>'.$this->getLang('title_push').'</h1><br/><p>');
      ptln($this->getLang('lbl_master_repo').'<br/>');
      ptln('</p>');
      ptln('  <input type="text" size="50" name="MasterRepo" value="" />');
      ptln('  <input type="submit" name="cmd[push]"  value="'.$this->getLang('btn_push').'" />');
      ptln('</form>');

      ptln('<h1>'.$this->getLang('title_output').'</h1><br/><p>');
      ptln($this->output);
      ptln('</p>');
    }


    function git_status() {
      
      $cmd = 'cd '.$this->git_repo_path.' && "C:\Program Files (x86)\Git\bin\git.exe" status 2>&1';

      $result = shell_exec($cmd);
      return $result;
    } 

    function git_commit() {
      
      $cmd = 'cd '.$this->git_repo_path.' && "C:\Program Files (x86)\Git\bin\git.exe" commit -am "'.$this->commit_message.'" 2>&1';

      $result = shell_exec($cmd);
      return $result;      
    } 

    function git_push() {
      $cmd = 'cd '.$this->git_repo_path.' && "C:\Program Files (x86)\Git\bin\git.exe" push '.$this->master_repo.' 2>&1';

      $result = shell_exec($cmd);
      return $result;
    } 
}