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

use FurqanSiddiqui\Bitcoin\Script\Script;

/**
 * Class TxInputs
 * @package FurqanSiddiqui\Bitcoin\Transactions\Transaction
 */
class TxInputs extends AbstractTxInOut
{
    /**
     * @param $prevTxHash
     * @param int $index
     * @param Script|null $scriptPubKey
     * @param int|null $seqNo
     * @param int|null $value
     * @return TxInput
     */
    public function add($prevTxHash, int $index, ?Script $scriptPubKey, ?int $seqNo = null, ?int $value = null): TxInput
    {
        $input = new TxInput($this->tx, $prevTxHash, $index, $scriptPubKey, $seqNo, $value);
        $this->append($input);
        return $input;
    }
}