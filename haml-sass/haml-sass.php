<?php
/*
Plugin Name: HAML/SASS for Wordpress
Description: HAML,SASS and SCSS for Wordpress.
Author: Olexandr Skrypnyk
Version: 1.0.2
*/

class Haml {
  define('COMPILED_TEMPLATES', WP_CONTENT_DIR . '/compiled-templates');
  
  static function activate() {
    $haml_version = exec('haml --version');
    preg_match('/haml 3\.1.*/i',$haml_version,$match);
    if ($haml_version === '') {
      add_action('admin_notices','haml_not_installed');
    } elseif (strtolower($match[1]) !== 'haml 3.1') {
      add_action('admin_notices','haml_bad_version');
    }
    if (!file_exists(COMPILED_TEMPLATES) && !mkdir(COMPILED_TEMPLATES)) add_action('admin_notices', 'haml_error_dir');
  }
  
  static function deactivate() {
    return false;
  }
  
  static function error_dir() {
    echo '<div class="updated fade"><p>Wordpress can\'t access to directory <em>'.COMPILED_TEMPLATES.'</em>. Please make sure it\'s writeable by your webserver.</p></div>';
  }
  
  static function error_not_installed() {
    echo '<div class="updated fade"><p>It seems, you have no Haml installed on your server. Install it with: <em>[sudo] gem install haml</em></p></div>';
  }
  
  static function error_bad_version() {
    echo '<div class="updated fade"><p>In order to include <em><?php ?></em> tags in your HAML files, you need to install 3.1 version of Haml. <em>[sudo] gem install haml</em></p></div>';
  }
  
  private function fix_path($path) {
    return preg_replace('/(.*)\/(\w+\.php)/i','$1/haml/$2',$path);
  }
  
  static function template_include($template) {
    $haml_template = (substr($template,-5) === '.haml') ? $template : str_replace('.php','.haml',$template);
    self::haml_include($this->fix_path(TEMPLATEPATH.'/layout.haml'),array('page' => $this->fix_path($haml_template)));
  }

  private function do_camelcase($path) {
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

  static function haml_include($haml, $args = array()) {
    $cache = $this->do_camelcase($haml);
    if (!file_exists($cache) || filemtime($cache) < filemtime($haml)) {
      @unlink($cache);
      exec('haml --no-escape-attrs --style ugly --format html5 '.escapeshellcmd($haml).' '.escapeshellcmd($cache));
    }
    extract($args);
    require($hphp);
  }

  static function body_class($class = '') {
    echo join(' ',get_body_class($class));
  }
  
  static function sass($file_name,$scss = false) {
    $ext = ($scss) ? : '.scss' : '.sass';
    if (strpos($file_name, '.css') || strpos($file_name, $ext)) $file_name = str_replace(array('.css', $ext), '', $file_name);
    if (strpos($filename, '/')) {
      $parts = explode('/', $file_name);
      $file_name = $parts[sizeof($parts)-1];
    }
    $sass = TEMPLATEPATH.'/'.$file_name.$ext;
    $css = TEMPLATEPATH.'/'.$file_name.'.css';
    if (!file_exists($sass)) {
      $this->sass_error($css,'File '.$sass.' does not exist.');
    } else if (!file_exists($css) || filemtime($css) < filemtime($sass)) {
      @unlink($css);
      $run = ($scss) ? 'sass --scss ' : 'sass ';
      exec($run.escapeshellcmd($sass).' '.escapeshellcmd($css));
    }
    return get_bloginfo('template_directory') . '/' . $file_name . '.css';
  }
  
  private function sass_error($file,$error) {
    @unlink($file);
    file_put_contents($file, 'body:before { white-space: pre; font-family: monospace; content: "Sass error: '.str_replace('"','\"',$error).'";}');
  }
}

function haml_sass_activate() {
  Haml::activate();
}

function haml_sass_deactivate() {
  Haml::deactivate();
}

function haml_error_dir() {
  Haml::error_dir();
}

function haml_error_not_installed() {
  Haml::error_not_installed();
}

function haml_error_bad_version() {
  Haml::error_bad_version();
}

function haml_template_include() {
  Haml::template_include();
}

function haml_include() {
  Haml::haml_include();
}

function haml_body_class() {
  Haml::body_class();
}

function sass_stylesheet() {
  Haml::sass();
}

register_activation_hook(__FILE__, 'haml_sass_activate');
register_deactivation_hook(__FILE__, 'haml_sass_deactivate');
add_filter('template_include', 'haml_template_include');
?>