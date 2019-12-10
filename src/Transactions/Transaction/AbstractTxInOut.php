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

use FurqanSiddiqui\Bitcoin\Transactions\Transaction;

/**
 * Class AbstractTxInOut
 * @package FurqanSiddiqui\Bitcoin\Transactions\Transaction
 */
abstract class AbstractTxInOut implements \Countable, \Iterator
{
    /** @var Transaction */
    protected $tx;
    /** @var array */
    protected $list;
    /** @var int */
    protected $count;
    /** @var int */
    protected $pos;

    /**
     * AbstractTxInOut constructor.
     * @param Transaction $tx
     */
    public function __construct(Transaction $tx)
    {
        $this->tx = $tx;
        $this->list = [];
        $this->count = 0;
        $this->pos = 0;
    }

    /**
     * @param TxInOutInterface $inOut
     */
    protected function append(TxInOutInterface $inOut): void
    {
        $this->list[] = $inOut;
        $this->count++;
    }

    /**
     * @return array
     */
    public function dump(): array
    {
        $list = [];
        /** @var TxInOutInterface $item */
        foreach ($this->list as $item) {
            $list[] = $item->dump();
        }

        return $list;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->list;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->pos = 0;
    }

    /**
     * @return TxInOutInterface
     */
    public function current(): TxInOutInterface
    {
        return $this->list[$this->pos];
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->pos;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        ++$this->pos;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->list[$this->pos]);
    }
}