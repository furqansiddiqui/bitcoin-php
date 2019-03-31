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

namespace FurqanSiddiqui\Bitcoin\Networks;

use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;

/**
 * Class Bitcoin
 * @package FurqanSiddiqui\Bitcoin\Networks
 */
class Bitcoin extends AbstractBitcoinNode
{
    public const NAME = "Bitcoin";
    public const DECIMALS = 8;
    public const P2PKH_PREFIX = 0x00;
    public const P2SH_PREFIX = 0x05;
    public const WIF_PREFIX = 0x80;
    public const BIP32_SEED_KEY = "Bitcoin seed";
    public const BIP32_PRIVATE_PREFIX = 0x0488ADE4;
    public const BIP32_PUBLIC_PREFIX = 0x0488B21E;
    public const BIP44_COIN_INDEX = 0;
}