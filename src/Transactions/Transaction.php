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

namespace FurqanSiddiqui\Bitcoin\Transactions;

use Comely\DataTypes\Buffer\Base16;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Exception\TransactionEncodeException;
use FurqanSiddiqui\Bitcoin\Exception\TransactionInputSignException;
use FurqanSiddiqui\Bitcoin\Protocol\VarInt;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxInput;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxInputs;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxOutput;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxOutputs;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;

/**
 * Class Transaction
 * @package FurqanSiddiqui\Bitcoin\Transactions
 * @property-read Base16 $verUInt32LE
 * @property-read Base16 $lockTimeUInt32LE
 * @property-read AbstractBitcoinNode $network
 * @property-read bool $isSegWit
 */
class Transaction
{
    public const VALID_VERSIONS = [1, 2];

    /** @var AbstractBitcoinNode */
    private $network;
    /** @var int */
    private $version;
    /** @var TxInputs */
    private $inputs;
    /** @var TxOutputs */
    private $outputs;
    /** @var int */
    private $lockTime;
    /** @var bool */
    private $isSegWit;

    /**
     * @param AbstractBitcoinNode $network
     * @param Base16 $encodedTx
     * @return static
     * @throws \FurqanSiddiqui\Bitcoin\Exception\TransactionDecodeException
     */
    public static function Decode(AbstractBitcoinNode $network, Base16 $encodedTx): self
    {
        return RawTransactionDecoder::Decode($network, $encodedTx);
    }

    /**
     * Transaction constructor.
     * @param AbstractBitcoinNode $network
     */
    public function __construct(AbstractBitcoinNode $network)
    {
        $this->network = $network;
        $this->version = 1;
        $this->inputs = new TxInputs($this);
        $this->outputs = new TxOutputs($this);
        $this->lockTime = 0;
        $this->isSegWit = false;
    }

    /**
     * @param $prop
     * @return mixed
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
            case "network":
            case "isSegWit":
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
     * @return array
     */
    public function dump(): array
    {
        $txSize = $this->size();
        return [
            "version" => [
                "dec" => $this->version,
                "uInt32LE" => $this->verUInt32LE->hexits(false),
            ],
            "size" => [
                "bytes" => $txSize->size,
                "weight" => $txSize->weight
            ],
            "isSegWit" => $this->isSegWit,
            "inputs" => $this->inputs()->dump(),
            "outputs" => $this->outputs->dump(),
            "lockTime" => [
                "dec" => $this->lockTime,
                "uInt32LE" => $this->lockTimeUInt32LE->hexits(false)
            ]
        ];
    }

    /**
     * @param int $version
     * @return Transaction
     */
    public function setVersion(int $version): self
    {
        if ($version < 0 || !in_array($version, self::VALID_VERSIONS)) {
            throw new \InvalidArgumentException('Invalid transaction version');
        }

        $this->version = $version;
        return $this;
    }

