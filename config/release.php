<?php

return [
	'command' => [
		'signature'   => 'project:release',
		'description' => 'Release project',
	],
	'vsc' => [
		'remote' => 'origin',
		'branch' => 'master',
	],
	'watch' => [
		'composer_update' => ['composer.json', 'composer.lock'],
	],
];