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

namespace FurqanSiddiqui\Bitcoin\Serialize;

/**
 * Class Base58Check
 * @package FurqanSiddiqui\Bitcoin\Serialize
 */
class Base58Check extends \FurqanSiddiqui\Base58\Base58Check
{
    public const BITCOIN_BASE58_CHARSET = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
    public const CHECKSUM_BYTES = 4;

    /** @var self */
    private static $instance;

    /**
     * @return Base58Check
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new Base58Check();
            self::$instance->charset(self::BITCOIN_BASE58_CHARSET)
                ->checksum(self::CHECKSUM_BYTES, null);
        }

        return self::$instance;
    }
}