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

use Comely\DataTypes\Buffer\Base16;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxInput;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxInputs;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxOutput;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxOutputs;

/**
 * Class Transaction
 * @package FurqanSiddiqui\Bitcoin\Transactions
 * @property-read Base16 $verUInt32LE
 * @property-read Base16 $lockTimeUInt32LE
 */
class Transaction
{
    /** @var int */
    private $version;
    /** @var TxInputs */
    private $inputs;
    /** @var TxOutputs */
    private $outputs;
    /** @var int */
    private $lockTime;

    public static function Decode(Base16 $encodedTx): self
    {

    }

    /**
     * Transaction constructor.
     */
    public function __construct()
    {
        $this->version = 1;
        $this->inputs = new TxInputs($this);
        $this->outputs = new TxOutputs($this);
        $this->lockTime = 0;
    }

    /**
     * @param $prop
     * @return Base16
     */
    public function __get($prop)
    {
        switch ($prop) {
            case "verUInt32LE":
                $uInt32LE = bin2hex(pack("V", $this->version));
                return new Base16($uInt32LE);
            case "lockTimeUInt32LE":
                $uInt32LE = bin2hex(pack("V", $this->lockTime));
                return new Base16($uInt32LE);
        }

        throw new \OutOfBoundsException('Cannot get value of inaccessible property');
    }

    /**
     * @param int $version
     * @return Transaction
     */
    public function setVersion(int $version): self
    {
        if ($version < 0) {
            throw new \InvalidArgumentException('Invalid transaction version');
        }

        $this->version = $version;
        return $this;
    }

    /**
     * @return int
     */
    public function version(): int
    {
        return $this->version;
    }

    /**
     * @return TxInputs
     */
    public function inputs(): TxInputs
    {
        return $this->inputs;
    }

    /**
     * @return TxOutputs
     */
    public function outputs(): TxOutputs
    {
        return $this->outputs;
    }

    /**
     * @param int $lockTime
     * @return Transaction
     */
    public function setLockTime(int $lockTime): self
    {
        if ($lockTime < 0) {
            throw new \InvalidArgumentException('Invalid transaction lock time');
        }

        $this->lockTime = $lockTime;
        return $this;
    }

    /**
     * @return int
     */
    public function lockTime(): int
    {
        return $this->lockTime;
    }

    /**
     * @return SerializedTransaction
     */
    public function serialize(): SerializedTransaction
    {
        $serialized = new Base16();

        // Add 4 byte version
        $serialized->append($this->verUInt32LE);

        // Inputs
        if ($this->inputs->count() < 1) {
            throw new \UnexpectedValueException('Transaction has no inputs');
        }

        // Append number of inputs
        $serialized->append(dechex($this->inputs->count()));

        /** @var TxInput $input */
        foreach ($this->inputs as $input) {
            // Append 32 byte prev. Tx hash
            $serialized->append($input->prevTxHash());

            // Four byte output index
            $serialized->append($input->indexUInt32LE);

            // Signing scriptSig
            $scriptSig = $input->scriptSig();
            $scriptSigLen = $scriptSig->script()->binary()->size()->bytes();

            // 1 byte scriptSig len
            $serialized->append(dechex($scriptSigLen));

            // Append actual scriptSig
            $serialized->append($scriptSig->script());

            // 4 byte sequence number
            $serialized->append($input->seqUInt32LE);

            // Input done! continue to next
            unset($scriptSig, $scriptSigLen);
        }

        // Outputs
        if ($this->outputs->count() < 1) {
            throw new \UnexpectedValueException('Transaction has no outputs');
        }

        // Append number of outputs
        $serialized->append(dechex($this->outputs->count()));

        /** @var TxOutput $output */
        foreach ($this->outputs as $output) {
            // Output amount (8-byte)
            $serialized->append($output->valueUInt64LE);

            // scriptPubKey
            $scriptPubKey = $output->scriptPubKey();
            $scriptPubKeyLen = $scriptPubKey->script()->binary()->size()->bytes();

            // 1 byte scriptPubKey len
            $serialized->append(dechex($scriptPubKeyLen));

            // Append actual scriptPubKey
            $serialized->append($scriptPubKey->script());

            // Output done!
            unset($scriptPubKey, $scriptPubKeyLen);
        }

        // Finally, Tx lock time! 4 byte
        $serialized->append($this->lockTimeUInt32LE);

        // Set buffer state as readOnly
        $serialized->readOnly(true);

        // Calculate TxHash
        $hash = $serialized->binary()->hash()->sha256()
            ->hash()->sha256(); // SHA256 twice

        return new SerializedTransaction($serialized, $hash->base16());
    }
}