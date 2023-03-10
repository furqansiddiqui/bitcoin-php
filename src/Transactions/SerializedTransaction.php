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
use Comely\Buffer\Bytes32;
use FurqanSiddiqui\Bitcoin\Bitcoin;

/**
 * Class SerializedTransaction
 * @package FurqanSiddiqui\Bitcoin\Transactions
 */
class SerializedTransaction
{
    public readonly Bytes32 $hash;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param \Comely\Buffer\Buffer $rawTx
     * @param bool $isSigned
     */
    public function __construct(
        Bitcoin                $btc,
        public readonly Buffer $rawTx,
        public readonly bool   $isSigned = false
    )
    {
        $this->hash = $btc->network->d_hash256($this->rawTx);
    }
}
