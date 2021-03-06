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
use FurqanSiddiqui\Bitcoin\Exception\TransactionInputSignException;
use FurqanSiddiqui\Bitcoin\Script\MultiSigScript;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class TxInput
 * @package FurqanSiddiqui\Bitcoin\Transactions\Transaction
 * @property-read Base16 $indexUInt32LE
 * @property-read Base16 $seqUInt32LE
 * @property-read string|null $scriptPubKeyType
 * @property-read string|null $scriptPubKeyAddr
 * @property-read string|null $scriptPubKeyError
 * @property-read string|null $redeemScriptType
 * @property-read int|null $value
 * @property-read string|null $valueUInt64LE
 */
class TxInput implements TxInOutInterface
{
    private const DEFAULT_SEQUENCE = 0xFFFFFFFF;

    /** @var Transaction */
    private $tx;
    /** @var Base16 */
    private $prevTxHash;
    /** @var int */
    private $index;
    /** @var null|int */
    private $value;
    /** @var Script */
    private $scriptPubKey;
    /** @var int|null */
    private $seqNo;
    /** @var null|Script */
    private $scriptSig;
    /** @var null|PrivateKey */
    private $privateKey;
    /** @var null|MultiSigScript */
    private $multiSigScript;
    /** @var array */
    private $segWitData;
    /** @var null|Script */
    private $redeemScript;
    /** @var null|string */
    private $redeemScriptType;

    /** @var null|string */
    private $scriptPubKeyType;
    /** @var null|string */
    private $scriptPubKeyAddr;
    /** @var null|string */
    private $scriptPubKeyError;

    /**
     * TxInput constructor.
     * @param Transaction $tx
     * @param $prevTxHash
     * @param int $index
     * @param Script|null $scriptPubKey (Optional, MUST provider if constructing new transaction; will not have if decoded an old transaction)
     * @param int|null $seqNo
     * @param int|null $value
     */
    public function __construct(Transaction $tx, $prevTxHash, int $index, ?Script $scriptPubKey, ?int $seqNo = null, ?int $value = null)
    {
        if (!$prevTxHash instanceof Base16) {
            if (!is_string($prevTxHash)) {
                throw new \InvalidArgumentException('Argument for $prevTxHash must be a Base16 Buffer or string');
            }

            $prevTxHash = new Base16($prevTxHash);
        }

        if ($prevTxHash->len() !== 64) {
            throw new \LengthException('TxInput arg $prevTxHash must be 64 hexits long');
        }

        $this->tx = $tx;
        $this->prevTxHash = $prevTxHash;
        $this->prevTxHash->readOnly(true);
        $this->index = $index;
        $this->value = $value;
        $this->scriptPubKey = $scriptPubKey;
        $this->seqNo = $seqNo ?? self::DEFAULT_SEQUENCE;
        $this->segWitData = [];

        // Convert ScriptPubKey to address and appropriate address-type
        if ($scriptPubKey) {
            try {
                $address = $tx->network->address()->addressFromScript($scriptPubKey);
                $this->scriptPubKeyType = $address->type();
                $this->scriptPubKeyAddr = $address->address();
            } catch (PaymentAddressException $e) {
                $this->scriptPubKeyError = $e->getMessage();
            }
        }
    }

    /**
     * @param $prop
     * @return Base16|string|null
     */
    public function __get($prop)
    {
        switch ($prop) {
            case "indexUInt32LE":
                $uInt32LE = bin2hex(pack("V", $this->index));
                return new Base16($uInt32LE);
            case "seqUInt32LE":
                $uInt32LE = bin2hex(pack("V", $this->seqNo));
                return new Base16($uInt32LE);
            case "value":
                return $this->value;
            case "valueUInt64LE":
                if (!is_int($this->value)) {
                    return null;
                }

                $uInt64LE = bin2hex(pack("P", $this->value));
                return new Base16($uInt64LE);
            case "scriptPubKeyError":
            case "redeemScriptType":
            case "scriptPubKeyType":
            case "scriptPubKeyAddr":
                return $this->$prop;
        }

        throw new \OutOfBoundsException('Cannot get value of inaccessible property');
    }

