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

/**
 * Class TxInputs
 * @package FurqanSiddiqui\Bitcoin\Transactions\Transaction
 */
class TxInputs extends AbstractTxInOut
{
    /**
     * @param $prevTxHash
     * @param int $index
     * @param Script $scriptSig
     * @param int|null $seqNo
     * @return TxInput
     */
    public function add($prevTxHash, int $index, Script $scriptSig, ?int $seqNo = null): TxInput
    {
        $input = new TxInput($prevTxHash, $index, $scriptSig, $seqNo);
        $this->append($input);
        return $input;
    }
}