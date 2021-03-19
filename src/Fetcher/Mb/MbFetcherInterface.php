<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Fetcher\Mb;


interface MbFetcherInterface
{
    
    public function fetch1(\DateTimeImmutable $date);
    
}