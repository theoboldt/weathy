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

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DefaultController
{
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
        return new JsonResponse(['status' => 'ok']);
    }


}
