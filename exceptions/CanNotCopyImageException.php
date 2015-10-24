<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\exceptions;


use yii\base\Exception;

/**
 * Class CanNotCopyImageException
 * @package romkaChev\yii2\images\exceptions
 */
class CanNotCopyImageException extends Exception {

	/**
	 * @var string
	 */
	public $sourcePath;
	/**
	 * @var string
	 */
	public $destinationPath;

	/**
	 * @param string     $sourcePath
	 * @param string     $destinationPath
	 * @param string     $message
	 * @param integer    $code
	 * @param \Exception $previous
	 */
	public function __construct( $sourcePath, $destinationPath, $message = null, $code = 0, \Exception $previous = null ) {
		$this->sourcePath      = $sourcePath;
		$this->destinationPath = $destinationPath;
		$message               = $message ?: $this->getDefaultMessage();

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * @return string
	 */
	public function getDefaultMessage() {
		return "Can not copy image file from '{$this->sourcePath}' to '{$this->destinationPath}'";
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'Can not copy image file';
	}
}