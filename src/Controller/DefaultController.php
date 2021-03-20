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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DefaultController
{
   
    private MbParser $parser;
    
    /**
     * DefaultController constructor.
     *
     * @param MbParser $parser
     */
    public function __construct(MbParser $parser) { $this->parser = $parser; }
    
    
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
        $result = $this->parser->parse($source, $date);
        
        return new JsonResponse(['status' => 'ok', 'result' => $result, 'dc' => time()]);
    }
    
    
}
