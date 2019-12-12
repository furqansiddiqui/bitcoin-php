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
     * @param int $inputIndex
     * @return SerializedTransaction
     * @throws TransactionInputSignException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function hashPreImage(int $inputIndex): SerializedTransaction
    {
        $preImage = new Base16();
        $hashPrevOuts = new Base16();
        $hashSequences = new Base16();
        $hashOutputs = new Base16();
        $signingInput = null;

        // Add 4 byte version
        $preImage->append($this->verUInt32LE);
        // Append number of inputs
        $preImage->append(VarInt::Encode($this->inputs->count()));
        // Append Inputs
        $inputNum = -1;
        /** @var TxInput $input */
        foreach ($this->inputs->all() as $input) {
            $inputNum++;
            if (!$signingInput && $inputNum === $inputIndex) {
                $signingInput = $input;
            }

            // Reverse byte order
            $prevTxHashRev = implode("", array_reverse(str_split($input->prevTxHash()->hexits(false), 2)));
            $preImage->append($prevTxHashRev);
            // 4-byte output index
            $preImage->append($input->indexUInt32LE);
            // ScriptCode or 0x00
            $inputScriptCode = $inputNum === $inputIndex ? $input->getScriptCode() : null;
            if ($inputScriptCode) {
                $inputScriptCode = $inputScriptCode->script();
                $preImage->append(VarInt::Encode($inputScriptCode->binary()->sizeInBytes));
                $preImage->append($inputScriptCode);
            } else {
                $preImage->append("00"); // NULL scriptSig or 0x00
            }
            // 4-byte Sequence Number
            $preImage->append($input->seqUInt32LE);

            // Append hashPrevOuts and hashSequences
            $hashPrevOuts->append($prevTxHashRev);
            $hashPrevOuts->append($input->indexUInt32LE);
            $hashSequences->append($input->seqUInt32LE);

            // Input done!
            unset($input, $inputScriptCode);
        }

        // Append Outputs
        $this->serializeBufferAppendOutputs($preImage, $hashOutputs);

        // Check if Input in SegWit P2SH-P2WPKH
        if ($signingInput) {
            if ($signingInput->redeemScriptType === "p2sh-p2wpkh") {
                // Swap buffer with SegWit format hashPreImage buffer
                $preImage = $this->hashPreImageSegWit($signingInput, $hashPrevOuts, $hashSequences, $hashOutputs);
            }
        }

        // Finally, Tx lock time! 4 byte
        $preImage->append($this->lockTimeUInt32LE);
        // 4-byte Hash code type
        $preImage->append("01000000");
        // Set buffer state as readOnly
        $preImage->readOnly(true);
        // Calculate TxHash
        $hash = $preImage->binary()->hash()->sha256()
            ->hash()->sha256(); // SHA256 twice

        return new SerializedTransaction($preImage, $hash->base16(), false);
    }

    /**
     * @param TxInput $input
     * @param Base16 $prevOuts
     * @param Base16 $prevSeq
     * @param Base16 $outputs
     * @return Base16
     * @throws TransactionInputSignException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function hashPreImageSegWit(TxInput $input, Base16 $prevOuts, Base16 $prevSeq, Base16 $outputs): Base16
    {
        $segWitInput = new Base16();
        $inputHashShort = sprintf("%s-%d", substr($input->prevTxHash()->hexits(), 0, 8), $input->index());

        // Convert all argument buffers into hashes
        $prevOutsHash = $prevOuts->binary()->hash()->sha256()
            ->hash()->sha256()
            ->base16();
        $prevSeqHash = $prevSeq->binary()->hash()->sha256()
            ->hash()->sha256()
            ->base16();
        $outputsHash = $outputs->binary()->hash()->sha256()
            ->hash()->sha256()
            ->base16();

        // Add 4 byte version
        $segWitInput->append($this->verUInt32LE);
        // Append hashPrevOuts
        $segWitInput->append($prevOutsHash);
        // Append hashSequence
        $segWitInput->append($prevSeqHash);
        // Outpoint
        $segWitInput->append(implode("", array_reverse(str_split($input->prevTxHash()->hexits(false), 2))));
        $segWitInput->append($input->indexUInt32LE);
        // ScriptCode
        $scriptCode = null;
        if ($input->redeemScriptType === "p2sh-p2wpkh") {
            // P2SH-P2WPKH
            $redeemScriptHash = substr($input->getRedeemScript()->script()->hexits(false), 4, 40);
            $scriptCode = $this->network->script()->new()
                ->OP_DUP()
                ->OP_HASH160()
                ->PUSHDATA((new Base16($redeemScriptHash))->binary())
                ->OP_EQUALVERIFY()
                ->OP_CHECKSIG()
                ->script();
        }

        if (!$scriptCode) {
            throw new TransactionInputSignException(
                sprintf('Cannot create SegWith hashPreImage for input "%s"', $inputHashShort)
            );
        }

        $scriptCode = $scriptCode->script();
        $segWitInput->append(VarInt::Encode($scriptCode->binary()->sizeInBytes));
        $segWitInput->append($scriptCode);

        // Value/Amount
        if (!is_int($input->value)) {
            throw new TransactionInputSignException(
                sprintf('SegWith hashPreImage for requires amount/value for input "%s"', $inputHashShort)
            );
        }

        $segWitInput->append($input->valueUInt64LE);
        // Sequence
        $segWitInput->append($input->seqUInt32LE);
        // Append HashOutputs
        $segWitInput->append($outputsHash);

        return $segWitInput;
    }

    /**
     * @return SerializedTransaction
     * @throws TransactionInputSignException
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function sign(): SerializedTransaction
    {
        $serialized = new Base16();

        // Add 4 byte version
        $serialized->append($this->verUInt32LE);

        // Is segWit?
        if ($this->isSegWit) {
            $serialized->append("0001"); // 2 byte SegWit flag
        }

        // Append number of inputs
        $serialized->append(VarInt::Encode($this->inputs->count()));

        // Append Inputs
        $inputNum = -1;
        /** @var TxInput $input */
        foreach ($this->inputs->all() as $input) {
            $inputNum++;
            // Reverse byte order
            $prevTxHashRev = implode("", array_reverse(str_split($input->prevTxHash()->hexits(false), 2)));
            $serialized->append($prevTxHashRev);
            // 4-byte output index
            $serialized->append($input->indexUInt32LE);
            // ScriptSig or 00
            $scriptSig = null;
            $inputScriptSigMethod = $input->getSigningMethod();
            if ($inputScriptSigMethod instanceof Script) {
                // Pre-defined scriptSig
                $scriptSig = $inputScriptSigMethod;
            } elseif ($inputScriptSigMethod instanceof PrivateKey) {
                // Sign with private key
                $signature = $inputScriptSigMethod->sign()->transaction($this->hashPreImage($inputNum));
                $scriptSig = $input->createScriptSig($signature, $inputScriptSigMethod->publicKey());
            }

            if (!$scriptSig) {
                throw new TransactionInputSignException(
                    sprintf('No signature available for input # %d (index: %d)', $inputNum + 1, $inputNum),
                    $inputNum
                );
            }

            $scriptSig = $scriptSig->script();
            $serialized->append(VarInt::Encode($scriptSig->binary()->sizeInBytes));
            $serialized->append($scriptSig);

            // 4-byte Sequence Number
            $serialized->append($input->seqUInt32LE);
            // Input done!
            unset($input, $inputScriptSigMethod, $scriptSig);
        }

        // Outputs
        if ($this->outputs->count() < 1) {
            throw new \UnexpectedValueException('Transaction has no outputs');
        }

        // Append number of outputs
        $this->serializeBufferAppendOutputs($serialized);

        // SegWit?
        if ($this->isSegWit) {
            $segWitInputNo = 0;
            /** @var TxInput $input */
            foreach ($this->inputs->all() as $input) {
                $segWitInputNo++;
                $inputWitnessFields = $input->getSegWitData();
                if (!$inputWitnessFields) {
                    $inputWitnessFields = [];
                }

                $serialized->append(VarInt::Encode(count($inputWitnessFields)));
                /** @var Base16 $inWitnessElem */
                foreach ($inputWitnessFields as $inWitnessElem) {
                    $serialized->append(VarInt::Encode($inWitnessElem->binary()->size()->bytes()));
                    $serialized->append($inWitnessElem);
                }

                unset($inWitness, $input, $inWitnessElem);
            }
        }

        // Finally, Tx lock time! 4 byte
        $serialized->append($this->lockTimeUInt32LE);

        // Set buffer state as readOnly
        $serialized->readOnly(true);

        // Calculate TxHash
        $hash = $serialized->binary()->hash()->sha256()
            ->hash()->sha256(); // SHA256 twice

        return new SerializedTransaction($serialized, $hash->base16(), true);
    }

    /**
     * @param Base16 $buffer
     * @param Base16|null $hashOutputsBuffer
     */
    private function serializeBufferAppendOutputs(Base16 $buffer, ?Base16 $hashOutputsBuffer = null): void
    {
        // Append number of outputs
        $buffer->append(VarInt::Encode($this->outputs->count()));
        // Append Outputs
        $outputNum = -1;
        /** @var TxOutput $output */
        foreach ($this->outputs->all() as $output) {
            $outputNum++;
            // 8-byte output amount
            $buffer->append($output->valueUInt64LE);
            // Output ScriptPubKey
            $outputPubKey = $output->scriptPubKey()->script();
            $outputPubKeyLen = VarInt::Encode($outputPubKey->binary()->sizeInBytes);
            $buffer->append($outputPubKeyLen);
            $buffer->append($outputPubKey);
            // hashOutputs buffer?
            if ($hashOutputsBuffer) {
                $hashOutputsBuffer->append($output->valueUInt64LE);
                $hashOutputsBuffer->append($outputPubKeyLen);
                $hashOutputsBuffer->append($outputPubKey);
            }
            unset($output, $outputPubKey);
        }
    }
}