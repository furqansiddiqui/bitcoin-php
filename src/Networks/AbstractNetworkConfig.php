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
     */
    final protected function __construct(
        public readonly string $name,
        public readonly int    $scale,
        public readonly int    $p2pkh_prefix,
        public readonly int    $p2sh_prefix,
        public readonly int    $wif_prefix,
        public readonly string $signedMessagePrefix,
        public readonly string $bip32_hmacSeed,
        public readonly Bits32 $bip32_privatePrefix,
        public readonly Bits32 $bip32_publicPrefix,
        public readonly int    $bip32_hardenedIndexBeginsFrom,

    )
    {
        parent::__construct(
            $this->bip32_privatePrefix,
            $this->bip32_publicPrefix,
            $this->bip32_hardenedIndexBeginsFrom,
            $this->bip32_hmacSeed
        );
    }

    /**
     * @return static
     */
    public static function createConfigInstance(): static
    {
        throw new \DomainException('This method is not available');
    }
}
