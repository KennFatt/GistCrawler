<?php

declare(strict_types=1);

/**
 * Parsing the `setting.json` and store into $filterOptions.
 *
 * @var array $filterOptions
 */
$filterOptions = [];
if (file_exists("setting.json")) {
    $settingJson = json_decode(file_get_contents("setting.json"));
    $filterOptions = [
        "types"      => $settingJson->types,
        "languages" => $settingJson->languages,
        "max_size"  => $settingJson->max_size
    ];
}

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

