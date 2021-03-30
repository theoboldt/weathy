<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace App\Parser;


use App\Parser\Fc\FcParserForecast;
use App\Parser\Mb\MbParser;
use App\Parser\Oc\OcParserDaily;

class ParserForecast
{
    private MbParser $mbParser;

    private OcParserDaily $ocParser;

    private FcParserForecast $fcParser;

    /**
     * ParserForecast constructor.
     *
     * @param MbParser         $mbParser
     * @param OcParserDaily    $ocParser
     * @param FcParserForecast $fcParser
     */
    public function __construct(MbParser $mbParser, OcParserDaily $ocParser, FcParserForecast $fcParser)
    {
        $this->mbParser = $mbParser;
        $this->ocParser = $ocParser;
        $this->fcParser = $fcParser;
    }

    /**
     * @param int                $source
     * @param \DateTimeImmutable $date
     * @return array
     */
    public function parse(int $source, \DateTimeImmutable $date): array
    {
        $resultMb = $this->mbParser->parse($source, $date);
        $resultOc = $this->ocParser->parse($source, $date);
        $resultFc = $this->fcParser->parse($source, $date);
        
        $result = array_merge($resultMb, $resultFc);
        
        foreach ($result['daily'] as &$mbDaily) {

            foreach ($resultOc['daily'] as $ocIndex => $ocDaily) {
                if ($ocDaily['day'] === $mbDaily['day']) {
                    if ($mbDaily['rain_min'] > $ocDaily['rain_min']) {
                        $mbDaily['rain_min'] = $ocDaily['rain_min'];
                    }
                    if ($mbDaily['rain_max'] < $ocDaily['rain_max']) {
                        $mbDaily['rain_max'] = $ocDaily['rain_max'];
                    }
                    if ($mbDaily['wind_min'] > $ocDaily['wind_min']) {
                        $mbDaily['wind_min'] = $ocDaily['wind_min'];
                    }
                    if ($mbDaily['wind_max'] < $ocDaily['wind_max']) {
                        $mbDaily['wind_max'] = $ocDaily['wind_max'];
                    }
                    $mbDaily['uvi'] = $ocDaily['uvi'];

                    unset($resultOc['daily'][$ocIndex]);
                    break;
                }
            }
        }

        return $result;
    }

}
