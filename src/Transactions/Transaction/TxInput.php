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
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;

/**
 * Class TxInput
 * @package FurqanSiddiqui\Bitcoin\Transactions\Transaction
 * @property-read Base16 $indexUInt32LE
 * @property-read Base16 $seqUInt32LE
 */
class TxInput implements TxInOutInterface
{
    private const DEFAULT_SEQUENCE = 0xFFFFFFFF;

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

    /**
     * TxInput constructor.
     * @param $prevTxHash
     * @param int $index
     * @param Script|null $scriptPubKey
     * @param int|null $seqNo
     */
    public function __construct($prevTxHash, int $index, ?Script $scriptPubKey, ?int $seqNo = null)
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

        $this->prevTxHash = $prevTxHash;
        $this->prevTxHash->readOnly(true);
        $this->index = $index;
        $this->scriptPubKey = $scriptPubKey;
        $this->seqNo = $seqNo ?? self::DEFAULT_SEQUENCE;
        $this->segWitData = [];
    }

    /**
     * @return array
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function dump(): array
    {
        $inputData = [
            "prevTxHash" => $this->prevTxHash,
            "prevTxIndex" => [
                "dec" => $this->index,
                "uInt32LE" => $this->indexUInt32LE
            ],
            "script" => [
                "script" => null,
                "base16" => null,
                "type" => null
            ],
            "seqNo" => [
                "dec" => $this->seqNo,
                "uInt32LE" => $this->seqUInt32LE
            ],
        ];

        // Script?
        $dumpScript = $this->scriptSig ?? $this->scriptPubKey ?? null;
        if ($dumpScript) {
            $inputData["script"]["script"] = $dumpScript->raw();
            $inputData["script"]["base16"] = $dumpScript->script()->hexits(false);
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

    /**
     * @param $prop
     * @return Base16
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
    public function segWitData(): array
    {
        return $this->segWitData;
    }

    /**
     * @return int
     */
    public function seqNo(): int
    {
        return $this->seqNo;
    }
}