<?php

return [
	'command' => [
		'signature'   => 'project:build {--cu} {--nu} {--ni}',
		'description' => 'Run build project',
	],
	'vsc' => [
		'log_depth' => 10,
		'remote'    => 'origin',
		'branch'    => 'master',
	]
];