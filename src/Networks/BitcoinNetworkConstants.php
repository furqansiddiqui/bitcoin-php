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

/**
 * Class BitcoinNetworkConstants
 * @package FurqanSiddiqui\Bitcoin\Networks
 */
abstract class BitcoinNetworkConstants
{
    /** @var string Network name */
    public const NAME = null;
    /** @var int Number of decimals/scale */
    public const DECIMALS = null;

    /**
     *  Address Prefixes
     */

    /** @var int P2PKH (Pay to public key hash) addresses prefix */
    public const P2PKH_PREFIX = null;
    /** @var int P2SH (Pay to script hash) addresses prefix */
    public const P2SH_PREFIX = null;

    /**
     * WIF
     */

    /** @var int WIF (Wallet import format) prefix */
    public const WIF_PREFIX = null;

    /**
     * BIP32 Constants
     */

    /** @var string HMAC key for BIP32 master seed */
    public const BIP32_SEED_KEY = null;
    /** @var int BIP32 private keys prefix */
    public const BIP32_PRIVATE = null;
    /** @var int BIP32 public keys prefix */
    public const BIP32_PUBLIC = null;

    /**
     * BIP44 Constants
     */

    /** @var int BIP44 Coin Index */
    public const BIP44_COIN_INDEX = null;
}