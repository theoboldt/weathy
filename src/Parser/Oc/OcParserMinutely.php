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

class OcParserMinutely
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

        $minDt    = 0;
        $maxDt    = 0;
        $minutely = [];
        foreach ($data['minutely'] as $datum) {
            if ($minDt === 0 || $datum['dt'] < $minDt) {
                $minDt = $datum['dt'];
            }
            if ($datum['dt'] > $maxDt) {
                $maxDt = $datum['dt'];
            }
            $minutely[] = $datum['precipitation']; //Precipitation volume, mm
        }

        return [
            'dt_min'          => $minDt,
            'dt_max'          => $maxDt,
            'timezone_offset' => $data['timezone_offset'],
            'minutely'        => $minutely,
        ];
    }

}
