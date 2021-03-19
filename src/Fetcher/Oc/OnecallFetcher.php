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


class OnecallFetcher implements OnecallFetcherInterface
{
    private string $url1;
    
    /**
     * OnecallFetcher constructor.
     *
     * @param string $url1
     */
    public function __construct(string $url1)
    {
        $this->url1 = $url1;
    }
    
    
    public function fetch1($date)
    {
        $date = new \DateTimeImmutable();
        $key  = 'oc_' . $date->format('Y-m-d_h-i');
    }
    
}
