<?php

/**
 * Specific to front-end archives
 */

class GrlxPage_Archive extends GrlxPage {

	protected $xml;
	protected $xmlVersion;
	protected $currentList;
	protected $markerType;
	protected $chapterNum;
	protected $range;
	protected $layout;
	protected $meta;
	protected $showArchiveNav;

	/**
	 * Set defaults, etc.
	 */
	public function __construct() {
		parent::__construct(func_get_args());
		$this->markerType['id'] = 1; // Hard-coded to chapter markers for now
		$this->template = $this->templateFileList['archive'];
		if ( $this->path[1] != $this->bookInfo['url'] ){
			$this->getBookInfo('url');
		}
		$this->theme['tone_id'] = $this->bookInfo['tone_id'];
		$this->pageInfo['permalink'] = $this->path[1].$this->path[2];
		$this->getChapterNum();
		if ( $this->chapterNum ) {
			$this->pageInfo['permalink'] .= '/'.$this->chapterNum;
		}
		if ( substr($this->bookInfo['options'], 0,5) == '<?xml' ) {
			$args['stringXML'] = $this->bookInfo['options'];
			$this->xml = new GrlxXMLPublic($args);
			$this->xmlVersion = $this->xml->version;
			$this->routeVersion();
			$this->routeBehaviorOptions();
		}
	}

	/**
	 * Get the chapter number from a request formatted “/comic/archive?1” or “/comic/options/archive/1”
	 */
	protected function getChapterNum() {
		if ( $this->query ) {
			$id = array_keys($this->query);
			$this->chapterNum = $id[0];
		}
		else {
			$x = $this->path[3];
			remove_first_slash($x);
			if ( is_numeric($x) ) {
				$this->chapterNum = $x;
			}
		}
	}

	/**
	 * Route the page build according to xml version number
	 */
	protected function routeVersion() {
		switch ( $this->xmlVersion ) {
			case '1.1':
				$this->layout['behavior'] = $this->xml->getValue('/archive/behavior');
				$this->layout['pages'] = $this->xml->getValue('/archive/page/layout');
				$this->layout['chapters'] = $this->xml->getValue('/archive/chapter/layout');
				$this->meta['pages'] = $this->xml->getClones('/archive/page','option');
				$this->meta['chapters'] = $this->xml->getClones('/archive/chapter','option');
				break;
			default:
				echo('Incompatible info');
				break;
		}
	}

	/**
	 * Action for the different layout settings
	 */
	protected function routeBehaviorOptions() {
		switch ( $this->layout['behavior'] ) {
			case 'single':
				unset($this->chapterNum);
				$this->getPages();
				break;
			case 'multi':
				$this->layout['behavior'] = 'multi';
				if ( is_numeric($this->chapterNum) ) {
					$this->getPageRange();
					$this->getPages();
				}
				else {
					$this->getPages($this->markerType['id']);
				}
				break;
		}
	}

	/**
	 * Accessory to getPages()
	 * Get sort position of requested chapter and the one that follows
	 */
	protected function getPageRange() {
		$cols = array(
			'id',
			'sort_order'
		);
		$limit = array(
			$this->chapterNum - 1,
			2
		);
		$result = $this->db
			->where('marker_id',1,'>=')
			->orderBy('sort_order','ASC')
			->get('book_page',$limit,$cols);
		$this->range['start'] = $result[0]['sort_order'];
		$this->range['end'] = $result[1]['sort_order'];
	}

