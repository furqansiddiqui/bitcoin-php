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

namespace FurqanSiddiqui\Bitcoin\Transactions\Transaction;

use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\DataTypes\Base16;

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
    private $scriptSig;
    /** @var int|null */
    private $seqNo;

    /**
     * TxInput constructor.
     * @param $prevTxHash
     * @param int $index
     * @param Script $scriptSig
     * @param int|null $seqNo
     */
    public function __construct($prevTxHash, int $index, Script $scriptSig, ?int $seqNo = null)
    {
        if (!$prevTxHash instanceof Base16) {
            if (!is_string($prevTxHash)) {
                throw new \InvalidArgumentException('Argument for $prevTxHash must be a Base16 Buffer or string');
            }

            $prevTxHash = new Base16($prevTxHash);
        }

        if ($prevTxHash->size()->chars() !== 64) {
            throw new \LengthException('TxInput arg $prevTxHash must be 64 hexits long');
        }

        $this->prevTxHash = $prevTxHash;
        $this->prevTxHash->readOnly(true);
        $this->index = $index;
        $this->scriptSig = $scriptSig;
        $this->seqNo = $seqNo ?? self::DEFAULT_SEQUENCE;
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
     * @return Script
     */
    public function scriptSig(): Script
    {
        return $this->scriptSig;
    }

    /**
     * @return int
     */
    public function seqNo(): int
    {
        return $this->seqNo;
    }
}