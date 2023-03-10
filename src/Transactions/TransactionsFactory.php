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
use FurqanSiddiqui\Bitcoin\Bitcoin;

/**
 * Class TransactionsFactory
 * @package FurqanSiddiqui\Bitcoin\Transactions
 */
class TransactionsFactory
{
    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     */
    public function __construct(private readonly Bitcoin $btc)
    {
    }

    /**
     * @return Transaction
     */
    public function new(): Transaction
    {
        return new Transaction($this->btc);
    }

    /**
     * @param \Comely\Buffer\Buffer $rawTx
     * @return \FurqanSiddiqui\Bitcoin\Transactions\Transaction
     * @throws \FurqanSiddiqui\Bitcoin\Exception\TransactionDecodeException
     */
    public function decode(Buffer $rawTx): Transaction
    {
        return RawTransactionDecoder::Decode($this->btc, $rawTx);
    }
}
