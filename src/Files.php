<?php

declare(strict_types=1);

class Files {
    /** @var string|null $filename */
    private $filename;
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
     * @param bool $forceDownload Forcing to download the content while initialize.
     */
    public function __construct(array $props, bool $forceDownload = false) {
        $this->filename = $props["filename"];
        $this->type     = $props["type"];
        $this->language = $props["language"];
        $this->raw_url  = $props["raw_url"];
        $this->size     = $props["size"];

        if ($forceDownload) {
            $this->content = file_get_contents($this->getRawUrl());
        }
    }

    /**
     * Used to apply the filter options after initialize the object.
     *
     * @param array $options = [
     *     "type" => ["*"],
     *     "language" => ["*"],
     *     "max_size" => 10 ** 6
     * ];
     *
     * @return Files|null
     */
    public function applyOptions(array $options) : ?Files {
        if ($options === [])
            return null;

        foreach ($options as $key => $value) {
            if ($key === "max_size" || $value[0] === "*") continue;

            $tmp = $key === "type" ? $this->getType() : $this->getLanguage();
            $matches = array_filter($value, function (string $value) use ($tmp) : bool {
                return $value === $tmp;
            });

            if ($matches === []) return null;
        }

        return $options['max_size'] >= $this->getSize() ? $this : null;
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
     * Override magical __toString method.
     * This is helpful to save content into a real file.
     *
     * @return string
     */
    public function __toString() : string {
        return (is_string($this->content) && strlen($this->content)) > 0
            ? $this->content
            : "null";
    }
}