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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultController
{
    private MbCachedFetcher $mbFetcher;
    
    /**
     * DefaultController constructor.
     *
     * @param MbCachedFetcher $mbFetcher
     */
    public function __construct(MbCachedFetcher $mbFetcher)
    {
        $this->mbFetcher = $mbFetcher;
    }
    
    
    /**
     * @return Response
     */
    public function index(): Response
    {
        return new JsonResponse(['status' => 'ok']);
    }
    
    /**
     * @return Response
     */
    public function w1(): Response
    {
        $date   = new \DateTimeImmutable('now');
        $result = $this->mbFetcher->fetch1($date);
        
        return new JsonResponse(['status' => 'ok']);
    }
    
    
}
