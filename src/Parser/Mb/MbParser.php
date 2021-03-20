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
     * @param Crawler $dayNodes
     * @return array
     */
    private function provideTribus(Crawler $dayNodes): array
    {
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
                    if (preg_match('/^(\-\d+)°$/', trim($node->text()), $matches)) {
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

        return $tribus;
    }

    /**
     * @param Crawler $dayNodes
     * @return array
     */
    private function provideHourly(Crawler $dayNodes): array
    {
        $hourly = [];
        $i      = 0;
        $dayNodes->filter('tr.precip-hourly-title div.precip-hourly .precip-help')->each(
            function (Crawler $node) use (&$hourly, &$i) {
                $text = trim($node->text());
                if (preg_match(
                    '/^(\d+):(?:\d+) to (?:\d+):(?:\d+):(\d+)\% chance of precipitation in the area\.([0-9\.]+)/',
                    $text, $matches
                )) {
                    $hourly[$i++] = [
                        'hour'               => (int)$matches[1],
                        'rain_probability'   => (int)$matches[2],
                        'rain_precipitation' => (float)$matches[3],
                    ];
                }
            }
        );

        return $hourly;
    }

    /**
     * @param Crawler $dailyNodes
     * @return array
     */
    private function provideDaily(Crawler $dailyNodes): array
    {
        $daily = [];
        $i     = 0;

        $dailyNodes->each(
            function (Crawler $node) use (&$daily, &$i) {
                $dayShort = trim($node->filter('.tab_day_short')->text());

                $daily[$i]['day'] = self::dayShortToTerm($dayShort);

                $tempMax = trim($node->filter('.tab_temp_max')->text());
                if (preg_match('/^([\-\d+]+)\s*/', $tempMax, $matches)) {
                    $daily[$i]['temperature_max'] = (int)$matches[1];
                }
                $tempMin = trim($node->filter('.tab_temp_min')->text());
                if (preg_match('/^([\-\d+]+)\s*/', $tempMin, $matches)) {
                    $daily[$i]['temperature_min'] = (int)$matches[1];
                }
                $wind = trim($node->filter('.wind')->text());
                if (preg_match('/^.*(\d+)\skm\/h.*$/', $wind, $matches)) {
                    $daily[$i]['wind'] = (int)$matches[1];
                }
                $rain = trim($node->filter('.tab_precip')->text());
                if ($rain !== '-') {
                    $rain = '';
                }
                $daily[$i]['rain'] = $rain;

                $sun = trim($node->filter('.tab_sun')->text());
                if (preg_match('/^\s*(\d+)\sh\s*$/', $sun, $matches)) {
                    $daily[$i]['sun'] = (int)$matches[1];
                }

                $predictabilityStyle = $node->filter('.meter_outer .meter_inner')->attr('style');
                if (preg_match('/^.*width:\s+(\d+)\%.*$/', $predictabilityStyle, $matches)) {
                    $daily[$i]['predictability'] = (int)$matches[1];
                }

                $i++;
            }
        );

        return $daily;
    }

    /**
     * @param int                $source
     * @param \DateTimeImmutable $date
     * @return array
     */
    public function parse(int $source, \DateTimeImmutable $date): array
    {
        $html = $this->fetcher->fetch($source, $date);
        $html = str_replace('<di class="precip-bar-flex">', '<div class="precip-bar-flex">', $html);

        $crawler = new Crawler($html);

        $dayNodes   = $crawler->filter('html body main #tab_wrapper .tab');
        $dailyNodes = $crawler->filter('html body main #tab_wrapper .tab_detail.active table tbody');

        return [
            'tribus' => $this->provideTribus($dailyNodes),
            'hourly' => $this->provideHourly($dailyNodes),
            'daily'  => $this->provideDaily($dayNodes),
        ];
    }

    private static function dayShortToTerm(string $dayShort)
    {
        switch (strtolower($dayShort)) {
            case 'sat':
                return 'Sa';
            case 'sun':
                return 'So';
            case 'mon':
                return 'Mo';
            case 'tue':
                return 'Di';
            case 'wed':
                return 'Mi';
            case 'thu':
                return 'Do';
            case 'fri':
                return 'Fr';
            default:
                return $dayShort;
        }
    }
}
