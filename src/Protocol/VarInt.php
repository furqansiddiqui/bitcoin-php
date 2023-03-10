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

namespace FurqanSiddiqui\Bitcoin\Protocol;

/**
 * Class VarInt
 * @package FurqanSiddiqui\Bitcoin\Protocol
 */
class VarInt
{
    /**
     * @param int $dec
     * @return string
     */
    public static function Int2Bin(int $dec): string
    {
        // Single byte integer
        if ($dec < 0xfd) {
            return chr($dec);
        }

        // 2-byte integer
        if ($dec <= 0xffff) {
            return chr(0xfd) . pack("v", $dec);
        }

        // 4-byte integer
        if ($dec <= 0xffffffff) {
            return chr(0xfe) . pack("V", $dec);
        }

        // 8-byte integer
        return chr(0xff) . bin2hex(pack("P", $dec));
    }

    /**
     * @param string $bin
     * @return int
     */
    public static function Bin2Int(string $bin): int
    {
        return match (ord(substr($bin, 0, 1))) {
            253 => unpack("v", hex2bin(substr($bin, 1)))[1],
            254 => unpack("V", hex2bin(substr($bin, 1)))[1],
            255 => unpack("P", hex2bin(substr($bin, 1)))[1],
            default => ord(substr($bin, 0, 1))
        };
    }
}
