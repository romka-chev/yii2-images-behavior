<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\autocomplete;


use yii\db\ActiveRecord;

/**
 * Class AutocompleteActiveRecord
 * @package romkaChev\yii2\images\models
 */
class AutocompleteActiveRecord extends ActiveRecord {
	use AutocompleteTrait;
}