<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace App\Parser\Oc;


use App\Fetcher\Oc\OcFetcherInterface;
use App\Parser\Mb\MbParser;

class OcParserDaily
{

    /**
     * @var OcFetcherInterface
     */
    private OcFetcherInterface $fetcher;

    /**
     * OcParserMinutely constructor.
     *
     * @param OcFetcherInterface $fetcher
     */
    public function __construct(OcFetcherInterface $fetcher)
    {
        $this->fetcher = $fetcher;
    }

    /**
     * @param int                $source
     * @param \DateTimeImmutable $date
     * @param int|null           $tribusFirstHour
     * @return array
     */
    public function parse(int $source, \DateTimeImmutable $date, ?int $tribusFirstHour = null): array
    {
        $json         = $this->fetcher->fetch($source, $date);
        $data         = json_decode($json, true);
        $dataTimezone = new \DateTimeZone($data['timezone']);

        $resultDaily = [];
        foreach ($data['daily'] as $dataDaily) {
            $dt        = \DateTimeImmutable::createFromFormat('U', $dataDaily['dt']);
            $dtWeekday = MbParser::dayShortToCode($dt->format('D'));
            $windSpeed = round($dataDaily['wind_speed'] * 3.6);
            $rainProb  = round($dataDaily['pop'] * 100);
            $rainValue = round(isset($dataDaily['rain']) ?? 0);
            
            $resultDaily[] = [
                'day'       => $dtWeekday,
                'uvi'       => (int)round($dataDaily['uvi']),
//                'temp_max'  => (int)round($dataDaily['temp']['max']),
//                'temp_min'  => (int)round($dataDaily['temp']['min']),
                'wind_min'  => (int)$windSpeed,
                'wind_max'  => (int)(isset($dataDaily['wind_gust']) ? round($dataDaily['wind_gust'] * 3.6) : $windSpeed),
                'rain_prob' => (int)($rainProb > 100 ? 100 : $rainProb),
                'rain_min'  => (int)$rainValue,
                'rain_max'  => (int)$rainValue,
            ];
        }
        
        $resultHourly = [];
        $expectedHourOccurred = false;
        if ($tribusFirstHour) {
            foreach ($data['hourly'] as $dataHourly) {
                $dt          = \DateTimeImmutable::createFromFormat('U', $dataHourly['dt']);
                $dtTimezoned = $dt->setTimezone($dataTimezone);
                $dtHour      = (int)$dtTimezoned->format('H');

                if (!$expectedHourOccurred && $dtHour != $tribusFirstHour) {
                    continue;
                }
                $expectedHourOccurred = true;

                $rainProb  = isset($dataHourly['pop']) ? round($dataHourly['pop'] * 100) : 0;
                $rainValue = isset($dataHourly['rain']['1h']) ? $dataHourly['rain']['1h'] : 0.0; //mm

                $resultHourly[] = [
                    'hour'   => $dtHour,
                    'prob'   => (int)(min($rainProb, 100)),
                    'amount' => $rainValue,
                ];
                if (count($resultHourly) >= 24) {
                    break;
                }
            }
        }
        
        return ['daily' => $resultDaily, 'hourly_rain' => $resultHourly];
    }

}
