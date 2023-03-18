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

namespace FurqanSiddiqui\Bitcoin;

use Comely\Buffer\AbstractByteArray;
use Comely\Buffer\Buffer;
use Comely\Buffer\Bytes32;
use FurqanSiddiqui\BIP32\BIP32;
use FurqanSiddiqui\BIP32\Buffers\Base58;
use FurqanSiddiqui\BIP32\Buffers\BIP32_Provider;
use FurqanSiddiqui\Bitcoin\Address\AddressFactory;
use FurqanSiddiqui\Bitcoin\Exception\KeyPairException;
use FurqanSiddiqui\Bitcoin\Networks\AbstractNetworkConfig;
use FurqanSiddiqui\Bitcoin\Protocol\Bech32;
use FurqanSiddiqui\Bitcoin\Protocol\VarInt;
use FurqanSiddiqui\Bitcoin\Script\ScriptFactory;
use FurqanSiddiqui\Bitcoin\Transactions\TransactionsFactory;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;
use FurqanSiddiqui\Bitcoin\Wallets\Wallets;
use FurqanSiddiqui\ECDSA\ECC\EllipticCurveInterface;
use FurqanSiddiqui\ECDSA\KeyPair;

/**
 * Class Bitcoin
 * @package FurqanSiddiqui\Bitcoin
 */
class Bitcoin implements BIP32_Provider
{
    public readonly BIP32 $bip32;
    public readonly ScriptFactory $scripts;
    public readonly AddressFactory $address;
    public readonly TransactionsFactory $txn;
    public readonly Wallets $wallets;
    public readonly Bech32 $bech32;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Networks\AbstractNetworkConfig $network
     * @param \FurqanSiddiqui\ECDSA\ECC\EllipticCurveInterface $ecc
     */
    public function __construct(
        public readonly AbstractNetworkConfig  $network,
        public readonly EllipticCurveInterface $ecc,
    )
    {
        $this->bip32 = new BIP32($this->ecc, $this->network);
        $this->scripts = new ScriptFactory($this);
        $this->address = new AddressFactory($this);
        $this->txn = new TransactionsFactory($this);
        $this->wallets = new Wallets($this);
        $this->bech32 = new Bech32($this);
    }

    /**
     * @return \FurqanSiddiqui\BIP32\BIP32
     */
    public function bip32(): BIP32
    {
        return $this->bip32;
    }

    /**
     * @return \FurqanSiddiqui\BIP32\Buffers\Base58
     */
    public function base58(): Base58
    {
        return $this->bip32->base58;
    }

    /**
     * @param string $message
     * @return \Comely\Buffer\Bytes32
     */
    public function messageHash(string $message): Bytes32
    {
        $msgPrefix = $this->network->signedMessagePrefix;
        $buffer = (new Buffer())
            ->append(VarInt::Int2Bin(strlen($msgPrefix)))
            ->append($msgPrefix)
            ->append(VarInt::Int2Bin(strlen($message)))
            ->append($message);

        return new Bytes32(hash("sha256", hash("sha256", $buffer->raw(), true), true));
    }

    /**
     * @param \Comely\Buffer\Bytes32 $entropy
     * @return \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey
     * @throws \FurqanSiddiqui\ECDSA\Exception\KeyPairException
     */
    public function privateKeyFromEntropy(Bytes32 $entropy): PrivateKey
    {
        return new PrivateKey($this, new KeyPair($this->ecc, $entropy));
    }

    /**
     * @param \Comely\Buffer\AbstractByteArray $compressedPubKey
     * @return \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey
     * @throws \FurqanSiddiqui\Bitcoin\Exception\KeyPairException
     */
    public function publicKeyFromIncomplete(AbstractByteArray $compressedPubKey): PublicKey
    {
        if ($compressedPubKey->len() !== 33) {
            throw new KeyPairException('Compressed public key must be 33 bytes long');
        }

        $compressedPubKey = $compressedPubKey->raw();
        if (!in_array($compressedPubKey[0], ["\x02", "\x03"])) {
            throw new KeyPairException('Invalid compressed public key prefix');
        }

        return new PublicKey(
            $this,
            new \FurqanSiddiqui\ECDSA\ECC\PublicKey(bin2hex(substr($compressedPubKey, 1)), "", bin2hex($compressedPubKey[0]))
        );
    }
}
