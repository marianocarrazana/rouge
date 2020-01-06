<?php

namespace Rouge;

/**
 * Utils.
 */
class Utils
{
	/**
	 * Set html content
	 * @param DOMElement $element node element
	 * @param string     $html    set html
	 */
    public function setInnerHTML(\DOMElement $element, string $html)
    {
        $fragment = $element->ownerDocument->createDocumentFragment();
        $fragment->appendXML($html);
        $clone = $element->cloneNode();
        $clone->appendChild($fragment);
        $element->parentNode->replaceChild($clone, $element);
    }

    /**
     * Load a json file
     * @param  string       $path             local path
     * @param  bool|boolean $convert_to_array return an array
     * @return mixed                         array or object based on json content
     */
    public static function loadJSON(string $path, bool $convert_to_array = true)
    {
        if (!file_exists($path)) {
            trigger_error("File doesnt exist:" . $path, E_USER_WARNING);
            return false;
        }

        $string = file_get_contents($path);
        $json   = json_decode($string, $convert_to_array);
        if($json == null){
            trigger_error("JSON bad formatted or null",E_USER_NOTICE);
            return false;
        }
        return $json;
    }
}
