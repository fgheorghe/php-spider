<?php

namespace Spider;

/**
 * Class Spider
 *
 * Determines download size of a given HTTP/S URL.
 *
 * @package Spider
 */
class HttpUrlDownloadSize {
    private $url;
    private $curlLibrary;
    private $htmlExternalResourcesLibrary;
    private $requestCount = 0;

    // List of complex mime types: HTML and CSS files.
    // Ignores types such as: application/x-pointplus, text/x-server-parsed-html which may be ignored by some browsers.
    // Anything outside the list is treated as it if does not have external resource references.
    private $complexMimeTypes = array(
        "text/html",
        "text/css" // CSS files may load external resources.
    );

    /**
     * Sets HTML External Resources dependency.
     *
     * @param Util\HtmlExternalResources $htmlExternalResourcesLibrary
     * @return $this
     */
    public function setHtmlExternalResourcesLibrary(Util\HtmlExternalResources $htmlExternalResourcesLibrary) {
        $this->htmlExternalResourcesLibrary = $htmlExternalResourcesLibrary;
        return $this;
    }

    /**
     * Fetches HTML External Resources dependency.
     *
     * @return mixed
     */
    public function getHtmlExternalResourcesLibrary() {
        return $this->htmlExternalResourcesLibrary;
    }

    /**
     * Sets CURL Library dependency.
     *
     * @param Util\Curl $curlLibrary
     * @return $this
     */
    public function setCurlLibrary(Util\Curl $curlLibrary) {
        $this->curlLibrary = $curlLibrary;
        return $this;
    }

    /**
     * Fetches CURL Library dependency.
     *
     * @return mixed
     */
    public function getCurlLibrary() {
        return $this->curlLibrary;
    }

    /**
     * Validates and sets the URL.
     *
     * @param $url String
     * @return $this
     * @throws \Exception
     */
    public function setUrl($url) {
        if (!$this->isValidHttpUrl($url)) {
            throw new \Exception("Invalid URL: " . $url);
        }
        $this->url = $url;
        return $this;
    }

    /**
     * Checks if a given string is a valid HTTP/S url.
     *
     * @param $url
     * @return bool
     */
    private function isValidHttpUrl($url) {
        // Basic pattern check for a URL.
        return preg_match('#^(http|https)://#i', $url);
    }

    /**
     * Fetches the URL.
     *
     * @return mixed
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Fetches report, by triggering download logic.
     *
     * Returned array content:
     * size-list -> the size, mime-type and url of each external resource
     *      Each item in size list is an array of:
     *          url -> full URL of external resource
     *          size -> size in bytes
     *          mime-type -> mime type
     *          children -> any sub-items, for iframes
     * request-count -> total number of HTTP requests, including iFrames
     *
     * @param $skipIframe Boolean Do not create iframe report. Used for stopping at first iframe.
     * @return Array
     */
    public function getReport($skipIframe = false) {
        return array(
            "size-list" => $this->download($skipIframe),
            "request-count" => $this->requestCount
        );
    }

    /**
     * Downloads URL content and stores report data.
     * @param $skipIframe Boolean Do not create iframe report. Used for stopping at first iframe.
     * @throws \Exception
     * @return Array
     */
    private function download($skipIframe = true) {
        // Check for URL.
        if (is_null($this->getUrl())) {
            throw new \Exception("No URL set.");
        }

        // Count number of HTTP requests...
        $this->requestCount++;
        // ...and get items.
        $resourceContentAndInfo = $this->getCurlLibrary()
            ->get($this->getUrl());

        // For HTML / CSS files, fetch all referenced resources.
        if (in_array(strtolower($resourceContentAndInfo["mime-type"]), $this->complexMimeTypes)) {
            $externalResourceUrls = $this->getHtmlExternalResourcesLibrary()
                ->setCharSet($resourceContentAndInfo["charset"])
                ->setContent($resourceContentAndInfo["content"])
                ->getExternalResources();

            // Get iframe(s) content.
            $iFrameReport = array();
            if ($skipIframe === false) {
                foreach ($externalResourceUrls["iframe"] as $iFrameUrl) {
                    $downloadSize = new self();
                    $report = $downloadSize
                        ->setCurlLibrary($this->getCurlLibrary())
                        ->setHtmlExternalResourcesLibrary($this->getHtmlExternalResourcesLibrary())
                        ->setUrl($this->constructResourceUrl($iFrameUrl))
                        ->getReport(true);

                    $iFrameReport[] = array(
                            "download-size" => $report['size-list'][0]["download-size"],
                            "mime-type" => $report['size-list'][0]["mime-type"],
                            "url" => $this->constructResourceUrl($iFrameUrl),
                            "children" => $report['size-list'][0]["children"]
                    );
                    // Include current iframe items in count.
                    $this->requestCount += $report["request-count"];
                }
            }

            // Return all items.
            return array(
                array(
                    "download-size" => $resourceContentAndInfo["download-size"],
                    "mime-type" => $resourceContentAndInfo["mime-type"],
                    "url" => $this->getUrl(),
                    "children" => array_merge(
                        $this->downloadExternalResources($externalResourceUrls["normal"]),
                        $iFrameReport
                    )
                )
            );
        } else {
            // Otherwise, return one result.
            return array(
                array(
                    "download-size" => $resourceContentAndInfo["download-size"],
                    "mime-type" => $resourceContentAndInfo["mime-type"],
                    "url" => $this->getUrl(),
                    "children" => array()
                )
            );
        }
    }

