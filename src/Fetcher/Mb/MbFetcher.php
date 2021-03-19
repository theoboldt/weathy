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


use App\Fetcher\ClientTrait;

class MbFetcher implements MbFetcherInterface
{
    use ClientTrait;
    
    /**
     * @var string
     */
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
    
    /**
     * @param \DateTimeImmutable $date
     * @return string
     */
    public function fetch1(\DateTimeImmutable $date): string
    {
        if (empty($this->url1)) {
            throw new \RuntimeException('Url not configured');
        }
        $client   = $this->client($this->url1);
        $response = $client->get('');
        $status   = $response->getStatusCode();
        
        if ($status === 200) {
            return $response->getBody()->getContents();
        } else {
            throw new \RuntimeException('Unexpected status code ' . $status . ' received');
        }
    }
    
}
