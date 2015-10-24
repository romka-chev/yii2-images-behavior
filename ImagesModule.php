<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images;


use romkaChev\yii2\images\models\Image;
use romkaChev\yii2\images\models\Placeholder;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\Module;
use yii\helpers\Inflector;

/**
 * Class ImagesModule
 * @package romkaChev\yii2\images
 *
 * @property Placeholder placeholder
 */
class ImagesModule extends Module {

	public $ds = DIRECTORY_SEPARATOR;

	public $storePath;
	public $publishPath;
	public $publishUrl;

	public $placeholderPath;
	public $watermarkPath;
	public $useWatermark = false;

	public $imageClass       = '\romkaChev\yii2\images\models\Image';
	public $placeholderClass = '\romkaChev\yii2\images\models\Placeholder';

	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public function init() {
		parent::init();

		if ( $this->storePath === null ) {
			throw new InvalidConfigException( 'StorePath property must be set' );
		}
		if ( $this->publishPath === null ) {
			throw new InvalidConfigException( 'PublishPath property must be set' );
		}
		if ( $this->publishUrl === null ) {
			throw new InvalidConfigException( 'PublishUrl property must be set' );
		}
		if ( $this->placeholderPath !== null ) {
			if ( ! is_file( \Yii::getAlias( $this->placeholderPath ) ) ) {
				throw new \RuntimeException( "Placeholder at path '{$this->placeholderPath}' was not found" );
			}
		}
	}

	/**
	 * @param \yii\base\Model $model
	 *
	 * @return string
	 */
	public function getModelDirectory( Model $model ) {
		if ( ! $model->canGetProperty( 'idAttribute' ) ) {
			throw new \LogicException( "Model {$model->className()} has not 'idAttribute' property" );
		}

		$modelName = $this->getShortClass( $model );
		/** @noinspection PhpUndefinedFieldInspection */
		$modelId = $model->{$model->idAttribute};

		return $this->_getSubDirectory( $modelName, $modelId );
	}

	/**
	 * @param mixed $object
	 *
	 * @return string
	 */
	public function getShortClass( $object ) {
		$className = get_class( $object );
		if ( preg_match( '@\\\\([\w]+)$@', $className, $matches ) ) {
			$className = $matches[1];
		}

		return $className;
	}

	/**
	 * @param string $modelName
	 * @param int    $modelId
	 *
	 * @return string
	 */
	protected function _getSubDirectory( $modelName, $modelId ) {
		$modelDir = "{$modelName}{$modelId}";
		$baseDir  = Inflector::pluralize( $modelName );

		return "{$baseDir}{$this->ds}{$modelDir}";
	}

	/**
	 * @param \romkaChev\yii2\images\models\Image $image
	 *
	 * @return string
	 */
	public function getImageDirectory( Image $image ) {
		return $this->_getSubDirectory( $image->modelName, $image->modelId );
	}

	/**
	 * @param string $stringSize
	 *
	 * @return int[]
	 */
	public function parseSize( $stringSize ) {
		$sizeParts = explode( 'x', $stringSize );
		$isPart1   = ( isset( $sizeParts[0] ) && $sizeParts[0] != '' );
		$isPart2   = ( isset( $sizeParts[1] ) && $sizeParts[1] != '' );

		if ( $isPart1 && $isPart2 ) {
			if ( intval( $sizeParts[0] ) > 0
			     &&
			     intval( $sizeParts[1] ) > 0
			) {
				$size = [
					'width'  => intval( $sizeParts[0] ),
					'height' => intval( $sizeParts[1] )
				];
			} else {
				$size = null;
			}
		} elseif ( $isPart1 && ! $isPart2 ) {
			$size = [
				'width'  => intval( $sizeParts[0] ),
				'height' => null
			];
		} elseif ( ! $isPart1 && $isPart2 ) {
			$size = [
				'width'  => null,
				'height' => intval( $sizeParts[1] )
			];
		} else {
			throw new \LogicException( "Something bad with size '{$stringSize}'" );
		}

		return $size;
	}

	/**
	 * @return \romkaChev\yii2\images\models\Placeholder
	 */
	public function getPlaceholder() {
		if ( $this->placeholderPath === null ) {
			throw new \LogicException( "Trying to get placeholder, but 'placeholderPath' property was not set." );
		}

		return new $this->placeholderClass( $this->placeholderPath );
	}
}