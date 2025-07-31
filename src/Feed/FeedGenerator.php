<?php
// Abstract base class for feeds.
// Specific feeds (RSS, Atom, etc.) will inherit from this.
// This will wrap the basic generator functionality.
abstract class FeedGenerator {
    protected $config;
    protected $ticks;

    public function __construct(ConfigModel $config, array $ticks) {
        $this->config = $config;
        $this->ticks = $ticks;
    }

    abstract public function generate(): string;
    abstract public function getContentType(): string;

    protected function buildTickUrl(int $tickId): string {
        return Util::buildUrl($this->config->baseUrl, $this->config->basePath, "tick/{$tickId}");
    }

    protected function getSiteUrl(): string {
        return Util::buildUrl($this->config->baseUrl, $this->config->basePath);
    }
}