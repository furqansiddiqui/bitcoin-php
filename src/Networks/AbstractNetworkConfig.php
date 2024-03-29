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
use Comely\Buffer\Bytes32;
use FurqanSiddiqui\BIP32\Buffers\Bits32;

/**
 * Class AbstractNetworkConfig
 * @package FurqanSiddiqui\Bitcoin\Networks
 */
abstract class AbstractNetworkConfig extends \FurqanSiddiqui\BIP32\Networks\AbstractNetworkConfig
{
    /**
     * @param string $name
     * @param int $scale
     * @param int $p2pkh_prefix
     * @param int $p2sh_prefix
     * @param int $wif_prefix
     * @param string $signedMessagePrefix
     * @param string $bip32_hmacSeed
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits32 $bip32_privatePrefix
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits32 $bip32_publicPrefix
     * @param int $bip32_hardenedIndexBeginsFrom
     * @param string $base58Charset
     * @param bool $base58CaseSensitive
     * @param string $bech32Charset
     * @param int $bech32Witness
     * @param string $bech32HRP
     */
    final protected function __construct(
        public readonly string $name,
        public readonly int    $scale,
        public readonly int    $p2pkh_prefix,
        public readonly int    $p2sh_prefix,
        public readonly int    $wif_prefix,
        public readonly string $signedMessagePrefix,
        string                 $bip32_hmacSeed,
        Bits32                 $bip32_privatePrefix,
        Bits32                 $bip32_publicPrefix,
        int                    $bip32_hardenedIndexBeginsFrom,
        string                 $base58Charset,
        bool                   $base58CaseSensitive,
        public readonly string $bech32Charset,
        public readonly int    $bech32Witness,
        public readonly string $bech32HRP,
    )
    {
        parent::__construct(
            $bip32_privatePrefix,
            $bip32_publicPrefix,
            $bip32_hardenedIndexBeginsFrom,
            $bip32_hmacSeed,
            $base58Charset,
            $base58CaseSensitive,
        );
    }

    /**
     * @param \Comely\Buffer\AbstractByteArray $buffer
     * @return \Comely\Buffer\Bytes20
     */
    abstract public function hash160(AbstractByteArray $buffer): Bytes20;

    /**
     * @param \Comely\Buffer\AbstractByteArray $buffer
     * @return \Comely\Buffer\Bytes32
     */
    abstract public function d_hash256(AbstractByteArray $buffer): Bytes32;

    /**
     * @return static
     */
    public static function createConfigInstance(): static
    {
        throw new \DomainException('This method is not available');
    }
}
