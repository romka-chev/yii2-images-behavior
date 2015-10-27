<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace data;


use romkaChev\yii2\images\autocomplete\AutocompleteTrait;
use yii\db\ActiveRecord;

/**
 * Class Book
 * @package data
 *
 * @property int    id
 * @property string name
 */
class Book extends ActiveRecord {

	use AutocompleteTrait;

	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return 'book';
	}

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[ [ 'name' ], 'string', 'max' => 255 ]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [
			[
				'class'       => \romkaChev\yii2\images\behaviors\ImagesBehavior::className(),
				'idAttribute' => 'id',
				'modelClass'  => \romkaChev\yii2\images\models\Image::className(),
				'modelConfig' => [ ]
			]
		];
	}
}