    /**
     * @return Base16
     */
    public function prevTxHash(): Base16
    {
        return $this->prevTxHash;
    }

    /**
     * @return int
     */
    public function index(): int
    {
        return $this->index;
    }

    /**
     * @return Script|null
     */
    public function scriptPubKey(): ?Script
    {
        return $this->scriptPubKey;
    }

    /**
     * @param Script $scriptSig
     * @return $this
     */
    public function setScriptSig(Script $scriptSig): self
    {
        $this->scriptSig = $scriptSig;
        return $this;
    }

    /**
     * @param Script $redeemScript
     * @return $this
     */
    public function setRedeemScript(Script $redeemScript): self
    {
        $this->redeemScript = $redeemScript;

        // Is P2SH-P2WPKH script?
        $scriptBase16 = $redeemScript->script()->hexits(false);
        if (preg_match('/^0014[a-f0-9]{40}$/i', $scriptBase16)) {
            $this->redeemScriptType = "p2sh-p2wpkh";
        } elseif (preg_match('/^0020[a-f0-9]{64}$/i', $scriptBase16)) {
            $this->redeemScriptType = "p2sh-p2wsh";
        }

        return $this;
    }

    /**
     * @param PrivateKey $privateKey
     * @return $this
     */
    public function signWithPrivateKey(PrivateKey $privateKey): self
    {
        $this->privateKey = $privateKey;
        return $this;
    }

    /**
     * @param MultiSigScript $multiSigScript
     * @return $this
     */
    public function signaturesFromMultiSig(MultiSigScript $multiSigScript): self
    {
        $this->multiSigScript = $multiSigScript;
        return $this;
    }

    /**
     * @return MultiSigScript|Script|PrivateKey|null
     */
    public function getSigningMethod()
    {
        return $this->scriptSig ?? $this->privateKey ?? $this->multiSigScript ?? null;
    }

    /**
     * @param Base16 $script
     * @return $this
     */
    public function setWitnessData(Base16 $script): self
    {
        $this->segWitData[] = $script;
        return $this;
    }

    /**
     * @return array
     */
    public function getSegWitData(): array
    {
        return $this->segWitData;
    }

    /**
     * @return Script|null
     */
    public function getRedeemScript(): ?Script
    {
        return $this->redeemScript;
    }

    /**
     * @param Base16 $signature
     * @param PublicKey $publicKey
     * @return Script
     * @throws TransactionInputSignException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function createScriptSig(Base16 $signature, PublicKey $publicKey): Script
    {
        $signature = $signature->copy()->append("01"); // One-byte hash code type
        $scriptSig = $this->tx->network->script()->new();

        if (preg_match('/^p2sh/', $this->scriptPubKeyType)) { // P2SH
            if (!$this->redeemScript) {
                throw new TransactionInputSignException('Cannot create ScriptSig for a P2SH input without RedeemScript');
            }

            if ($this->redeemScriptType === "p2sh-p2wpkh") {
                $scriptSig->PUSHDATA($this->redeemScript->script()->binary());
                $this->setWitnessData($signature);
                $this->setWitnessData($publicKey->compressed());
            } else {
                // 1st part for P2PKH, P2SH, P2SH-P2WPKH = Signature
                $scriptSig->PUSHDATA($signature->binary());
                // 2nd part of ScriptSig for P2SH = RedeemScript
                $scriptSig->PUSHDATA($this->redeemScript->script()->binary());
            }
        } elseif ($this->scriptPubKeyType === "p2pkh") { // P2PKH
            // 1st part for P2PKH, P2SH, P2SH-P2WPKH = Signature
            $scriptSig->PUSHDATA($signature->binary());
            // 2nd part of ScriptSig for P2PKH = PublicKey
            $scriptSig->PUSHDATA($publicKey->compressed()->binary());
        }

        $scriptSig = $scriptSig->script();
        $this->setScriptSig($scriptSig);
        return $scriptSig;
    }

    /**
     * @return Script|null
     * @throws TransactionInputSignException
     */
    public function getScriptCode(): ?Script
    {
        if (preg_match('/^p2sh/', $this->scriptPubKeyType)) {
            if (!$this->redeemScript) {
                throw new TransactionInputSignException('Cannot sign a P2SH input without RedeemScript');
            }

            /*if ($this->redeemScriptType === "p2sh-p2wpkh") {
                $redeemScriptHash = substr($this->redeemScript->script()->hexits(false), 4, 40);
                return $this->tx->network->script()->new()
                    ->OP_DUP()
                    ->OP_HASH160()
                    ->PUSHDATA((new Base16($redeemScriptHash))->binary())
                    ->OP_EQUALVERIFY()
                    ->OP_CHECKSIG()
                    ->script();
            }*/

            return $this->redeemScript;
        } elseif ($this->scriptPubKeyType === "p2pkh") {
            return $this->scriptPubKey;
        }

        return null;
    }

