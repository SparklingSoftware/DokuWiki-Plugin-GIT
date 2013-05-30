<?php

/*
 * Git.php
 *
 * A PHP git library
 *
 * @package    Git.php
 * @version    0.1.1-a
 * @author     James Brumond
 * @copyright  Copyright 2010 James Brumond
 * @license    http://github.com/kbjr/Git.php
 * @link       http://code.kbjrweb.com/project/gitphp
 */

if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) die('Bad load order');

// ------------------------------------------------------------------------

/**
 * Git Interface Class
 *
 * This class enables the creating, reading, and manipulation
 * of git repositories.
 *
 * @class  Git
 */
class Git {

	/**
	 * Create a new git repository
	 *
	 * Accepts a creation path, and, optionally, a source path
	 *
	 * @access  public
	 * @param   string  repository path
	 * @param   string  directory to source
	 * @return  GitRepo
	 */
	public static function &create($repo_path, $source = null) {
		return GitRepo::create_new($repo_path, $source);
	}

	/**
	 * Open an existing git repository
	 *
	 * Accepts a repository path
	 *
	 * @access  public
	 * @param   string  repository path
	 * @return  GitRepo
	 */
	public static function open($repo_path) {
		return new GitRepo($repo_path);
	}

	/**
	 * Checks if a variable is an instance of GitRepo
	 *
	 * Accepts a variable
	 *
	 * @access  public
	 * @param   mixed   variable
	 * @return  bool
	 */
	public static function is_repo($var) {
		return (get_class($var) == 'GitRepo');
	}

}

// ------------------------------------------------------------------------

/**
 * Git Repository Interface Class
 *
 * This class enables the creating, reading, and manipulation
 * of a git repository
 *
 * @class  GitRepo
 */
class GitRepo {

	protected $repo_path = null;
    
    public function get_repo_path() {
        return $this->repo_path;
    }
        
    public $git_path = '/usr/bin/git';
    /* The git path defaults to the default location for linux, the consumer of this class needs to override with setting from config:
    
    function doSomeGitWork() {
       global $conf;
       $this->getConf('');
       $git_exe_path = $conf['plugin']['git']['git_exe_path'];
    
       $repo = new GitRepo(.....);
       $repo->git_path = $git_exe_path;
       .... do more work here ....
    }
    
     Make sure you enclose the path with double quotes for windows paths like this:
     $conf['plugin']['git']['git_exe_path'] = '"C:\Program Files (x86)\Git\bin\git.exe"';
    */

	/**
	 * Create a new git repository
	 *
	 * Accepts a creation path, and, optionally, a source path
	 *
	 * @access  public
	 * @param   string  repository path
	 * @param   string  directory to source
	 * @return  GitRepo
	 */
	public static function &create_new($repo_path, $source = null) {
		if (is_dir($repo_path) && file_exists($repo_path."/.git") && is_dir($repo_path."/.git")) {
			throw new Exception('"'.$repo_path.'" is already a git repository');
		} else {
			$repo = new self($repo_path, true, false);
			if (is_string($source))
				$repo->clone_from($source);
			else $repo->run('init');
			return $repo;
		}
	}

	/**
	 * Constructor
	 *
	 * Accepts a repository path
	 *
	 * @access  public
	 * @param   string  repository path
	 * @param   bool    create if not exists?
	 * @return  void
	 */
	public function __construct($repo_path = null, $create_new = false, $_init = true) {
		if (is_string($repo_path))
			$this->set_repo_path($repo_path, $create_new, $_init);
	}

