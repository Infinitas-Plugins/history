<?php
	$config = array();

	$config['History'] = array(
		'behaviorConfig' => array(
			'limit' => false,
			'ignore' => array(
				'views'
			),
			'auto' => true,
			'useDbConfig' => null
		),
		'suffix' => '_revs'
	);

	$config['Drafted'] = array(
		'behaviorConfig' => array(
		),
		'suffix' => '_drafts'
	);