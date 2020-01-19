<?php

declare(strict_types=1);

require './config.php';
require './src/GistCrawler.php';
require './src/GistFile.php';

/**
 * Used to indicate invalid given username.
 * 
 * @var int ERR_INVALID_USERNAME
 */
define("ERR_INVALID_USERNAME", 0x03);
/**
 * Used to indicate invalid given options.
 * 
 * @var int ERR_INVALID_OPTIONS
 */
define("ERR_INVALID_OPTIONS", 0x05);
/**
 * Some of requirements is missing.
 * 
 * @var int ERR_MISSING_EXTENSIONS
 */
define("ERR_MISSING_EXTENSIONS", 0x0D);

/**
 * Checking the required extensions.
 * 
 * @return array|string[]
 */
function checkExtensions() : array {
    $requirements = ["curl"];
    $missing = [];
    foreach ($requirements as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }

    return $missing;
}

/**
 * Write a message to STDOUT.
 * 
 * @param string $message
 */
function consoleOut(string $message) : void {
    fwrite(
        STDOUT,
        ord($message[strlen($message) - 1]) !== 10 ? $message . "\x0a" : $message
    );
}

/**
 * Exit the program with optional $exitMsg and $exitCode
 * 
 * @param int $exitCode
 * @param string|null $exitMsg
 * @param Closure $callback
 */
function programExit(int $exitCode = 0, ?string $exitMsg = null, Closure $callback = null) : void {
    if ($exitMsg !== null) {
        consoleOut("[$exitCode] " . $exitMsg);
    }
    if ($callback !== null) {
        $callback();
    }
    
    exit($exitCode);
}

/**
 * Validate GitHub username.
 * 
 * @link https://github.com/shinnn/github-username-regex Regex pattern.
 * 
 * @param string $username
 * 
 * @return bool
 */
function validateUsername(string $username) : bool {
    if  (
        stripos($username, "about") !== false ||
        stripos($username, "help") !== false ||
        stripos($username, "pricing") !== false
    ) return false;

    return preg_match("/^[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}$/i", $username) === 1;
}

/**
 * Strip the filename ($argv[0]) from $argv.
 * Since we reverse the array and new produced array are look like this:
 *  0 = options
 *  1 = username
 * 
 * @param array $args
 * 
 * @return array|null
 */
function parseArgs(array $args) : ?array {
    if (count($args) === 1) {
        return null;
    }

    $retVal = array_reverse($args, false);
    unset($retVal[count($retVal) - 1]);
    return $retVal;
}

(function(array $args, array $filterOptions, array $callbacks) : void {
    /**
     * Validate the extension.
     */
    if (count($missing = checkExtensions()) > 0) {
        programExit(ERR_MISSING_EXTENSIONS, null, function() use ($missing) : void {
            foreach ($missing as $ext) {
                consoleOut("${ext} is not loaded or installed, please try again!");
            }
        });
    }

    $args = parseArgs($args) ?? [];

    /**
     * Show main interface.
     */
    $interface = function() : void {
        consoleOut(pack(
            "\x48\x2A",
            "e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e294800a09090947697374437261776c6572202d2076312e300a0955736167653a2072756e2e706870205b537472696e673a20757365726e616d655d205b4f7074696f6e732e2e2e5d0ae29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e29480e294800a"
        ));

        consoleOut(
            "\tAvailable options:
            \timport\t: Import all the gists
            \traw\t: Take raw json response\n"
        );
    };

    if (count($args) !== 2) {
        $interface();
        programExit();
    }

    /**
     * @var string $username
     * @var string $option
     */
    $username = trim($args[1]);
    $option = trim($args[0]);

    if (!validateUsername($username)) {
        programExit(ERR_INVALID_USERNAME, "Invalid github username.");
    }
    
    switch(strtolower($option)) {
        case "raw":
            $status = GistCrawler::initialize($username, [], $callbacks);
            if ($status) {
                GistCrawler::execute(0);
            }
            break;
        case "import":
            $status = GistCrawler::initialize($username, $filterOptions, $callbacks);
            if ($status) {
                GistCrawler::execute(1);
            }
            break;
        default:
            $interface();
            programExit(ERR_INVALID_OPTIONS, "Invalid options given.");
            break;
    }

    programExit();
})($argv, $filterOptions, $callbacks);
