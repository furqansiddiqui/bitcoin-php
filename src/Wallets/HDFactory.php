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

namespace FurqanSiddiqui\Bitcoin\Wallets;


use Comely\Buffer\AbstractByteArray;
use Comely\Buffer\Bytes32;
use FurqanSiddiqui\BIP32\Buffers\Bits32;
use FurqanSiddiqui\BIP32\Buffers\Bits512;
use FurqanSiddiqui\BIP32\Buffers\SerializedBIP32Key;
use FurqanSiddiqui\BIP39\Mnemonic;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Wallets\HD\HDKey;
use FurqanSiddiqui\Bitcoin\Wallets\HD\MasterHDKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;
use FurqanSiddiqui\ECDSA\KeyPair;

/**
 * Class MasterKeyFactory
 * @package FurqanSiddiqui\Bitcoin\Wallets
 */
class HDFactory
{
    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     */
    public function __construct(public readonly Bitcoin $btc)
    {
    }

    /**
     * @param \FurqanSiddiqui\BIP32\Buffers\SerializedBIP32Key|\Comely\Buffer\AbstractByteArray $ser
     * @param bool $isMaster
     * @return \FurqanSiddiqui\Bitcoin\Wallets\HD\HDKey|\FurqanSiddiqui\Bitcoin\Wallets\HD\MasterHDKey
     * @throws \FurqanSiddiqui\BIP32\Exception\UnserializeBIP32KeyException
     */
    public function unserialize(SerializedBIP32Key|AbstractByteArray $ser, bool $isMaster = false): HDKey|MasterHDKey
    {
        if (!$ser instanceof SerializedBIP32Key) {
            $ser = new SerializedBIP32Key($ser->raw());
        }

        return $isMaster ? MasterHDKey::Unserialize($this->btc, $ser) : HDKey::Unserialize($this->btc, $ser);
    }

    /**
     * @param \Comely\Buffer\AbstractByteArray $prv
     * @param string|null $overrideSeed
     * @return \FurqanSiddiqui\Bitcoin\Wallets\HD\MasterHDKey
     * @throws \FurqanSiddiqui\BIP32\Exception\UnserializeBIP32KeyException
     * @throws \FurqanSiddiqui\ECDSA\Exception\KeyPairException
     */
    public function masterKeyFromEntropy(AbstractByteArray $prv, ?string $overrideSeed = null): MasterHDKey
    {
        return $this->masterKeyFromSeed($this->btc->bip32->hmacEntropy($prv, $overrideSeed));
    }

    /**
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits512 $seed
     * @return \FurqanSiddiqui\Bitcoin\Wallets\HD\MasterHDKey
     * @throws \FurqanSiddiqui\BIP32\Exception\UnserializeBIP32KeyException
     * @throws \FurqanSiddiqui\ECDSA\Exception\KeyPairException
     */
    public function masterKeyFromSeed(Bits512 $seed): MasterHDKey
    {
        $seed = $seed->raw();
        return new MasterHDKey(
            $this->btc->bip32,
            new PrivateKey($this->btc, new KeyPair($this->btc->ecc, new Bytes32(substr($seed, 0, 32)))),
            0,
            Bits32::fromInteger(0),
            new Bits32(str_repeat("\0", 4)),
            new Bytes32(substr($seed, 32)),
            $this->btc
        );
    }

    /**
     * @param \FurqanSiddiqui\BIP39\Mnemonic $mnemonic
     * @param string $passphrase
     * @return \FurqanSiddiqui\Bitcoin\Wallets\HD\MasterHDKey
     * @throws \FurqanSiddiqui\BIP32\Exception\UnserializeBIP32KeyException
     * @throws \FurqanSiddiqui\ECDSA\Exception\KeyPairException
     */
    public function masterKeyFromMnemonic(Mnemonic $mnemonic, string $passphrase = ""): MasterHDKey
    {
        return $this->masterKeyFromSeed(new Bits512($mnemonic->generateSeed($passphrase)));
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey|\FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey $key
     * @param \Comely\Buffer\Bytes32 $chainCode
     * @param int $depth
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits32 $childNum
     * @param \FurqanSiddiqui\BIP32\Buffers\Bits32 $parentPubFp
     * @return \FurqanSiddiqui\Bitcoin\Wallets\HD\HDKey
     */
    public function childKey(
        PrivateKey|PublicKey $key,
        Bytes32              $chainCode,
        int                  $depth,
        Bits32               $childNum,
        Bits32               $parentPubFp
    ): HDKey
    {
        return new HDKey(
            $this->btc->bip32,
            $key,
            $depth,
            $childNum,
            $parentPubFp,
            $chainCode,
            $this->btc
        );
    }
}
