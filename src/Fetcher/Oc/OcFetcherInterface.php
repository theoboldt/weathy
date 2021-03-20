<?php
/**
 * This file is part of the Juvem package.
 *
 * (c) Erik Theoboldt <erik@theoboldt.eu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Fetcher\Oc;


interface OcFetcherInterface
{

    /**
     * @param int                $source
     * @param \DateTimeImmutable $date
     * @return string
     */
    public function fetch(int $source, \DateTimeImmutable $date): string;

}
