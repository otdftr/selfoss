<?php

namespace spouts\rss;

/**
 * Plugin for fetching the news from Lightreading with the full text
 *
 * @copyright  Copyright (c) Martin Sauter (http://www.wirelessmoves.com)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Martin Sauter  <martin.sauter@wirelessmoves.com>
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 */
class lightreading extends feed {
    /** @var string name of spout */
    public $name = '[English] lightreading.com';

    /** @var string description of this source type */
    public $description = 'Fetch Lightreading news with full content (not only the header as content).';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * - Values for type: text, password, checkbox, select
     * - Values for validation: alpha, email, numeric, int, alnum, notempty
     *
     * When type is "select", a new entry "values" must be supplied, holding
     * key/value pairs of internal names (key) and displayed labels (value).
     * See /spouts/rss/heise for an example.
     *
     * e.g.
     * array(
     *   "id" => array(
     *     "title"      => "URL",
     *     "type"       => "text",
     *     "default"    => "",
     *     "required"   => true,
     *     "validation" => array("alnum")
     *   ),
     *   ....
     * )
     *
     * @var bool|mixed
     */
    public $params = false;

    /**
     * addresses of feeds for the sections
     */
    private $feedUrl = 'http://www.lightreading.com/rss_simple.asp';

    /**
     * delimiters of the article text
     *
     * elements: start tag, attribute of start tag, value of start tag attribute, end
     */
    private $textDivs = [
         ['p', 'class', 'smalltallline lightergray', '<div class="divsplitter"']
    ];

    /**
     * htmLawed configuration
     */
    private $htmLawedConfig = [
        'abs_url' => 1,
        'base_url' => 'http://www.lightreading.com/',
        'comment' => 1,
        'safe' => 1,
    ];

    /**
     * loads content for given source
     *
     * @param string $url
     *
     * @return void
     */
    public function load($params) {
        parent::load(['url' => $this->getXmlUrl()]);
    }

    /**
     * returns the xml feed url for the source
     *
     * @param mixed $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl($params = null) {
        return $this->feedUrl;
    }

    /**
     * returns the content of this item
     *
     * @return string content
     */
    public function getContent() {
        if ($this->items !== false && $this->valid()) {
            $originalContent = @file_get_contents($this->getLink());
            foreach ($this->textDivs as $div) {
                $content = $this->getTag($div[1], $div[2], $originalContent, $div[0], $div[3]);
                if (is_array($content) && count($content) >= 1) {
                    $content[0] = '<p>' . mb_convert_encoding($content[0], 'UTF-8', 'Windows-1252');

                    return htmLawed($content[0], $this->htmLawedConfig);
                }
            }
        }

        return parent::getContent();
    }

    /**
     * get tag by attribute
     * taken from http://www.catswhocode.com/blog/15-php-regular-expressions-for-web-developers
     *
     * @return string content
     * @return string $attr attribute
     * @return string $value necessary value
     * @return string $xml data string
     * @return string $tag optional tag
     */
    private function getTag($attr, $value, $xml, $tag = null, $end = null) {
        if (is_null($tag)) {
            $tag = '\w+';
        } else {
            $tag = preg_quote($tag);
        }

        if (is_null($end)) {
            $end = '</\1>';
        } else {
            $end = preg_quote($end);
        }

        $attr = preg_quote($attr);
        $value = preg_quote($value);
        $tag_regex = '|<(' . $tag . ')[^>]*' . $attr . '\s*=\s*([\'"])' . $value . '\2[^>]*>(.*?)' . $end . '|ims';
        preg_match_all($tag_regex, $xml, $matches, PREG_PATTERN_ORDER);

        return $matches[3];
    }
}
