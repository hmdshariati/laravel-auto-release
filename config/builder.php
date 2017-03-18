<?php

return [
	'command' => [
		'signature'   => 'project:build {--composer-update}',
		'description' => 'Run build project',
	],
	'vsc' => [
		'log_depth' => 10,
		'remote'    => 'origin',
		'branch'    => 'master',
	]
];