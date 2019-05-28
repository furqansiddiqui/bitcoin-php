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
 * Class TxOutputs
 * @package FurqanSiddiqui\Bitcoin\Transactions\Transaction
 */
class TxOutputs extends AbstractTxInOut
{
    /**
     * @param Script $scriptPubKey
     * @param int $value
     * @return TxOutputs
     */
    public function add(Script $scriptPubKey, int $value): self
    {
        $output = new TxOutput($value, $scriptPubKey);
        $this->append($output);
        return $this;
    }
}