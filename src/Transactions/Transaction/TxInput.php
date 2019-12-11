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
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;

/**
 * Class TxInput
 * @package FurqanSiddiqui\Bitcoin\Transactions\Transaction
 * @property-read int $sizeInBytes
 * @property-read Base16 $indexUInt32LE
 * @property-read Base16 $seqUInt32LE
 * @property-read string|null $scriptPubKeyType
 * @property-read string|null $scriptPubKeyAddr
 * @property-read string|null $scriptPubKeyError
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
    /** @var Script */
    private $scriptPubKey;
    /** @var int|null */
    private $seqNo;
    /** @var null|Script */
    private $scriptSig;
    /** @var null|PrivateKey */
    private $privateKey;
    /** @var array */
    private $segWitData;
    /** @var null|Script */
    private $redeemScript;

    /** @var null|string */
    private $scriptPubKeyType;
    /** @var null|string */
    private $scriptPubKeyAddr;
    /** @var null|string */
    private $scriptPubKeyError;

    /** @var int */
    private $sizeInBytes;

    /**
     * TxInput constructor.
     * @param Transaction $tx
     * @param $prevTxHash
     * @param int $index
     * @param Script|null $scriptPubKey (Optional, MUST provider if constructing new transaction; will not have if decoded an old transaction)
     * @param int|null $seqNo
     */
    public function __construct(Transaction $tx, $prevTxHash, int $index, ?Script $scriptPubKey, ?int $seqNo = null)
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
        $this->sizeInBytes = 40; // 32 byte hash + 4 byte index + 4 byte sequence no.
        $this->index = $index;
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
            case "sizeInBytes":
                return $this->sizeInBytes;
            case "indexUInt32LE":
                $uInt32LE = bin2hex(pack("V", $this->index));
                return new Base16($uInt32LE);
            case "seqUInt32LE":
                $uInt32LE = bin2hex(pack("V", $this->seqNo));
                return new Base16($uInt32LE);
            case "scriptPubKeyError":
            case "scriptPubKeyType":
            case "scriptPubKeyAddress":
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
        $this->sizeInBytes += $scriptSig->script()->binary()->size()->bytes();
        return $this;
    }

    /**
     * @param Script $redeemScript
     * @return $this
     */
    public function setRedeemScript(Script $redeemScript): self
    {
        $this->redeemScript = $redeemScript;
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
     * @return Script|PrivateKey|null
     */
    public function getSigningMethod()
    {
        return $this->scriptSig ?? $this->privateKey ?? null;
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
     * @return int
     */
    public function seqNo(): int
    {
        return $this->seqNo;
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
            "script" => null,
            "scriptPubKey" => null,
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
                "base16" => $this->redeemScript->script()->hexits(false)
            ];
        }

        // Has PrivateKey?
        if ($this->privateKey) {
            $inputData["privateKey"] = [
                "p2pkh" => $this->privateKey->publicKey()->p2pkh()->address()
            ];
        }

        // Witness Data?
        if ($this->segWitData) {
            $inputData["witness"] = [];
            /** @var Base16 $witness */
            foreach ($this->segWitData as $witness) {
                $inputData["witness"][] = $witness->hexits(false);
            }
        }

        return $inputData;
    }
}