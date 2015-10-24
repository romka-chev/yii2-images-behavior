<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\behaviors;


use romkaChev\yii2\images\autocomplete\AutocompleteActiveRecord;
use romkaChev\yii2\images\exceptions\CanNotCopyImageException;
use romkaChev\yii2\images\exceptions\FileNotExistsException;
use romkaChev\yii2\images\exceptions\ModelNotLoadedException;
use romkaChev\yii2\images\exceptions\ModelNotSavedException;
use romkaChev\yii2\images\helpers\FileHelper;
use romkaChev\yii2\images\models\IImageInterface;
use romkaChev\yii2\images\models\Image;
use romkaChev\yii2\images\models\Placeholder;
use romkaChev\yii2\images\traits\ImagesModuleTrait;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

/**
 * Class ImagesBehavior
 * @package romkaChev\yii2\images\behaviors
 */
class ImagesBehavior extends Behavior {

	use ImagesModuleTrait;

	/** @var AutocompleteActiveRecord */
	public $owner;
	/** @var string */
	public $idAttribute = 'id';
	/** @var string */
	public $modelClass = '\common\modules\images\models\Image';
	/** @var array */
	public $modelConfig = [ ];

	/**
	 * @inheritdoc
	 */
	public function init() {
		parent::init();

		if ( ! $this->owner->canGetProperty( 'idAttribute' ) ) {
			throw new \LogicException( "Model {$this->owner->className()} has not 'idAttribute' property" );
		}
	}

	/**
	 * @param string|UploadedFile $file
	 * @param bool|false          $isMain
	 *
	 * @throws FileNotExistsException
	 * @throws CanNotCopyImageException
	 * @throws InvalidConfigException
	 * @throws ModelNotLoadedException
	 * @throws ModelNotSavedException
	 *
	 * @return Image
	 */
	public function attachImage( $file, $isMain = false ) {

		$module = $this->getModule();
		$owner  = $this->owner;

		if ( $file instanceof UploadedFile ) {
			$sourcePath = $file->tempName;
			$extension  = mb_strtolower( $file->extension );
		} else {
			if ( preg_match( '/http/', $file ) ) {
				if ( ! FileHelper::remoteFileExists( $file ) ) {
					throw new FileNotExistsException( $file );
				}
			} else {
				if ( ! file_exists( $file ) ) {
					throw new FileNotExistsException( $file );
				}
			}

			$sourcePath = $file;
			$extension  = mb_strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		}

		$ds             = $module->ds;
		$storePath      = $module->storePath;
		$modelDirectory = $module->getModelDirectory( $owner );
		$name           = substr( sha1( microtime( true ) . $sourcePath ), 4, 12 ) . $extension;

		//@formatter:off
		$modelDirectoryPath = "{$storePath}{$ds}{$modelDirectory}";
		$destinationPath    = "{$storePath}{$ds}{$modelDirectory}{$ds}{$name}";
		$modelFilePath      =                  "{$modelDirectory}{$ds}{$name}";
		//@formatter:on

		FileHelper::createDirectory( $modelDirectoryPath );

		if ( ! copy( $sourcePath, $destinationPath ) ) {
			throw new CanNotCopyImageException( $sourcePath, $destinationPath );
		}

		$modelConfig          = $this->modelConfig;
		$modelConfig['class'] = ArrayHelper::getValue( $modelConfig, 'class', $this->modelClass );
		/** @var Image $image */
		$image = \Yii::createObject( $modelConfig );
		if ( ! $image instanceof IImageInterface ) {
			unlink( $destinationPath );
			throw new InvalidConfigException( 'ModelClass property must be instance of \romkaChev\yii2\images\models\IImageInterface' );
		}

		$isLoaded = $image->load( [
			$image->formName() => [
				'modelId'   => $owner->{$this->idAttribute},
				'modelName' => $module->getShortClass( $image ),
				'filePath'  => $modelFilePath
			]
		] );
		if ( ! $isLoaded ) {
			unlink( $destinationPath );
			throw new ModelNotLoadedException( $image );
		}

		$isSaved = $image->save();
		if ( ! $isSaved ) {
			unlink( $destinationPath );
			throw new ModelNotSavedException( $image );
		}

		$currentMainImage = $owner->getImage();

		$isCurrentMainImageNull        = $currentMainImage === null;
		$isCurrentMainImagePlaceholder = $currentMainImage instanceof Placeholder;
		$isExplicitDesignation         = $isMain;

		$isMustBeMain = $isCurrentMainImageNull || $isCurrentMainImagePlaceholder || $isExplicitDesignation;
		if ( $isMustBeMain ) {
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

		return $this;
	}

	/**
	 * @return static
	 */
	public function flushImagePublishedCopies() {
		$module   = $this->getModule();
		$modelDir = $module->getModelDirectory( $this->owner );

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