<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace App\Controller;

use App\Parser\Oc\OcParserMinutely;
use App\Parser\ParserForecast;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DefaultController
{

    /**
     * @var ParserForecast
     */
    private ParserForecast $forecastParser;

    /**
     * @var OcParserMinutely
     */
    private OcParserMinutely $ocParser;

    /**
     * DefaultController constructor.
     *
     * @param ParserForecast   $mbParser
     * @param OcParserMinutely $ocParser
     */
    public function __construct(ParserForecast $mbParser, OcParserMinutely $ocParser)
    {
        $this->forecastParser = $mbParser;
        $this->ocParser       = $ocParser;
    }

    /**
     * @return Response
     */
    public function index(): Response
    {
        return new JsonResponse(['status' => 'ok']);
    }

    /**
     * @param int $source
     * @return Response
     */
    public function weatherGeneral(int $source): Response
    {
        if ($source < 1 || $source > 2) {
            throw new BadRequestHttpException('Unknown source requested');
        }

        $date   = new \DateTimeImmutable('now');
        $result = $this->forecastParser->parse($source, $date);

        return new JsonResponse(['status' => 'ok', 'result' => $result, 'dt' => time()]);
    }

    /**
     * @param int $source
     * @return Response
     */
    public function weatherRainMinutely(int $source): Response
    {
        if ($source < 1 || $source > 2) {
            throw new BadRequestHttpException('Unknown source requested');
        }

        $date   = new \DateTimeImmutable('now');
        $result = $this->ocParser->parse($source, $date);

        return new JsonResponse(['status' => 'ok', 'result' => $result, 'dt' => time()]);
    }

}
