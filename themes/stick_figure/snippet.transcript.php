<?php
$transcript = show('transcript');
$host = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
/*print "$host";*/
if($host !== 'grawlix.wereallgame.com/' &&  $transcript) 
{
    echo ('<article role="text" id="transcript"><div class="borderDividerBottomOnly"><div style="margin: 0 0 -0.35em 0;">Comic transcript</div></div><div>'.$transcript.'</div></article>');
}

else 
{
	echo ('');
}

?>
