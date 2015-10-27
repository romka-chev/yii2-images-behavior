<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\traits;

use romkaChev\yii2\images\ImagesModule;
use yii\base\InvalidConfigException;

/**
 * Class ImagesModuleTrait
 * @package romkaChev\yii2\images\traits
 */
trait ImagesModuleTrait {

	/** @var ImagesModule */
	protected $_module;

	/**
	 * @return ImagesModule
	 * @throws InvalidConfigException
	 */
	protected function getModule() {
		if ( $this->_module === null ) {
			$this->_module = \Yii::$app->getModule( 'images' );
			if ( $this->_module === null ) {
				throw new InvalidConfigException( "Yii2 'images' module was not found, check config" );
			}
		}

		return $this->_module;
	}
}