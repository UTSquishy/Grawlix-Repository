		<?=snippet('header')?>
		<!-- template: page.comic -->
		<main>
						<article class="row" itemscope itemtype="http://schema.org/CreativeWork" id="comic">
				<div class="borderDivider">
					<div style="margin-top: -0.5em; margin-bottom: -0.35em;"><a style="color: #000000;" href="<?=show('permalink')?>" rel="bookmark"><?=show('page_title')?></a></div>
					<!--<class="column medium-6 small-text-left medium-text-right"><?=show('date_publish')?>-->
				</div>
				<figure class="column text-center">
					<a href="<?=show('comic_url_next')?>" rel="next"><?=show('comic_image')?></a>
				</figure>
			</article>
			<div style="margin: 0px;"><?=snippet('comic-nav')?></div>
			<div><?=snippet('share')?></div>
			<div class="borderDivider" align="right" style="font-family: 'Walter Turncoat', cursive; font-size: medium; font-weight: bold;">
			<div style="margin: 0 25px 0 0;">Contact Me: <a href="mailto:WereAllGame@gmail.com">WereAllGame@gmail.com</a></div></div>			
			<!-- blog post and sidebar -->
			<div class="row">
			<div class="borderDividerBottomOnly"><div style="margin: 0 0 -0.35em 0;">Author's Comments</div></div>			
			<article style="margin-bottom: 0px;" class="column medium-8" role="article" itemscope itemtype="https://schema.org/BlogPosting" id="blog_post">
					</br><h3 itemprop="headline"><?=show('blog_title')?></h3>
					<div itemprop="articleBody">
						<h4 style="line-height: 175%;"><?=show('blog_post')?></h4>
						<h4 class="text-right" role="complementary" class="meta">Posted <?=show('date_publish')?>&mdash;<span style="font-family: 'Rock Salt', cursive; font-size: medium; font-weight: bold;"><?=show('artist_name')?></span></h4><br>
					</div>
					<?=snippet('comments')?>
					<div><?=snippet('transcript')?></div>
				</article>
				<div style="text-align: center; margin: 25px 0 0 0;" class="column medium-4">
					<?=snippet('follow')?>
					<?=snippet('twitterstream')?>
					<!--ads--><?=show_ad('default') ?> <?=show_ad('PW') ?>
						<div style="margin: 0 4.5em;">
						<!-- Project Wonderful Ad Box Code -->
						<div id="pw_adbox_77806_3_0"></div>
						<script type="text/javascript"></script>
						<noscript><map name="admap77806" id="admap77806"><area href="http://www.projectwonderful.com/out_nojs.php?r=0&c=0&id=77806&type=3" shape="rect" coords="0,0,160,600" title="" alt="" target="_blank" /></map>
						<div style="width:160px;border-style:none;background-color:#ffffff;"><img src="http://www.projectwonderful.com/nojs.php?id=77806&type=3" style="width:160px;height:600px;border-style:none;" usemap="#admap77806" alt="" /></div><div style="background-color:#ffffff;" colspan="1"><a style="font-size:10px;color:#0000ff;text-decoration:none;line-height:1.2;font-weight:bold;font-family:Tahoma, verdana,arial,helvetica,sans-serif;text-transform: none;letter-spacing:normal;text-shadow:none;white-space:normal;word-spacing:normal;" href="http://www.projectwonderful.com/advertisehere.php?id=77806&type=3" target="_blank">Ads by Project Wonderful!  Your ad here, right now: $0</a></div>
						</noscript>
						<!-- End Project Wonderful Ad Box Code -->
						</div>
				</div>
			</div>
			<!--<div style="margin: -100px 0 0 0;"><?//=snippet('comic-nav')?></div>-->
<?php
$host = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
if($host == 'wereallgame.com/' or'www.wereallgame.com/') 
{
    echo ('<br><div class="center">
<div class="borderDivider"><div style="margin-top: -0.5em; margin-bottom: -0.35em;">The Web Series</div></div>
	
	<p>When I was a kid, there were a ton of values based programs on television. I would venture to say that, a lot of that programming helped contribute to my being a good person.
		There are the obvious examples: <em>Mr. Rogers</em>, <em>School House Rock</em>, <em>Reading Rainbow</em>. . . But there were also plenty of others, that you may not have thought to categorize
		that way: <em>Transformers</em>, <em>G.I. Joe</em>, . . . <em>Dungeons and Dragons</em>. They all contained messages about being good to one another, being truthful, honest, honorable, etc.
		--They were pretty overt about it, and it was contained in programming that was adventurous and fun.</p>
		
	<p>I would like to create an 80&#39;s style animated show that is values based and entertaining for kids, and adults.</p>
	
	<p><em>We&#39;re All Game</em> features an ensemble cast of School Age friends learning how to be better people through gaming, and learning to be better gamers through lessons learned in life.</p>
	</br>
		
	<div class="borderDivider"><div style="margin-top: -0.5em; margin-bottom: -0.35em;">The Web Comic</div></div>

	<p>Right now I&#39;m just one person, with no budget--<em>We&#39;re All Game</em> was conceptualized as an animated series, but for now I&#39;m going to start with a web comic.</p>
	</br>
	
	<!--<div class="borderDivider"><div style="margin-top: -0.5em; margin-bottom: -0.35em;">&nbsp;</div></div>-->
	</div>');
}

else 
{
	echo ('');
}

?>
	</div></div>	


		</main>
		<?=snippet('footer')?>			</div></div><br>

