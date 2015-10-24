<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

/**
 * Class m151021_201915_create_table__image
 */
class m151021_201915_create_table__image extends \yii\db\Migration {

	/**
	 * @inheritdoc
	 */
	public function safeUp() {
		$this->createTable( 'image', [
			'id'        => $this->primaryKey(),
			'modelId'   => $this->integer(),
			'modelName' => $this->string( 255 ),
			'isMain'    => $this->boolean()->defaultValue( false ),
			'filePath'  => $this->string( 400 ),
		] );

		//@formatter:off
		$this->createIndex( 'isMain',    'image', 'isMain' );
		$this->createIndex( 'modelId',   'image', 'modelId' );
		$this->createIndex( 'modelName', 'image', 'modelName' );
		//@formatter:on
	}

	/**
	 * @inheritdoc
	 */
	public function safeDown() {
		echo "m151021_201915_create_table__image cannot be reverted.\n";

		return false;
	}
}