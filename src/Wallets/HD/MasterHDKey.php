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

namespace FurqanSiddiqui\Bitcoin\Wallets\HD;

use Comely\Buffer\Bytes32;
use FurqanSiddiqui\BIP32\BIP32;
use FurqanSiddiqui\BIP32\Buffers\Bits32;
use FurqanSiddiqui\BIP32\Exception\UnserializeBIP32KeyException;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class MasterKey
 * @package FurqanSiddiqui\Bitcoin\Wallets\HD
 */
class MasterHDKey extends HDKey
{
    /**
     * @param \FurqanSiddiqui\BIP32\BIP32 $bip32
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey|\FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey $key
     * @param int $depth
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits32 $childNum
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits32 $parentPubFp
     * @param \Comely\Buffer\Bytes32 $chainCode
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin|null $btc
     * @throws \FurqanSiddiqui\BIP32\Exception\UnserializeBIP32KeyException
     */
    public function __construct(
        BIP32                $bip32,
        PublicKey|PrivateKey $key,
        int                  $depth,
        Bits32               $childNum,
        Bits32               $parentPubFp,
        Bytes32              $chainCode,
        ?Bitcoin             $btc = null
    )
    {
        if (!$childNum->isZeroBytes() || !$parentPubFp->isZeroBytes() || $depth !== 0) {
            throw new UnserializeBIP32KeyException('Cannot unserialize child key as MasterHDKey');
        }

        parent::__construct($bip32, $key, $depth, $childNum, $parentPubFp, $chainCode, $btc);
    }
}
