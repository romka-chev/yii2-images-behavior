<?php
/**
 * Author: Kulikov Roman
 * Email: flinnraider@yandex.ru
 */
return [
	'id'          => 'app-console',
	'class'       => 'yii\console\Application',
	'basePath'    => \Yii::getAlias( '@tests' ),
	'runtimePath' => \Yii::getAlias( '@tests/_output' ),
	'bootstrap'   => [ ],
	'components'  => [
		'db' => [
			'class'    => '\yii\db\Connection',
			'dsn'      => 'sqlite:' . \Yii::getAlias( '@tests/_output/temp.db' ),
			'username' => '',
			'password' => '',
		]
	]
];