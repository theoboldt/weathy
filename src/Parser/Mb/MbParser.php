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
        $html = $this->fetcher->fetch1($date);
        $html = str_replace('<di class="precip-bar-flex">', '<div class="precip-bar-flex">', $html);

        $crawler = new Crawler($html);

        $dayNodes = $crawler->filter('html body main #tab_wrapper .tab_detail.active table tbody');

        $tribus = [];
        $i      = 0;
        $dayNodes->filter('tr.times')->children()->each(
            function (Crawler $node) use (&$tribus, &$i) {
                if ($node->matches('td')) {
                    $tribus[$i++]['hour'] = (int)((int)trim($node->text()) / 100);
                }
            }
        );
        $i = 0;
        $dayNodes->filter('tr.icons')->children()->each(
            function (Crawler $node) use (&$tribus, &$i) {
                if ($node->matches('td div.picon')) {
                    $iconNode = $node->filter('td div.picon');
                    $classes  = explode(' ', $iconNode->attr('class'));
                    foreach ($classes as $class) {
                        if (preg_match('/^p(\d+)\_(?:day|night)$/', $class, $matches)) {
                            $tribus[$i++]['condition'] = (int)$matches[1];
                        }
                    }
                }
            }
        );
        $i = 0;
        $dayNodes->filter('tr.temperatures')->children()->each(
            function (Crawler $node) use (&$tribus, &$i) {
                if ($node->matches('td')) {
                    if (preg_match('/^(\d+)°$/', trim($node->text()), $matches)) {
                        $tribus[$i++]['temperature'] = (int)$matches[1];
                    }

                }
            }
        );

        $i = 0;
        $dayNodes->filter('tr.windspeeds')->children()->each(
            function (Crawler $node) use (&$tribus, &$i) {
                if ($node->matches('td')) {
                    if (preg_match('/^(\d+)\-(\d+)$/', trim($node->text()), $matches)) {
                        $windMin                    = (int)$matches[1];
                        $windMax                    = (int)$matches[2];
                        $tribus[$i]['wind_min']     = $windMin;
                        $tribus[$i]['wind_max']     = $windMax;
                        $tribus[$i]['wind_average'] = round(($windMin + $windMax) / 2);
                        $i++;
                    }
                }
            }
        );

        $i = 0;
        $dayNodes->filter('tr.precips')->children()->each(
            function (Crawler $node) use (&$tribus, &$i) {
                if ($node->matches('td')) {
                    $text = trim($node->text());
                    if ($text === '< 1') {
                        $precipitation = 0.5;
                    } elseif (is_numeric($text)) {
                        $precipitation = (int)$text;
                    } else {
                        $precipitation = null;
                    }
                    $tribus[$i++]['rain_precipitation'] = $precipitation;
                }
            }
        );

        $i = 0;
        $dayNodes->filter('tr.precipprobs')->children()->each(
            function (Crawler $node) use (&$tribus, &$i) {
                if ($node->matches('td')) {
                    $text = trim($node->text());
                    if (preg_match('/^(\d+)(?:\s*)\%$/', $text, $matches)) {
                        $tribus[$i++]['rain_probability'] = (int)$matches[1];
                    }
                }
            }
        );

        $rainHourly = [];
        $i          = 0;
        $dayNodes->filter('tr.precip-hourly-title div.precip-hourly .precip-help')->each(
            function (Crawler $node) use (&$rainHourly, &$i) {
                $text = trim($node->text());
                if (preg_match(
                    '/^(\d+):(?:\d+) to (?:\d+):(?:\d+):(\d+)\% chance of precipitation in the area\.([0-9\.]+)/',
                    $text, $matches
                )) {
                    $rainHourly[$i++] = [
                        'hour'               => (int)$matches[1],
                        'rain_probability'   => (int)$matches[2],
                        'rain_precipitation' => (float)$matches[3],
                    ];
                }
            }
        );

        return ['tribus' => $tribus, 'hourly' => $rainHourly];
    }
}
