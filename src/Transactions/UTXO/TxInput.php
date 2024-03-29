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

use Comely\Buffer\AbstractByteArray;
use Comely\Buffer\BigInteger\LittleEndian;
use Comely\Buffer\Buffer;
use Comely\Buffer\Bytes32;
use Comely\Utils\OOP\OOP;
use FurqanSiddiqui\Bitcoin\Address\AbstractPaymentAddress;
use FurqanSiddiqui\Bitcoin\Address\Bech32_P2WPKH_Address;
use FurqanSiddiqui\Bitcoin\Address\P2PKH_Address;
use FurqanSiddiqui\Bitcoin\Address\P2SH_Address;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Exception\TransactionInputSignException;
use FurqanSiddiqui\Bitcoin\Script\MultiSigScript;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\BaseKeyPair;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class TxInput
 * @package FurqanSiddiqui\Bitcoin\Transactions\UTXO
 */
class TxInput implements UTXOInterface
{
    public readonly null|AbstractPaymentAddress $address;
    public readonly null|string $scriptError;

    private array $segWitData = [];
    private null|BaseKeyPair|MultiSigScript $signingMethod;
    private ?Script $scriptSig = null;
    private ?Script $redeemScript;
    public ?string $redeemScriptType = null;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Transactions\Transaction $tx
     * @param \Comely\Buffer\Bytes32 $prevTxHash
     * @param int $index
     * @param \FurqanSiddiqui\Bitcoin\Script\Script|null $scriptPubKey
     * @param int $seqNo
     * @param int $value
     * @param \FurqanSiddiqui\Bitcoin\Address\AbstractPaymentAddress|null $address
     */
    public function __construct(
        private readonly Transaction $tx,
        public readonly Bytes32      $prevTxHash,
        public readonly int          $index,
        public readonly ?Script      $scriptPubKey = null,
        public readonly int          $seqNo = 0xffffffff,
        public readonly int          $value = 0,
        ?AbstractPaymentAddress      $address = null,
    )
    {
        // Convert ScriptPubKey to address and appropriate address-type
        if ($scriptPubKey && !$address) {
            try {
                $address = $tx->btc->address->fromScriptPubKey($scriptPubKey);
            } catch (PaymentAddressException $e) {
                $this->scriptError = $e->getMessage();
            }
        } else {
            $this->scriptError = null;
        }

        $this->address = $address instanceof AbstractPaymentAddress ? $address : null;
    }

    /**
     * @param \Comely\Buffer\Buffer $buffer
     * @return $this
     */
    public function setWitnessData(Buffer $buffer): static
    {
        $this->segWitData[] = $buffer;
        return $this;
    }

    /**
     * @return array
     */
    public function getWitnessData(): array
    {
        return $this->segWitData;
    }

