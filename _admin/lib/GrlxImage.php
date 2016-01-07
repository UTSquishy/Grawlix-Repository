<?php

class GrlxImage {
	public $db = null;
	public $src = '';
	public $alt = '';
	public $class = '';

	function paint() {
		$this-> check_src();
		$this->src ? $src = ' src="'.$this->src.'"' : null;
		$this->alt ? $alt = ' alt="'.$this->alt.'"' : null;
		$this->class ? $class = ' class="'.$this->class.'"' : null;
		$this->style ? $style = ' style="'.$this->style.'"' : null;
		$this->target ? $target = ' target="'.$this->target.'"' : null;

		$output = '<img'.$src.$alt.$class.$style.'/>';

		return $output;
	}

	function check_src($src='') {
		$src ? $src : $src = $this-> src;
		if ( substr($src,0,1) == '/' ) {
			$src = '..'.$src;
		}
		if ( is_file($src) ) {
			$this-> src = $src;
		}
		else {
			$this-> src = 'img/image_not_found.100x.png';
		}
	}

	// Check image_reference table to see if record exists and return the ID if yes
	function check_image_ref($query='', $col='url') {
		$db = $this-> db_new;
		$ref_id = $db
			-> where($col, $query)
			-> getOne('image_reference', array('id'))
		;
		$ref_id = $ref_id['id'];
		return $ref_id;
	}
}
/*

$i = new GrlxImage;
$i->src = 'aaa';
$i->alt = 'bbb';
$i->class = 'ccc';
$i->style = 'ddd';
$i->url = 'eee';
$image = $i->paint();

*/