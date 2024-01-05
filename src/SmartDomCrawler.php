<?php

namespace SmartDomCrawler;

use DOMAttr;
use Symfony\Component\DomCrawler\Crawler;

class SmartDomCrawler extends Crawler
{

    public $error = null;
    public $content = null;
    public $head = null; // <head>...</head>
    public $dom = null;
    public $xml = null;
    public $htmlTags = null;

    /**
     * Count tag occurrences
     * @param string $tag
     * @return int
     */
    public function countTag($tag)
    {
        try {
            return $this->filterXPath('//'.$tag)->count();
        } catch (\Exception $e) {
            return 0;
        }

    }

    /**
     * Get last tag content
     * @param string $tag
     * @return string
     */
    public function getTagContent($tag)
    {
        $value = null;

        if ($this->filterXPath('//'.$tag)->count() > 0) {
            $value = $this->filterXPath('//' . $tag)->text();
            $value = $this->cleanContent($value);
        }
        //echoLog('Estrai tag getTag <' . $tag .'> : ' . $value);
        return $value;
    }

    /**
     * Get all tag content
     * @param string $tag
     * @return array
     */
    public function getAllTagContent($tag)
    {

        $value = $this->filter($tag)->each(function (Crawler $node, $i): string {
            return $node->text();
        });
        //dd($value);

        //echoLog('Estrai tag getAllTag ' . $tag .' -> ' . json_encode($value));

        return $value;
    }

    /**
     * Get all tag list
     * @param string $tag
     * @return Crawler
     */
    public function getAllTagList($tag)
    {

        $list = $this->filter($tag)->each(function (Crawler $node, $i): Crawler {
            return $node;
        });
        //dd($list);

        //echoLog('Estrai tag getAllTag ' . $tag .' -> ' . json_encode($list));

        return $list;
    }

    /**
     * Get last tag/attribute value
     * @param string $tag
     * @param string $attr
     * @return string
     */
    public function getTagAttr($tag, $attr)
    {
        $attrContent = $this->first($tag.'['.$attr.']');
        $attrContent = html_entity_decode($attrContent->attr($attr));

        //echoLog('Estrai tag getTagAttr <' . $tag .'> attrib='. $attr . ' : ' . $attrContent);
        return $attrContent;
    }

    /**
     * Get last tag/attribute value
     * @param string $tag
     * @return array
     */
    public function getTagAllAttr($tag)
    {
        //echoLog('Estrai tag getTagAllAttr <' . $tag . '>');
        $value = $this->filter($tag)->each(function (Crawler $node, $i) {
            return $node->attributes();
        });

        //echoLog('Estrai tag getTagAllAttr <' . $tag .'> attrib='. json_encode($value));
//dd($value);
        return $value;
    }

    /**
     * Return content Meta Tag
     * @param string $name
     * @return string
     */
    public function getMetaTag($name): string
    {
        $list = $this->getMetaTags();
        return $list[$name];
    }

    /**
     * Return array all Meta Tags
     * @return array name -> content
     */
    public function getMetaTags(): array
    {
        $tagList = array();
        try {

            $tags = $this->getAllTagList('meta');
            foreach ($tags as $node) {
                $metaname = $node->attr('name');
                $attrContent = html_entity_decode(trim($node->attr('content')));
                //echoLog($metaname.  ': '.$attrContent);
                $tagList[$metaname] = $attrContent;
            }

        } catch (Exception $e) {
            echoLog( 'Exception: ' . $e->getCode() . ' ' . $e->getMessage() );
            dd($e->getLine());
            return $tagList;
        }

        return $tagList;
    }

    /**
     * Return List all Link (tag A)
     * @return array
     */
    public function getAllLinks()
    {
        $links = array();
        $tags = $this->getAllTagList('a');
        foreach ($tags as $node) {
            echoLog($node->nodeName().' '.$node->attr('href') . ' - ' . $node->text());
            $links[] = [
                'id' => trim($node->attr('id')),
                'href' => trim($node->attr('href')),
                'title' => trim($node->attr('title')),
                'target' => trim($node->attr('target')),
                'hreflang' => trim($node->attr('hreflang')),
                'rel' => trim($node->attr('rel')),
                'text' => trim($node->text())
                ];
        }
        return $links;

    }






    /**
     * Extracts information from the list of nodes.
     *
     * You can extract attributes or/and the node value (_text).
     *
     * Example:
     *
     *     $crawler->filter('h1 a')->extract(['_text', 'href']);
     */
    public function attributes(): array
    {
        /**
         * @var DOMAttr $attribute
         */

        $elements = array();
        $i = 0;
        foreach ($this->getIterator() as $node) {
            //echoLog('Attributes: tag ' . $node->nodeName);
            $attributes = $node->attributes;
            //dd($attributes);

            foreach ($attributes as $attribute) {
                //dd($attribute->value);
                //echoLog('Attributes: attr ' . $attribute->name .' = '. $attribute->value);
                $elements[$attribute->name] = $attribute->nodeValue;
                $i++;

            }

        }
        //dd($elements);
        //echoLog(json_encode($elements, JSON_PRETTY_PRINT));

        return $elements;
    }

