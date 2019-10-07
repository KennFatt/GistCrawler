<?php

declare(strict_types=1);

class GistCrawler {
    
    /**
     * GitHub API requires User-Agent to access. Therefore, we have to bypass it.
     * Following User identification is taken from KDE System Setting.
     * 
     * @var string
     */
    private const FAKE_USER_AGENT = "Mozilla/5.0 (X11; Linux; English) KHTML/5.62.0 (like Gecko) Konqueror/5 KIO/5.62";

    /**
     * Flag to measure the initialize state.
     * 
     * @var bool $initialized
     */
    private static $initialized = false;

    /**
     * User out directory.
     * 
     * @var string $userDirectory
     */
    private static $userDirectory = "";
    
    /**
     * Given GitHub username to fetch.
     * 
     * @var string $username
     */
    private static $username = "";

    /**
     * List of options to filter importing process.
     * 
     * @var array $filterOptions
     */
    private static $filterOptions = [];

    /**
     * Final fetch data.
     * 
     * @var array|null $data
     */
    private static $files = NULL;

    /**
     * Fetch main Gist API and store it as PHP's array.
     * 
     * @var array|null $data
     */
    private static $data = NULL;

    /**
     * Create new user's directory if not exists.
     * 
     * @return string
     */
    private static function getUserDirectory() : string {
        if (!is_dir(self::$userDirectory)) {
            mkdir(self::$userDirectory, 0777, true);
        }

        return self::$userDirectory;
    }

    /**
     * Classifying the data and make it as object.
     * Return type:
     *  [
     *      string => Files[]
     *      ...
     *  ]
     * 
     * @see Files
     * 
     * @return array|null
     */
    private static function classifyFiles() : ?array {
        if (self::$data === null)
            return null;
        
        $files = [];
        $headIndex = null;
        for ($i = 0; $i < count(self::$data); ++$i) {
            foreach (self::$data[$i]["files"] as $index => $fileProps) {
                if ($headIndex === null)
                    $headIndex = $index;

                $files[$headIndex][] = new Files($fileProps);
            }
            $headIndex = null;
        }
        
        return $files;
    }

    /**
     * Execute process.
     * 
     * @param int $mode 0 for raw json and 1 import option.
     */
    private static function execute(int $mode) {
        switch ($mode) {
            case 0:
                /**
                 * It would write "null" if the data itself has null value.
                 * Note that we use `fwrite` to STDOUT. So we can control the output in CLI.
                 *  Example: php run.php kennfatt raw > kennfatt.json
                 */
                fwrite(STDOUT, json_encode(
                    self::$data, 
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ));
            break;

            case 1:
                // TODO
            break;
        }
    }

    /**
     * Private method to fetch given username's gist data
     * and return it as decoded JSON.
     * 
     * @api https://api.github.com/users/:username/gists
     * 
     * @return array|null Returned as array if succeed and null otherwise.
     */
    private static function fetchData() : ?array {
        if (!self::$initialized)
            return NULL;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.github.com/users/" . self::$username . "/gists",
            CURLOPT_RETURNTRANSFER => 0x01,
            CURLOPT_USERAGENT => GistCrawler::FAKE_USER_AGENT,
            CURLOPT_HEADER => 0x00
        ]);

        $jsonResponse = curl_exec($ch);
        curl_close($ch);
        
        return is_string($jsonResponse) 
            ? json_decode($jsonResponse, true, 0x200, JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY)
            : [];
    }

    /**
     * First step to use the GistCrawler class.
     * Initialize username and state.
     * 
     * @param string $username
     * @param bool $import
     * 
     * @return bool
     */
    public static function initialize(string $username, bool $import, array $opt = []) : bool {
        if (!self::$initialized) {
            self::$initialized = true;

            self::$username = $username;
            self::$userDirectory = "\x6f\x75\x74\x2f" . $username . "\x2f";
            self::$data = self::fetchData();

            if ($import && $opt !== []) {
                self::$filterOptions["type"]        = $opt["type"] ?? "*";
                self::$filterOptions["languages"]   = $opt["languages"] ?? ["*"];
                self::$filterOptions["max_size"]    = $opt["max_size"] ?? (10 ** 6);
            }
            self::execute((int) $import);

            return true;
        }

        return false;
    }

    /**
     * Get all API response as an array.
     * 
     * @return array|null
     */
    public static function getGists() : ?array {
        return self::$files ?? NULL;
    }

    /**
     * Get all API response as an JSON.
     * 
     * @return string|null
     */
    public static function getGistsJson() : ?string {
        return is_array(self::$files)
            ? json_encode(self::$files, JSON_PRETTY_PRINT)
            : NULL;
    }

    /**
     * Get count all public user's gist.
     * 
     * @return int
     */
    public static function getCountGist() : int {
        return count(self::$files);
    }
}