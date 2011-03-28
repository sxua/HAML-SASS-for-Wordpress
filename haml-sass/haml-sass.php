<?php
/*
Plugin Name: HAML/SASS for Wordpress
Description: HAML,SASS and SCSS for Wordpress.
Author: Olexandr Skrypnyk
Version: 1.0.2
*/

register_activation_hook(__FILE__, 'haml_sass_activate');
register_deactivation_hook(__FILE__, 'haml_sass_deactivate');
add_filter('template_include', 'haml_template_include');

define('COMPILED_TEMPLATES', WP_CONTENT_DIR . '/compiled-templates');

function haml_sass_activate() {
	$haml_version = exec('haml --version');
	preg_match('/haml 3\.1.*/i',$haml_version,$match);
	if ($haml_version === '') {
		add_action('admin_notices','haml_not_installed');
	} elseif (strtolower($match[1]) !== 'haml 3.1') {
		add_action('admin_notices','haml_bad_version');
	}
	if (!file_exists(COMPILED_TEMPLATES) && !mkdir(COMPILED_TEMPLATES)) {
		add_action('admin_notices', 'haml_dir_warning');
	}
}

function haml_sass_deactivate() {}

function haml_dir_warning() {
	echo '<div class="updated fade"><p>Wordpress can\'t access to directory <em>'.COMPILED_TEMPLATES.'</em>. Please make sure it\'s writeable by your webserver.</p></div>';
}

function haml_not_installed() {
	echo '<div class="updated fade"><p>It seems, you have no Haml installed on your server. Install it with: <em>gem install haml --pre</em></p></div>';
}

function haml_bad_version() {
	echo '<div class="updated fade"><p>In order to include <em><?php ?></em> tags in your HAML files, you need to install 3.1 version of Haml. <em>gem isntall haml --pre</em></p></div>';
}

function haml_fix_path($path) {
	return preg_replace('/(.*)\/(\w+\.php)/i','$1/haml/$2',$path);
}

function haml_template_include($template) {
	$haml_template = (substr($template,-5) === '.haml') ? $template : str_replace('.php','.haml',$template);
	haml_include(haml_fix_path(TEMPLATEPATH.'/layout.haml'),array('page' => haml_fix_path($haml_template)));
}

function do_camelcase($path) {
	$path = str_replace('.haml','.php',$path);
	$path_array = explode('-',$path);
	if (sizeof($path_array) < 2) {
		$camelcased_file_name = ucfirst(strtolower($path));
	} else {
		$camelcased_file_name = '';
		foreach ($path_array as $i => $part) {
			$camelcased_file_name .= ($i === 0) ? strtolower($part) : ucfirst(strtolower($part));
		}
	}
	return COMPILED_TEMPLATES.'/'.strtolower(get_template()).'_'.$camelcased_file_name;
}

function haml_include($haml, $args = array()) {
	$cache = do_camelcase($haml);
	if (!file_exists($cache) || filemtime($cache) < filemtime($haml)) {
		@unlink($cache);
		exec('haml --no-escape-attrs --style ugly --format html5 '.escapeshellcmd($haml).' '.escapeshellcmd($cache));
	}
	extract($args);
	require($hphp);
}

function haml_body_class($class = '') {
	echo join(' ',get_body_class($class));
}

function sass($file_name,$scss = false) {
	$ext = ($scss) ? : '.scss' : '.sass';
	if (strpos($file_name, '.css') || strpos($file_name, $ext)) {
		$file_name = str_replace(array('.css', $ext), '', $file_name);
	}
	if (strpos($filename, '/')) {
		$parts = explode('/', $file_name);
		$file_name = $parts[sizeof($parts)-1];
	}
	$sass = TEMPLATEPATH.'/'.$file_name.$ext;
	$css = TEMPLATEPATH.'/'.$file_name.'.css';
	if (!file_exists($sass)) {
		sass_error($css,'File '.$sass.' does not exist.');
	} else if (!file_exists($css) || filemtime($css) < filemtime($sass)) {
		@unlink($css);
		$run = ($scss) ? 'sass --scss ' : 'sass ';
		exec($run.escapeshellcmd($sass).' '.escapeshellcmd($css));
	}
	return get_bloginfo('template_directory') . '/' . $file_name . '.css';
}

function sass_error($file,$error) {
	@unlink($file);
	file_put_contents($file, 'body:before { white-space: pre; font-family: monospace; content: "Sass error: '.str_replace('"','\"',$error).'";}');
}
?>