    /**
     * Determines if the given element has the attributes.
     *
     * @param \Symfony\Component\DomCrawler\AbstractUriElement $element
     *
     * @return bool
     */
    protected function hasAttributes(Crawler $element)
    {
        foreach ($element->attributes as $name => $value) {
            if (is_numeric($name)) {
                if (is_null($element->attr($value))) {
                    return false;
                }
            } else {
                if ($element->attr($name) != $value) {
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * Fix Html
     * @param string $content
     * @return string|null
     */
    public function fixHtml(string $content = null): ?string
    {

        if(!is_null($content)) {
            $this->content = trim($content);
        }
        if(is_null($this->content) || strlen($this->content) < 10 ) {
            return null;
        }
        $html = trim($this->content);

        //$html = self::xss($html);

        //Elimina a capo, tab e doppi spazi
        $html = trim(str_replace(["\n", "\r", "\t", "  "], ' ', $html));
        $html = trim(str_replace(["     ", "    ", "   ", "  "], ' ', $html));
        $html = str_replace('""', '', $html);
        $html = str_replace("> <", "><", $html);

        //$html = preg_replace('#\\s{2,}#', ' ', $html);

        //$valid = 'class|src|target|alt|title|href|rel';
        //$html = preg_replace('#<(font|span) style="font-weight[^"]+">([^<]+)</(font|span)>#i', '<strong>$2</strong>', $html);
        //$html = preg_replace('#<(font|span) style="font-style:\\s*italic[^"]+">([^<]+)</(font|span)>#i', '<i>$2</i>', $html);
        //$html = preg_replace('# (' . $valid . ')=#i', ' |$1|', $html);
        //$html = preg_replace('# [a-z]+=["\'][^"\']*["\']#i', '', $html);
        //$html = preg_replace('#\\|(' . $valid . ')\\|#i', ' $1=', $html);
        //$html = preg_replace('#</?(font|span)[^>]*>#', '', $html);
        //$html = preg_replace('#<(/?)div#', '<$1p', $html);
        //$html = preg_replace('#<(/?)b>#', '<$1strong>', $html);


        libxml_use_internal_errors(true);
        $DOM = new \DOMDocument();
        $DOM->recover = true;
        $DOM->preserveWhiteSpace = false;
        $DOM->substituteEntities = false;
        $DOM->loadHtml(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOBLANKS | LIBXML_ERR_NONE);
        $DOM->encoding = 'utf-8';
        $html = $DOM->saveHTML();
        $this->content = $html;

        return $html;
    }


    public static function xss($html)
    {
        // Fix &entity\n;
        $html = str_replace(['&amp;', '&lt;', '&gt;'], ['&amp;amp;', '&amp;lt;', '&amp;gt;'], $html);
        $html = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $html);
        $html = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $html);
        $html = html_entity_decode($html, ENT_COMPAT, 'UTF-8');

        // Remove any attribute starting with "on" or xmlns
        $html = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $html);

        // Remove javascript: and vbscript: protocols
        $html = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $html);
        $html = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $html);
        $html = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $html);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $html = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $html);
        $html = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $html);
        $html = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $html);

        // Remove namespaced elements (we do not need them)
        $html = preg_replace('#</*\w+:\w[^>]*+>#i', '', $html);

        do {
            // Remove really unwanted tags
            $old_data = $html;
            $html = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $html);
        } while ($old_data !== $html);

        return $html;
    }

    /**
     * Ripulisce stringa
     * @param $content
     * @return string
     */
    public function cleanContent($content) {

        $content = str_replace(['\'', '\"', '\\',"\r\n","\n","\r"], '', $content);
        $content = htmlspecialchars_decode($content);
        $content = html_entity_decode($content);
        $content = strip_tags($content);
        //var_dump($content);
        $content = str_replace(['&amp;'], ' ', $content);
        $content = str_replace([',_', ', '], '_', $content);
        $content = str_replace(['  '], ' ', $content);
        //var_dump($content);
        $content = preg_replace('/[^\x{20}-\x{7F}]/u','', $content);
        $content = str_replace(['\'', '\"','"', '\\',"\r\n","\n","\r"], '', $content);
        $content = str_replace(['  '], ' ', $content);
        $content = addslashes($content);
        //var_dump($content);

        return $content;
    }

}
