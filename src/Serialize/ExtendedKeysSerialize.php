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

namespace FurqanSiddiqui\Bitcoin\Serialize;

use FurqanSiddiqui\Base58\Result\Base58Encoded;
use FurqanSiddiqui\BcMath\BcMath;
use FurqanSiddiqui\DataTypes\DataTypes;

/**
 * Class ExtendedKeysSerialize
 * @package FurqanSiddiqui\Bitcoin\Serialize
 */
class ExtendedKeysSerialize
{
    public const IS_PUBLIC = 8;
    public const IS_PRIVATE = 16;

    public static function Encode(int $type, string $key, string $chainCode, int $versionBytes, int $childIndex, int $depth, int $fingerPrint): Base58Encoded
    {
        if (!in_array($type, [self::IS_PUBLIC, self::IS_PRIVATE])) {
            throw new \InvalidArgumentException('Invalid EKD type; Use IS_PUBLIC/IS_PRIVATE const');
        }

        if (!DataTypes::isBase16($key)) {
            throw new \InvalidArgumentException('EKD key must be a hexadecimal string');
        }

        if (!DataTypes::isBase16($chainCode)) {
            throw new \InvalidArgumentException('EKD chain code must be a hexadecimal string');
        }

        $versionBytesHex = BcMath::Encode($versionBytes);
        if (strlen($versionBytesHex) !== 8) {
            throw new \InvalidArgumentException('Version prefix must be 4 bytes');
        }



    }
}