    /**
     * @return bool
     */
    public function hasWitnessData(): bool
    {
        return count($this->segWitData) > 0;
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Script\Script $redeemScript
     * @return $this
     */
    public function setRedeemScript(Script $redeemScript): static
    {
        $this->redeemScript = $redeemScript;

        // Is P2SH-P2WPKH script?
        $scriptBase16 = $redeemScript->buffer->toBase16();
        if (preg_match('/^0014[a-f0-9]{40}$/i', $scriptBase16)) {
            $this->redeemScriptType = "p2sh-p2wpkh";
        } elseif (preg_match('/^0020[a-f0-9]{64}$/i', $scriptBase16)) {
            $this->redeemScriptType = "p2sh-p2wsh";
        }

        return $this;
    }

    /**
     * @return Script|null
     */
    public function getRedeemScript(): ?Script
    {
        return $this->redeemScript;
    }

    /**
     * @param \Comely\Buffer\AbstractByteArray $signature
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey $publicKey
     * @return \FurqanSiddiqui\Bitcoin\Script\Script|null
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\TransactionInputSignException
     */
    public function createScriptSig(AbstractByteArray $signature, PublicKey $publicKey): ?Script
    {
        $signature = (new Buffer($signature->raw()))->append("\1"); // One-byte hash code type
        $scriptSig = $this->tx->btc->scripts->new();

        if (!$this->scriptPubKey || !$this->address) {
            throw new TransactionInputSignException('Cannot create ScriptSig without a valid ScriptPubKey');
        }

        if ($this->address instanceof P2PKH_Address) {
            // 1st part for P2PKH, P2SH, P2SH-P2WPKH = Signature
            $scriptSig->PUSHDATA($signature);
            // 2nd part of ScriptSig for P2PKH = PublicKey
            $scriptSig->PUSHDATA($publicKey->compressed());
        } elseif ($this->address instanceof P2SH_Address) {
            if (!$this->redeemScript) {
                throw new TransactionInputSignException('Cannot create ScriptSig for a P2SH input without RedeemScript');
            }

            if ($this->redeemScriptType === "p2sh-p2wpkh") {
                $scriptSig->PUSHDATA($this->redeemScript->buffer);
                $this->setWitnessData($signature);
                $this->setWitnessData($publicKey->compressed());
            } else {
                // 1st part for P2PKH, P2SH, P2SH-P2WPKH = Signature
                $scriptSig->PUSHDATA($signature);
                // 2nd part of ScriptSig for P2SH = RedeemScript
                $scriptSig->PUSHDATA($this->redeemScript->buffer);
            }
        } elseif ($this->address instanceof Bech32_P2WPKH_Address) {
            $this->setWitnessData($signature);
            $this->setWitnessData($publicKey->compressed());
            $scriptSig = null;
        }

        if ($scriptSig) {
            $scriptSig = $scriptSig->getScript();
            $this->setScriptSig($scriptSig);
        }

        return $scriptSig;
    }

    /**
     * @param Script $scriptSig
     * @return $this
     */
    public function setScriptSig(Script $scriptSig): static
    {
        $this->scriptSig = $scriptSig;
        return $this;
    }

    /**
     * @return Script|null
     * @throws TransactionInputSignException
     */
    public function getScriptCode(): ?Script
    {
        if (!$this->scriptPubKey || !$this->address) {
            return null;
        }

        if ($this->address instanceof P2SH_Address) {
            if (!isset($this->redeemScript)) {
                throw new TransactionInputSignException('Cannot sign a P2SH input without RedeemScript');
            }

            return $this->redeemScript;
        } elseif ($this->address instanceof P2PKH_Address) {
            return $this->scriptPubKey;
        } elseif ($this->address instanceof Bech32_P2WPKH_Address) {
            return null;
        }

        throw new TransactionInputSignException(
            sprintf('Cannot determine Script code from input of %s address type', OOP::baseClassName(get_class($this->address)))
        );
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\BaseKeyPair|\FurqanSiddiqui\Bitcoin\Script\MultiSigScript $signingMethod
     * @return $this
     */
    public function setSigningMethod(BaseKeyPair|MultiSigScript $signingMethod): static
    {
        $this->signingMethod = $signingMethod;
        return $this;
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Script\Script|\FurqanSiddiqui\Bitcoin\Wallets\KeyPair\BaseKeyPair|\FurqanSiddiqui\Bitcoin\Script\MultiSigScript|null
     */
    public function getSigningMethod(): null|Script|BaseKeyPair|MultiSigScript
    {
        return $this->scriptSig ?? $this->signingMethod;
    }

    /**
     * @return int
     */
    public function sizeInBytes(): int
    {
        $inputSize = 41; // 32 byte hash, 4 byte index and 4 byte seq. num and 1 byte scriptSig length
        if ($this->tx->isSegWit) {
            /** @var Buffer $witness */
            foreach ($this->segWitData as $witness) {
                $inputSize++; // Stack elements count
                $inputSize += $witness->len();
            }
        } elseif ($this->scriptSig) {
            $inputSize += $this->scriptSig->buffer->len();
        }

        return $inputSize;
    }

    /**
     * @return array
     */
    public function dump(): array
    {
        $inputData = [
            "prevTxHash" => $this->prevTxHash->toBase16(),
            "prevTxIndex" => [
                "dec" => $this->index,
                "uInt32LE" => bin2hex(LittleEndian::PackUInt32($this->index))
            ],
            "scriptPubKey" => null,
            "scriptSig" => null,
            "seqNo" => [
                "dec" => $this->seqNo,
                "uInt32LE" => bin2hex(LittleEndian::PackUInt32($this->seqNo))
            ]
        ];

        // ScriptSig?
        if ($this->scriptSig) {
            $inputData["scriptSig"] = [
                "script" => $this->scriptSig->script,
                "base16" => $this->scriptSig->buffer->toBase16()
            ];
        }

        // ScriptPubKey?
        if ($this->scriptPubKey) {
            $inputData["scriptPubKey"] = [
                "script" => $this->scriptPubKey->script,
                "base16" => $this->scriptPubKey->buffer->toBase16(),
            ];

            if ($this->address) {
                $inputData["scriptPubKey"]["address"] = $this->address->address;
                $inputData["scriptPubKey"]["type"] = OOP::baseClassName(get_class($this->address));
            }

            if ($this->scriptError) {
                $inputData["scriptPubKey"]["error"] = $this->scriptError;
            }
        }

        // RedeemScript?
        if ($this->redeemScript) {
            $inputData["redeemScript"] = [
                "script" => $this->redeemScript->script,
                "base16" => $this->redeemScript->buffer->toBase16(),
                "type" => $this->redeemScriptType
            ];
        }

        // Witness Data?
        if ($this->tx->isSegWit && $this->segWitData) {
            $inputData["witness"] = [];
            /** @var Buffer $witness */
            foreach ($this->segWitData as $witness) {
                $inputData["witness"][] = $witness->toBase16();
            }
        }

        return $inputData;
    }
}
