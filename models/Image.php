<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\models;

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
class Image extends ActiveRecord {

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

		$subDir      = $this->getSubDur();
		$dirToRemove = "{$module->publishPath}{$module->ds}{$subDir}";

		$quotedModelName = preg_quote( $this->modelName, '/' );

		if ( preg_match( "@{$quotedModelName}@", $dirToRemove ) ) {
			BaseFileHelper::removeDirectory( $dirToRemove );
		}

		return $this;
	}

	/**
	 * @return string
	 */
	protected function getSubDur() {
		return $this->getModule()->getImageSubDir( $this );
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
		$urlSize = ( $size ) ? '_' . $size : '';
		$base    = $this->getModule()->publishPath;
		$sub     = $this->getSubDur();

		$origin = $this->getPathToOrigin();

		$extension = pathinfo( $origin, PATHINFO_EXTENSION );
		$filePath  = $base . DIRECTORY_SEPARATOR . $sub . DIRECTORY_SEPARATOR . $this->url_alias . $urlSize . '.' . $extension;
		$fileUrl   = "/files/uploads/images/cache/{$sub}/{$this->url_alias}{$urlSize}.{$extension}";

		if ( ! file_exists( $filePath ) ) {
			$this->createVersion( $origin, $size );

			if ( ! file_exists( $filePath ) ) {
				throw new \Exception( 'Problem with image creating.' );
			}
		}

		return $fileUrl;
	}

	public function createVersion( $imagePath, $sizeString = false ) {
		if ( strlen( $this->url_alias ) < 1 ) {
			throw new \Exception( 'Image without url alias!' );
		}

		$cachePath     = $this->getModule()->publishPath;
		$subDirPath    = $this->getSubDur();
		$fileExtension = pathinfo( $this->filePath, PATHINFO_EXTENSION );

		if ( $sizeString ) {
			$sizePart = '_' . $sizeString;
		} else {
			$sizePart = '';
		}

		$pathToSave = $cachePath . '/' . $subDirPath . '/' . $this->url_alias . $sizePart . '.' . $fileExtension;

		BaseFileHelper::createDirectory( dirname( $pathToSave ), 0777, true );


		if ( $sizeString ) {
			$size = $this->getModule()->parseSize( $sizeString );
		} else {
			$size = false;
		}

		if ( $this->getModule()->graphicsLibrary == 'Imagick' ) {
			$image = new \Imagick( $imagePath );
			$image->setImageCompressionQuality( 100 );

			if ( $size ) {
				if ( $size['height'] && $size['width'] ) {
					$image->cropThumbnailImage( $size['width'], $size['height'] );
				} elseif ( $size['height'] ) {
					$image->thumbnailImage( 0, $size['height'] );
				} elseif ( $size['width'] ) {
					$image->thumbnailImage( $size['width'], 0 );
				} else {
					throw new \Exception( 'Something wrong with this->module->parseSize($sizeString)' );
				}
			}

			$image->writeImage( $pathToSave );
		} else {
			$image = new \abeautifulsite\SimpleImage( $imagePath );
			if ( $size ) {
				if ( $size['height'] && $size['width'] ) {
					$image->thumbnail( $size['width'], $size['height'] );
				} elseif ( $size['height'] ) {
					$image->fit_to_height( $size['height'] );
				} elseif ( $size['width'] ) {
					$image->fit_to_width( $size['width'] );
				} else {
					throw new \Exception( 'Something wrong with this->module->parseSize($sizeString)' );
				}
			}

			//WaterMark
			if ( $this->getModule()->waterMark ) {
				if ( ! file_exists( Yii::getAlias( $this->getModule()->waterMark ) ) ) {
					throw new Exception( 'WaterMark not detected!' );
				}

				$wmMaxWidth    = intval( $image->get_width() * 0.4 );
				$wmMaxHeight   = intval( $image->get_height() * 0.4 );
				$waterMarkPath = Yii::getAlias( $this->getModule()->waterMark );
				$waterMark     = new \abeautifulsite\SimpleImage( $waterMarkPath );
				if ( ( $waterMark->get_height() > $wmMaxHeight ) || ( $waterMark->get_width() > $wmMaxWidth ) ) {
					$waterMarkPath = $this->getModule()->publishPath . DIRECTORY_SEPARATOR .
					                 pathinfo( $this->getModule()->waterMark )['filename'] .
					                 $wmMaxWidth . 'x' . $wmMaxHeight . '.' .
					                 pathinfo( $this->getModule()->waterMark )['extension'];

					//throw new Exception($waterMarkPath);
					if ( ! file_exists( $waterMarkPath ) ) {
						$waterMark->fit_to_width( $wmMaxWidth );
						$waterMark->save( $waterMarkPath, 100 );
						if ( ! file_exists( $waterMarkPath ) ) {
							throw new Exception( 'Cant save watermark to ' . $waterMarkPath . '!!!' );
						}
					}
				}
				$image->overlay( $waterMarkPath, 'bottom right', .5, - 10, - 10 );
			}
			$image->save( $pathToSave, 100 );
		}

		return $image;
	}

	public function getPath( $size = false ) {
		$urlSize = ( $size ) ? '_' . $size : '';
		$base    = $this->getModule()->publishPath;
		$sub     = $this->getSubDur();

		$origin = $this->getPathToOrigin();

		$filePath = $base . DIRECTORY_SEPARATOR .
		            $sub . DIRECTORY_SEPARATOR . $this->url_alias . $urlSize . '.' . pathinfo( $origin, PATHINFO_EXTENSION );;
		if ( ! file_exists( $filePath ) ) {
			$this->createVersion( $origin, $size );

			if ( ! file_exists( $filePath ) ) {
				throw new \Exception( 'Problem with image creating.' );
			}
		}

		return $filePath;
	}

	public function getSizesWhen( $sizeString ) {
		$size = $this->getModule()->parseSize( $sizeString );
		if ( ! $size ) {
			throw new \Exception( 'Bad size..' );
		}

		$sizes = $this->getSizes();

		$imageWidth  = $sizes['width'];
		$imageHeight = $sizes['height'];
		$newSizes    = [ ];
		if ( ! $size['width'] ) {
			$newWidth           = $imageWidth * ( $size['height'] / $imageHeight );
			$newSizes['width']  = intval( $newWidth );
			$newSizes['heigth'] = $size['height'];
		} elseif ( ! $size['height'] ) {
			$newHeight          = intval( $imageHeight * ( $size['width'] / $imageWidth ) );
			$newSizes['width']  = $size['width'];
			$newSizes['heigth'] = $newHeight;
		}

		return $newSizes;
	}

	public function getSizes() {
		$sizes = false;
		if ( $this->getModule()->graphicsLibrary == 'Imagick' ) {
			$image = new \Imagick( $this->getPathToOrigin() );
			$sizes = $image->getImageGeometry();
		} else {
			$image           = new \abeautifulsite\SimpleImage( $this->getPathToOrigin() );
			$sizes['width']  = $image->get_width();
			$sizes['height'] = $image->get_height();
		}

		return $sizes;
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