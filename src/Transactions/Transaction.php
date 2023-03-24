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

namespace FurqanSiddiqui\Bitcoin\Transactions;

use Comely\Buffer\BigInteger\LittleEndian;
use Comely\Buffer\Buffer;
use Comely\Buffer\Bytes32;
use FurqanSiddiqui\Bitcoin\Address\AbstractPaymentAddress;
use FurqanSiddiqui\Bitcoin\Address\Bech32_P2WPKH_Address;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Exception\TransactionInputSignException;
use FurqanSiddiqui\Bitcoin\Protocol\VarInt;
use FurqanSiddiqui\Bitcoin\Script\MultiSigScript;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Transactions\UTXO\TxInput;
use FurqanSiddiqui\Bitcoin\Transactions\UTXO\TxOutput;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\BaseKeyPair;

/**
 * Class Transaction
 * @package FurqanSiddiqui\Bitcoin\Transactions
 */
class Transaction
{
    private array $inputs = [];
    private array $outputs = [];

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $network
     * @param \Comely\Buffer\Buffer $rawTx
     * @return static
     * @throws \FurqanSiddiqui\Bitcoin\Exception\TransactionDecodeException
     */
    public static function Decode(Bitcoin $network, Buffer $rawTx): static
    {
        return RawTransactionDecoder::Decode($network, $rawTx);
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param int $version
     * @param int $lockTime
     * @param bool $isSegWit
     */
    public function __construct(
        public readonly Bitcoin $btc,
        public int              $version = 1,
        public int              $lockTime = 0,
        public bool             $isSegWit = false
    )
    {
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
        $dump = [
            "version" => [
                "dec" => $this->version,
                "uInt32LE" => bin2hex(LittleEndian::PackUInt32($this->version)),
            ],
            "size" => [
                "bytes" => $txSize->size,
                "weight" => $txSize->weight
            ],
            "isSegWit" => $this->isSegWit,
            "inputs" => [],
            "outputs" => [],
            "lockTime" => [
                "dec" => $this->lockTime,
                "uInt32LE" => bin2hex(LittleEndian::PackUInt32($this->lockTime))
            ]
        ];

        /** @var TxInput $input */
        foreach ($this->inputs as $input) {
            $dump["inputs"][] = $input->dump();
        }

        /** @var TxOutput $input */
        foreach ($this->outputs as $output) {
            $dump["outputs"][] = $output->dump();
        }

        return $dump;
    }

    /**
     * @return array
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @param \Comely\Buffer\Bytes32 $prevTxHash
     * @param int $index
     * @param \FurqanSiddiqui\Bitcoin\Script\Script|null $scriptPubKey
     * @param int $seq
     * @param int $value
     * @param \FurqanSiddiqui\Bitcoin\Address\AbstractPaymentAddress|null $address
     * @return \FurqanSiddiqui\Bitcoin\Transactions\UTXO\TxInput
     */
    public function appendInput(
        Bytes32                 $prevTxHash,
        int                     $index,
        ?Script                 $scriptPubKey = null,
        int                     $seq = 0xffffffff,
        int                     $value = 0,
        ?AbstractPaymentAddress $address = null
    ): TxInput
    {
        $txI = new TxInput($this, $prevTxHash, $index, $scriptPubKey, $seq, $value, $address);
        $this->inputs[] = $txI;
        return $txI;
    }

    /**
     * @return array
     */
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Script\Script $scriptPubKey
     * @param int $value
     * @return \FurqanSiddiqui\Bitcoin\Transactions\UTXO\TxOutput
     */
    public function appendOutput(Script $scriptPubKey, int $value): TxOutput
    {
        $txO = new TxOutput($this, $scriptPubKey, $value);
        $this->outputs[] = $txO;
        return $txO;
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Transactions\TxSize
     */
    public function size(): TxSize
    {
        $totalSize = 10; // 4 byte version, 2x 1 byte input and output counts, 4 byte lockTime
        /** @var TxInput $input */
        foreach ($this->inputs as $input) {
            $totalSize += $input->sizeInBytes();
        }

        /** @var TxOutput $output */
        foreach ($this->outputs as $output) {
            $totalSize += $output->sizeInBytes();
        }

        if ($this->isSegWit) {
            $totalSize += 2; // 2 byte SegWit flag
        }

        return new TxSize($totalSize, $this->isSegWit ? $totalSize : $totalSize * 4);
    }

    /**
     * @param int $inputIndex
     * @return \FurqanSiddiqui\Bitcoin\Transactions\SerializedTransaction
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\TransactionInputSignException
     */
    public function hashPreImage(int $inputIndex): SerializedTransaction
    {
        $preImage = new Buffer();
        $hashPrevOuts = new Buffer();
        $hashSequences = new Buffer();
        $hashOutputs = new Buffer();
        $signingInput = null;

        // Add 4 byte version
        $preImage->appendUInt32LE($this->version);
        // Append number of inputs
        $preImage->append(VarInt::Int2Bin(count($this->inputs)));
        // Append Inputs
        $inputNum = -1;
        /** @var TxInput $input */
        foreach ($this->inputs as $input) {
            $inputNum++;
            if (!$signingInput && $inputNum === $inputIndex) {
                $signingInput = $input;
            }

            // Reverse byte order
            $prevTxHashRev = implode("", array_reverse(str_split($input->prevTxHash->raw(), 1)));
            $preImage->append($prevTxHashRev);
            // 4-byte output index
            $preImage->appendUInt32LE($input->index);
            // ScriptCode or 0x00
            $inputScriptCode = $inputNum === $inputIndex ? $input->getScriptCode() : null;
            if ($inputScriptCode) {
                $preImage->append(VarInt::Int2Bin($inputScriptCode->buffer->len()));
                $preImage->append($inputScriptCode->buffer);
            } else {
                $preImage->append("\0"); // NULL scriptSig or 0x00
            }

            // 4-byte Sequence Number
            $preImage->appendUInt32LE($input->seqNo);

            // Append hashPrevOuts and hashSequences
            $hashPrevOuts->append($prevTxHashRev)
                ->appendUInt32LE($input->index);
            $hashSequences->appendUInt32LE($input->seqNo);

            // Input done!
            unset($input, $inputScriptCode);
        }

        // Append Outputs
        $this->serializeBufferAppendOutputs($preImage, $hashOutputs);

        // Check if Input in SegWit P2SH-P2WPKH/P2SH-P2WSH
        if ($signingInput) {
            if (in_array($signingInput->redeemScriptType, ["p2sh-p2wpkh", "p2sh-p2wsh"])) {
                // Swap buffer with SegWit format hashPreImage buffer
                $preImage = $this->hashPreImageSegWit($signingInput, $hashPrevOuts, $hashSequences, $hashOutputs);
            } elseif ($signingInput->address instanceof Bech32_P2WPKH_Address) {
                $preImage = $this->hashPreImageSegWit($signingInput, $hashPrevOuts, $hashSequences, $hashOutputs);
            }
        }

        // Finally, Tx lock time! 4 byte
        $preImage->appendUInt32LE($this->lockTime);
        // 4-byte Hash code type
        $preImage->append("\1\0\0\0");

        // Set buffer state as readOnly
        $preImage->readOnly();

        return new SerializedTransaction($this->btc, $preImage, false);
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Transactions\UTXO\TxInput $input
     * @param \Comely\Buffer\Buffer $prevOuts
     * @param \Comely\Buffer\Buffer $prevSeq
     * @param \Comely\Buffer\Buffer $outputs
     * @return \Comely\Buffer\Buffer
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\TransactionInputSignException
     */
    private function hashPreImageSegWit(TxInput $input, Buffer $prevOuts, Buffer $prevSeq, Buffer $outputs): Buffer
    {
        $segWitInput = new Buffer();
        $inputHashShort = sprintf("%s-%d", substr($input->prevTxHash->toBase16(), 0, 8), $input->index);

        // Convert all argument buffers into hashes
        $prevOutsHash = $this->btc->network->d_hash256($prevOuts);
        $prevSeqHash = $this->btc->network->d_hash256($prevSeq);
        $outputsHash = $this->btc->network->d_hash256($outputs);

        // Add 4 byte version
        $segWitInput->appendUInt32LE($this->version);
        // Append hashPrevOuts
        $segWitInput->append($prevOutsHash);
        // Append hashSequence
        $segWitInput->append($prevSeqHash);
        // Outpoint
        $segWitInput->append(implode("", array_reverse(str_split($input->prevTxHash->raw(), 1))));
        $segWitInput->appendUInt32LE($input->index);

        // ScriptCode
        $scriptCode = null;
        if ($input->redeemScriptType === "p2sh-p2wpkh") {
            // P2SH-P2WPKH
            $redeemScriptHash = substr($input->getRedeemScript()->buffer->raw(), 2, 20);
            $scriptCode = $this->btc->scripts->new()
                ->OP_DUP()
                ->OP_HASH160()
                ->PUSHDATA(new Buffer($redeemScriptHash))
                ->OP_EQUALVERIFY()
                ->OP_CHECKSIG()
                ->getScript();
        } elseif ($input->redeemScriptType === "p2sh-p2wsh") {
            $signingMethod = $input->getSigningMethod();
            if ($signingMethod instanceof MultiSigScript) {
                $scriptCode = $signingMethod->redeemScript;
            } else {
                $scriptCode = $input->getRedeemScript();
            }
        } elseif ($input->address instanceof Bech32_P2WPKH_Address) {
            if (!$input->address->publicKey) {
                throw new TransactionInputSignException(
                    'Cannot from scriptCode for "Bech32_P2WPKH_Address" without relative PublicKey set for address'
                );
            }

            $scriptCode = $this->btc->scripts->new()
                ->OP_DUP()
                ->OP_HASH160()
                ->PUSHDATA($input->address->publicKey->hash160)
                ->OP_EQUALVERIFY()
                ->OP_CHECKSIG()
                ->getScript();
        }

        if (!$scriptCode) {
            throw new TransactionInputSignException(
                sprintf('Cannot create SegWith hashPreImage for input "%s"', $inputHashShort)
            );
        }

        $scriptCode = $scriptCode->buffer ?? new Buffer();
        $segWitInput->append(VarInt::Int2Bin($scriptCode->len()));
        $segWitInput->append($scriptCode);

        // Value
        $segWitInput->appendUInt64LE($input->value);
        // Sequence
        $segWitInput->appendUInt32LE($input->seqNo);
        // Append HashOutputs
        $segWitInput->append($outputsHash);

        return $segWitInput;
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Transactions\SerializedTransaction
     * @throws \FurqanSiddiqui\BIP32\Exception\KeyPairException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\TransactionInputSignException
     * @throws \FurqanSiddiqui\ECDSA\Exception\SignatureException
     */
    public function sign(): SerializedTransaction
    {
        $serialized = new Buffer();

        // Add 4 byte version
        $serialized->appendUInt32LE($this->version);

        // Is segWit?
        if ($this->isSegWit) {
            $serialized->append("\0\1"); // 2 byte SegWit flag
        }

        // Append number of inputs
        $serialized->append(VarInt::Int2Bin(count($this->inputs)));

        // Append Inputs
        $inputNum = -1;
        /** @var TxInput $input */
        foreach ($this->inputs as $input) {
            $inputNum++;
            // Reverse byte order
            $prevTxHashRev = implode("", array_reverse(str_split($input->prevTxHash->raw(), 1)));
            $serialized->append($prevTxHashRev);
            // 4-byte output index
            $serialized->appendUInt32LE($input->index);
            // ScriptSig or 00
            $scriptSig = null;
            $inputScriptSigMethod = $input->getSigningMethod();
            if ($inputScriptSigMethod instanceof Script) {
                // Pre-defined scriptSig
                $scriptSig = $inputScriptSigMethod;
            } elseif ($inputScriptSigMethod instanceof BaseKeyPair) {
                // Sign with private key
                $signature = $inputScriptSigMethod->privateKey()->signTransaction($this->hashPreImage($inputNum));
                $scriptSig = $input->createScriptSig($signature->getDER(), $inputScriptSigMethod->publicKey());
            } elseif ($inputScriptSigMethod instanceof MultiSigScript) {
                // Take signatures from MultiSigScript object
                $signatures = $inputScriptSigMethod->signTransaction($this, $inputNum);
                if (!$signatures) {
                    throw new TransactionInputSignException('There are no private keys in MultiSigScript object');
                }

                if ($input->redeemScriptType === "p2sh-p2wsh") { // SegWit
                    $scriptSig = $this->btc->scripts->new()
                        ->PUSHDATA($input->getRedeemScript()->buffer)
                        ->getScript();

                    // Witness Data
                    $input->setWitnessData(new Buffer());

                    /** @var Buffer $signature */
                    foreach ($signatures as $signature) {
                        $input->setWitnessData($signature);
                    }

                    $input->setWitnessData($inputScriptSigMethod->redeemScript->buffer);
                } else { // Legacy
                    $scriptCode = $this->btc->scripts->new()
                        ->OP_0();
                    /** @var Buffer $signature */
                    foreach ($signatures as $signature) {
                        $scriptCode->PUSHDATA($signature);
                    }

                    $scriptCode->PUSHDATA($inputScriptSigMethod->redeemScript->buffer);
                    $scriptSig = $scriptCode->getScript();
                }

                $input->setScriptSig($scriptSig);
            }

            if (!$scriptSig && !$input->hasWitnessData()) {
                throw new TransactionInputSignException(
                    sprintf('No signature available for input # %d (index: %d)', $inputNum + 1, $inputNum),
                    $inputNum
                );
            }

            $scriptSigBuffer = $scriptSig ? $scriptSig->buffer : new Buffer();
            $serialized->append(VarInt::Int2Bin($scriptSigBuffer->len()));
            $serialized->append($scriptSigBuffer);

            // 4-byte Sequence Number
            $serialized->appendUInt32LE($input->seqNo);
            // Input done!
            unset($input, $inputScriptSigMethod, $scriptSig);
        }

        // Outputs
        if (count($this->outputs) < 1) {
            throw new \UnexpectedValueException('Transaction has no outputs');
        }

        // Append number of outputs
        $this->serializeBufferAppendOutputs($serialized);

        // SegWit?
        if ($this->isSegWit) {
            /** @var TxInput $input */
            foreach ($this->inputs as $input) {
                $inputWitnessFields = $input->getWitnessData();
                $serialized->append(VarInt::Int2Bin(count($inputWitnessFields)));
                /** @var Buffer $inWitnessElem */
                foreach ($inputWitnessFields as $inWitnessElem) {
                    $serialized->append(VarInt::Int2Bin($inWitnessElem->len()));
                    if ($inWitnessElem->len()) {
                        $serialized->append($inWitnessElem);
                    }
                }

                unset($inWitness, $input, $inWitnessElem);
            }
        }

        // Finally, Tx lock time! 4 byte
        $serialized->appendUInt32LE($this->lockTime);

        // Set buffer state as readOnly
        $serialized->readOnly();

        return new SerializedTransaction($this->btc, $serialized, true);
    }

    /**
     * @param \Comely\Buffer\Buffer $buffer
     * @param \Comely\Buffer\Buffer|null $hashOutputsBuffer
     * @return void
     */
    private function serializeBufferAppendOutputs(Buffer $buffer, ?Buffer $hashOutputsBuffer = null): void
    {
        // Append number of outputs
        $buffer->append(VarInt::Int2Bin(count($this->outputs)));

        /** @var TxOutput $output */
        foreach ($this->outputs as $output) {
            // 8-byte output amount
            $buffer->appendUInt64LE($output->value);
            // Output ScriptPubKey
            $outputPubKeyLen = VarInt::Int2Bin($output->scriptPubKey->buffer->len());
            $buffer->append($outputPubKeyLen);
            $buffer->append($output->scriptPubKey->buffer);
            // hashOutputs buffer?
            $hashOutputsBuffer?->appendUInt64LE($output->value)
                ->append($outputPubKeyLen)
                ->append($output->scriptPubKey->buffer);

            unset($output, $outputPubKey);
        }
    }
}