    /**
     * Downloads external resources.
     *
     * @param $externalResourceUrls
     * @return Array Numeric array of download-size, mime-type, url and children pairs.
     *
     * NOTE: Children defaults to 0 -> only used by
     *
     */
    private function downloadExternalResources($externalResourceUrls) {
        $results = array();
        for ($i = 0; $i < count($externalResourceUrls); $i++) {
            $url = $this->constructResourceUrl($externalResourceUrls[$i]);

            // Count number of HTTP requests.
            $this->requestCount++;
            $details = $this->getCurlLibrary()
                ->get(
                    $url,
                    true
                );

            $results[] = array(
                "download-size" => $details["download-size"],
                "mime-type" => $details["mime-type"],
                "url" => $url,
                "children" => array()
            );
        }
        return $results;
    }

    /**
     * Convenience method used for extracting the base URL from the configured URL.
     *
     * @return string
     */
    private function getBaseUrl() {
        return $this->getScheme() . "://" .
            // Username.
            (!is_null(parse_url($this->getUrl(), PHP_URL_USER)) ? parse_url($this->getUrl(), PHP_URL_USER) : "") .
            // Password.
            (!is_null(parse_url($this->getUrl(), PHP_URL_PASS)) ? ":" . parse_url($this->getUrl(), PHP_URL_PASS) : "") .
            // @ - If username is set.
            (!is_null(parse_url($this->getUrl(), PHP_URL_USER)) ? "@" : "") .
            // Host name.
            parse_url($this->getUrl(), PHP_URL_HOST) .
            // Port - if any.
            (!is_null(parse_url($this->getUrl(), PHP_URL_PORT)) ? parse_url($this->getUrl(), PHP_URL_PORT) : "");
    }

    /**
     * Returns the base URL and path, without script name.
     *
     * @return string
     */
    private function getBaseUrlAndPath() {
        $baseUrl = $this->getBaseUrl() .
            parse_url($this->getUrl(), PHP_URL_PATH);

        // Remove the last bit if a trailing / is missing. By convention it means a script name:
        // http://googlewebmastercentral.blogspot.co.uk/2010/04/to-slash-or-not-to-slash.html
        if (substr($baseUrl, -1) != "/") {
            $baseUrl = substr(
                $baseUrl,
                0,
                strlen($baseUrl) - (strlen($baseUrl) - strrpos($baseUrl, "/"))
            ) . "/";
        }

        return $baseUrl;
    }

    /**
     * Convenience method used for extracting the configured URL scheme.
     *
     * NOTE: Used for prepending scheme name that a // type of SRC.
     *
     * @return string
     */
    private function getScheme() {
        return parse_url($this->getUrl(), PHP_URL_SCHEME);
    }

    /**
     * Convenience function for prepending a full URL to a relative path.
     *
     * @param $url
     * @return String
     */
    private function constructResourceUrl($url) {
        if (!$this->isValidHttpUrl($url)) {
            // First, prepend scheme if needed.
            if (substr($url, 0, 2) == "//") {
                $url = $this->getScheme() . ":" . $url;
            }
            // Then check if the URL starts with /, to append schema, and domain name.
            elseif (substr($url, 0, 1) == "/") {
                $url = $this->getBaseUrl() . $url;
            }
            // Finally, check if we need to include the path without script.
            else {
                $url = $this->getBaseUrlAndPath() . $url;
            }
        }

        return $url;
    }
}