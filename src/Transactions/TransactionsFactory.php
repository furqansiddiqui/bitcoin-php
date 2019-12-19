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
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;

/**
 * Class TransactionsFactory
 * @package FurqanSiddiqui\Bitcoin\Transactions
 */
class TransactionsFactory
{
    /** @var AbstractBitcoinNode */
    private $network;

    /**
     * TransactionsFactory constructor.
     * @param AbstractBitcoinNode $network
     */
    public function __construct(AbstractBitcoinNode $network)
    {
        $this->network = $network;
    }

    /**
     * @return Transaction
     */
    public function new(): Transaction
    {
        return new Transaction($this->network);
    }

    /**
     * @param Base16 $rawTx
     * @return Transaction
     * @throws \FurqanSiddiqui\Bitcoin\Exception\TransactionDecodeException
     */
    public function decode(Base16 $rawTx): Transaction
    {
        return RawTransactionDecoder::Decode($this->network, $rawTx);
    }
}