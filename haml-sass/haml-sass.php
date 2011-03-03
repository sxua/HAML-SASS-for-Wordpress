<?php
/*
Plugin Name: HAML/SASS for Wordpress
Description: HAML,SASS and SCSS for Wordpress.
Author: Olexandr Skrypnyk
Version: 1.0.2
*/

define('COMPILED_TEMPLATES', WP_CONTENT_DIR . '/compiled-templates');
register_activation_hook(__FILE__, 'haml_activate');
register_deactivation_hook(__FILE__, 'haml_deactivate');

function haml_activate() {
	if (!file_exists(COMPILED_TEMPLATES) && !mkdir(COMPILED_TEMPLATES)) {
		add_action('admin_notices', 'haml_dir_warning');
	}
}

function haml_deactivate() {}

function haml_dir_warning() {
	echo "<div class='updated fade'><p>php-haml will currently work: you need to create <em>" . COMPILED_TEMPLATES . "</em> and make sure it's writeable by your webserver</p></div>";
}

add_filter('template_include', 'haml_template_include');

function haml_fix_path($path) {
	$path = explode('/',$path);
	$size = sizeof($path);
	$path[$size] = $path[$size - 1];
	$path[$size - 1] = 'haml';
	$path = join('/',$path);
	return $path;
}

function haml_template_include($template) {
	if (substr($template, -5) === '.haml') {
		$haml_template = haml_fix_path($template);
	} else {
		$haml_template = haml_fix_path(str_replace('.php', '.haml', $template));
	}
	haml_include(haml_fix_path(TEMPLATEPATH.'/layout.haml'),array('page' => $haml_template));
}

function haml_include($haml, $args = array()) {
	$hphp = COMPILED_TEMPLATES.'/'.md5($haml).'.hphp';
	if (!file_exists($hphp) || filemtime($hphp) < filemtime($haml)) {
		@unlink($hphp);
		exec('haml --no-escape-attrs --style ugly --format html5 '.escapeshellcmd($haml).' '.escapeshellcmd($hphp));
	}
	extract($args);
	require($hphp);
}

function haml_body_class($class = '') {
	echo join(' ',get_body_class($class));
}

function sass($filename) {
	if (strpos($filename, '.css') || strpos($filename, '.sass')) {
		$filename = str_replace(array('.css', '.sass'), '', $filename);
	}
	if (strpos($filename, '/')) {
		$parts = explode('/', $filename);
		$filename = $parts[count($parts)-1];
	}
	$sass_filename = TEMPLATEPATH.'/'.$filename.'.sass';
	$css_filename = TEMPLATEPATH.'/'.$filename.'.css';
	if (!file_exists($sass_filename)) {
		sass_error($css_filename,'File '.$sass_filename.' does not exist.');
	} else if (!file_exists($css_filename) || filemtime($css_filename) < filemtime($sass_filename)) {
		@unlink($css_filename);
		exec('sass '.escapeshellcmd($sass_filename).' '.escapeshellcmd($css_filename));
	}
	
	return get_bloginfo('template_directory') . '/' . $filename . '.css';
}

function scss($filename) {
	if (strpos($filename, '.css') || strpos($filename, '.scss')) {
		$filename = str_replace(array('.css', '.scss'), '', $filename);
	}
	if (strpos($filename, '/')) {
		$parts = explode('/', $filename);
		$filename = $parts[count($parts)-1];
	}
	$sass_filename = TEMPLATEPATH . '/'. $filename . '.scss';
	$css_filename = TEMPLATEPATH . '/'. $filename . '.css';
	if (!file_exists($sass_filename)) {
		sass_error($css_filename, 'File ' . $sass_filename . ' does not exist.');
	} else if (!file_exists($css_filename) || filemtime($css_filename) < filemtime($sass_filename)) {
		@unlink($css_filename);
		exec('sass --scss '.escapeshellcmd($sass_filename).' '.escapeshellcmd($css_filename));
	}
	
	return get_bloginfo('template_directory').'/'.$filename .'.css';
}

function sass_error($css_filename, $error) {
	@unlink($css_filename);
	file_put_contents($css_filename, 'body:before { white-space: pre; font-family: monospace; content: "Sass for Wordpress error: ' . str_replace('"','\"', $error) . '"; }');
}
?>
