<?php

namespace Spider\Util;

/**
 * Class Curl. Provides convenience methods for CURL functionality.
 *
 * @package Spider\Util\Curl
 */
class Curl {
    private $userAgent;

    /**
     * Sets the user agent.
     *
     * A list of valid user agents can be found here: http://www.useragentstring.com/pages/Chrome/
     *
     * @param $userAgent
     * @return $this
     */
    public function setUserAgent($userAgent) {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * Fetches the user agent.
     *
     * @return mixed
     */
    public function getUserAgent() {
        return $this->userAgent;
    }

    /**
     * Function used for executing an HTTP GET request.
     *
     * Return array keys:
     *
     * mime-type - Returned mime type, as per: http://www.iana.org/assignments/media-types/media-types.xhtml
     * download-size - Download body size, in bytes, as per: http://curl.haxx.se/libcurl/c/curl_easy_getinfo.html
     * http-code - HTTP response code.
     * content - Content. Boolean false if request failed.
     *
     * @param $url
     * @return Array
     */
    public function get($url) {
        // Prepare resource.
        $curl = curl_init();

        // Prepare options.
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false
        );

        // Set a user agent, if any.
        if (!is_null($this->getUserAgent())) {
            $options[CURLOPT_USERAGENT] = $this->getUserAgent();
        }

        curl_setopt_array($curl, $options);

        // Execute.
        $content = curl_exec($curl);

        // Prepare return values.
        // Silently ignore errors.
        $returnValues = array(
            "content" => $content,
            "mime-type" => $this->extractMimeType($curl),
            "download-size" => curl_getinfo($curl, CURLINFO_SIZE_DOWNLOAD),
            "http-code" => curl_getinfo($curl, CURLINFO_HTTP_CODE),
            "charset" => $this->extractCharset($curl)
        );

        // And close connection.
        curl_close($curl);

        return $returnValues;
    }

    /**
     * Extract mime type, from a CURLINFO_CONTENT_TYPE value - excludes charset.
     *
     * NOTE: If an invalid Content-Type header is returned, text/plain is assumed.
     *
     * @param $curl Resource Curl resource.
     * @return String
     */
    private function extractMimeType($curl) {
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

        // If the server returned an invalid content type, assume text/plain.
        if (is_null($contentType)) {
            return "text/plain";
        }

        $contentType = explode(";", $contentType);

        return $contentType[0];
    }

    /**
     * Extract character set, from a CURLINFO_CONTENT_TYPE value - excludes mime type.
     *
     * NOTE: If an invalid Content-Type header is returned or charset is missing, utf8 is assumed.
     *
     * @param $curl Resource Curl resource.
     * @return String
     */
    private function extractCharset($curl) {
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

        // If the server returned an invalid content type, assume UTF8.
        if (is_null($contentType)) {
            return "utf8";
        }

        $contentType = explode(";", $contentType);

        return count($contentType) == 2 ? str_replace(" charset=", "", $contentType[1]) : "utf8";
    }
}