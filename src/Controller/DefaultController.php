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

use App\Fetcher\Mb\MbCachedFetcher;
use App\Fetcher\Mb\MbFetcher;
use App\Parser\Mb\MbParser;
use App\Parser\Oc\OcParser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DefaultController
{

    /**
     * @var MbParser
     */
    private MbParser $mbParser;

    /**
     * @var OcParser
     */
    private OcParser $ocParser;

    /**
     * DefaultController constructor.
     *
     * @param MbParser $mbParser
     * @param OcParser $ocParser
     */
    public function __construct(MbParser $mbParser, OcParser $ocParser)
    {
        $this->mbParser = $mbParser;
        $this->ocParser = $ocParser;
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
        $result = $this->mbParser->parse($source, $date);

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
