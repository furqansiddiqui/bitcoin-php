<?php
/**
 * This file is a part of "furqansiddiqui/bitcoin-php" package.
 * https://github.com/furqansiddiqui/bitcoin-php
 *
 * Copyright (c) 2019 Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/furqansiddiqui/bitcoin-php/blob/master/LICENSE
 */

declare(strict_types=1);

namespace FurqanSiddiqui\Bitcoin\Transactions;

use FurqanSiddiqui\DataTypes\Base16;

/**
 * Class SerializedTransaction
 * @package FurqanSiddiqui\Bitcoin\Transactions
 */
class SerializedTransaction
{
    private $serializedBase16;
    private $hash;

    public function __construct(Base16 $serializedTxBase16, ?Base16 $hash = null)
    {

    }

    public function get(): Base16
    {
        return $this->serializedBase16;
    }

    public function hash(): Base16
    {
        return $this->hash;
    }

    public function decode(): Transaction
    {

    }
}