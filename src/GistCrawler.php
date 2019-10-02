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
     * Given GitHub username to fetch.
     * 
     * @var string $username
     */
    private static $username = "";

    /**
     * Final fetch data.
     * 
     * @var array|null $data
     */
    private static $data;

    private static function initOutDirectory() : string {
        /**
         * Suppress the mkdir and recursively include username's folder.
         */
        $dirGenerate = "\x6f\x75\x74\x2f" . self::$username . "\x2f";
        @mkdir($dirGenerate, 0777, true);
        return $dirGenerate;
    }

    /**
     * Inner method to fetch given username's gist data
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

        $retVal = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($retVal, true, 512, JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY);
    }

    private static function fetchGists() : void {
        $userDirectory = self::initOutDirectory();
        $unknownProps = 0;

        foreach (self::$data as $gist) {
            if (isset($gist["files"]) && count($gist["files"]) > 0) {
                // var_dump($gist["files"]);
                $subDir = "";

                foreach (array_keys($gist["files"]) as $fileName) {
                    if ($subDir === "") {
                        $subDir = $userDirectory . str_replace(".", "_", $fileName) . "\x2f";
                        mkdir($subDir);
                    }

                    $fileName = $gist["files"][$fileName]["filename"];
                    $contentUrl = $gist["files"][$fileName]["raw_url"];
                    $content = file_get_contents($contentUrl);

                    $file = @fopen($subDir . $fileName, "w+");
                    fwrite($file, $content);
                    fclose($file);
                }

            } // Safe guard.

            ++$unknownProps;
        }
    }

    /**
     * First step to use the GistCrawler class.
     * Initialize username and state.
     * 
     * @return bool
     */
    public static function initialize(string $username) : bool {
        if (!self::$initialized) {
            self::$initialized = true;

            self::$username = $username;
            self::$data = self::fetchData();

            // self::fetchGists();

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
        return self::$data ?? NULL;
    }

    /**
     * Get all API response as an JSON.
     * 
     * @return string|null
     */
    public static function getGistsJson() : ?string {
        return is_array(self::$data)
            ? json_encode(self::$data, JSON_PRETTY_PRINT)
            : NULL;
    }

    /**
     * Get count all public user's gist.
     * 
     * @return int
     */
    public static function getCountGist() : int {
        return count(self::$data);
    }
}