	/**
	 * Set the repository's path
	 *
	 * Accepts the repository path
	 *
	 * @access  public
	 * @param   string  repository path
	 * @param   bool    create if not exists?
	 * @return  void
	 */
	public function set_repo_path($repo_path, $create_new = false, $_init = true) {
		if (is_string($repo_path)) {
			if ($new_path = realpath($repo_path)) {
				$repo_path = $new_path;
				if (is_dir($repo_path)) {
					if (file_exists($repo_path."/.git") && is_dir($repo_path."/.git")) {
						$this->repo_path = $repo_path;
					} else {
						if ($create_new) {
							$this->repo_path = $repo_path;
							if ($_init) $this->run('init');
						} else {
							throw new Exception('"'.$repo_path.'" is not a git repository');
						}
					}
				} else {
					throw new Exception('"'.$repo_path.'" is not a directory');
				}
			} else {
				if ($create_new) {
					if ($parent = realpath(dirname($repo_path))) {
						mkdir($repo_path);
						$this->repo_path = $repo_path;
						if ($_init) $this->run('init');
					} else {
						throw new Exception('cannot create repository in non-existent directory');
					}
				} else {
					throw new Exception('"'.$repo_path.'" does not exist');
				}
			}
		}
	}

	/**
	 * Tests if git is installed
	 *
	 * @access  public
	 * @return  bool
	 */
	public function test_git() {
		$descriptorspec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
		$pipes = array();
		$resource = proc_open($this->git_path, $descriptorspec, $pipes);

		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		foreach ($pipes as $pipe) {
			fclose($pipe);
		}

		$status = trim(proc_close($resource));
		return ($status != 127);
	}

