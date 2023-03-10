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
use FurqanSiddiqui\BIP39\Mnemonic;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Exception\KeyPairException;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\BaseKeyPair;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;
use FurqanSiddiqui\ECDSA\KeyPair;

/**
 * Class KeyPairFactory
 * @package FurqanSiddiqui\Bitcoin\Wallets
 */
class KeyPairFactory
{
    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     */
    public function __construct(private readonly Bitcoin $btc)
    {
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\BaseKeyPair
     * @throws \FurqanSiddiqui\BIP32\Exception\KeyPairException
     * @throws \FurqanSiddiqui\ECDSA\Exception\KeyPairException
     */
    public function generateSecurePrivateKey(): BaseKeyPair
    {
        return $this->privateKeyFromEntropy($this->btc->bip32()->generateSecureEntropy());
    }

    /**
     * @param \Comely\Buffer\Bytes32 $entropy
     * @return \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\BaseKeyPair
     * @throws \FurqanSiddiqui\ECDSA\Exception\KeyPairException
     */
    public function privateKeyFromEntropy(Bytes32 $entropy): BaseKeyPair
    {
        $pK = new PrivateKey($this->btc, new KeyPair($this->btc->ecc, $entropy));
        return new BaseKeyPair($this->btc, $pK);
    }

    /**
     * @param \FurqanSiddiqui\BIP39\Mnemonic $mnemonic
     * @param string|null $passphrase
     * @return \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\BaseKeyPair
     * @throws \FurqanSiddiqui\ECDSA\Exception\KeyPairException
     */
    public function privateKeyFromMnemonic(Mnemonic $mnemonic, ?string $passphrase = null): BaseKeyPair
    {
        $entropy = new Bytes32($mnemonic->generateSeed($passphrase, 32));
        return $this->privateKeyFromEntropy($entropy);
    }

    /**
     * @param \Comely\Buffer\AbstractByteArray $publicKey
     * @return \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey
     * @throws \FurqanSiddiqui\ECDSA\Exception\KeyPairException
     */
    public function publicKeyFromUncompressed(AbstractByteArray $publicKey): PublicKey
    {
        return new PublicKey($this->btc, \FurqanSiddiqui\ECDSA\ECC\PublicKey::fromDER($publicKey));
    }

    /**
     * @param string $b58WIF
     * @return \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\BaseKeyPair
     * @throws \FurqanSiddiqui\BIP32\Exception\Base58CheckException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\KeyPairException
     * @throws \FurqanSiddiqui\ECDSA\Exception\KeyPairException
     */
    public function importWIF(string $b58WIF): BaseKeyPair
    {
        $buffer = $this->btc->base58()->checkDecode($b58WIF);
        if ($buffer->len() !== 34) {
            throw new KeyPairException(sprintf('Expected decoded WIF buffer of 34 bytes, got %d', $buffer->len()));
        }

        $prefixByte = ord($buffer->pop(1));
        if ($prefixByte !== $this->btc->network->wif_prefix) {
            throw new KeyPairException('WIF private key network prefix does not match');
        }

        $buffer->pop(-1); // Remove last byte (compressed flag)
        return $this->privateKeyFromEntropy(new Bytes32($buffer->raw()));
    }
}
