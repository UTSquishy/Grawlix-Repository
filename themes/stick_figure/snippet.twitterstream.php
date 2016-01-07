<?php
$info = $this->widgets['twitter_timeline'];
if ( $info ) : ?>
<?=$info['link']?>
		<h4>Recent tweets</h4>
		<div id="tweets"></div>
		<script>
		var config = {
			"id": '<?=$info['widget']?>',
			"domId": 'tweets',
			"maxTweets": 3,
			"enableLinks": true,
			"showUser": true,
			"showTime": true,
			"showRetweet": false
		};
		twitterFetcher.fetch(config);
		</script>
<?php endif;
