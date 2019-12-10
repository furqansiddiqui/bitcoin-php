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

namespace FurqanSiddiqui\Bitcoin;

use FurqanSiddiqui\Bitcoin\Networks;

/**
 * Class Bitcoin
 * @package FurqanSiddiqui\Bitcoin
 */
class Bitcoin
{
    public const VERSION = "0.20.1";

    /**
     * @return Networks\Bitcoin
     */
    public static function Mainnet(): Networks\Bitcoin
    {
        return new Networks\Bitcoin();
    }

    /**
     * @return Networks\BitcoinTestnet
     */
    public static function Testnet(): Networks\BitcoinTestnet
    {
        return new Networks\BitcoinTestnet();
    }
}