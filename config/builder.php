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
		'npm_install'     => ['package.json', 'npm-shrinkwrap.json'],
		'laravel_mix'     => [
			'webpack.mix.js',
			'resources/assets/js',
			'resources/assets/sass',
			'resources/assets/fonts',
		],
	],
];