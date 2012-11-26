<?php
/**
 * Default settings for the gitstatus plugin
 *
 * @author Wolfgang Gassler <wolfgang@gassler.org>
 */

$conf['GitpushAfterCommit'] = 0;
$conf['GitperiodicPull'] = 0;
$conf['GitperiodicMinutes'] = 60;
$conf['GitcommitPageMsg']	= '%page% changed with %summary% by %user%';
$conf['GitcommitPageMsgDel']	= '%page% deleted %summary% by %user%';
$conf['GitcommitMediaMsg']	= '%media% uploaded by %user%';
$conf['GitcommitMediaMsgDel']	= '%media% deleted by %user%';
$conf['GitrepoPath']	= $GLOBALS['conf']['savedir'];
$conf['GitaddParams'] = '';
