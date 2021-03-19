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


class OnecallCachedFetcher implements OnecallFetcherInterface
{
    
    /**
     * @var string
     */
    private string $cachePath;
    
    /**
     * OnecallFetcher constructor.
     *
     * @param string $cachePath
     */
    public function __construct(string $cachePath)
    {
        $this->cachePath = rtrim($cachePath, DIRECTORY_SEPARATOR);
    }
    
    
    public function fetch1($date)
    {
        $date = new \DateTimeImmutable();
        $key  = 'oc_' . $date->format('Y-m-d_h-i');
    }
    
}