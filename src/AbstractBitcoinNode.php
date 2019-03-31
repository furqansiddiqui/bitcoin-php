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

namespace FurqanSiddiqui\Bitcoin;

use FurqanSiddiqui\Bitcoin\Networks\BitcoinNetworkConstants;
use FurqanSiddiqui\Bitcoin\Wallets\Wallets;

/**
 * Class AbstractBitcoinNode
 * @package FurqanSiddiqui\Bitcoin
 * @property-read null|string $const_name
 * @property-read null|int $const_decimals
 * @property-read null|int $const_private_key_bits
 * @property-read null|int $const_ecdsa_curve
 * @property-read null|int $const_p2pkh_prefix
 * @property-read null|int $const_p2sh_prefix
 * @property-read null|int $const_wif_prefix
 * @property-read null|string $const_bip32_seed_key
 * @property-read null|int $const_bip32_private_prefix
 * @property-read null|int $const_bip32_public_prefix
 * @property-read null|int $const_bip44_coin_index
 */
abstract class AbstractBitcoinNode extends BitcoinNetworkConstants
{
    /** @var Wallets */
    protected $wallets;

    /**
     * AbstractBitcoinNode constructor.
     */
    public function __construct()
    {
        $this->wallets = new Wallets($this);
    }

    /**
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop)
    {
        $prop = strtolower($prop);
        if (preg_match('/^const_\w+$/', $prop)) {
            return constant("static::" . strtoupper(substr($prop, 6)));
        }

        throw new \DomainException('Cannot read inaccessible properly');
    }

    /**
     * @return Wallets
     */
    public function wallets(): Wallets
    {
        return $this->wallets;
    }
}