	/**
	 * Run a command in the git repository
	 *
	 * Accepts a shell command to run
	 *
	 * @access  protected
	 * @param   string  command to run
	 * @return  string
	 */
	protected function run_command($command) {

		$descriptorspec = array(
			1 => array('pipe', 'w'),
			2 => array('pipe', 'w'),
		);
		$pipes = array();
		$resource = proc_open($command, $descriptorspec, $pipes, $this->repo_path);

		$stdout = stream_get_contents($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		foreach ($pipes as $pipe) {
			fclose($pipe);
		}

		$status = trim(proc_close($resource));
		if ($status) throw new Exception($stderr);

		return $stdout;
	}

	/**
	 * Run a git command in the git repository
	 *
	 * Accepts a git command to run
	 *
	 * @access  public
	 * @param   string  command to run
	 * @return  string
	 */
	public function run($command) {        
        $path = $this->git_path;        
		return $this->run_command($path." ".$command);
	}

	/**
	 * Runs a `git add` call
	 *
	 * Accepts a list of files to add
	 *
	 * @access  public
	 * @param   mixed   files to add
	 * @return  string
	 */
	public function add($files = "*") {
		if (is_array($files)) $files = '"'.implode('" "', $files).'"';
		return $this->run("add $files -v");
	}
    
    /**
     * Runs a `git log` call
     *
     * @access  public
     * @return  string
     */
	public function get_log($revision="..origin/master") {
		return $this->run("log ".$revision." --reverse");
	}
    
    /**
     * Retieves a specific file from GIT
     *
     * @access  public
     * @param   string   filename
     * @param   string   identifyer to id the branch/commit/position
     * @return  string
     */
	public function getFile($filename, $branch = 'HEAD') {

        $cmd = 'show '.$branch.':'.$filename;
        try
        {
    		return $this->run($cmd);
        }
        catch (Exception $e)
        {
            // msg('Exception during command: '.$cmd);
            // Not really an exception, if a new page has been added the exception is part of normal operation :-(
            return "Page not found";
        }
	}
    

    /**
     * Runs a `git status` call
     *
     * @access  public
     * @param   bool porcelain
     * @return  string
     */
	public function get_status($porcelain=true) {
        try
        {            
            if ($porcelain) return $this->run("status -u --porcelain");
            return $this->run("status");
        }
        catch(Exception $e)
        {
            return $e->getMessage();  
        }
	}
    
    function LocalCommitsExist() {
        $status = $this->get_status(false);
        $pos = strpos($status, 'Your branch is ahead of');
        return $pos > 0;            
    }

    // As suggested by: https://gist.github.com/961488
    function &get_commits($log)
    {
        $output = explode("\n", $log);
        $history = array();
        foreach($output as $line)
        {
            if(strpos($line, 'commit')===0){
                // Skip merges
                if (strpos($line, 'merge') > 0) continue;
                if(!empty($commit)){
                    array_push($history, $commit);	
                    unset($commit);
                }
                $commit['hash'] = trim(substr($line, strlen('commit')));
            }
            else if(strpos($line, 'Author')===0){
                $commit['author'] = trim(substr($line, strlen('Author:')));
            }
            else if(strpos($line, 'Date')===0){
                $commit['date'] = trim(substr($line, strlen('Date:')));
            }
            else{	
                if(isset($commit['message']))
                    $commit['message'] .= trim($line);
                else
                    $commit['message'] = trim($line);
            }
        }
        if(!empty($commit)) {
            array_push($history, $commit);
        }
        
        return $history;
    }


        /**
     * Returns the names of the files that have changed in a commit
     *
     * @access  public
     * @param   string  hash to get the changes for
     * @return  string
     */
	public function get_files_by_commit($hash) {
		return $this->run("diff-tree -r --name-status --no-commit-id ".$hash);
	}
    
	/**
	 * Runs a `git commit` call
	 *
	 * Accepts a commit message string
	 *
	 * @access  public
	 * @param   string  commit message
	 * @return  string
	 */
	public function commit($message = "blank") {
        try {
            $cmd = "gc";
            $fullcmd = "cd \"".$this->repo_path."\" && ".$this->git_path." ".$cmd;
            $this->run_command($fullcmd);

            $cmd = "prune";
            $fullcmd = "cd \"".$this->repo_path."\" && ".$this->git_path." ".$cmd;
            $this->run_command($fullcmd);
            
            $cmd = "add . -A";
            $fullcmd = "cd \"".$this->repo_path."\" && ".$this->git_path." ".$cmd;
            $this->run_command($fullcmd);
        
            $cmd = "commit -a -m \"".$message."\"";
            $fullcmd = "cd \"".$this->repo_path."\" && ".$this->git_path." ".$cmd;
		    $this->run_command($fullcmd);
            return true;
        }
        Catch (Exception $e)
        {
            msg($e->getMessage());
            return false;
        }
	}
    
	/**
	 * Runs a `git clone` call to clone the current repository
	 * into a different directory
	 *
	 * Accepts a target directory
	 *
	 * @access  public
	 * @param   string  target directory
	 * @return  string
	 */
	public function clone_to($target) {
		return $this->run("clone --local ".$this->repo_path." $target");
	}

	/**
	 * Runs a `git clone` call to clone a different repository
	 * into the current repository
	 *
	 * Accepts a source directory
	 *
	 * @access  public
	 * @param   string  source directory
	 * @return  string
	 */
	public function clone_from($source) {

    try 
        {
            $cmd = "clone -q $source \"".$this->repo_path."\"";
            $fullcmd = "cd \"".$this->repo_path."\" && ".$this->git_path." ".$cmd;
            // msg('Full command: '.$fullcmd);
            $this->run_command($fullcmd);
        }
        Catch (Exception $e)
        {
            msg($e->getMessage());
        }
	}

	/**
	 * Runs a `git clone` call to clone a remote repository
	 * into the current repository
	 *
	 * Accepts a source url
	 *
	 * @access  public
	 * @param   string  source url
	 * @return  string
	 */
	public function clone_remote($source) {
		return $this->run("clone $source ".$this->repo_path);
	}

	/**
	 * Runs a `git clean` call
	 *
	 * Accepts a remove directories flag
	 *
	 * @access  public
	 * @param   bool    delete directories?
	 * @return  string
	 */
	public function clean($dirs = false) {
		return $this->run("clean".(($dirs) ? " -d" : ""));
	}

	/**
	 * Runs a `git branch` call
	 *
	 * Accepts a name for the branch
	 *
	 * @access  public
	 * @param   string  branch name
	 * @return  string
	 */
	public function create_branch($branch) {
		return $this->run("branch $branch");
	}

	/**
	 * Runs a `git branch -[d|D]` call
	 *
	 * Accepts a name for the branch
	 *
	 * @access  public
	 * @param   string  branch name
	 * @return  string
	 */
	public function delete_branch($branch, $force = false) {
		return $this->run("branch ".(($force) ? '-D' : '-d')." $branch");
	}

	/**
	 * Runs a `git branch` call
	 *
	 * @access  public
	 * @param   bool    keep asterisk mark on active branch
	 * @return  array
	 */
	public function list_branches($keep_asterisk = false) {
		$branchArray = explode("\n", $this->run("branch"));
		foreach($branchArray as $i => &$branch) {
			$branch = trim($branch);
			if (! $keep_asterisk)
				$branch = str_replace("* ", "", $branch);
			if ($branch == "")
				unset($branchArray[$i]);
		}
		return $branchArray;
	}

	/**
	 * Returns name of active branch
	 *
	 * @access  public
	 * @param   bool    keep asterisk mark on branch name
	 * @return  string
	 */
	public function active_branch($keep_asterisk = false) {
		$branchArray = $this->list_branches(true);
		$active_branch = preg_grep("/^\*/", $branchArray);
		reset($active_branch);
		if ($keep_asterisk)
			return current($active_branch);
		else
			return str_replace("* ", "", current($active_branch));
	}

	/**
	 * Runs a `git checkout` call
	 *
	 * Accepts a name for the branch
	 *
	 * @access  public
	 * @param   string  branch name
	 * @return  string
	 */
	public function checkout($branch) {
		return $this->run("checkout $branch");
	}


    /**
     * Runs a `git merge` call
     *
     * Accepts a name for the branch to be merged
     *
     * @access  public
     * @param   string $branch
     * @return  string
     */
    public function merge($branch, $msg = "")
    {
        if ($msg == "") return $this->run("merge $branch --no-ff");
        return $this->run("merge $branch --no-ff -m ".$msg);
    }

    /**
     * Runs a `git reset` call
     *
     * Reverts the last commit, leaving the local files intact
     *
     * @access  public
     * @return  string
     */
    public function revertLastCommit()
    {
        return $this->run("reset --soft HEAD~1");
    }


    /**
     * Runs a git fetch on the current branch
     *
     * @access  public
     * @return  string
     */
    public function fetch()
    {
        return $this->run("fetch");
    }

    /**
     * Tests whether origin points to a valid repo
     *
     * @access  public
     * @return  string
     */
    public function test_origin()
    {
        try
        {
           $this->run("fetch --dry-run");
           return true;
        }
        catch (Exception $e)
        {
           return false;
        }
    }


    /**
     * Add a new tag on the current position
     *
     * Accepts the name for the tag and the message
     *
     * @param string $tag
     * @param string $message
     * @return string
     */
    public function add_tag($tag, $message = null)
    {
        if ($message === null) {
            $message = $tag;
        }
        return $this->run("tag -a $tag -m $message");
    }


    /**
     * Push specific branch to a remote
     *
     * @return string
     */
    public function push()
    {
        $cmd = 'push';
        return $this->run($cmd);
    }

    /**
     * Pull specific branch from remote
     *
     * Accepts the name of the remote and local branch
     *
     * @param string $remote
     * @param string $branch
     * @return string
     */
    public function pull($remote, $branch)
    {
        return $this->run("pull $remote $branch");
    }
    
    /**
     * Sets the project description.
     *
     * @param string $new
     */
    public function set_description($new)
    {
        file_put_contents($this->repo_path."/.git/description", $new);
    }

    /**
     * Gets the project description.
     *
     * @return string
     */
    public function get_description()
    {
        return file_get_contents($this->repo_path."/.git/description");
    }
}

/* End Of File */
