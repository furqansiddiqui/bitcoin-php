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

namespace FurqanSiddiqui\Bitcoin\Networks;

use Comely\Buffer\AbstractByteArray;
use Comely\Buffer\Bytes20;
use FurqanSiddiqui\BIP32\Buffers\Bits32;

/**
 * Class Bitcoin
 * @package FurqanSiddiqui\Bitcoin\Networks
 */
class Bitcoin extends AbstractNetworkConfig
{
    /**
     * @return static
     */
    public static function Mainnet(): static
    {
        return static::CustomNetwork(
            name: "Bitcoin Mainnet",
            p2pkh_prefix: 0x00,
            p2sh_prefix: 0x05,
            wif_prefix: 0x80,
            bip32_privatePrefix: new Bits32(hex2bin("0488ADE4")),
            bip32_publicPrefix: new Bits32(hex2bin("0488B21E")),
        );
    }

    /**
     * @return static
     */
    public static function Testnet3(): static
    {
        return static::CustomNetwork(
            name: "Bitcoin Testnet3",
            p2pkh_prefix: 0x6F,
            p2sh_prefix: 0xC4,
            wif_prefix: 0xEF,
            bip32_privatePrefix: new Bits32(hex2bin("04358394")),
            bip32_publicPrefix: new Bits32(hex2bin("043587CF")),
        );
    }

    /**
     * @param string $name
     * @param int $scale
     * @param int $p2pkh_prefix
     * @param int $p2sh_prefix
     * @param int $wif_prefix
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits32|null $bip32_privatePrefix
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits32|null $bip32_publicPrefix
     * @param int $bip32_hardenedIndexBeginsFrom
     * @param string $bip32_hmacSeed
     * @param string $base58Charset
     * @param bool $base58CaseSensitive
     * @param string $signedMessagePrefix
     * @return static
     */
    public static function CustomNetwork(
        string  $name,
        int     $scale = 8,
        int     $p2pkh_prefix = 0x00,
        int     $p2sh_prefix = 0x05,
        int     $wif_prefix = 0x80,
        ?Bits32 $bip32_privatePrefix = null,
        ?Bits32 $bip32_publicPrefix = null,
        int     $bip32_hardenedIndexBeginsFrom = 0x80000000,
        string  $bip32_hmacSeed = "Bitcoin seed",
        string  $base58Charset = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz",
        bool    $base58CaseSensitive = true,
        string  $signedMessagePrefix = "Bitcoin Signed Message:\n",
    ): static
    {
        return new static(
            name: $name,
            scale: $scale,
            p2pkh_prefix: $p2pkh_prefix,
            p2sh_prefix: $p2sh_prefix,
            wif_prefix: $wif_prefix,
            signedMessagePrefix: $signedMessagePrefix,
            bip32_hmacSeed: $bip32_hmacSeed,
            bip32_privatePrefix: $bip32_privatePrefix ?? new Bits32(hex2bin("0488ADE4")),
            bip32_publicPrefix: $bip32_publicPrefix ?? new Bits32(hex2bin("0488B21E")),
            bip32_hardenedIndexBeginsFrom: $bip32_hardenedIndexBeginsFrom,
            base58Charset: $base58Charset,
            base58CaseSensitive: $base58CaseSensitive
        );
    }

    /**
     * @param \Comely\Buffer\AbstractByteArray $buffer
     * @return \Comely\Buffer\Bytes20
     */
    public function hash160(AbstractByteArray $buffer): Bytes20
    {
        return new Bytes20(hash("ripemd160", hash("sha256", $buffer->raw(), true), true));
    }
}
