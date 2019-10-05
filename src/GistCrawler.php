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
     * Final fetch data.
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

    private static function batchDownload(array $rawUrl) : void {

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

        $chRes = curl_exec($ch);
        curl_close($ch);

        $retVal = [];
        if ((is_string($chRes)) && (strlen($chRes) > 2)) {
            $json = json_decode($chRes, true, 0x200, JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY);
            if (is_array($json)) {
                foreach ($json as $k => $datum) {
                    $retVal[] = $datum["files"];
                }
            }
        }
        
        return $retVal;
    }

    /**
     * Fetch all user's gists.
     */
    private static function fetchGists() : void {
        $downloadedSize = 0;
        $downloadedFiles = 0;
        $elapsedTimes = [];
        $startTime = microtime(true);
        foreach (self::$data as $gist) {
            if (!isset($gist["files"]) && !(count($gist["files"]) > 0))
                break;

            $subDir = "";
            $timeEachFile = microtime(true);
            foreach (array_keys($gist["files"]) as $fileName) {
                if ($subDir === "") {
                    if (stripos($fileName, ".") !== false) {
                        $subDir = self::getUserDirectory() . substr($fileName, 0, stripos($fileName, ".")) . "\x2f";
                    } else {
                        $subDir = self::getUserDirectory() . $fileName . "\x2f";
                    }
                    @mkdir($subDir);
                }

                // TODO: Remove boilerplate
                // TODO: Take 10 content (or optional) and write it, repeat.
                $fileName = $gist["files"][$fileName]["filename"];
                $contentUrl = $gist["files"][$fileName]["raw_url"];
                $downloadedSize += (int) $gist["files"][$fileName]["size"];
                $content = file_get_contents($contentUrl);

                $file = @fopen($subDir . $fileName, "w+");
                fwrite($file, $content);
                fclose($file);

                $elapsedTimes[$downloadedFiles] = (microtime(true) - $timeEachFile) * 1000;
                ++$downloadedFiles;
                echo "[!] Downloaded file(s): $downloadedFiles" . PHP_EOL;
                $timeEachFile = microtime(true);
            }
        }

        echo "[*] Downloaded files: $downloadedFiles" . PHP_EOL;
        /**
         * The only way to make it better is using cache / local file validation.
         * If file exists and has the same size, skip it!
         * 
         * That's what I had on my head for a moment.
         */
        var_dump($elapsedTimes);
        echo "[*] Download size: $downloadedSize Bytes" . PHP_EOL;
        echo "[*] Total time: " . ((microtime(true) - $startTime) * 1000) . "ms" . PHP_EOL;
    }

    /**
     * First step to use the GistCrawler class.
     * Initialize username and state.
     * 
     * @param string $username
     * @param bool $execute TODO
     * 
     * @return bool
     */
    public static function initialize(string $username, bool $execute) : bool {
        if (!self::$initialized) {
            self::$initialized = true;

            self::$username = $username;
            self::$userDirectory = "\x6f\x75\x74\x2f" . $username . "\x2f";
            self::$data = self::fetchData();

            if ($execute) {
                self::fetchGists();
            }

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