<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Fetcher\Mb;


class MbCachedFetcher implements MbFetcherInterface
{
    const MAIN_KEY = 'mb';
    
    /**
     * @var string
     */
    private string $cachePath;
    
    /**
     * @var MbFetcherInterface
     */
    private MbFetcherInterface $fetcher;
    
    /**
     * MbCachedFetcher constructor.
     *
     * @param string $cachePath
     * @param MbFetcherInterface $fetcher
     */
    public function __construct(string $cachePath, MbFetcherInterface $fetcher)
    {
        $this->cachePath = rtrim($cachePath, DIRECTORY_SEPARATOR);
        $this->fetcher   = $fetcher;
    }
    
    /**
     * Get cache key
     *
     * @param int $provider
     * @param \DateTimeImmutable $date
     * @return string
     */
    private function getCacheKey(int $provider, \DateTimeImmutable $date): string
    {
        return self::MAIN_KEY . '_' . $provider . '_' . $date->format('Y-m-d_h-i') . '.htm';
    }
    
    /**
     * Fetch cached
     *
     * @param \DateTimeImmutable $date
     * @return string
     */
    public function fetch1(\DateTimeImmutable $date): string
    {
        $file = $this->getCachePath(1, $date);
        if (!file_exists($file) || !is_readable($file)) {
            $result = $this->fetcher->fetch1($date);
            if (!file_exists(dirname($file))) {
                if (!mkdir(dirname($file), 0777, true)) {
                    throw new \RuntimeException('Failed to create ' . dirname($file));
                }
            }
            file_put_contents($file, $result);
        } else {
            $result = file_get_contents($file);
        }
        return $result;
    }
    
    /**
     * @param int $provider
     * @param \DateTimeImmutable $date
     * @return string
     */
    private function getCachePath(int $provider, \DateTimeImmutable $date): string
    {
        $key = $this->getCacheKey($provider, $date);
        return $this->cachePath . DIRECTORY_SEPARATOR . $key;
    }
    
}