    /**
     * @return int
     */
    public function seqNo(): int
    {
        return $this->seqNo;
    }

    /**
     * @return int
     */
    public function sizeInBytes(): int
    {
        $inputSize = 41; // 32 byte hash, 4 byte index and 4 byte seq. num and 1 byte scriptSig length
        if ($this->tx->isSegWit) {
            /** @var Base16 $witness */
            foreach ($this->segWitData as $witness) {
                $inputSize++; // Stack elements count
                $inputSize += $witness->binary()->sizeInBytes;
            }
        } elseif ($this->scriptSig) {
            $inputSize += $this->scriptSig->script()->binary()->sizeInBytes;
        }

        return $inputSize;
    }

    /**
     * @return array
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function dump(): array
    {
        $inputData = [
            "prevTxHash" => $this->prevTxHash->hexits(false),
            "prevTxIndex" => [
                "dec" => $this->index,
                "uInt32LE" => $this->indexUInt32LE->hexits(false)
            ],
            "scriptPubKey" => null,
            "scriptSig" => null,
            "seqNo" => [
                "dec" => $this->seqNo,
                "uInt32LE" => $this->seqUInt32LE->hexits(false)
            ]
        ];

        // ScriptSig?
        if ($this->scriptSig) {
            $inputData["scriptSig"] = [
                "script" => $this->scriptSig->raw(),
                "base16" => $this->scriptSig->script()->hexits(false)
            ];
        }

        // ScriptPubKey?
        if ($this->scriptPubKey) {
            $inputData["scriptPubKey"] = [
                "script" => $this->scriptPubKey->raw(),
                "base16" => $this->scriptPubKey->script()->hexits(false),
                "type" => $this->scriptPubKeyType,
                "address" => $this->scriptPubKeyAddr,
            ];

            if ($this->scriptPubKeyError) {
                $inputData["scriptPubKey"]["error"] = $this->scriptPubKeyError;
            }
        }

        // RedeemScript?
        if ($this->redeemScript) {
            $inputData["redeemScript"] = [
                "script" => $this->redeemScript->raw(),
                "base16" => $this->redeemScript->script()->hexits(false),
                "type" => $this->redeemScriptType
            ];
        }

        // Has PrivateKey?
        if ($this->privateKey) {
            $inputData["privateKey"] = [
                "p2pkh" => $this->privateKey->publicKey()->p2pkh()->address()
            ];
        }

        // Witness Data?
        if ($this->tx->isSegWit && $this->segWitData) {
            $inputData["witness"] = [];
            /** @var Base16 $witness */
            foreach ($this->segWitData as $witness) {
                $inputData["witness"][] = $witness->hexits(false);
            }
        }

        return $inputData;
    }
}