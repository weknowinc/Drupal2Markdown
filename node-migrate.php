<?php

/**
 * @file
 * Export nodes to Markup.
 */

use League\HTMLToMarkdown\HtmlConverter;

/**
 * Migrate Drupal 7 to Markdown content script. (@author: guidor - grobertone@weknowinc.com)
 *
 * Dependencies: league/html-to-markdown.
 *
 * example to use: php node-migrate.php [CONTENT_TYPE]
 *                 php node-migrate.php blog
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

// Args.
if (!isset($argv[1])) {
  echo "error: A content type is required.\n";
}

$content_type = $argv[1];

// Query.
$nids = db_select('node', 'n')
  ->fields('n', ['nid'])
  ->fields('n', ['type'])
  ->condition('n.status', 1)
  ->condition('n.type', $content_type)
  ->orderBy('n.created', 'ASC')
  ->execute()
  ->fetchCol();
$nodes = node_load_multiple($nids);

$content_type_dir = $destination_dir . '/' . $content_type . '/';
if (!file_exists($content_type_dir)) {
  mkdir($content_type_dir, 0777, TRUE);
}
else {
  delete_directory($content_type_dir);
  mkdir($content_type_dir, 0777, TRUE);
}

if ($nodes) {
  foreach ($nodes as $node) {
    $text = "---\n";
    $title = clean_text($node->title);
    $text .= "title: $title\n";

    $date = date('Y-m-d', $node->created);
    $text .= "date: $date\n";

    $user_author = user_load($node->uid);
    $author = clean_text($user_author->name);
    $text .= "author: $author\n";

    $path = drupal_get_path_alias('node/' . $node->nid, 'es');
    print_r($path . "\n");
    $text .= "path: $path\n";

    $text .= "nid: $node->nid\n";

    // Blog custom fields.
    if ($content_type == 'blog') {
      if ($node->field_tema['und']) {
        $text .= "topics:\n";
        foreach ($node->field_tema['und'] as $topic) {
          $taxonomy = taxonomy_term_load($topic['tid']);
          $taxonomy_name = clean_text($taxonomy->name);
          $text .= "  - $taxonomy_name\n";
        }
      }

      if ($node->field_cover['und']) {
        $uri = clean_media($node->field_cover['und'][0]["uri"]);
        $text .= "cover: $uri\n";
      }
    }

    // Glosario custom fields.
    if ($content_type == 'glosario') {
      if ($node->field_tipo['und']) {
        $taxonomy = taxonomy_term_load($node->field_tipo['und'][0]['tid']);
        $taxonomy_name = clean_text($taxonomy->name);
        $text .= "type: $taxonomy_name\n";
      }
    }

    // Lesson custom fields.
    if ($content_type == 'lesson') {
      if ($node->field_vimeo_free['und'][0]['vimeo']) {
        $vimeo = $node->field_vimeo_free['und'][0]['vimeo'];
        $text .= "vimeo: $vimeo\n";
      }

      if ($node->field_tema['und']) {
        $text .= "topics:\n";
        foreach ($node->field_tema['und'] as $topic) {
          $taxonomy = taxonomy_term_load($topic['tid']);
          $taxonomy_name = clean_text($taxonomy->name);
          $text .= "  - $taxonomy_name\n";
        }
      }

      if ($node->field_duracion['und'][0]['value']) {
        $duracion = $node->field_duracion['und'][0]['value'];
        $text .= "duration: $duracion\n";
      }

      if ($node->field_score['und'][0]['rating']) {
        $score = $node->field_score['und'][0]['rating'];
        $text .= "score: $score\n";
      }

      if ($node->field_type['und'][0]['value']) {
        $type = $node->field_type['und'][0]['value'];
        $clean_type = clean_text($type);
        $text .= "type: $clean_type\n";
      }

      if ($node->field_version['und']) {
        $taxonomy = taxonomy_term_load($node->field_version['und'][0]['tid']);
        $taxonomy_name = clean_text($taxonomy->name);
        $text .= "version: $taxonomy_name\n";
      }

      if ($node->field_etiquetas['und']) {
        $taxonomy = taxonomy_term_load($node->field_etiquetas['und'][0]['tid']);
        $taxonomy_name = clean_text($taxonomy->name);
        $text .= "tags: $taxonomy_name\n";
      }

      if ($node->field_timeline) {
        $text .= "transcript:\n";
        $field_collection_item = reset(entity_load('field_collection_item', [$node->field_timeline['und'][0]['value']]));
        if ($field_collection_item->field_marca_temporal) {
          $marca_temporal = $field_collection_item->field_marca_temporal['und'];
          foreach ($marca_temporal as $item) {
            $marca_temporal_item = reset(entity_load('field_collection_item', [$item['value']]));
            $texto = clean_text($marca_temporal_item->field_texto_['und'][0]['value']);
            $segundo = $marca_temporal_item->field_segundo['und'][0]['value'];
            $text .= "  - time: '$segundo'\n";
            $text .= "    text: $texto\n";
          }
        }
      }

      if ($node->field_snapshot['und']) {
        $uri = clean_media($node->field_snapshot['und'][0]["uri"]);
        $text .= "snapshot: $uri\n";
      }
    }

    // Entrevista custom fields.
    if ($content_type == 'entrevista') {

      if ($node->field_vimeo_entrevista_link['und'][0]['vimeo']) {
        $vimeo = $node->field_vimeo_entrevista_link['und'][0]['vimeo'];
        $text .= "vimeo: $vimeo\n";
      }

      if ($node->field_tema['und']) {
        $text .= "topics:\n";
        foreach ($node->field_tema['und'] as $topic) {
          $taxonomy = taxonomy_term_load($topic['tid']);
          $taxonomy_name = clean_text($taxonomy->name);
          $text .= "  - $taxonomy_name\n";
        }
      }

      if ($node->field_type['und'][0]['value']) {
        $type = $node->field_type['und'][0]['value'];
        $clean_type = clean_text($type);
        $text .= "type: $clean_type\n";
      }

      if ($node->field_duracion['und'][0]['value']) {
        $duracion = $node->field_duracion['und'][0]['value'];
        $text .= "duration: $duracion\n";
      }

      if ($node->field_score['und'][0]['rating']) {
        $score = $node->field_score['und'][0]['rating'];
        $text .= "score: $score\n";
      }

      if ($node->field_snapshot['und']) {
        $uri = clean_media($node->field_snapshot['und'][0]["uri"]);
        $text .= "snapshot: $uri\n";
      }

      $text .= "---\n\n";
    }

    if ($content_type != 'entrevista') {
      if ($node->body['und'][0]['summary']) {
        $summary = clean_media(strip_tags($converter->convert($node->body['und'][0]['summary'])));
        $summary = str_replace('"', '\"', $summary);
        $text .= "summary: \"$summary\"\n";
      }

      $text .= "---\n\n";
      if ($node->body['und'][0]['value']) {
        $converter->getConfig()->setOption('strip_tags', TRUE);

        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($node->body['und'][0]['value'], 'HTML-ENTITIES', 'UTF-8'));

        $xpath = new DOMXPath($dom);

        foreach ($xpath->evaluate("//pre") as $section) {
          $section->removeAttribute('class');
        }

        foreach ($xpath->evaluate("//img") as $section) {
          $src = $section->getAttribute('src');

          $parsed = parse_url($src);
          $query = $parsed['query'];
          parse_str($query, $params);
          unset($params['itok']);
          $parsed['query'] = http_build_query($params);

          $new_scr = unparse_url($parsed);
          $src = $section->setAttribute('src', $new_scr);
        }

        $clean_html = $dom->saveHtml();


        // Echo $converter->convert($clean_html);
        $body = clean_media($converter->convert($clean_html));
        $text .= "$body\n";
      }
    }

    $filename = file_name($path);
    write_file($content_type_dir . $filename, $text);
  }
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
 * Clean text strings.
 */
function clean_text($text) {

  if (count(explode(' ', $text)) >= 2) {
    $text = "'$text'";
  }

  return $text;
}

/**
 * Reverse parse_url function.
 *
 * @param array $parsed_url
 *
 * @return void
 */
function unparse_url($parsed_url) {
  $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
  $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
  $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
  $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
  $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
  $pass     = ($user || $pass) ? "$pass@" : '';
  $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
  $query    = !empty($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
  $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
  return "$scheme$user$pass$host$port$path$query$fragment";
}
