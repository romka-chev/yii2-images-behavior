<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */

namespace romkaChev\yii2\images\helpers;

/**
 * Class FileHelper
 * @package romkaChev\yii2\images\helpers
 */
class FileHelper extends \yii\helpers\FileHelper {

	/**
	 * @link http://stackoverflow.com/a/982067
	 *
	 * @param string $url
	 *
	 * @return bool
	 */
	public static function remoteFileExists( $url ) {
		$curl = curl_init( $url );

		//don't fetch the actual page, you only want to check the connection is ok
		curl_setopt( $curl, CURLOPT_NOBODY, true );

		//do request
		$result = curl_exec( $curl );

		$ret = false;

		//if request did not fail
		if ( $result !== false ) {
			//if request was ok, check response code
			$statusCode = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

			if ( $statusCode == 200 || $statusCode == 304 ) {
				$ret = true;
			}

		}

		curl_close( $curl );

		return $ret;
	}

}