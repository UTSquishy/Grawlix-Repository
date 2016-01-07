<?php
$info = $this->showArchiveNav; // Get info from database via page class
if ( $info ) : ?>
	<nav role="navigation">
		<ul class="text-center">
			<li>
				<a href="<?=show('archive_url_prev')?>" class="<?=show('archive_css_prev')?>" title="Previous page" rel="prev">
					<span>Previous</span>
					<img src="/assets/system/images/button-back.svg" alt="previous" width="128" height="40" />
				</a>
			</li>
			<li>
				<a href="<?=show('archive_url')?>" class="<?=show('archive_css_next')?>" title="Main page">
					<span>Main</span>
					<img src="/assets/system/images/button-main.svg" alt="main" width="128" height="40" />
				</a>
			</li>
			<li>
				<a href="<?=show('archive_url_next')?>" class="<?=show('archive_css_next')?>" title="Next page" rel="next">
					<span>Next</span>
					<img src="/assets/system/images/button-next.svg" alt="next" width="128" height="40" />
				</a>
			</li>
		</ul>
	</nav>
<?php endif;
