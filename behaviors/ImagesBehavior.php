<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\behaviors;


use romkaChev\yii2\images\autocomplete\AutocompleteActiveRecord;
use romkaChev\yii2\images\models\Image;
use romkaChev\yii2\images\models\Placeholder;
use romkaChev\yii2\images\traits\ImagesModuleTrait;
use yii\base\Behavior;
use yii\db\ActiveQuery;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * Class ImagesBehavior
 * @package romkaChev\yii2\images\behaviors
 */
class ImagesBehavior extends Behavior {

	use ImagesModuleTrait;

	/**
	 * @var AutocompleteActiveRecord
	 */
	public $owner;

	/**
	 * @var string
	 */
	public $idAttribute = 'id';

	public $createAliasMethod = false;

	public $modelClass = '\common\modules\images\models\Image';

	/**
	 * todo
	 * Method copies image file to module store and creates db record.
	 *
	 * @param string|UploadedFile $newImage
	 * @param bool                $isMain
	 *
	 * @return bool|Image
	 * @throws \Exception
	 */
	public function attachImage( $newImage, $isMain = false ) {
		if ( ! $this->owner->{$this->idAttribute} ) {
			throw new \Exception( $this->owner->classname() . ' must have an id when you attach image!' );
		}

		$pictureFileName = '';

		if ( $newImage instanceof UploadedFile ) {
			$sourcePath = $newImage->tempName;
			$imageExt   = $newImage->extension;
		} else {
			if ( ! preg_match( '#http#', $newImage ) ) {
				if ( ! file_exists( $newImage ) ) {
					throw new \Exception( 'File not exist! :' . $newImage );
				}
			} else {
				//nothing
			}
			$sourcePath = $newImage;
			$imageExt   = pathinfo( $newImage, PATHINFO_EXTENSION );
		}

		$pictureFileName = substr( sha1( microtime( true ) . $sourcePath ), 4, 12 );
		$pictureFileName .= '.' . $imageExt;

		if ( ! file_exists( $sourcePath ) ) {
			throw new \Exception( 'Source file doesnt exist! ' . $sourcePath . ' to ' . $newAbsolutePath );
		}

		$pictureSubDir = $this->getModule()->getModelSubDir( $this->owner );
		$storePath     = $this->getModule()->getStorePath( $this->owner );

		$destPath = $storePath .
		            DIRECTORY_SEPARATOR . $pictureSubDir .
		            DIRECTORY_SEPARATOR . $pictureFileName;

		FileHelper::createDirectory( $storePath . DIRECTORY_SEPARATOR . $pictureSubDir,
			0775, true );

		if ( ! copy( $sourcePath, $destPath ) ) {
			throw new \Exception( 'Failed to copy file from ' . $sourcePath . ' to ' . $destPath );
		}

		$image = new $this->modelClass;

		$image->item_id    = $this->owner->{$this->idAttribute};
		$image->file_path  = $pictureSubDir . '/' . $pictureFileName;
		$image->model_name = $this->getModule()->getShortClass( $this->owner );

		$image->url_alias = $this->getAlias( $image );

		if ( ! $image->save() ) {
			return false;
		}

		if ( count( $image->getErrors() ) > 0 ) {
			$ar = array_shift( $image->getErrors() );

			unlink( $newAbsolutePath );
			throw new \Exception( array_shift( $ar ) );
		}
		$img = $this->owner->getImage();

		// If main image not exists
		if (
			is_object( $img ) && get_class( $img ) == '\common\modules\images\models\PlaceHolder'
			or
			$img == null
			or
			$isMain
		) {
			$this->setMainImage( $image );
		}

		return $image;
	}

	/**
	 * @param Image $image
	 *
	 * @return static
	 */
	public function setMainImage( Image $image ) {

		$ownerId = $this->owner->{$this->idAttribute};
		$modelId = $image->modelId;

		if ( $ownerId != $modelId ) {
			throw new \LogicException( "Image must belong to it's owner model" );
		}

		$image->setMain();
		$image->save();

		Image::updateAll( [ 'isMain' => false ], [
			'and',
			[ 'modelName' => $image->modelName, 'modelId' => $modelId ],
			[ 'not', [ 'id' => $image->id ] ],
		] );

		$this->owner->clearImagesCache();

		return $this;
	}

	/**
	 * @return static
	 */
	public function clearImagesCache() {
		$module   = $this->getModule();
		$modelDir = $module->getModelSubDir( $this->owner );

		$dirToRemove = "{$module->publishPath}{$module->ds}{$modelDir}";

		if ( is_dir( $dirToRemove ) ) {
			FileHelper::removeDirectory( $dirToRemove );
		}

		return $this;
	}

	/**
	 * @param bool $usePlaceholder
	 *
	 * @return Image[]
	 */
	public function getImages( $usePlaceholder = true ) {
		/** @var Image[] $images */
		$images = $this->_imageQuery()->all();

		if ( ! count( $images ) ) {
			if ( $usePlaceholder ) {
				$images = [ $this->getModule()->getPlaceholder() ];
			}
		}

		return $images;
	}

	/**
	 * @return ActiveQuery
	 */
	protected function _imageQuery() {
		$module = $this->getModule();

		$modelId   = $this->owner->{$this->idAttribute};
		$modelName = $module->getShortClass( $this->owner );

		$condition = [
			'modelId'   => $modelId,
			'modelName' => $modelName
		];

		return Image::find()->where( $condition )->orderBy( [ 'is_main' => SORT_DESC ] );
	}

	/**
	 * @param int  $id
	 * @param bool $usePlaceholder
	 *
	 * @return Image|Placeholder|null
	 */
	public function getImage( $id = null, $usePlaceholder = true ) {
		$query = $this->_imageQuery();

		if ( $id !== null ) {
			$query->andWhere( [ 'id' => $id ] );
		} else {
			$query->andWhere( [ 'isMain' => true ] );
		}

		/** @var Image $image */
		$image = $query->one();

		if ( $image === null && $usePlaceholder ) {
			return $this->getModule()->getPlaceholder();
		}

		return $image;
	}

	/**
	 * @return static
	 */
	public function removeImages() {
		$owner = $this->owner;

		$images = $owner->getImages();
		foreach ( $images as $image ) {
			$owner->removeImage( $image );
		}

		return $this;
	}

	/**
	 * @param Image $image
	 *
	 * @return static
	 * @throws \Exception
	 */
	public function removeImage( Image $image ) {
		$image->delete();

		return $this;
	}
}