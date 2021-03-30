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
                            $tribus[$i++]['condition'] = self::convertConditionCode($matches[1]);
                        }
                    }
                }
            }
        );
        $i = 0;
        $dayNodes->filter('tr.temperatures')->children()->each(
            function (Crawler $node) use (&$tribus, &$i) {
                if ($node->matches('td')) {
                    $text = trim($node->text());
                    if (preg_match('/^([\-\d]+)°$/', $text, $matches)) {
                        $temperature = (int)$matches[1];
                    } else {
                        $temperature = null;
                    }
                    $tribus[$i++]['temp'] = $temperature;
                }
            }
        );

        $i = 0;
        $dayNodes->filter('tr.windspeeds')->children()->each(
            function (Crawler $node) use (&$tribus, &$i) {
                if ($node->matches('td')) {
                    if (preg_match('/^(\d+)\-(\d+)$/', trim($node->text()), $matches)) {
                        $windMin                = (int)$matches[1];
                        $windMax                = (int)$matches[2];
                        $tribus[$i]['wind_min'] = $windMin;
                        $tribus[$i]['wind_max'] = $windMax;
                        //                        $tribus[$i]['wind_avg'] = round(($windMin + $windMax) / 2);
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
                        $precipitationMin = 1;
                        $precipitationMax = 1;
                    } elseif (is_numeric($text)) {
                        $precipitationMin = (int)$text;
                        $precipitationMax = (int)$text;
                    } else {
                        $precipitationMin = 0;
                        $precipitationMax = 0;
                    }
                    $tribus[$i]['rain_min'] = $precipitationMin;
                    $tribus[$i]['rain_max'] = $precipitationMax;
                    ++$i;
                }
            }
        );

        $i = 0;
        $dayNodes->filter('tr.precipprobs')->children()->each(
            function (Crawler $node) use (&$tribus, &$i) {
                if ($node->matches('td')) {
                    $text = trim($node->text());
                    if (preg_match('/^(\d+)(?:\s*)\%$/', $text, $matches)) {
                        $tribus[$i++]['rain_prob'] = (int)$matches[1];
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
                        'hour'      => (int)$matches[1],
                        'rain_prob' => (int)$matches[2],
                        'rain_prec' => (float)$matches[3],
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

                $daily[$i]['day'] = self::dayShortToCode($dayShort);
                $tempMax          = trim($node->filter('.tab_temp_max')->text());
                if (preg_match('/^([\-\d+]+)\s*/', $tempMax, $matches)) {
                    $daily[$i]['temp_max'] = (int)$matches[1];
                }
                $tempMin = trim($node->filter('.tab_temp_min')->text());
                if (preg_match('/^([\-\d+]+)\s*/', $tempMin, $matches)) {
                    $daily[$i]['temp_min'] = (int)$matches[1];
                }
                $wind = trim($node->filter('.wind')->text());
                if (preg_match('/^.*(\d+)\skm\/h.*$/', $wind, $matches)) {
                    $daily[$i]['wind_min'] = (int)$matches[1];
                    $daily[$i]['wind_max'] = (int)$matches[1];
                }
                $rain = trim($node->filter('.tab_precip')->text());
                if (preg_match('/(\d)\-(\d)\s*mm/i', $rain, $matches)) {
                    $rainMin = (int)$matches[1];
                    $rainMax = (int)$matches[2];
                } elseif (preg_match('/(\d)\s*mm/i', $rain, $matches)) {
                    $rainMin = (int)$matches[1];
                    $rainMax = (int)$matches[1];
                } else {
                    $rainMin = 0;
                    $rainMax = 0;
                }
                $daily[$i]['rain_min'] = $rainMin;
                $daily[$i]['rain_max'] = $rainMax;

                $sun = trim($node->filter('.tab_sun')->text());
                if (preg_match('/^\s*(\d+)\sh\s*$/', $sun, $matches)) {
                    $daily[$i]['sun'] = (int)$matches[1];
                }

                $predictabilityStyle = $node->filter('.meter_outer .meter_inner')->attr('style');
                if (preg_match('/^.*width:\s+(\d+)\%.*$/', $predictabilityStyle, $matches)) {
                    $daily[$i]['pred'] = (int)$matches[1];
                }

                $iconNode = $node->filter('.weather .day .weather_pictogram');
                $imgSrc   = $iconNode->attr('src');
                if (preg_match('/\/(\d+)_day(?:.*)$/', $imgSrc, $matches)) {
                    $daily[$i]['condition'] = self::convertConditionCode($matches[1]);
                }
                if (preg_match('/\/(\d+)_iday(?:.*)$/', $imgSrc, $matches)) {
                    $daily[$i]['condition'] = self::convertIConditionCode($matches[1]);
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
    
    private static function convertIConditionCode(int $mbCode): int
    {
        switch ($mbCode) {
            case 1:
                return 110;
            case 2:
                return 215;
            case 3: //mostly cloudy, might should get a separate icon
                return 214;
            case 4:
                return 226;
            case 5:
                return 130; //fog
            case 6:
                return 326;
            case 7:
                return 315;
            case 8:
                return 514;
            case 9:
                return 426;
            case 10:
                return 415;
            case 11: //sun, 2 cloud, 2 snow, 2 rain
                return 436;
            case 12:
                return 324;
            case 13:
                return 424;
            case 14: // sun, 2 clouds, 5 rain
                return 315;
            case 15: // sun, 2 clouds, 6 snow
                return 416;
            case 16:
                return 315;
            case 17:
                return 415;
            case 18:
            case 19:
                return 810;
            default:
                return (int)('9' . (string)$mbCode);
        }
    }

    /**
     * Switch to internal condition code
     * 
     * @param int $mbCode
     * @return int
     */
    private static function convertConditionCode(int $mbCode): int
    {
        switch ($mbCode) {
            // 100 sun
            case 1: //sun
            case 2: //sun
            case 3: //sun
                return 110;
            case 13: //sun, hazy
            case 14: //sun, hazy
            case 15: //sun, hazy
                return 120;

            // 200 cloudy
            case 4: //sun, cloud
            case 5: //sun, cloud
            case 6: //sun, cloud
                return 215;
            case 7: //sun, cloud 2
            case 8: //sun, cloud 2
            case 9: //sun, cloud 2
                return 216;
            case 19: //sun in back, darker cloud 2
            case 20: //sun in back, darker cloud 2
            case 21: //sun in back, darker cloud 2
                return 217;
            case 10: //sun, cloud big dark
            case 11: //sun, cloud big dark
            case 12: //sun, cloud big dark
                return 218;
            case 16: //cloud 2, hazy
            case 17: //cloud 2, hazy
            case 18: //cloud 2, hazy
                return 225;
            case 22: //cloud 2 (dark and light) 
                return 226;

            // 300 rain
            case 31: //mostly sun, 1 cloud, rain 3 
                return 315;
            case 33: //cloud 2 (dark and light), rain 3
                return 324;
            case 23: //cloud 2 (dark and light), rain 5 
                return 325;
            case 25: //cloud 2 dark, rain 7 
                return 326;
       
            // 400 snow
            case 32: //sun, cloud 1, snow 3 
                return 415;
            case 34: //cloud 2, snow 2
                return 424;
            case 24: //cloud 2, snow 3 
                return 425;
            case 26: //cloud 2, snow 6 
                return 426;
            case 29: //cloud 3, snow 6 
                return 427;
            case 35: //cloud 3, snow 2, rain 2 
                return 435;

            // 500 thunderstorm
            case 28: //sun, mostly cloud 1, thunder, rain 3 
                return 514;
            case 27: //sun, mostly cloud 1, thunder, rain 5 
                return 515;

            case 30: //cloud 3, thunder, rain 7 
                return 525;

            // 800 special
            case 36: //sandstorm 
            case 37: //sandstorm 
                return 810;


            default:
                return (int)('9' . (string)$mbCode);
        }
    }

    /**
     * @param string $dayShort
     * @return string
     */
    private static function dayShortToTerm(string $dayShort): string
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

    /**
     * @param string $dayShort
     * @return int
     */
    public static function dayShortToCode(string $dayShort): int
    {
        switch (strtolower($dayShort)) {
            case 'sat':
                return 7;
            case 'sun':
                return 1;
            case 'mon':
                return 2;
            case 'tue':
                return 3;
            case 'wed':
                return 4;
            case 'thu':
                return 5;
            case 'fri':
                return 6;
            default:
                return (int)$dayShort;
        }
    }
}
