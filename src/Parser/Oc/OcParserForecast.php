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

class OcParserForecast
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
     * @return array
     */
    public function parse(int $source, \DateTimeImmutable $date): array
    {
        $json = $this->fetcher->fetch($source, $date);
        $data = json_decode($json, true);

        $resultDaily = [];
        foreach ($data['daily'] as $dataDaily) {
            $dt        = \DateTimeImmutable::createFromFormat('U', $dataDaily['dt']);
            $dtWeekday = MbParser::dayShortToCode($dt->format('D'));
            $windSpeed     = round($dataDaily['wind_speed'] * 3.6);
            $rainProb      = round($dataDaily['pop'] * 100);
            $rainValue     = round(isset($dataDaily['rain']) ?? 0);
            
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

        return ['daily' => $resultDaily];
    }

}
