<?php

use HTML5\Parser\StringInputStream;
use HTML5\Parser\FileInputStream;
use HTML5\Parser\Scanner;
use HTML5\Parser\Tokenizer;
use HTML5\Parser\DOMTreeBuilder;
use HTML5\Serializer\OutputRules;
use HTML5\Serializer\Traverser;

/**
 * The main HTML5 front end.
 *
 * This class offers convenience methods for parsing and serializing HTML5.
 * It is roughly designed to mirror the \DOMDocument class that is 
 * provided with most versions of PHP.
 *
 * EXPERIMENTAL. This may change or be completely replaced.
 */
class HTML5 {

  public static $options = array(

    // If the serializer should encode all entities.
    'encode_entities' => FALSE,
  );

  /**
   * Load and parse an HTML file.
   *
   * This will apply the HTML5 parser, which is tolerant of many 
   * varieties of HTML, including XHTML 1, HTML 4, and well-formed HTML 
   * 3. Note that in these cases, not all of the old data will be 
   * preserved. For example, XHTML's XML declaration will be removed.
   *
   * The rules governing parsing are set out in the HTML 5 spec.
   *
   * @param string $file
   *   The path to the file to parse. If this is a resource, it is 
   *   assumed to be an open stream whose pointer is set to the first 
   *   byte of input.
   * @return \DOMDocument
   *   A DOM document. These object type is defined by the libxml 
   *   library, and should have been included with your version of PHP.
   */
  public static function load($file) {

    // Handle the case where file is a resource.
    if (is_resource($file)) {
      // FIXME: We need a StreamInputStream class.
      return self::loadHTML(stream_get_contents($file));
    }

    $input = new FileInputStream($file);
    return self::parse($input);
  }

  /**
   * Parse a HTML Document from a string.
   * 
   * Take a string of HTML 5 (or earlier) and parse it into a 
   * DOMDocument.
   *
   * @param string $string
   *   A html5 document as a string.
   * @return \DOMDocument
   *   A DOM document. DOM is part of libxml, which is included with 
   *   almost all distribtions of PHP.
   */
  public static function loadHTML($string) {
    $input = new StringInputStream($string);
    return self::parse($input);
  }

  /**
   * Convenience function to load an HTML file.
   *
   * This is here to provide backwards compatibility with the
   * PHP DOM implementation. It simply calls load().
   */
  public static function loadHTMLFile($file, $options = NULL) {
    return self::load($file, $options);
  }

  /**
   * Parse a HTML fragment from a string.
   *
   * @param string $string
   *   The html5 fragment as a string.
   *
   * @return \DOMDocumentFragment
   *   A DOM fragment. The DOM is part of libxml, which is included with
   *   almost all distributions of PHP.
   */
  public static function loadHTMLFragment($string) {
    $input = new StringInputStream($string);
    return self::parseFragment($input);
  }

  /**
   * Save a DOM into a given file as HTML5.
   *
   * @param mixed $dom
   *   The DOM to be serialized.
   * @param string $file
   *   The filename to be written.
   * @param array $options
   *   Configuration options when serializing the DOM. These include:
   *   - encode_entities: Text written to the output is escaped by default and not all
   *     entities are encoded. If this is set to TRUE all entities will be encoded.
   *     Defaults to FALSE.
   */
  public static function save($dom, $file, $options = array()) {
    $options = $options + self::options();
    $close = TRUE;
    if (is_resource($file)) {
      $stream = $file;
      $close = FALSE;
     }
    else {
      $stream = fopen($file, 'w');
    }
    $rules = new OutputRules($stream, $options);
    $trav = new Traverser($dom, $stream, $rules, $options);

    $trav->walk();

    if ($close) {
      fclose($stream);
    }
  }

  /**
   * Convert a DOM into an HTML5 string.
   *
   * @param mixed $dom
   *   The DOM to be serialized.
   * @param array $options
   *   Configuration options when serializing the DOM. These include:
   *   - encode_entities: Text written to the output is escaped by default and not all
   *     entities are encoded. If this is set to TRUE all entities will be encoded.
   *     Defaults to FALSE.
   *
   * @return string
   *   A HTML5 documented generated from the DOM.
   */
  public static function saveHTML($dom, $options = array()) {
    $stream = fopen('php://temp', 'w');
    static::save($dom, $stream, $options);
    return stream_get_contents($stream, -1, 0);
  }

  /**
   * Parse an input stream.
   *
   * Lower-level loading function. This requires an input stream instead 
   * of a string, file, or resource.
   */
  public static function parse(\HTML5\Parser\InputStream $input) {
    $events = new DOMTreeBuilder();
    $scanner = new Scanner($input);
    $parser = new Tokenizer($scanner, $events);

    $parser->parse();

    return $events->document();
  }

  public static function parseFragment(\HTML5\Parser\InputStream $input) {
    $events = new DOMTreeBuilder(TRUE);
    $scanner = new Scanner($input);
    $parser = new Tokenizer($scanner, $events);

    $parser->parse();

    return $events->fragment();
  }

  /**
   * Get the default options.
   *
   * @return array
   *   The default options.
   */
  public static function options() {
    return self::$options;
  }

  /**
   * Set a default option.
   *
   * @param string $name
   *   The option name.
   * @param mixed $value
   *   The option value.
   */
  public static function setOption($name, $value) {
    self::$options[$name] = $value;
  }

}
