<?php
$config = parse_ini_file('../config.ini'); 
?>
<a href="https://slack.com/oauth/authorize?scope=commands&client_id=<?=$config['client_id']?>"><img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>