	/**
	 * Get list of chapters and/or pages in sequential order
	 *
	 * @param integer $typeID - use to restrict list to rows with this as marker_type_id for multi page view
	 */
	protected function getPages($typeID=null) {
		// Default items
		$bpCol[] = 'bp.id AS page_id';
		$bpCol[] = 'bp.sort_order';
		$bpCol[] = 'bp.marker_id';
		$bpCol[] = 'mrk.marker_type_id';
		$bpCol[] = 'mrk.type_title';
		$bpCol[] = 'mrk.rank';
		// Page options
		if ( in_array('title',$this->meta['pages']) ) {
			$bpCol[] = 'bp.title AS page_title';
		}
		if ( in_array('description',$this->meta['pages']) ) {
			$bpCol[] = 'bp.description AS page_description';
		}
		if ( in_array('date',$this->meta['pages']) ) {
			$bpCol[] = 'bp.date_publish';
		}
		if ( in_array('image',$this->meta['pages']) ) {
			$bpCol[] = 'pImg.url AS page_img';
			$bpCol[] = 'pImg.description AS page_img_alt';
			$pImage = $this->db->subQuery('pImg');
			$pImage->join('image_reference ir','im.image_reference_id = ir.id','INNER');
			$pImage->where('im.rel_type','page');
			$pImage->where('im.sort_order',1);
			$pImage->get('image_match im',null,'rel_id,url,description');
			$this->db->join($pImage,'pImg.rel_id = bp.id','LEFT');
		}
		// Chapter options
		$mCol[] = 'm.id AS marker_id';
		$mCol[] = 'm.marker_type_id';
		$mCol[] = 'mt.title AS type_title';
		$mCol[] = 'mt.rank';
		if ( in_array('title',$this->meta['chapters']) ) {
			$bpCol[] = 'mrk.marker_title';
			$mCol[] = 'm.title AS marker_title';
		}
		if ( in_array('description',$this->meta['chapters']) ) {
			$bpCol[] = 'mrk.marker_description';
			$mCol[] = 'm.description AS marker_description';
		}
		$marker = $this->db->subQuery('mrk');
		$marker->join('marker_type mt','m.marker_type_id = mt.id','INNER');
		$marker->get('marker m',null,$mCol);
		$this->db->join($marker,'mrk.marker_id = bp.marker_id','LEFT');
		if ( in_array('image',$this->meta['chapters']) ) {
			$bpCol[] = 'mImg.url AS marker_img';
			$bpCol[] = 'mImg.description AS marker_img_alt';
			$mImage = $this->db->subQuery('mImg');
			$mImage->join('image_reference ir','im.image_reference_id = ir.id','INNER');
			$mImage->where('im.rel_type','marker');
			$mImage->where('im.sort_order',1);
			$mImage->get('image_match im',null,'rel_id,url,description');
			$this->db->join($mImage,'mImg.rel_id = mrk.marker_id','LEFT');
		}
		if ( is_numeric($typeID) ) {
			$this->db->where('mrk.marker_type_id',$typeID);
		}
		$this->db->where('book_id',$this->bookInfo['id']);
		$this->db->where('date_publish <= NOW()');
		$this->db->where('bp.sort_order',$this->bookInfo['latest_page'],'<=');
		if ( $this->range ) {
			$this->db->where('bp.sort_order',$this->range['start'],'>=');
			if ( $this->range['end'] ) {
				$this->db->where('bp.sort_order',$this->range['end'],'<');
			}
		}
		$this->db->orderBy('bp.sort_order','ASC');
		$result = $this->db->get('book_page bp',null,$bpCol);
		if ( $result ) {
			$this->currentList = $result;
		}
	}

	/**
	 * Start generating output
	 */
	public function buildPage() {
		$this->pageInfo['edit_this']['text'] = 'Edit archive page';
		$this->pageInfo['edit_this']['link'] = 'book.archive.php';
		if ( $this->layout['behavior'] == 'multi' && $this->chapterNum ) {
			$this->buildArchiveNavURLs();
			$this->showArchiveNav = true;
		}
		$this->buildHierarchy();
		$this->buildHeadline();
		$this->formatHierarchy();
		parent::buildPage();
	}

	/**
	 * Determine last chapter number and build back/next for multi-page archives
	 */
	protected function buildArchiveNavURLs() {
		$result = $this->db
			->join('marker m','bp.marker_id = m.id','LEFT')
			->where('m.marker_type_id',1)
			->where('bp.marker_id',0,'>')
			->where('bp.sort_order',$this->bookInfo['latest_page'],'<=')
			->get('book_page bp',null,'COUNT(bp.marker_id) AS count');
		$max = $result[0]['count'];
		$prev = $this->chapterNum - 1;
		if ( $prev > 0 ) {
			$navLinks['prev']['url'] = $this->bookInfo['archive_url'].'/'.$prev;
		}
		else {
			$navLinks['prev']['url'] = $this->bookInfo['archive_url'];
			$navLinks['prev']['css'] = ' disabled';
		}
		$next = $this->chapterNum + 1;
		if ( $next <= $max ) {
			$navLinks['next']['url'] = $this->bookInfo['archive_url'].'/'.$next;
		}
		else {
			$navLinks['next']['url'] = $this->bookInfo['archive_url'];
			$navLinks['next']['css'] = ' disabled';
		}
		$this->navLinks['archive'] = $navLinks;
	}

	/**
	 * Reorganize the list for easier output
	 */
	protected function buildHierarchy() {
		$i = 0;
		if ( $this->currentList ) {
			foreach ( $this->currentList as $page ) {
				$page['sort_order'] = (integer)$page['sort_order'];
				if ( is_numeric($page['marker_id']) && $page['marker_type_id'] <= 1 ) {
					$this->markerType['title'] = $page['type_title'];
					$this->markerType['rank'] = $page['rank'];
					$i++;
					$list[$i] = array(
						'marker_id'      => $page['marker_id'],
						'marker_title'   => $page['marker_title'],
						'marker_img'     => $page['marker_img'],
						'marker_img_alt' => $page['marker_img_alt'],
						'pages'          => array()
					);
				}
				$list[$i]['pages'][$page['sort_order']] = array(
					'page_id'      => $page['page_id'],
					'page_title'   => $page['page_title'],
					'date_publish' => $page['date_publish'],
					'page_img'     => $page['page_img'],
					'page_img_alt' => $page['page_img_alt']
				);
			}
		}
		$this->currentList = $list;
	}

