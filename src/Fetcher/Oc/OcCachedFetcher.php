<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Fetcher\Oc;


use App\Fetcher\Mb\MbFetcherInterface;

class OcCachedFetcher implements OcFetcherInterface
{
    const MAIN_KEY = 'oc';

    /**
     * @var string
     */
    private string $cachePath;

    /**
     * @var OcFetcherInterface
     */
    private OcFetcherInterface $fetcher;

    /**
     * MbCachedFetcher constructor.
     *
     * @param string             $cachePath
     * @param OcFetcherInterface $fetcher
     */
    public function __construct(string $cachePath, OcFetcherInterface $fetcher)
    {
        $this->cachePath = rtrim($cachePath, DIRECTORY_SEPARATOR);
        $this->fetcher   = $fetcher;
    }

    /**
     * Fetch cached
     *
     * @param int                $source
     * @param \DateTimeImmutable $date
     * @return string
     */
    public function fetch(int $source, \DateTimeImmutable $date): string
    {
        $file = $this->getCachePath($source, 1, $date);
        if (!file_exists($file) || !is_readable($file)) {
            $result = $this->fetcher->fetch($source, $date);
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
     * Get cache key
     *
     * @param int                $source
     * @param int                $provider
     * @param \DateTimeImmutable $date
     * @return string
     */
    private function getCacheKey(int $source, int $provider, \DateTimeImmutable $date): string
    {
        return self::MAIN_KEY . '_' . $source . '_' . $provider . '_' . $date->format('Y-m-d_h_i') . '.json';
    }

    /**
     * @param int                $source
     * @param int                $provider
     * @param \DateTimeImmutable $date
     * @return string
     */
    private function getCachePath(int $source, int $provider, \DateTimeImmutable $date): string
    {
        $key = $this->getCacheKey($source, $provider, $date);
        return $this->cachePath . DIRECTORY_SEPARATOR . $key;
    }
}
