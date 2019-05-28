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

namespace FurqanSiddiqui\Bitcoin\Transactions\Transaction;

use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\DataTypes\Base16;

/**
 * Class TxOutput
 * @package FurqanSiddiqui\Bitcoin\Transactions\Transaction
 * @property-read int $valueAsInt
 * @property-read Base16 $valueUInt64LE
 */
class TxOutput implements TxInOutInterface
{
    /** @var int */
    private $value;
    /** @var Script */
    private $scriptPubKey;

    /**
     * TxOutput constructor.
     * @param int $satoshis
     * @param Script $scriptPubKey
     */
    public function __construct(int $satoshis, Script $scriptPubKey)
    {
        if ($satoshis < 0) {
            throw new \InvalidArgumentException('Tx output value must be positive integer');
        }

        $this->value = $satoshis;
        $this->scriptPubKey = $scriptPubKey;
    }

    /**
     * @param $prop
     * @return Base16|int
     */
    public function __get($prop)
    {
        switch ($prop) {
            case "valueAsInt":
                return $this->value;
            case "valueUInt64LE":
                // Convert value to uint64 little endian
                $uInt64LE = bin2hex(pack("P", $this->value));
                return new Base16($uInt64LE);
        }

        throw new \OutOfBoundsException('Cannot get value of inaccessible property');
    }

    /**
     * @return int
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * @return Script
     */
    public function scriptPubKey(): Script
    {
        return $this->scriptPubKey;
    }
}