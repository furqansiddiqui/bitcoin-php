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

namespace FurqanSiddiqui\Bitcoin\Transactions;

use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxInputs;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction\TxOutputs;
use FurqanSiddiqui\DataTypes\Base16;

/**
 * Class Transaction
 * @package FurqanSiddiqui\Bitcoin\Transactions
 */
class Transaction
{
    /** @var int */
    private $version;
    /** @var TxInputs */
    private $inputs;
    /** @var TxOutputs */
    private $outputs;
    /** @var int */
    private $lockTime;

    public function Decode(Base16 $encodedTx): self
    {

    }

    /**
     * Transaction constructor.
     */
    public function __construct()
    {
        $this->version = 1;
        $this->inputs = new TxInputs($this);
        $this->outputs = new TxOutputs($this);
        $this->lockTime = 0;
    }

    /**
     * @param int $version
     * @return Transaction
     */
    public function version(int $version): self
    {
        $this->version = $version;
        return $this;
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
    public function lockTime(int $lockTime): self
    {
        $this->lockTime = $lockTime;
        return $this;
    }

    public function serialize()
    {

    }
}