	/**
	 * Build the headline for this archive page
	 */
	protected function buildHeadline() {
		if ( $this->layout['behavior'] == 'multi' ) {
			$str = $this->markerType['title'].' ';
			if ( is_numeric($this->chapterNum) ) {
				$str .= $this->chapterNum.' ';
				if ( $this->currentList[1]['marker_title'] ) {
					$str .= '<span class="title">'.$this->currentList[1]['marker_title'].'</span>';
				}
			}
			else {
				$str .= 'Archive';
			}
		}
		else {
			$str = 'Archive';
		}
		$this->pageInfo['archive_headline'] = $str;
		$this->pageInfo['page_title'] = strip_tags($str);
	}

	/**
	 * Format HTML for the archive info to be shown
	 */
	protected function formatHierarchy() {
		if ( $this->currentList ) {
			foreach ( $this->currentList as $c=>$list ) {
				unset($outputChapter);
				unset($outputPages);
				!$this->chapterNum ? $outputChapter = $this->formatChapterHead($c,$list) : $outputChapter = null;
				if ( $this->chapterNum || $this->layout['behavior'] == 'single' ) {
					foreach ( $list['pages'] as $p=>$item ) {
						$outputPages .= $this->formatPageItem($p,$item);
					}
					switch ( $this->layout['pages'] ) {
						case 'list':
							$cssPage = 'no-bullet';
							break;
						case 'grid':
							$cssPage = 'small-block-grid-2 medium-block-grid-3 large-block-grid-4';
							break;
						case 'inline':
							$cssPage = 'inline-list';
							break;
					}
					$output .= $outputChapter.'<ul class="'.$cssPage.'">'.$outputPages.'</ul>';
				}
				else {
					$output .= $outputChapter;
				}
			}
		}
		switch ( $this->layout['chapters'] ) {
			case 'list':
				$cssChapter = 'no-bullet';
				break;
			case 'grid':
				$cssChapter = 'small-block-grid-1 medium-block-grid-2 large-block-grid-3';
				break;
		}
		$this->pageInfo['archive_content'] = '<ul class="'.$cssChapter.'">'.$output.'</ul>';
	}

	/**
	 * Format the image/text for the chapter links for multipage archives
	 *
	 * @param int $x - chapter number
	 * @param array $info - info for one chapter
	 * @return string $link - HTML for link
	 */
	protected function formatChapterHead($x=null,$info=null) {
		// Only multi needs links for the chapters
		$this->layout['behavior'] == 'multi' ? $url = $this->buildPermalink($x,'archive') : $url = null;
		// Thumbnail
		if ( in_array('image', $this->meta['chapters']) && $info['marker_img'] ) {
			$image = '<img src="'.$info['marker_img'].'" alt="'.$info['marker_img_alt'].'" />';
			if ( $url ) {
				$image = '<a class="thumb" href="'.$url.'">'.$image.'</a>';
			}
		}
		// Chapter number
		if ( in_array('number', $this->meta['chapters']) && $x ) {
			$text[] = $this->markerType['title'].' '.$x;
		}
		// Title
		if ( in_array('title', $this->meta['chapters']) ) {
			$text[] = '<span class="title">'.$info['marker_title'].'</span>';
		}
		$text = implode(' ', $text);
		if ( $url ) {
			$text = '<a href="'.$url.'">'.$text.'</a>';
		}
		$link = '<li class="item chapter"><h3>'.$image.$text.'</h3></li>';
		return $link;
	}

	/**
	 * Format the image/text for the page links
	 *
	 * @param int $num - sort order
	 * @param array $info - info for one page
	 * @return string $link - HTML for link
	 */
	protected function formatPageItem($num=null,$info=null) {
		$url = $this->buildPermalink($num,'page');
		// Thumbnail
		if ( in_array('image', $this->meta['pages']) && $info['page_img'] ) {
			$image = '<a class="thumb" href="'.$url.'"><img src="'.$info['page_img'].'" alt="'.$info['page_img_alt'].'" /></a>';
		}
		// Page number
		if ( in_array('number', $this->meta['pages']) && $num ) {
			$page = 'Page '.$num.' ';
		}
		// Title
		if ( in_array('title', $this->meta['pages']) ) {
			$info['page_title'] ? $title = $info['page_title'].' ' : $title = 'Untitled ';
		}
		// Pub date
		if ( in_array('date', $this->meta['pages']) ) {
			if ( $info['date_publish'] ) {
				$date = $this->formatDate($info['date_publish']);
				$date = '<time datetime="'.$info['date_publish'].'">'.$date.'</time>';
			}
			else {
				$date = 'Undated';
			}
		}
		if ( $page || $title || $date ) {
			$text = '<h5><a href="'.$url.'">'.$page.$title.$date.'</a></h5>';
		}
		$link = '<li class="item page">'.$image.$text.'</li>';
		return $link;
	}
}