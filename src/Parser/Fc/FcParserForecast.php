<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace App\Parser\Fc;


use App\Fetcher\Fc\FcFetcherInterface;
use App\Parser\Mb\MbParser;

class FcParserForecast
{

    /**
     * @var FcFetcherInterface
     */
    private FcFetcherInterface $fetcher;

    /**
     * FcParserMinutely constructor.
     *
     * @param FcFetcherInterface $fetcher
     */
    public function __construct(FcFetcherInterface $fetcher)
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

        $timezoneDiff = $data['city']['timezone'];

        $resultTribus = [];
        foreach ($data['list'] as $dataTribus) {
            $dt = \DateTimeImmutable::createFromFormat('U', $dataTribus['dt'] + $timezoneDiff);

            $condition = isset($dataTribus['weather'][0]['id']) ? self::convertWeatherIdToCode(
                $dataTribus['weather'][0]['id']
            ) : 0;
            $windSpeed = isset($dataTribus['wind']['speed']) ? round($dataTribus['wind']['speed'] * 3.6) : 0;
            $rainProb  = round($dataTribus['pop'] * 100);
            $rainValue = round(isset($dataTribus['rain']['3h']) ?? 0);

            $tempMax = $dataTribus['main']['temp_max'];
            $tempMin = $dataTribus['main']['temp_min'];

            $resultTribus[] = [
                'hour'      => (int)$dt->format('H'),
                'condition' => $condition,
                'temp'      => (int)round(($tempMin + $tempMax + $tempMax) / 3), //temp max get's more weighted
                'temp_max'  => (int)round($tempMax),
                'temp_min'  => (int)round($tempMin),
                //                'temp_feel' => (int)round($dataTribus['main']['feels_like']),
                'wind_min'  => (int)$windSpeed,
                'wind_max'  => (int)$windSpeed,
                'rain_prob' => (int)($rainProb > 100 ? 100 : $rainProb),
                'rain_min'  => (int)$rainValue,
                'rain_max'  => (int)$rainValue,
            ];

            if (count($resultTribus) >= 8) {
                break;
            }
        }

        $sunrise = \DateTimeImmutable::createFromFormat('U', $data['city']['sunrise'] + $timezoneDiff);
        $sunset  = \DateTimeImmutable::createFromFormat('U', $data['city']['sunset'] + $timezoneDiff);

        return [
            'tribus'  => $resultTribus,
            'sunrise' => ['h' => (int)$sunrise->format('H'), 'm' => (int)$sunrise->format('i')],
            'sunset'  => ['h' => (int)$sunset->format('H'), 'm' => (int)$sunset->format('i')],
        ];
    }

    /**
     * @param int $id
     * @return int
     */
    public static function convertWeatherIdToCode(int $id): int
    {
        switch ($id) {
            case 200:    //thunderstorm with light rain	 11d
                return 523;
            case 201:    //thunderstorm with rain	 11d
                return 524;
            case 202:    //thunderstorm with heavy rain	 11d
                return 525;
            case 210:    //light thunderstorm	 11d
                return 535;
            case 211:    //thunderstorm	 11d
                return 535;
            case 212:    //heavy thunderstorm	 11d
                return 536;
            case 221:    //ragged thunderstorm	 11d
                return 537;
            case 230:    //thunderstorm with light drizzle	 11d
                return 523;
            case 231:    //thunderstorm with drizzle	 11d
                return 524;
            case 232:    //thunderstorm with heavy drizzle	 11d
                return 525;

            case 300: //	Drizzle	light intensity drizzle	 09d
                return 323;
            case 301: //	Drizzle	drizzle	 09d
                return 324;
            case 302: //	Drizzle	heavy intensity drizzle	 09d
                return 325;
            case 310: //	Drizzle	light intensity drizzle rain	 09d
                return 326;
            case 311: //	Drizzle	drizzle rain	 09d
                return 324;
            case 312: //	Drizzle	heavy intensity drizzle rain	 09d
                return 325;
            case 313: //	Drizzle	shower rain and drizzle	 09d
                return 325;
            case 314: //	Drizzle	heavy shower rain and drizzle	 09d
                return 325;
            case 321: //	Drizzle	shower drizzle	 09d
                return 325;

            case 500: //	Rain	light rain	 10d
                return 323;
            case 501: //	Rain	moderate rain	 10d
                return 324;
            case 502: //	Rain	heavy intensity rain	 10d
                return 325;
            case 503: //	Rain	very heavy rain	 10d
                return 326;
            case 504: //	Rain	extreme rain	 10d
                return 326;
            case 511: //	Rain	freezing rain	 13d
                return 435;
            case 520: //	Rain	light intensity shower rain	 09d
                return 323;
            case 521: //	Rain	shower rain	 09d
                return 324;
            case 522: //	Rain	heavy intensity shower rain	 09d
                return 325;
            case 531: //	Rain	ragged shower rain	 09d
                return 326;

            case 600: //	Snow	light snow	 13d
                return 424;
            case 601: //	Snow	Snow	 13d
                return 425;
            case 602: //	Snow	Heavy snow	 13d
                return 426;
            case 611: //	Snow	Sleet	 13d
                return 435;
            case 612: //	Snow	Light shower sleet	 13d
                return 435;
            case 613: //	Snow	Shower sleet	 13d
                return 435;
            case 615: //	Snow	Light rain and snow	 13d
                return 435;
            case 616: //	Snow	Rain and snow	 13d
                return 435;
            case 620: //	Snow	Light shower snow	 13d
                return 435;
            case 621: //	Snow	Shower snow	 13d
                return 435;
            case 622: //	Snow	Heavy shower snow	 13d
                return 435;

            case 701: //	Mist	mist	 50d
                return 130;
            case 711: //	Smoke	Smoke	 50d
                return 130;
            case 721: //	Haze	Haze	 50d
                return 130;
            case 731: //	Dust	sand/ dust whirls	 50d
                return 130;
            case 741: //	Fog	fog	 50d
                return 130;
            case 751: //	Sand	sand	 50d
                return 130;
            case 761: //	Dust	dust	 50d
                return 130;
            case 762: //	Ash	volcanic ash	 50d
                return 0;
            case 771: //	Squall	squalls	 50d
                return 0;
            case 781: //	Tornado	tornado	 50d
                return 0;

            case 800: //	Clear	clear sky	 01d
                return 110;

            case 801: //	Clouds	few clouds: 11-25%	 02d
                return 214;
            case 802: //	Clouds	scattered clouds: 25-50%	 03d
                return 216;
            case 803: //	Clouds	broken clouds: 51-84%	 04d
                return 217;
            case 804: //	Clouds	overcast clouds: 85-100%	 04d
                return 226;

            default:
                return 900;
        }
    }

}
