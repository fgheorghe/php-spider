<?php
/**
 * Main application entry point.
 *
 * Usage: php spider.php URL
 */
// Set verbose error reporting.
error_reporting(E_ALL);

// Prepare dependencies.
require('./Spider/Util/CommandLineParameters.php');
require('./Spider/Util/Curl.php');
require('./Spider/Util/HtmlExternalResources.php');
require('./Spider/Util/ReportOutput.php');
require('./Spider/HttpUrlDownloadSize.php');

try {
    // Prepare parameters.
    $parameters = \Spider\Util\CommandLineParameters::getInstance();

    // Prepare CURL library utility.
    $curlLibrary = new \Spider\Util\Curl();
    // Set optional user agent.
    $curlLibrary->setUserAgent("Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36");

    // Fetch download size.
    $downloadSize = new \Spider\HttpUrlDownloadSize();
    $report = $downloadSize
        ->setCurlLibrary($curlLibrary)
        ->setHtmlExternalResourcesLibrary(new \Spider\Util\HtmlExternalResources())
        ->setUrl($parameters->get('url'))
        ->getReport();

    // Display report.
    Spider\Util\ReportOutput::printOutput($report);
} catch (Exception $exception) {
    die($exception->getMessage() . "\n");
}
