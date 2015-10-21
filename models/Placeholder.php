<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\models;


class Placeholder extends Image {

	public function __construct( $filePath, $config = [ ] ) {
		$this->filePath = $filePath;
		parent::__construct( $config );
	}

}