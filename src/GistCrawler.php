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
     * Fetch main Gist API and store it as PHP array.
     *
     * @var array|null $data
     */
    private static $data = null;

    /**
     * List of options to filter importing process.
     *
     * @var array $filterOptions
     */
    private static $filterOptions = [];

    /**
     * Trace your data by using callback.
     *
     * @var array $callbacks
     */
    private static $callbacks = [];

    /**
     * Invoke callback when specific event triggered.
     *
     * @param string $eventName
     * @param array $value
     */
    private static function invokeCallback(string $eventName, array $value = []) : void {
        if (!isset(self::$callbacks[$eventName])) {
            return;
        }

        (self::$callbacks[$eventName])($value);
    }

    /**
     * Create new user's directory if not exists.
     * 
     * @return string
     */
    private static function getUserDirectory() : string {
        if (!is_dir(self::$userDirectory)) {
            mkdir(self::$userDirectory, 0777, true);

            self::invokeCallback("onDirectoryCreated");
        }

        return self::$userDirectory;
    }

    /**
     * Writing `$file` into local file.
     *
     * @param GistFile $file
     */
    private static function writeFile(GistFile $file) : void {
        $userDirectory = self::getUserDirectory();
        $fileDirectory = $userDirectory . $file->getHeadIndex();
        if (!is_dir($fileDirectory)) {
            mkdir($fileDirectory);
        }

        if ($resource = fopen($fileDirectory . DIRECTORY_SEPARATOR . $file->getFilename(), 'w+')) {
            fwrite($resource, (string) $file);
            fclose($resource);
        }

        self::invokeCallback("onFileWritten", ["file" => $file, "file_directory" => $fileDirectory]);
    }

    /**
     * Classifying the data and make it as an object.
     * 
     * @param bool $forceWrite Forcing to write the content after downloaded.
     *
     * @return array|null
     */
    private static function classifyFiles(bool $forceWrite = false) : ?array {
        if (self::$data === null) {
            return null;
        }

        $files = [];
        $headIndex = null;
        for ($i = 0; $i < count(self::$data); ++$i) {
            foreach (self::$data[$i]["files"] as $index => $fileProps) {
                $file = (new GistFile($fileProps))->applyOptions(self::$filterOptions);
                if ($file === null) {
                    continue;
                }

                if ($headIndex === null) {
                    $headIndex = explode(".", $file->getFilename())[0];
                }
                $file->setHeadIndex((string) $headIndex);
                $file->fetchContent();
                $files[$headIndex][] = $file;
                self::invokeCallback("onFileDownloaded", ["file" => $file, "count" => count($files)]);

                if ($forceWrite) {
                    self::writeFile($file);
                }
            }
            $headIndex = null;
        }
        
        return $files;
    }

    /**
     * Instruction to execute given `$mode`.
     * 
     * @param int $mode 0 for raw json and 1 import option.
     */
    public static function execute(int $mode) : void {
        switch ($mode) {
            case 0:
                fwrite(STDOUT, json_encode(
                    self::$data, 
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ));

                self::invokeCallback("onExecuted", ["mode" => 0]);
            break;

            case 1:
                self::classifyFiles(true);

                self::invokeCallback("onExecuted", ["mode" => 1]);
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
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.github.com/users/" . self::$username . "/gists",
            CURLOPT_RETURNTRANSFER => 0x01,
            CURLOPT_USERAGENT => GistCrawler::FAKE_USER_AGENT,
            CURLOPT_HEADER => 0x00
        ]);

        $jsonResponse = curl_exec($ch);
        curl_close($ch);

        self::invokeCallback("onFetched", ["response" => $jsonResponse]);
        return is_string($jsonResponse) 
            ? json_decode($jsonResponse, true, 0x200, JSON_BIGINT_AS_STRING | JSON_OBJECT_AS_ARRAY)
            : [];
    }

    /**
     * First step to use the GistCrawler class.
     * Initialize username and state.
     * 
     * @param string $username
     * @param array $filterOptions
     * @param array $callbacks
     * 
     * @return bool
     */
    public static function initialize(string $username, array $filterOptions = [], array $callbacks = []) : bool {
        if (!self::$initialized) {
            self::invokeCallback("onInitialize", ["username" => $username, "filter_options" => $filterOptions]);

            self::$initialized = true;

            self::$username = $username;
            self::$userDirectory = "\x6f\x75\x74\x2f" . $username . DIRECTORY_SEPARATOR;
            self::$callbacks = $callbacks;
            self::$data = self::fetchData();

            self::$filterOptions["types"]        = $filterOptions["types"] ?? ["*"];
            self::$filterOptions["languages"]    = $filterOptions["languages"] ?? ["*"];
            self::$filterOptions["max_size"]    = $filterOptions["max_size"] ?? -1;

            return true;
        }

        return false;
    }
}
