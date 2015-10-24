<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\models;

/**
 * Class Placeholder
 * @package romkaChev\yii2\images\models
 */
class Placeholder extends Image implements IImageInterface {
	/**
	 * @param string $filePath
	 *
	 * @inheritdoc
	 */
	public function __construct( $filePath, $config = [ ] ) {
		$this->filePath = $filePath;
		parent::__construct( $config );
	}

}