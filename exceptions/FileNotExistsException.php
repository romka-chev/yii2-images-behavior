<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\exceptions;


use yii\base\Exception;

/**
 * Class FileNotExistsException
 * @package romkaChev\yii2\images\exceptions
 */
class FileNotExistsException extends Exception {

	/**
	 * @var string
	 */
	public $path;

	/**
	 * @param string     $path
	 * @param string     $message
	 * @param integer    $code
	 * @param \Exception $previous
	 */
	public function __construct( $path, $message = null, $code = 0, \Exception $previous = null ) {
		$this->path = $path;
		$message    = $message ?: $this->getDefaultMessage();

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * @return string
	 */
	public function getDefaultMessage() {
		return "File not exists: '{$this->path}'";
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'File not exists';
	}
}