<?php
// This file is generated. Do not modify it manually.
return array(
	'code-embed' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-secure-code-embeds/code-embed',
		'version' => '96caefdcc51df0461df4',
		'title' => 'Code Embed',
		'category' => 'embed',
		'icon' => 'editor-code',
		'description' => 'Place an administrator-approved code embed on this page.',
		'keywords' => array(
			'html',
			'css',
			'javascript',
			'custom',
			'code'
		),
		'textdomain' => 'wp-secure-code-embeds',
		'attributes' => array(
			'ref' => array(
				'type' => 'integer',
				'default' => 0
			)
		),
		'supports' => array(
			'align' => array(
				'wide',
				'full'
			),
			'html' => false
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'editorScript' => 'file:index.js',
		'editorStyle' => 'file:index.css',
		'render' => 'file:render.php'
	),
	'code-embed-fields' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'wp-secure-code-embeds/code-embed-fields',
		'version' => '87770f67328863b1e3ca',
		'title' => 'Code Embed Fields',
		'category' => 'embed',
		'icon' => 'editor-code',
		'description' => 'Edit the head and body code of a code embed.',
		'textdomain' => 'wp-secure-code-embeds',
		'supports' => array(
			'html' => false,
			'inserter' => false,
			'multiple' => false,
			'reusable' => false
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'editorScript' => 'file:index.js',
		'editorStyle' => 'file:index.css'
	)
);
