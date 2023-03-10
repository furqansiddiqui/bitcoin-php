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
use FurqanSiddiqui\BIP32\Buffers\BIP32_Provider;
use FurqanSiddiqui\BIP32\Buffers\Bits32;
use FurqanSiddiqui\BIP32\Buffers\SerializedBIP32Key;
use FurqanSiddiqui\BIP32\KeyPair\ExtendedKeyPair;
use FurqanSiddiqui\BIP32\KeyPair\PublicKeyInterface;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class HDKey
 * @package FurqanSiddiqui\Bitcoin\Wallets\HD
 */
class HDKey extends ExtendedKeyPair
{
    public readonly Bitcoin $btc;
    protected ?PublicKey $_public = null;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin|\FurqanSiddiqui\BIP32\Buffers\BIP32_Provider $bip32
     * @param \FurqanSiddiqui\BIP32\Buffers\SerializedBIP32Key $ser
     * @return static
     * @throws \FurqanSiddiqui\BIP32\Exception\UnserializeBIP32KeyException
     */
    public static function Unserialize(Bitcoin|BIP32_Provider $bip32, SerializedBIP32Key $ser): static
    {
        if (!$bip32 instanceof Bitcoin) {
            throw new \InvalidArgumentException('Expected instance of Bitcoin for Unserialize method');
        }

        $hdKey = parent::Unserialize($bip32, $ser);
        $hdKey->btc = $bip32;
        return $hdKey;
    }

    /**
     * @param \FurqanSiddiqui\BIP32\BIP32 $bip32
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey|\FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey $key
     * @param int $depth
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits32 $childNum
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits32 $parentPubFp
     * @param \Comely\Buffer\Bytes32 $chainCode
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin|null $btc
     */
    public function __construct(
        BIP32                $bip32,
        PublicKey|PrivateKey $key,
        int                  $depth,
        Bits32               $childNum,
        Bits32               $parentPubFp,
        Bytes32              $chainCode,
        ?Bitcoin             $btc = null,
    )
    {
        parent::__construct($bip32, $key, $depth, $childNum, $parentPubFp, $chainCode);
        if ($btc) {
            $this->btc = $btc;
        }
    }

    /**
     * @param int $index
     * @param bool $isHardened
     * @return $this
     * @throws \FurqanSiddiqui\BIP32\Exception\ChildKeyDeriveException
     * @throws \FurqanSiddiqui\BIP32\Exception\UnserializeBIP32KeyException
     */
    public function derive(int $index, bool $isHardened = false): HDKey
    {
        return HDKey::Unserialize($this->btc, $this->_derive($index, $isHardened));
    }

    /**
     * @param $path
     * @return $this
     * @throws \FurqanSiddiqui\BIP32\Exception\ChildKeyDeriveException
     * @throws \FurqanSiddiqui\BIP32\Exception\ExtendedKeyException
     */
    public function derivePath($path): HDKey
    {
        return parent::derivePath($path);
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey|\FurqanSiddiqui\BIP32\KeyPair\PublicKeyInterface
     * @throws \FurqanSiddiqui\BIP32\Exception\KeyPairException
     */
    public function publicKey(): PublicKey|PublicKeyInterface
    {
        if (!$this->_public) {
            $this->_public = new PublicKey($this->btc, $this->privateKey()->eccPrivateKey->public());
        }

        return $this->_public;
    }
}
