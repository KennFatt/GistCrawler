<?php

declare(strict_types=1);

class Files {
    private $filename;
    private $type;
    private $language;
    private $raw_url;
    private $size;
    private $content;
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

    public function getFilename() : string {
        return $this->filename ?? "";
    }

    public function getType() : string {
        return $this->type ?? "";
    }

    public function getLanguage() : string {
        return $this->language ?? "";
    }

    public function getRawUrl() : string {
        return $this->raw_url ?? "";
    }

    public function getSize() : int {
        return $this->size ?? 0;
    }

    public function fetchContent() : void {
        $this->content = file_get_contents($this->getRawUrl());
    }

    public function __toString() : string {
        return (is_string($this->content) && strlen($this->content)) > 0
            ? $this->content
            : "null";
    }
}