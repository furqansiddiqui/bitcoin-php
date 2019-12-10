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

namespace FurqanSiddiqui\Bitcoin\Networks;

use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;

/**
 * Class BitcoinTestnet
 * @package FurqanSiddiqui\Bitcoin\Networks
 */
class BitcoinTestnet extends AbstractBitcoinNode
{
    public const NAME = "Bitcoin Testnet";
    public const DECIMALS = 8;
    public const P2PKH_PREFIX = 0x6F;
    public const P2SH_PREFIX = 0xC4;
    public const WIF_PREFIX = 0xEF;
    public const SIGNED_MESSAGE_PREFIX = "Bitcoin Signed Message:\n";
    public const BIP32_SEED_KEY = "Bitcoin seed";
    public const BIP32_PRIVATE_PREFIX = 0x04358394;
    public const BIP32_PUBLIC_PREFIX = 0x043587CF;
    public const BIP44_COIN_INDEX = 1;
}