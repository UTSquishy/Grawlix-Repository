<!doctype html>
<html class="no-js" lang="en">
	<head>
	<!-- Project Wonderful Ad Box Loader -->
<script type="text/javascript">
   (function(){function pw_load(){
      if(arguments.callee.z)return;else arguments.callee.z=true;
      var d=document;var s=d.createElement('script');
      var x=d.getElementsByTagName('script')[0];
      s.type='text/javascript';s.async=true;
      s.src='//www.projectwonderful.com/pwa.js';
      x.parentNode.insertBefore(s,x);}
   if (window.attachEvent){
    window.attachEvent('DOMContentLoaded',pw_load);
    window.attachEvent('onload',pw_load);}
   else{
    window.addEventListener('DOMContentLoaded',pw_load,false);
    window.addEventListener('load',pw_load,false);}})();
</script>
<!-- End Project Wonderful Ad Box Loader -->
	<link href='http://fonts.googleapis.com/css?family=Rock+Salt' rel='stylesheet' type='text/css'> 
	<link href="//fonts.googleapis.com/css?family=Walter+Turncoat:400" rel="stylesheet" type="text/css">
	<link href='http://fonts.googleapis.com/css?family=PT+Mono' rel='stylesheet' type='text/css'>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<?=show('meta_head')?>
		<title><?=show('site_name')?> | <?=show('page_title')?></title>
		<link rel="stylesheet" href="/themes/stick_figure/base.css" />
		<link rel="stylesheet" href="/themes/stick_figure/theme.css" />
		<script src="/assets/scripts/modernizr.min.js"></script>
		<?=show('support_head')?>
		<?=show('favicons')?>

		<!--Google Analytics-->		
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-7539452-7', 'auto');
  ga('require', 'displayfeatures');
  ga('send', 'pageview');

</script>
		
	</head>
	<body role="document">
		<?=snippet('adminbar')?>
		<?=snippet('menu-widget')?><br>
		<div id="container"><div id="content">
		<header class="row" role="contentinfo" id="site-head">
			<div class="column text-center">
				<a href="<?=show('home_url')?>" title="<?=show('site_name')?>"><img role="banner" src="/assets/system/images/WereAllGame.png" alt="We're All Game Logo" /></a>				
			</div>
		</header>
		<?=snippet('menu-main')?>