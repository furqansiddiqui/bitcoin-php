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
use Comely\DataTypes\Buffer\Binary\ByteReader;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Exception\ScriptException;
use FurqanSiddiqui\Bitcoin\Exception\TransactionDecodeException;
use FurqanSiddiqui\Bitcoin\Protocol\VarInt;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxInput;

/**
 * Class RawTransactionDecoder
 * @package FurqanSiddiqui\Bitcoin\Transactions
 */
class RawTransactionDecoder
{
    private const INPUT_SCRIPT_PUB_KEYS = [
        "/^76a914[a-f0-9]{40}88ac$/i",
        "/^a914[a-f0-9]{40}87$/i",
    ];

    /**
     * @param AbstractBitcoinNode $network
     * @param Base16 $rawTx
     * @return Transaction
     * @throws TransactionDecodeException
     */
    public static function Decode(AbstractBitcoinNode $network, Base16 $rawTx): Transaction
    {
        // Prepare
        $tx = new Transaction($network);
        $rawTx = $rawTx->binary();
        $rawTxStream = $rawTx->read();
        $rawTxStream->throwUnderflowEx(); // Throws \UnderflowException if runs out of bytes
        $txIsSegWit = false;

        try {
            $decodeProgress = "version bytes";
            // Start with version, first 4 bytes
            $versionInt = VarInt::Decode(bin2hex($rawTxStream->first(4)), 4);
            if (!isset($versionInt) || !is_int($versionInt)) {
                throw TransactionDecodeException::InvalidVersion();
            }

            $tx->setVersion($versionInt);

            // SegWit flag?
            $decodeProgress = "SegWit flag";
            $segWitFlag = $rawTxStream->next(2);
            if (bin2hex($segWitFlag) === "0001") {
                $txIsSegWit = true;
                $tx->isSegWit(); // Mark SegWit flag
            } else {
                $rawTxStream->setPointer(4); // Reverse pointer
            }

            // Inputs
            $decodeProgress = "inputs";
            $inputsCount = self::readNextVarInt($rawTxStream);
            if ($inputsCount) {
                for ($i = 1; $i <= $inputsCount; $i++) {
                    $decodeProgress = sprintf("input # %d", $i);
                    $prevTxHash = implode("", array_reverse(str_split(bin2hex($rawTxStream->next(32)), 2)));
                    $prevTxIndex = VarInt::Decode(bin2hex($rawTxStream->next(4)), 4);
                    $inputScript = null;
                    $inputScriptSig = null;
                    $inputScriptPubKey = null;
                    $scriptLen = self::readNextVarInt($rawTxStream);
                    if ($scriptLen) {
                        try {
                            $inputScript = Script::Decode($network, bin2hex($rawTxStream->next($scriptLen)));
                        } catch (ScriptException $e) {
                            throw TransactionDecodeException::InputScriptParseException($i, $e);
                        }

                        // Determine script Type
                        $inputScriptHex = $inputScript->script()->hexits(false);
                        foreach (self::INPUT_SCRIPT_PUB_KEYS as $inputScriptExp) {
                            if (preg_match($inputScriptExp, $inputScriptHex)) {
                                $inputScriptPubKey = $inputScript;
                                break;
                            }
                        }

                        if (!$inputScriptPubKey) {
                            $inputScriptSig = $inputScript;
                        }

                    }

                    $seqNo = VarInt::Decode(bin2hex($rawTxStream->next(4)), 4);
                    $txInput = $tx->inputs()->add($prevTxHash, $prevTxIndex, $inputScriptPubKey, $seqNo);
                    if ($inputScriptSig) {
                        $txInput->setScriptSig($inputScript);
                    }

                    unset($prevTxHash, $prevTxIndex, $scriptLen, $txInput, $inputScript, $inputScriptPubKey, $inputScriptSig, $inputScriptHex, $seqNo);
                }
            }

            // Outputs
            $decodeProgress = "outputs";
            $outputsCount = self::readNextVarInt($rawTxStream);
            for ($i = 0; $i < $outputsCount; $i++) {
                $decodeProgress = sprintf("output # %d", $i);
                try {
                    $value = VarInt::Decode(bin2hex($rawTxStream->next(8)), 8);
                    $scriptLen = self::readNextVarInt($rawTxStream);
                    $script = bin2hex($rawTxStream->next($scriptLen));
                    $tx->outputs()->add(Script::Decode($network, $script), $value);
                } catch (ScriptException $e) {
                    throw TransactionDecodeException::OutputScriptParseException($i, $e);
                }
            }

            // Segregated Witness Data?
            if ($txIsSegWit) {
                $inputNo = 0;
                /** @var TxInput $input */
                foreach ($tx->inputs()->all() as $input) {
                    $inputNo++;
                    $decodeProgress = sprintf("witness # %d", $inputNo);
                    $witElemCount = self::readNextVarInt($rawTxStream);
                    if (!$witElemCount) {
                        throw TransactionDecodeException::WitnessParseException(
                            sprintf('Expecting SegWit witness data for input # %d', $inputNo)
                        );
                    }

                    for ($i = 1; $i <= $witElemCount; $i++) {
                        $witElemLength = self::readNextVarInt($rawTxStream);
                        if ($witElemLength) {
                            $input->setWitnessData(new Base16(bin2hex($rawTxStream->next($witElemLength))));
                        }
                    }

                    unset($input);
                }
            }

            // Lock time
            $decodeProgress = "lock time";
            $tx->setLockTime(VarInt::Decode(bin2hex($rawTxStream->next(4)), 4));
        } catch (\UnderflowException $e) {
            if ($e->getCode() === ByteReader::UNDERFLOW_EX_SIGNAL) {
                throw new TransactionDecodeException(
                    sprintf('Incomplete transaction data; Ran out of bytes near "%s"', $decodeProgress)
                );
            }
        }

        // Excess bytes?
        if (!$rawTxStream->isEnd()) {
            throw new TransactionDecodeException('Overflow/excess bytes');
        }

        return $tx;
    }

    /**
     * @param ByteReader $stream
     * @return int
     */
    private static function readNextVarInt(ByteReader $stream): int
    {
        $varInt = bin2hex($stream->next(1)); // Next 1 byte
        if (in_array(strtolower($varInt), ["fd", "fe", "ff"])) {
            $varIntSize = VarInt::Size($varInt);
            return VarInt::Decode(bin2hex($stream->next($varIntSize)), $varIntSize);
        } else {
            return hexdec($varInt);
        }
    }
}