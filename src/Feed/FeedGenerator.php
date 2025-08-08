<?php
declare(strict_types=1);

// Abstract base class for feeds.
// Specific feeds (RSS, Atom, etc.) will inherit from this.
// This will wrap the basic generator functionality.
abstract class FeedGenerator {
    protected $settings;
    protected $ticks;

    public function __construct(SettingsModel $settings, array $ticks) {
        $this->settings = $settings;
        $this->ticks = $ticks;
    }

    abstract public function generate(): string;
    abstract public function getContentType(): string;

    protected function buildTickUrl(int $tickId): string {
        return Util::buildUrl($this->settings->baseUrl, $this->settings->basePath, "tick/{$tickId}");
    }

    protected function getSiteUrl(): string {
        return Util::buildUrl($this->settings->baseUrl, $this->settings->basePath);
    }
}