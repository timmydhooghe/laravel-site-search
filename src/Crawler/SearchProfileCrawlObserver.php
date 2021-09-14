<?php

namespace Spatie\SiteSearch\Crawler;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\SiteSearch\Drivers\Driver;
use Spatie\SiteSearch\Profiles\SearchProfile;

class SearchProfileCrawlObserver extends CrawlObserver
{
    public function __construct(
        protected SearchProfile $searchProfile,
        protected Driver        $driver
    ) {
    }

    public function crawled(UriInterface $url, ResponseInterface $response, ?UriInterface $foundOnUrl = null): void
    {
        ray('Adding to index: ' . $url)->green();

        $indexer = $this->searchProfile->useIndexer($url, $response);

        if (! $indexer) {
            return;
        }

        $title = $indexer->title();
        $dateModified = $indexer->dateModified();
        $description = $indexer->description();
        $entries = $indexer->entries();

        foreach ($indexer->entries() as $entry) {
            $this->driver->update([
                'id' => Str::uuid(),
                'entry' => $entry,
                'title' => $title,
                'url' => (string)$url,
                'description' => $description,
                'date_modified_timestamp' => $dateModified->getTimestamp(),
            ]);
        }
    }

    public function crawlFailed(UriInterface $url, RequestException $requestException, ?UriInterface $foundOnUrl = null): void
    {
        ray('crawl failed for ' . $url)->red();
    }
}