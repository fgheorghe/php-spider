<?php
namespace Spider\Util;

/**
 * Singleton Class CommandLineParameters.
 *
 * Utility for parsing command line parameters.
 *
 * @package Spider\Util\
 */
final class CommandLineParameters {
    // Stores parameters and their initial values.
    private $parameters = array(
        "url" => ""
    );

    /**
     * Classic Singleton implementation.
     */
    public static function getInstance() {
        static $instance = null;
        // Check if an instance already exists.
        if (is_null($instance)) {
            // If not, create one.
            $instance = new static();
        }
        return $instance;
    }

    /**
     * Enforces a Singleton, and triggers parameter caching.
     *
     * @throws \Exception
     */
    private function __construct() {
        $this->constructParameters();
    }

    /**
     * Convenience method used for validating parameter count and 'caching' their values.
     *
     * @throws \Exception
     */
    private function constructParameters() {
        global $argv;

        // Check required parameters are set.
        if (count($argv) !== 2) {
            throw new \Exception("Invalid parameter count. Usage: php " . $argv[0] . " URL");
        }

        $this->parameters["url"] = $argv[1];
    }

    /**
     * Fetches parameter value.
     *
     * @param $parameterName String Case sensitive parameter name.
     * @return String
     * @throws \Exception
     */
    public function get($parameterName) {
        if (array_key_exists($parameterName, $this->parameters)) {
            return $this->parameters[$parameterName];
        } else {
            throw new \Exception("Invalid parameter name.");
        }
    }
}