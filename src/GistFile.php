<?php

declare(strict_types=1);

class GistFile {
    /** @var string|null $filename */
    private $filename;
    /** @var string|null $headIndex */
    private $headIndex;
    /** @var string|null $type */
    private $type;
    /** @var string|null $language */
    private $language;
    /** @var string|null $raw_url */
    private $raw_url;
    /** @var int|null $size */
    private $size;
    /** @var string|null $content */
    private $content;

    /**
     * Gist file object.
     *
     * @param array $props File properties (filename, type, language, raw_url, size).
     */
    public function __construct(array $props) {
        $this->filename = $props["filename"];
        $this->type     = $props["type"];
        $this->language = $props["language"];
        $this->raw_url  = $props["raw_url"];
        $this->size     = $props["size"];
    }

    /**
     * Used to apply the filter options AFTER the object initialized.
     * Return NULL if one of given $options is not matches.
     * Otherwise to succeed the apply method.
     *
     * @param array $options = [
     *     "type" => ["*"],
     *     "language" => ["*"],
     *     "max_size" => -1
     * ];
     *
     * @return GistFile|null
     */
    public function applyOptions(array $options) : ?GistFile {
        if ($options === []) {
            return $this; // Nothing to do, continue.
        }

        $filterCallback = function(string $identifier) : callable {
            return function (string $val) use ($identifier) : bool {
                return strtolower($val) === strtolower($identifier);
            };
        };

        // Check for `type` options.
        if ($options["types"][0] !== "*") {
            if (array_filter($options["types"], $filterCallback($this->getType())) === []) {
                return null;
            }
        }

        // Check for `language` options.
        if ($options["languages"][0] !== "*") {
            if (array_filter($options["languages"], $filterCallback($this->getLanguage())) === []) {
                return null;
            }
        }

        // Last, check the `max_size` options.
        return $options["max_size"] > 0
            ? ($this->getSize() <= $options["max_size"]
                ? $this
                : null
            ) : $this;
    }

    /**
     * Head Index is used to determine basis group of this file.
     * For example, if there are 3 files and in order: bitwise_ops.c, bitwise_ops.h, unit_testing.h
     * Then we use bitwise_ops (the first file without ext) as a Head Index.
     *
     * Basically, this function is made to generate file structure as shown below:
     *  - bitwise_ops/
     *  --- bitwise_ops.c
     *  --- bitwise_ops.h
     *  --- unit_testing.h
     *
     * @return string
     */
    public function getHeadIndex(): string {
        return $this->headIndex ?? explode(".", $this->getFilename())[0];
    }

    /**
     * Manually set the Head Index value.
     *
     * @param string $headIndex
     */
    public function setHeadIndex(string $headIndex): void {
        $this->headIndex = $headIndex;
    }

    /**
     * Get file name with extension (if exists).
     *
     * @return string
     */
    public function getFilename() : string {
        return $this->filename ?? "";
    }

    /**
     * Get file type.
     *
     * @return string
     */
    public function getType() : string {
        return $this->type ?? "";
    }

    /**
     * Get programming or scripting language that used by the file.
     *
     * @return string
     */
    public function getLanguage() : string {
        return $this->language ?? "";
    }

    /**
     * Get file's content url.
     *
     * @return string
     */
    public function getRawUrl() : string {
        return $this->raw_url ?? "";
    }

    /**
     * Measure file size in byte(s).
     *
     * @return int
     */
    public function getSize() : int {
        return $this->size ?? 0;
    }

    /**
     * Fetch the file content and store it to memory.
     */
    public function fetchContent() : void {
        $this->content = file_get_contents($this->getRawUrl());
    }

    /**
     * Lazy and useful function to show its content.
     *
     * @return string
     */
    public function __toString() : string {
        return (is_string($this->content) && strlen($this->content)) > 0
            ? $this->content
            : "null";
    }
}
