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
    public static function Encode(int $dec): string
    {
        // Single byte integer
        if ($dec < 0xfd) {
            $hex = dechex($dec);
            if ($hex % 2 !== 0) {
                $hex = "0" . $hex;
            }

            return $hex;
        }

        // 2-byte integer
        if ($dec <= 0xffff) {
            return "fd" . bin2hex(pack("v", $dec));
        }

        // 4-byte integer
        if ($dec <= 0xffffffff) {
            return "fe" . bin2hex(pack("V", $dec));
        }

        // 8-byte integer
        return "ff" . bin2hex(pack("P", $dec));
    }

    /**
     * @param string $hexVarInt
     * @param int|null $size
     * @return int
     */
    public static function Decode(string $hexVarInt, ?int $size = null): int
    {
        // Determine size if arg is NULL
        if (!$size > 0) {
            $firstByte = strtolower(substr($hexVarInt, 0, 2));
            $size = 1;
            switch ($firstByte) {
                case "ff":
                    $size = 8;
                    $hexVarInt = substr($hexVarInt, 2);
                    break;
                case "fe":
                    $size = 4;
                    $hexVarInt = substr($hexVarInt, 2);
                    break;
                case "fd":
                    $size = 2;
                    $hexVarInt = substr($hexVarInt, 2);
                    break;
            }
        }

        // Have size, convert dec
        switch ($size) {
            case 8:
                return unpack("P", hex2bin($hexVarInt))[1];
            case 4:
                return unpack("V", hex2bin($hexVarInt))[1];
            case 2:
                return unpack("v", hex2bin($hexVarInt))[1];
            case 1:
                return hexdec($hexVarInt);
            default:
                throw new \InvalidArgumentException('Cannot determine size of VarInt');
        }
    }
}