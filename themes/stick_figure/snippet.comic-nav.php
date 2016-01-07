		<div style="margin: 24px 0 10px 0;"class="row">
			<nav role="navigation" class="column text-center">
				<ul style="margin: 0 0 0 0; text-align: left;" align="left" class="button-group">
					<li>
						<a href="<?=show('comic_url_first')?>" title="First comic"><img src="/assets/system/images/First.png"/></a>
					</li>
					<li>
						<a href="<?=show('comic_url_prev')?>" title="Previous comic" rel="prev"><img src="/assets/system/images/Previous.png"/></a>
					</li>
				</ul>
				<ul style="margin: -86px 0 0 0; text-align: right;" align="right" class="button-group">
					<li>
						<a href="<?=show('comic_url_next')?>"title="Next comic" rel="next"><img src="/assets/system/images/Next.png"/></a>
					</li>
					<li>
						<a href="<?=show('comic_url_latest')?>" title="Latest comic"><img src="/assets/system/images/Last.png"/></a>
					</li>
				</ul>
			</nav>
		</div>


<?php 
$this->db->orderBy('sort_order','desc');
$last_page_info = $this->db->getOne('book_page');
$last_page_number = floor($last_page_info['sort_order']);
//print $last_page_number;
$host = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']; 
switch($host) 
{
case 'www.wereallgame.com/comic?sort=first': 
	echo ('<img id="NoFirst" style="position: absolute; z-index: +1;" src="/assets/system/images/NoFirst.png"/><img id="NoPrevious" style="position: absolute; z-index: +1;" src="/assets/system/images/NoPrevious.png"/>'); 
	break;
case 'www.wereallgame.com/comic?sort=1': 
	echo ('<img id="NoFirst" style="position: absolute; z-index: +1;" src="/assets/system/images/NoFirst.png"/><img id="NoPrevious" style="position: absolute; z-index: +1;" src="/assets/system/images/NoPrevious.png"/>'); 
	break;
case 'www.wereallgame.com/comic': 
	echo ('<img id="NoNext" style="position: absolute; z-index: +1;" src="/assets/system/images/NoNext.png"/><img id="NoLast" style="position: absolute; z-index: +1;" src="/assets/system/images/NoLast.png"/>');
	break;
case 'www.wereallgame.com/comic?sort=latest': 
	echo ('<img id="NoNext" style="position: absolute; z-index: +1;" src="/assets/system/images/NoNext.png"/><img id="NoLast" style="position: absolute; z-index: +1;" src="/assets/system/images/NoLast.png"/>'); 
	break;
case 'www.wereallgame.com/':
	echo ('<img id="NoNext" style="position: absolute; z-index: +1;" src="/assets/system/images/NoNext.png"/><img id="NoLast" style="position: absolute; z-index: +1;" src="/assets/system/images/NoLast.png"/>'); 
	break;
case ('www.wereallgame.com/comic?sort='.$last_page_number.''):
	echo ('<img id="NoNext" style="position: absolute; z-index: +1;" src="/assets/system/images/NoNext.png"/><img id="NoLast" style="position: absolute; z-index: +1;" src="/assets/system/images/NoLast.png"/>'); 
	break;
case 'wereallgame.com/comic?sort=first': 
	echo ('<img id="NoFirst" style="position: absolute; z-index: +1;" src="/assets/system/images/NoFirst.png"/><img id="NoPrevious" style="position: absolute; z-index: +1;" src="/assets/system/images/NoPrevious.png"/>'); 
	break;
case 'wereallgame.com/comic?sort=1': 
	echo ('<img id="NoFirst" style="position: absolute; z-index: +1;" src="/assets/system/images/NoFirst.png"/><img id="NoPrevious" style="position: absolute; z-index: +1;" src="/assets/system/images/NoPrevious.png"/>'); 
	break;
case 'wereallgame.com/comic': 
	echo ('<img id="NoNext" style="position: absolute; z-index: +1;" src="/assets/system/images/NoNext.png"/><img id="NoLast" style="position: absolute; z-index: +1;" src="/assets/system/images/NoLast.png"/>');
	break;
case 'wereallgame.com/comic?sort=latest': 
	echo ('<img id="NoNext" style="position: absolute; z-index: +1;" src="/assets/system/images/NoNext.png"/><img id="NoLast" style="position: absolute; z-index: +1;" src="/assets/system/images/NoLast.png"/>'); 
	break;
case 'wereallgame.com/':
	echo ('<img id="NoNext" style="position: absolute; z-index: +1;" src="/assets/system/images/NoNext.png"/><img id="NoLast" style="position: absolute; z-index: +1;" src="/assets/system/images/NoLast.png"/>'); 
	break;
case ('wereallgame.com/comic?sort='.$last_page_number.''):
	echo ('<img id="NoNext" style="position: absolute; z-index: +1;" src="/assets/system/images/NoNext.png"/><img id="NoLast" style="position: absolute; z-index: +1;" src="/assets/system/images/NoLast.png"/>'); 
	break;
default: 
	echo (''); 
}
?>