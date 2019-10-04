<?php

declare(strict_types=1);

require './src/GistCrawler.php';

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
 * Write a message to STDOUT.
 * 
 * @param string $message
 */
function consoleOut(string $message) : void {
    fwrite(
        STDOUT,
        ord($message[strlen($message) - 1]) !== 10 ? $message . "\n" : $message
    );
}

/**
 * Read a buffer from STDIN.
 * 
 * @return string
 */
function consoleIn() : string {
    return trim(((string) fgets(STDIN)));
}

/**
 * Exit the program with optional $exitMsg and $exitCode
 * 
 * @param string|null $exitMSG
 * @param int $exitCode
 */
function programExit(?string $exitMsg = NULL, int $exitCode = 0) : void {
    if ($exitMsg !== NULL) {
        consoleOut("[$exitCode] " . $exitMsg);
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
    if (count($args) === 1)
        return NULL;

    $retVal = array_reverse($args, false);
    unset($retVal[count($retVal) - 1]);
    return $retVal;
}

(function(array $args) : void {
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

    /**
     * Closure to execute crawl process.
     * Mode:
     *  0 IMPORT
     *  1 JSON_RESPONSE
     * 
     * @param string $username
     * @param int $mode 0 or 1
     * 
     * @return bool
     */
    $crawling = function(string $username, int $mode) : bool {
        return false;
    };

    if (count($args) !== 2) {
        $interface();
        programExit();
    }

    if (!validateUsername($args[1])) {
        programExit("Invalid github username.", ERR_INVALID_USERNAME);
    }
    
    switch(strtolower($args[0])) {
        case "import":
            GistCrawler::initialize($args[1], true); // TODO: Debug code.
            break;
        case "raw":
            break;
        default:
            $interface();
            programExit("Invalid options given.", ERR_INVALID_OPTIONS);
            break;
    }
})($argv);