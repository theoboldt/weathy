<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Parser\Mb;


use App\Fetcher\Mb\MbFetcherInterface;
use Symfony\Component\DomCrawler\Crawler;

class MbParser
{
    /**
     * @var MbFetcherInterface
     */
    private MbFetcherInterface $fetcher;
    
    /**
     * MbParser constructor.
     *
     * @param MbFetcherInterface $fetcher
     */
    public function __construct(MbFetcherInterface $fetcher)
    {
        $this->fetcher = $fetcher;
    }
    
    
    /**
     * @param \DateTimeImmutable $date
     * @return array
     */
    public function parse1(\DateTimeImmutable $date): array
    {
        $crawler = (new Crawler($this->fetcher->fetch1($date)));
        dump($crawler->matches('body'));
        
        $crawler = $crawler->filter('body #tab_wrapper');
        $dayResult = [];
        dump($crawler->matches('.tab_detail.active tbody tr'));
        $dayRows = $crawler->filter('.tab_detail.active tbody tr')->each(
            function (Crawler $node, $i) use ($dayResult) {
                $timeNode    = $node->filter('tr.times .cell.time time');
                $dayResult[] = $timeNode->text();
            }
        );
        
        dump($dayResult);
        
        return $dayResult;
    }
}