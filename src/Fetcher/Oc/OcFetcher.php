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


use App\Fetcher\ClientTrait;

class OcFetcher implements OcFetcherInterface
{
    use ClientTrait;

    /**
     * @var string[]
     */
    private array $url1;

    /**
     * OcFetcher constructor.
     *
     * @param string[] $url1
     */
    public function __construct(array $url1)
    {
        $this->url1 = $url1;
    }


    /**
     * @param int $source
     * @return string
     */
    public function urlForSource(int $source): string
    {
        switch ($source) {
            case 1:
                return $this->getUrl1();
            default:
                throw new \InvalidArgumentException('Unknown source requested');
        }
    }

    /**
     * @param int                $source
     * @param \DateTimeImmutable $date
     * @return string
     */
    public function fetch(int $source, \DateTimeImmutable $date): string
    {
        $url = $this->urlForSource($source);
        if (empty($url)) {
            throw new \RuntimeException('Url not configured');
        }
        $client   = $this->client($url);
        $response = $client->get('');
        $status   = $response->getStatusCode();

        if ($status === 200) {
            return $response->getBody()->getContents();
        } else {
            throw new \RuntimeException('Unexpected status code ' . $status . ' received');
        }
    }

    /**
     * @return string
     */
    private function getUrl1(): string
    {
        return $this->url1[array_rand($this->url1)];
    }

    /**
     * @param string $url1
     * @return $this
     */
    public function create(string $url1): self
    {
        return new self(explode(';', $url1));
    }
}
