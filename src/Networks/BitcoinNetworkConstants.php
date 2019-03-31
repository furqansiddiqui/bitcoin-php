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

use FurqanSiddiqui\BIP32\ECDSA\Curves;

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
    /** @var int|null Bitwise length of Private key entropy, (set NULL for any) */
    public const PRIVATE_KEY_BITS = 256;
    /** @var int ECDSA curve (defaults Secp256k1) */
    public const ECDSA_CURVE = Curves::SECP256K1;
    /** @var int ECDSA curve (default Secp256k1_OpenSSL) */
    public const ECDSA_CURVE_FALLBACK = Curves::SECP256K1_OPENSSL;

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
    public const BIP32_PRIVATE_PREFIX = null;
    /** @var int BIP32 public keys prefix */
    public const BIP32_PUBLIC_PREFIX = null;

    /**
     * BIP44 Constants
     */

    /** @var int BIP44 Coin Index */
    public const BIP44_COIN_INDEX = null;
}