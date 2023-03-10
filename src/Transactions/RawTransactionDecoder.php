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

use Comely\Buffer\Buffer;
use Comely\Buffer\ByteReader;
use Comely\Buffer\Bytes32;
use Comely\Buffer\Exception\ByteReaderUnderflowException;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Exception\ScriptException;
use FurqanSiddiqui\Bitcoin\Exception\TransactionDecodeException;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Transactions\UTXO\TxInput;

/**
 * Class RawTransactionDecoder
 * @package FurqanSiddiqui\Bitcoin\Transactions
 */
class RawTransactionDecoder
{
    /** @var string[] */
    private const INPUT_SCRIPT_PUB_KEYS = [
        "/^76a914[a-f0-9]{40}88ac$/i",
        "/^a914[a-f0-9]{40}87$/i",
    ];

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param \Comely\Buffer\Buffer $rawTx
     * @return \FurqanSiddiqui\Bitcoin\Transactions\Transaction
     * @throws \FurqanSiddiqui\Bitcoin\Exception\TransactionDecodeException
     */
    public static function Decode(Bitcoin $btc, Buffer $rawTx): Transaction
    {
        $tx = new Transaction($btc);
        $rawTxStream = $rawTx->read();

        try {
            $decodeProgress = "version bytes";
            // Start with version, first 4 bytes
            $tx->version = $rawTxStream->readUInt32LE();
            if ($tx->version < 0) {
                throw TransactionDecodeException::InvalidVersion();
            }

            // SegWit flag?
            $decodeProgress = "SegWit flag";
            if ($rawTxStream->lookAhead(2) === "\0\1") {
                $rawTxStream->next(2); // Increase pointer
                $tx->isSegWit = true;
            }

            // Inputs
            $decodeProgress = "inputs";
            $inputsCount = static::readNextVarInt($rawTxStream);
            if ($inputsCount) {
                for ($i = 1; $i <= $inputsCount; $i++) {
                    unset($prevTxHash, $prevTxIndex, $scriptLen, $txInput, $inputScript, $inputScriptPubKey, $inputScriptSig, $inputScriptHex, $seqNo);

                    $decodeProgress = sprintf("input # %d", $i);
                    $prevTxHash = new Bytes32(implode("", array_reverse(str_split($rawTxStream->next(32), 1))));
                    $prevTxIndex = $rawTxStream->readUInt32LE();
                    $inputScript = null;
                    $inputScriptSig = null;
                    $inputScriptPubKey = null;
                    $scriptLen = static::readNextVarInt($rawTxStream);
                    if ($scriptLen) {
                        try {
                            $inputScript = Script::Decode($btc, bin2hex($rawTxStream->next($scriptLen)));
                        } catch (ScriptException $e) {
                            throw TransactionDecodeException::InputScriptParseException($i, $e);
                        }

                        // Determine script Type
                        $inputScriptHex = $inputScript->buffer->toBase16();
                        foreach (static::INPUT_SCRIPT_PUB_KEYS as $inputScriptExp) {
                            if (preg_match($inputScriptExp, $inputScriptHex)) {
                                $inputScriptPubKey = $inputScript;
                                break;
                            }
                        }

                        if (!$inputScriptPubKey) {
                            $inputScriptSig = $inputScript;
                        }
                    }

                    $seqNo = $rawTxStream->readUInt32LE();
                    $txInput = $tx->appendInput($prevTxHash, $prevTxIndex, $inputScriptPubKey, $seqNo);
                    if ($inputScriptSig) {
                        $txInput->setScriptSig($inputScript);
                    }
                }
            }

            // Outputs
            $decodeProgress = "outputs";
            $outputsCount = static::readNextVarInt($rawTxStream);
            for ($i = 0; $i < $outputsCount; $i++) {
                $decodeProgress = sprintf("output # %d", $i);
                try {
                    $value = $rawTxStream->readUInt64LE();
                    $scriptLen = static::readNextVarInt($rawTxStream);
                    $tx->appendOutput(Script::Decode($btc, new Buffer($rawTxStream->next($scriptLen))), $value);
                } catch (ScriptException $e) {
                    throw TransactionDecodeException::OutputScriptParseException($i, $e);
                }
            }

            // Segregated Witness Data?
            if ($tx->isSegWit) {
                $inputNo = 0;
                /** @var TxInput $input */
                foreach ($tx->getInputs() as $input) {
                    $inputNo++;
                    $decodeProgress = sprintf("witness # %d", $inputNo);
                    $witElemCount = static::readNextVarInt($rawTxStream);
                    if (!$witElemCount) {
                        throw TransactionDecodeException::WitnessParseException(
                            sprintf('Expecting SegWit witness data for input # %d', $inputNo)
                        );
                    }

                    for ($i = 1; $i <= $witElemCount; $i++) {
                        $witElemLength = static::readNextVarInt($rawTxStream);
                        if ($witElemLength) {
                            $input->setWitnessData(new Buffer($rawTxStream->next($witElemLength)));
                        }
                    }

                    unset($input);
                }
            }

            // Lock time
            $decodeProgress = "lock time";
            $tx->lockTime = $rawTxStream->readUInt32LE();
        } catch (ByteReaderUnderflowException) {
            throw new TransactionDecodeException(
                sprintf('Incomplete transaction data; Ran out of bytes near "%s"', $decodeProgress)
            );
        }

        // Excess bytes?
        if (!$rawTxStream->isEnd()) {
            throw new TransactionDecodeException('Overflow/excess bytes');
        }

        return $tx;
    }

    /**
     * @param \Comely\Buffer\ByteReader $stream
     * @return int
     * @throws \Comely\Buffer\Exception\ByteReaderUnderflowException
     */
    private static function readNextVarInt(ByteReader $stream): int
    {
        $varInt = $stream->readUInt8();
        return match ($varInt) {
            253 => $stream->readUInt16LE(),
            254 => $stream->readUInt32LE(),
            255 => $stream->readUInt64LE(),
            default => $varInt
        };
    }
}
