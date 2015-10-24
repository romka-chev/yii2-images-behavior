<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\exceptions;


use yii\base\Exception;
use yii\base\Model;
use yii\helpers\VarDumper;

/**
 * Class ModelNotSavedException
 * @package romkaChev\yii2\images\exceptions
 */
class ModelNotSavedException extends Exception {

	/**
	 * @var Model
	 */
	public $model;

	/**
	 * @param Model      $model
	 * @param string     $message
	 * @param integer    $code
	 * @param \Exception $previous
	 */
	public function __construct( Model $model, $message = null, $code = 0, \Exception $previous = null ) {
		$this->model = $model;
		$message     = $message ?: $this->getDefaultMessage();

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * @return string
	 */
	public function getDefaultMessage() {
		return "Model not saved due to errors: " . VarDumper::dumpAsString( $this->model->errors );
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Model not saved';
	}
}