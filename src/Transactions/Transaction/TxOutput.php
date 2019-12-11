<?php
/**
 * This file is a part of "furqansiddiqui/bitcoin-php" package.
 * https://github.com/furqansiddiqui/bitcoin-php
 *
 * Copyright (c) 2019-2020 Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/furqansiddiqui/bitcoin-php/blob/master/LICENSE
 */

declare(strict_types=1);

namespace FurqanSiddiqui\Bitcoin\Transactions\Transaction;

use Comely\DataTypes\Buffer\Base16;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction;

/**
 * Class TxOutput
 * @package FurqanSiddiqui\Bitcoin\Transactions\Transaction
 * @property-read int $valueAsInt
 * @property-read Base16 $valueUInt64LE
 * @property-read string|null $type
 * @property-read string|null $address
 * @property-read string|null $scriptError
 */
class TxOutput implements TxInOutInterface
{
    /** @var Transaction */
    private $tx;
    /** @var int */
    private $index;
    /** @var int */
    private $value;
    /** @var Script */
    private $scriptPubKey;
    /** @var null|string */
    private $type;
    /** @var null|string */
    private $address;
    /** @var null|string */
    private $scriptError;

    /**
     * TxOutput constructor.
     * @param Transaction $tx
     * @param int $index
     * @param int $satoshis
     * @param Script $scriptPubKey
     */
    public function __construct(Transaction $tx, int $index, int $satoshis, Script $scriptPubKey)
    {
        if ($satoshis < 0) {
            throw new \InvalidArgumentException('Tx output value must be positive integer');
        }

        $this->tx = $tx;
        $this->index = $index;
        $this->value = $satoshis;
        $this->scriptPubKey = $scriptPubKey;

        // Convert ScriptPubKey to address and appropriate address-type
        try {
            $address = $tx->network->address()->addressFromScript($scriptPubKey);
            $this->type = $address->type();
            $this->address = $address->address();
        } catch (PaymentAddressException $e) {
            $this->scriptError = $e->getMessage();
        }
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
            case "type":
            case "address":
            case "scriptError":
                return $this->$prop;
        }

        throw new \OutOfBoundsException('Cannot get value of inaccessible property');
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->dump();
    }

    /**
     * Get amount to transfer in satoshis
     * @return int
     */
    public function value(): int
    {
        return $this->value;
    }

    /**
     * Get scriptPubKey
     * @return Script
     */
    public function scriptPubKey(): Script
    {
        return $this->scriptPubKey;
    }

    /**
     * Get dump of TxOutput as an array
     * @return array
     */
    public function dump(): array
    {
        $dump = [
            "value" => [
                "dec" => $this->valueAsInt,
                "uInt64LE" => $this->valueUInt64LE->hexits(false)
            ],
            "script" => [
                "script" => $this->scriptPubKey->raw(),
                "base16" => $this->scriptPubKey->script()->hexits(false),
                "type" => $this->type,
                "address" => $this->address,
            ]
        ];

        if ($this->scriptError) {
            $dump["script"]["error"] = $this->scriptError;
        }

        return $dump;
    }
}