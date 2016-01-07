<?php
$info = $this->showArchiveNav; // Get info from database via page class
if ( $info ) : ?>
		<nav role="navigation" class="text-center">
			<ul class="button-group">
				<li>
					<a class="button <?=show('archive_css_prev')?>"  href="<?=show('archive_url_prev')?>"title="Previous page" rel="prev">
						<i class="fa fa-backward"></i> Previous
					</a>
				</li>
				<li>
					<a class="button <?=show('archive_css_next')?>" href="<?=show('archive_url')?>" title="Main page">
						Main
					</a>
				</li>
				<li>
					<a class="button <?=show('archive_css_next')?>" href="<?=show('archive_url_next')?>" title="Next page" rel="next">
						Next <i class="fa fa-forward"></i>
					</a>
				</li>
			</ul>
		</nav>
<?php endif;
