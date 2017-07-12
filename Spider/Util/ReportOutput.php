<?php

namespace Spider\Util;

/**
 * Class ReportOutput. Provides screen output functionality.
 * @package Spider\Util
 */
final class ReportOutput {
    // Prevent from instantiation.
    private function __construct() {}

    /**
     * Convenience method used for printing URLs and their children (i.e.: iframe content).
     *
     * @param $reportItems
     * @param string $prependString
     */
    private static function printReportItems($reportItems, $prependString = "") {
        foreach ($reportItems as $reportItem) {
            echo $prependString . " URL: " . $reportItem["url"]
                . " Mime Type: " . $reportItem["mime-type"]
                . " Size: " . $reportItem["download-size"] . " byte" . ($reportItem["download-size"] > 1 ? "s" : "") . "\n";
            if (count($reportItem["children"]) != 0) {
                static::printReportItems($reportItem["children"], $prependString . " ");
            }
        }
    }

    /**
     * Echoes output to screen.
     *
     * @param $report Array Report content as returned by Spider\HttpUrlDownloadSize->getReport.
     */
    public static function printOutput($report) {
        echo "Total HTTP request count: " . $report["request-count"] . "\n";
        static::printReportItems($report["size-list"]);
    }
}