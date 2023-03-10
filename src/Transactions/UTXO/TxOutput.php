<?php
/*
 * This file is a part of "furqansiddiqui/bitcoin-php" package.
 * https://github.com/furqansiddiqui/bitcoin-php
 *
 *  Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/furqansiddiqui/bitcoin-php/blob/master/LICENSE
 */

declare(strict_types=1);

namespace FurqanSiddiqui\Bitcoin\Transactions\UTXO;

use Comely\Buffer\BigInteger\LittleEndian;
use Comely\Utils\OOP\OOP;
use FurqanSiddiqui\Bitcoin\Address\AbstractPaymentAddress;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction;

/**
 * Class TxOutput
 * @package FurqanSiddiqui\Bitcoin\Transactions\Transaction
 */
class TxOutput implements UTXOInterface
{
    public readonly null|AbstractPaymentAddress $address;
    public readonly null|string $scriptError;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Transactions\Transaction $tx
     * @param int $index
     * @param int $value
     * @param \FurqanSiddiqui\Bitcoin\Script\Script $scriptPubKey
     */
    public function __construct(
        Transaction            $tx,
        public readonly int    $index,
        public readonly int    $value,
        public readonly Script $scriptPubKey)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Tx output value must be positive integer');
        }

        // Convert ScriptPubKey to address and appropriate address-type
        try {
            $this->address = $tx->btc->address->fromScriptPubKey($scriptPubKey);
        } catch (PaymentAddressException $e) {
            $this->scriptError = $e->getMessage();
            $this->address = null;
        }
    }


    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->dump();
    }

    /**
     * @return int
     */
    public function sizeInBytes(): int
    {
        return 8 + 1 + $this->scriptPubKey->buffer->len();
    }

    /**
     * Get dump of TxOutput as an array
     * @return array
     */
    public function dump(): array
    {
        $dump = [
            "value" => [
                "dec" => $this->value,
                "uInt64LE" => bin2hex(LittleEndian::PackUInt64($this->value))
            ],
            "scriptPubKey" => [
                "script" => $this->scriptPubKey->script,
                "base16" => $this->scriptPubKey->buffer->toBase16(),
                "address" => $this->address->address,
                "type" => OOP::baseClassName(get_class($this->address)),
            ]
        ];

        if ($this->scriptError) {
            $dump["script"]["error"] = $this->scriptError;
        }

        return $dump;
    }
}
