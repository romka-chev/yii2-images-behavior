<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\autocomplete;

use romkaChev\yii2\images\models\Image;
use romkaChev\yii2\images\models\Placeholder;

/**
 * Class AutocompleteTrait
 * @package romkaChev\yii2\images\traits
 *
 * @method Image attachImage( $newImage, $isMain = false )
 * @see     \romkaChev\yii2\images\behaviors\ImagesBehavior::attachImage
 *
 * @method void setMainImage( Image $img )
 * @see     \romkaChev\yii2\images\behaviors\ImagesBehavior::setMainImage
 *
 * @method  bool clearImagesCache()
 * @see     \romkaChev\yii2\images\behaviors\ImagesBehavior::flushImagePublishedCopies
 *
 * @method Image[] getImages( $usePlaceholder = true )
 * @see     \romkaChev\yii2\images\behaviors\ImagesBehavior::getImages
 *
 * @method null|Image|Placeholder getImage( $id = null, $usePlaceholder = true )
 * @see     \romkaChev\yii2\images\behaviors\ImagesBehavior::getImage
 *
 * @method void removeImages()
 * @see     \romkaChev\yii2\images\behaviors\ImagesBehavior::removeImages
 *
 * @method void removeImage( Image $img )
 * @see     \romkaChev\yii2\images\behaviors\ImagesBehavior::removeImage
 */
trait AutocompleteTrait {

}