    /**
     * @return $this
     */
    public function isSegWit(): self
    {
        $this->isSegWit = true;
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
     * @throws TransactionEncodeException
     * @throws TransactionInputSignException
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function serialize(): SerializedTransaction
    {
        return $this->_encode(false);
    }

    /**
     * @return SerializedTransaction
     * @throws TransactionEncodeException
     * @throws TransactionInputSignException
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function sign(): SerializedTransaction
    {
        return $this->_encode(true);
    }

    /**
     * @return TransactionSize
     */
    public function size(): TransactionSize
    {
        $totalSize = 10; // 4 byte version, 2x 1 byte input and output counts, 4 byte lockTime
        /** @var TxInput $input */
        foreach ($this->inputs->all() as $input) {
            $totalSize += $input->sizeInBytes();
        }

        /** @var TxOutput $output */
        foreach ($this->outputs->all() as $output) {
            $totalSize += $output->sizeInBytes();
        }

        if ($this->isSegWit) {
            $totalSize += 2; // 2 byte SegWit flag
        }

        $txSize = new TransactionSize();
        $txSize->size = $totalSize;
        $txSize->weight = $this->isSegWit ? $totalSize : $totalSize * 4;
        return $txSize;
    }

    /**
     * @param bool $includeSignatures
     * @param int|null $inputScriptPubKey
     * @return SerializedTransaction
     * @throws TransactionEncodeException
     * @throws TransactionInputSignException
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    private function _encode(bool $includeSignatures, ?int $inputScriptPubKey = null): SerializedTransaction
    {
        $serialized = new Base16();

        // Add 4 byte version
        $serialized->append($this->verUInt32LE);

        // Is segWit?
        if ($this->isSegWit && $includeSignatures) {
            $serialized->append("0001"); // 2 byte SegWit flag
        }

        // Inputs
        if ($this->inputs->count() < 1) {
            throw new \UnexpectedValueException('Transaction has no inputs');
        }

        // Append number of inputs
        $serialized->append(VarInt::Encode($this->inputs->count()));

        $inputIndex = -1;
        /** @var TxInput $input */
        foreach ($this->inputs->all() as $input) {
            $inputIndex++;

            // Append 32 byte prev. Tx hash
            // Reverse byte order
            $prevTxHashRev = implode("", array_reverse(str_split($input->prevTxHash()->hexits(false), 2)));
            $serialized->append($prevTxHashRev);

            // Four byte output index
            $serialized->append($input->indexUInt32LE);

            // Include signatures?
            if ($includeSignatures) {
                $signingMethod = $input->getSigningMethod();
                if ($signingMethod instanceof Script) {
                    // Pre-defined scriptSig
                    $scriptSig = $signingMethod;
                } elseif ($signingMethod instanceof PrivateKey) {
                    // Sign with private key
                    $signature = $signingMethod->sign()->transaction($this->_encode(false, $inputIndex));
                    $scriptSig = $input->createScriptSig($signature, $signingMethod->publicKey());
                } else {
                    throw new TransactionInputSignException(
                        sprintf('No signature available for input # %d (index: %d)', $inputIndex + 1, $inputIndex),
                        $inputIndex
                    );
                }
            } else {
                // Get scriptCode to sign
                $scriptSig = (is_int($inputScriptPubKey) && $inputScriptPubKey === $inputIndex) ?
                    $input->getScriptCode() : null;
            }

            if ($scriptSig instanceof Script) {
                $scriptSigBase16 = $scriptSig->script();
                $scriptSigLen = $scriptSigBase16->binary()->size()->bytes();

                // 1 byte scriptSig len
                $serialized->append(VarInt::Encode($scriptSigLen));

                // Append actual scriptSig
                $serialized->append($scriptSigBase16);
            } else {
                $serialized->append("00");
            }

            // 4 byte sequence number
            $serialized->append($input->seqUInt32LE);

            // Input done! continue to next
            unset($scriptSig, $scriptSigLen, $input);
        }

        // Outputs
        if ($this->outputs->count() < 1) {
            throw new \UnexpectedValueException('Transaction has no outputs');
        }

        // Append number of outputs
        $serialized->append(VarInt::Encode($this->outputs->count()));

        /** @var TxOutput $output */
        foreach ($this->outputs->all() as $output) {
            // Output amount (8-byte)
            $serialized->append($output->valueUInt64LE);

            // scriptPubKey
            $scriptPubKey = $output->scriptPubKey();
            $scriptPubKeyLen = $scriptPubKey->script()->binary()->size()->bytes();

            // 1 byte scriptPubKey len
            $serialized->append(VarInt::Encode($scriptPubKeyLen));

            // Append actual scriptPubKey
            $serialized->append($scriptPubKey->script());

            // Output done!
            unset($scriptPubKey, $scriptPubKeyLen, $output);
        }

        // SegWit Transaction?
        if ($this->isSegWit && $includeSignatures) {
            $segWitInputNo = 0;
            /** @var TxInput $input */
            foreach ($this->inputs->all() as $input) {
                $segWitInputNo++;
                $inWitness = $input->getSegWitData();
                if (!$inWitness) {
                    throw new TransactionEncodeException(sprintf('Missing SegWit data for input # %d', $segWitInputNo));
                }

                $serialized->append(VarInt::Encode(count($inWitness)));
                /** @var Base16 $inWitnessElem */
                foreach ($inWitness as $inWitnessElem) {
                    $serialized->append(VarInt::Encode($inWitnessElem->binary()->size()->bytes()));
                    $serialized->append($inWitnessElem);
                }

                unset($inWitness, $input);
            }
        }

        // Finally, Tx lock time! 4 byte
        $serialized->append($this->lockTimeUInt32LE);

        // Add "hash code" type?
        if (!$includeSignatures) {
            $serialized->append("01000000");
        }

        // Set buffer state as readOnly
        $serialized->readOnly(true);

        // Calculate TxHash
        $hash = $serialized->binary()->hash()->sha256()
            ->hash()->sha256(); // SHA256 twice

        return new SerializedTransaction($serialized, $hash->base16());
    }
}