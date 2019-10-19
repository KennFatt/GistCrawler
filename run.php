<?php

declare(strict_types=1);

// TODO: Separate the initiator with runnable script.
require './src/GistCrawler.php';
require './src/GistFile.php';
/**
 * This is only works for importing gists into a local file.
 * type: File type, such as "application/x-python", "application/x-ruby", "plain/text".
 * language: File's programming language or scripting language, started with uppercase character, such as "C", "Ruby", "Python", "PHP".
 * max_size: File size in bytes.
 *
 * TODO: Set filter options as configurable JSON file, `type` and `language` value must be an array.
 *
 * @var array $filterOptions = [
 *     "type" => [],
 *     "language" => null|[],
 *     "max_size" => int
 * ]
 */
$filterOptions = [
    "type" => ["text/plain"]
];

/**
 * Array argument passed to each callback.
 * Will be invoked when exact even triggered.
 *
 * @var array $callbacks = [
 *     "onInitialize" => function(["username" => string, "filter_options" => array]) {},
 *     "onFetched" => function(["response" => mixed]) {},
 *     "onExecuted" => function(["mode" => int]) {},
 *     "onFileDownloaded" => function(["file" => GistFile, "count" => int]) {},
 *     "onDirectoryCreated" => function([]) {},
 *     "onFileWritten" => function(["file" => GistFile, "file_directory" => string]) {}
 * ]
 */
$callbacks = [];

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
        ord($message[strlen($message) - 1]) !== 10 ? $message . "\x0a" : $message
    );
}

/**
 * Exit the program with optional $exitMsg and $exitCode
 * 
 * @param string|null $exitMsg
 * @param int $exitCode
 */
function programExit(?string $exitMsg = null, int $exitCode = 0) : void {
    if ($exitMsg !== null) {
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
    if (count($args) === 1) {
        return null;
    }

    $retVal = array_reverse($args, false);
    unset($retVal[count($retVal) - 1]);
    return $retVal;
}

(function(array $args, array $filterOptions, array $callbacks) : void {
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
        programExit("Invalid github username.", ERR_INVALID_USERNAME);
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
            programExit("Invalid options given.", ERR_INVALID_OPTIONS);
            break;
    }

    programExit();
})($argv, $filterOptions, $callbacks);