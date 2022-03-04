<?php

/**
 * @file
 * Export users to Markup.
 */

use League\HTMLToMarkdown\HtmlConverter;

/**
 * Migrate Drupal 7 to Markdown content script. (@author: guidor - grobertone@weknowinc.com)
 *
 * Dependencies: league/html-to-markdown.
 *
 * to execute: php user-migrate.php [CONTENT_TYPE] > users.yml
 */

define('DRUPAL_SITE_URL', '7sabores.com');

// Loading Drupal Bootstrap.
define('DRUPAL_ROOT', getcwd());
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

$destination_dir = '../static';
if (!file_exists($destination_dir)) {
  mkdir($destination_dir, 0777, TRUE);
}

// HTML Converter autoload.
requireAutoloader();
$converter = new HtmlConverter();

$query = db_select('users', 'u');
$query->leftJoin('users_roles', 'r', 'u.uid = r.uid');
$query->innerJoin('node', 'n', 'u.uid = n.uid');

$uids = $query->fields('u', ['uid'])
  ->condition('r.rid', '8')
  ->condition('u.status', 1)
  ->orderBy('u.created', 'ASC')
  ->execute()
  ->fetchCol();

$users = user_load_multiple($uids);

$user_dir = $destination_dir . '/user/';
if (!file_exists($user_dir)) {
  mkdir($user_dir, 0777, TRUE);
}
else {
  delete_directory($user_dir);
  mkdir($user_dir, 0777, TRUE);
}

foreach ($users as $user) {

  if ($user->name) {
    $text = "---\n";

    $path = drupal_get_path_alias('user/' . $user->uid, 'es');
    $text .= "path: $path\n";


    $username = clean_text($user->name);
    $text .= "id: $username\n";

    $email = clean_text($user->mail);
    $text .= "email: $email\n";

    $name = '';
    if ($user->field_nombre['und'][0]) {
      $name = $user->field_nombre['und'][0]['value'];
    }
    if ($user->field_apellido['und'][0]) {
      $name .= ($name) ? ' ' . $user->field_apellido['und'][0]['value'] : $user->field_apellido['und'][0]['value'];
    }
    if ($name) {
      $name = clean_text($name);
      $text .= "name: $name\n";
    }

    if ($user->field_pais['und'][0]) {
      $country = clean_text($user->field_pais['und'][0]['value']);
      $text .= "country: $country\n";
    }

    if ($user->field_estado_provincia['und'][0]) {
      $province = clean_text($user->field_estado_provincia['und'][0]['value']);
      $text .= "province: $province\n";
    }

    $date = date('Y-m-d', $user->created);
    $text .= "created: $date\n";

    $profile = profile2_load_by_user($user, $type_name = 'contribuidor');

    if ($profile->field_fotografia['und']) {
      $uri = clean_media($profile->field_fotografia['und'][0]["uri"]);
      $text .= "picture: $uri\n";
    }

    if ($profile->field_compa_a['und']) {
      $title = clean_text($profile->field_compa_a['und'][0]["title"]);
      $url = clean_text($profile->field_compa_a['und'][0]["url"]);

      $text .= "company: $title\n";
      $text .= "company_url: $url\n";
    }

    if ($profile->field_titulo['und']) {
      $title = clean_text($profile->field_titulo['und'][0]["value"]);
      $text .= "title: $title\n";
    }

    if ($profile->field_facebook['und']) {
      $title = link_cleanup_url($profile->field_facebook['und'][0]["url"]);
      $text .= "facebook: $title\n";
    }

    if ($profile->field_twitter['und']) {
      $title = link_cleanup_url($profile->field_twitter['und'][0]["url"]);
      $text .= "twitter: $title\n";
    }

    if ($profile->field_linkedin['und']) {
      $title = link_cleanup_url($profile->field_linkedin['und'][0]["url"]);
      $text .= "linkedin: $title\n";
    }

    if ($profile->field_sitio_personal['und']) {
      $title = link_cleanup_url($profile->field_sitio_personal['und'][0]["url"]);
      $text .= "website: $title\n";
    }

    if ($profile->field_perfil_de_drupal['und']) {
      $title = link_cleanup_url($profile->field_perfil_de_drupal['und'][0]["url"]);
      $text .= "drupal: $title\n";
    }

    $text .= "---\n\n";

    if ($profile->field_descripcion['und']) {

      $converter->getConfig()->setOption('strip_tags', TRUE);

      $body = clean_media($converter->convert($profile->field_descripcion['und'][0]['value']));
      $text .= "$body\n";
    }

    $filename = file_name($path);
    write_file($user_dir . $filename, $text);
  }
}

/**
 * Delete old directory.
 */
function delete_directory($dir) {
  if (!file_exists($dir)) {
    return TRUE;
  }
  if (!is_dir($dir) || is_link($dir)) {
    return unlink($dir);
  }
  foreach (scandir($dir) as $item) {
    if ($item == '.' || $item == '..') {
      continue;
    }
    if (!delete_directory($dir . "/" . $item, FALSE)) {
      chmod($dir . "/" . $item, 0777);
      if (!delete_directory($dir . "/" . $item, FALSE)) {
        return FALSE;
      }
    };
  }
  return rmdir($dir);
}

/**
 * Save data to Files.
 */
function write_file($filen_name, $text) {
  $my_file = fopen($filen_name, "w") or die("Unable to create file!");
  fwrite($my_file, $text);
  fclose($my_file);
}

/**
 * Automatic file name generation.
 */
function file_name($path) {
  $explode_path = explode('/', $path);
  return $explode_path[1] . '.md';
}

/**
 * Clean text strings.
 */
function clean_text($text) {
  if (count(explode(' ', $text)) >= 2) {
    $text = "'$text'";
  }

  return $text;
}

/**
 * Clean media.
 */
function clean_media($str) {
  $asset_dir = '../assets/';

  $str = str_replace('public://', $asset_dir, $str);
  $str = str_replace('http://www.' . DRUPAL_SITE_URL . '/sites/default/files/styles/large/public/', $asset_dir, $str);
  $str = str_replace('http://www.' . DRUPAL_SITE_URL . '/sites/default/files/styles/medium/public/', $asset_dir, $str);
  $str = str_replace('http://www.' . DRUPAL_SITE_URL . '/sites/default/files/styles/thumbnail/public/', $asset_dir, $str);
  $str = str_replace('http://www.' . DRUPAL_SITE_URL . '/sites/default/files/styles/image_184x104/public/', $asset_dir, $str);
  $str = str_replace('http://www.' . DRUPAL_SITE_URL . '/sites/default/files/', $asset_dir, $str);

  $str = str_replace('http://' . DRUPAL_SITE_URL . '/sites/default/files/styles/large/public/', $asset_dir, $str);
  $str = str_replace('http://' . DRUPAL_SITE_URL . '/sites/default/files/styles/medium/public/', $asset_dir, $str);
  $str = str_replace('http://' . DRUPAL_SITE_URL . '/sites/default/files/styles/thumbnail/public/', $asset_dir, $str);
  $str = str_replace('http://' . DRUPAL_SITE_URL . '/sites/default/files/styles/image_184x104/public/', $asset_dir, $str);
  $str = str_replace('http://' . DRUPAL_SITE_URL . '/sites/default/files/', $asset_dir, $str);

  return $str;
}

/**
 * Autoloader.
 */
function requireAutoloader() {
  $autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
  ];
  foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
      include_once $path;
      break;
    }
  }
}
