<?php

namespace Spider\Util;

/**
 * Class HtmlExternalResources. Provides functionality for extracting external resources for an HTML file.
 * @package Spider\Util
 */
class HtmlExternalResources extends \Tidy {
    private $content;
    private $charSet = "utf-8";

    // Default tidy configuration.
    private $tidyConfiguration = array(
        // Irrelevant to a Spider, this will indent HTML code for easy debugging.
        'indent' => true
    );

    /**
     * Sets content to parse.
     *
     * @param $content
     * @return $this
     */
    public function setContent($content) {
        $this->content = $content;
        return $this;
    }

    /**
     * Fetches content to parse.
     *
     * @return mixed
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Sets content charset.
     *
     * @param $charSet
     * @return $this
     */
    public function setCharset($charSet) {
        $this->charSet = $charSet;
        return $this;
    }

    /**
     * Fetches content charset.
     *
     * @return mixed
     */
    public function getCharset() {
        return $this->charSet;
    }

    /**
     * Fetches external resources.
     *
     * NOTE: Does not append URL to relative paths!
     *
     * @return Array of URLs.
     * @throws \Exception
     */
    public function getExternalResources() {
        // Check if content is set.
        if (is_null($this->getContent())) {
            throw new \Exception("No content set.");
        }

        // First, clean-up the document.
        $cleanDocument = $this->getCleanDocument();

        // Extract RAW resource references.
        $rawResourceReferences = $this->extractResourceReferences($cleanDocument);

        return $rawResourceReferences;
    }


    /**
     * Returns a RAW array of external resources.
     *
     * NOTE: IFrame URL(s) and non IFrame resources are returned in separate keys:
     *
     * iframe
     * normal
     *
     * @param $cleanDocument
     * @return String
     */
    private function extractResourceReferences($cleanDocument) {
        $domDocument = new \DOMDocument();
        // Ensure a document is converted to HTML-ENTITIES to avoid encoding / decoding issues,
        // and ignore any tag related warnings (i.e.: duplicate ids, etc.).
        @$domDocument->loadHTML(
            mb_convert_encoding(
                $cleanDocument,
                "HTML-ENTITIES",
                "utf-8"
            )
        );

        // Begin extracting external resource urls, based on: http://www.w3.org/TR/REC-html40/index/attributes.html.
        return array(
            "iframe" => $this->extractTagSrc($domDocument, "iframe"),
            "normal" => array_merge(
                $this->extractTagSrc($domDocument, "img"),
                $this->extractTagSrc($domDocument, "frame"),
                $this->extractTagSrc($domDocument, "object", "codebase"),
                $this->extractTagSrc($domDocument, "object", "data"),
                $this->extractTagSrc($domDocument, "link", "href"),
                $this->extractTagSrc($domDocument, "script"),
                $this->extractTagSrc($domDocument, "applet", "codebase"),
                $this->extractTagSrc($domDocument, "body", "background"),
                $this->extractTagSrc($domDocument, "audio"),
                $this->extractTagSrc($domDocument, "command", "icon"),
                $this->extractTagSrc($domDocument, "embed"),
                $this->extractTagSrc($domDocument, "video")
            )
        );
    }

    /**
     * Convenience method used for extracting src attributes, for all tags of a given type.
     *
     * @param \DOMDocument $domDocument
     * @param $tagName
     * @param $attributeName String Attribute name that hosts the external resource name. Defaults to src.
     * @return array
     */
    private function extractTagSrc(\DOMDocument $domDocument, $tagName, $attributeName = 'src') {
        // Get all IMG tags.
        $documentTags = $domDocument->getElementsByTagName($tagName);
        $srcArray = array();

        // Extract SRC attribute for each tag.
        for ($i = 0; $i < $documentTags->length; $i++) {
            $domNode = $documentTags
                ->item($i);

            // If the item actually exists...
            if (!is_null($domDocument)) {
                $srcNodeAttribute = $domNode->attributes->getNamedItem($attributeName);

                // For link tags, get the 'rel' attribute if any.
                $relNodeAttributeValue = null;
                if (strtolower($tagName) == "link") {
                    $relNodeAttribute = $domNode->attributes->getNamedItem('rel');
                    // And the attribute value.
                    if (!is_null($relNodeAttribute)) {
                        $relNodeAttributeValue = $relNodeAttribute->value;
                    }
                }

                // ...and has an $attributeName attribute...
                if (!is_null($srcNodeAttribute)) {
                    $srcValue = $srcNodeAttribute->value;
                    // NOTE: Ignore HEX img / object / body-background / command-icon source contents.
                    // Uses basic regular expression for figuring out if this is a hex encoded value.
                    if (strtolower($tagName) == "img"
                        || (strtolower($tagName) == "object" && strtolower($attributeName) == "data")
                        || (strtolower($tagName) == "body" && strtolower($attributeName) == "background")
                        || (strtolower($tagName) == "command" && strtolower($attributeName) == "icon")
                    ) {
                        if (preg_match('#^data:image#i', $srcValue)) {
                            continue;
                        }
                    }

                    // Do not download a dns-prefetch resource.
                    if (strtolower($tagName) == "link" && strtolower($relNodeAttributeValue) == "dns-prefetch") {
                        continue;
                    }

                    // ...then append to list.
                    $srcArray[] = $srcValue;
                }
            }
        }

        return $srcArray;
    }

    /**
     * Convenience method used for cleaning an HTML document, so DOMDocument can parse it.
     *
     * @return String UTF8 Encoded clean document.
     */
    private function getCleanDocument() {
        $this->parseString(
            // Convert from returned charset to utf8.
            mb_convert_encoding(
                $this->getContent(),
                "utf8",
                $this->getCharset()
            ),
            $this->tidyConfiguration,
            "utf8"
        );
        $this->cleanRepair();

        return (string) $this;
    }
}