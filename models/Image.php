<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\models;

use Imagine\Image\ManipulatorInterface;
use romkaChev\yii2\images\traits\ImagesModuleTrait;
use yii\db\ActiveRecord;
use yii\helpers\BaseFileHelper;

/**
 * Class BaseImageAR
 * @package romkaChev\yii2\images\models
 *
 * @property int    id
 * @property string filePath
 * @property int    isMain
 * @property int    modelId
 * @property string modelName
 */
class Image extends ActiveRecord implements IImageInterface {

	use ImagesModuleTrait;

	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return 'image';
	}

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[ [ 'filePath', 'modelId', 'modelName', 'url_alias' ], 'required' ],
			[ [ 'modelId' ], 'integer' ],
			[ [ 'is_main' ], 'boolean' ],
			[ [ 'filePath', 'url_alias' ], 'string', 'max' => 400 ],
			[ [ 'modelName' ], 'string', 'max' => 150 ]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function beforeDelete() {
		if ( parent::beforeDelete() ) {

			$module       = $this->getModule();
			$fileToRemove = "{$module->storePath}{$module->ds}{$this->filePath}";

			$this->flushPublishedCopies();

			if ( is_file( $fileToRemove ) ) {
				unlink( $fileToRemove );
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return static
	 */
	public function flushPublishedCopies() {
		$module = $this->getModule();

		$subDirectory = $this->getSubDirectory();
		$dirToRemove  = "{$module->publishPath}{$module->ds}{$subDirectory}";

		$quotedModelName = preg_quote( $this->modelName, '/' );

		if ( preg_match( "@{$quotedModelName}@", $dirToRemove ) ) {
			BaseFileHelper::removeDirectory( $dirToRemove );
		}

		return $this;
	}

	/**
	 * @return string
	 */
	protected function getSubDirectory() {
		return $this->getModule()->getImageDirectory( $this );
	}

	/**
	 * @return string
	 */
	public function getExtension() {
		return pathinfo( $this->getPathToOrigin(), PATHINFO_EXTENSION );
	}

	/**
	 * @return string
	 */
	public function getPathToOrigin() {
		$module = $this->getModule();

		return "{$module->storePath}{$module->ds}{$this->filePath}";
	}

	/**
	 * @param bool|false $size
	 *
	 * @return string
	 * @throws \Exception
	 * @throws \yii\base\Exception
	 */
	public function getLink( $size = false ) {
		if ( ! is_file( $this->_buildPublishedPath( $size ) ) ) {
			$this->createVersion( $size );
		}

		return $this->_buildPublishedLink( $size );
	}

	/**
	 * @param string|null $size
	 *
	 * @return string
	 */
	protected function _buildPublishedPath( $size = null ) {
		$module = $this->getModule();

		$imageDirectory = $module->getImageDirectory( $this );
		$baseName       = $this->_getResizedBaseName( $size );
		$ds             = $module->ds;
		$publishPath    = $module->publishPath;

		return "{$publishPath}{$ds}{$imageDirectory}{$module->ds}{$baseName}";
	}

	/**
	 * @param string|null $size
	 *
	 * @return string
	 */
	protected function _getResizedBaseName( $size = null ) {
		if ( $size ) {
			$pathInfo  = pathinfo( $this->filePath );
			$name      = $pathInfo['filename'];
			$extension = $pathInfo['extension'];

			return "{$name}_{$size}.{$extension}";
		} else {
			return "{$this->filePath}";
		}
	}

	/**
	 * @param string $size
	 *
	 * @return string
	 */
	public function createVersion( $size = null ) {
		$module          = $this->getModule();
		$sourcePath      = "{$module->storePath}{$module->ds}{$this->filePath}";
		$destinationPath = $this->_buildPublishedPath( $size );

		if ( $size === null ) {
			copy( $sourcePath, $destinationPath );
		} else {
			$sourceImageSizeInfo = getimagesize( $sourcePath );
			$sourceWidth         = $sourceImageSizeInfo[0];
			$sourceHeight        = $sourceImageSizeInfo[1];

			$sizes = $module->parseSize( $size );

			if ( $sizes['width'] === null ) {
				$sizes['width'] = $sizes['height'] * ( $sourceWidth / $sourceHeight );
			}
			if ( $sizes['height'] === null ) {
				$sizes['height'] = $sizes['width'] / ( $sourceWidth / $sourceHeight );
			}

			\yii\imagine\Image::thumbnail( $sourcePath, $sizes['width'], $sizes['height'], ManipulatorInterface::THUMBNAIL_INSET )->save( $destinationPath,
				[ 'quality' => 100 ] );
		}

		return $destinationPath;
	}

	/**
	 * @param string|null $size
	 *
	 * @return string
	 */
	protected function _buildPublishedLink( $size = null ) {
		$module = $this->getModule();

		$imageDirectory = $module->getImageDirectory( $this );
		$baseName       = $this->_getResizedBaseName( $size );
		$ds             = $module->ds;
		$publishUrl     = $module->publishUrl;

		return "{$publishUrl}{$ds}{$imageDirectory}{$module->ds}{$baseName}";
	}

	/**
	 * @param bool|false $size
	 *
	 * @return string
	 */
	public function getPath( $size = false ) {
		return $this->_buildPublishedPath( $size );
	}

	/**
	 * @return \Imagine\Image\BoxInterface
	 */
	public function getSize() {
		$image = \yii\imagine\Image::getImagine()->open( $this->getPathToOrigin() );
		$box   = $image->getSize();
		unset( $image );

		return $box;
	}

	/**
	 * @param bool|true $isMain
	 *
	 * @return static
	 */
	public function setMain( $isMain = true ) {
		$this->isMain = $isMain;

		return $this;
	}

}