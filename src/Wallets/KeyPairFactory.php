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

namespace FurqanSiddiqui\Bitcoin\Wallets;

use Comely\DataTypes\Buffer\Base16;
use Comely\DataTypes\Buffer\Binary;
use Comely\DataTypes\DataTypes;
use FurqanSiddiqui\Base58\Result\Base58Encoded;
use FurqanSiddiqui\BIP32\ECDSA\Curves;
use FurqanSiddiqui\BIP39\Mnemonic;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Exception\KeyPairException;
use FurqanSiddiqui\Bitcoin\Serialize\WIF;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class KeyPairFactory
 * @package FurqanSiddiqui\Bitcoin\Wallets
 */
class KeyPairFactory
{
    /** @var AbstractBitcoinNode */
    private $node;

    /**
     * KeyPairFactory constructor.
     * @param AbstractBitcoinNode $node
     */
    public function __construct(AbstractBitcoinNode $node)
    {
        $this->node = $node;
    }

    /**
     * @param int|null $bits
     * @return PrivateKey
     * @throws KeyPairException
     */
    public function generateSecurePrivateKey(?int $bits = null): PrivateKey
    {
        $byteLength = 32; // Generates 256 bit of Entropy by default
        $bitwiseLength = $bits ?? $this->node->const_private_key_bits;
        if (is_int($bitwiseLength)) {
            $byteLength = $bitwiseLength / 8;
        }

        try {
            $randomBytes = random_bytes($byteLength);
        } catch (\Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            throw new KeyPairException('Failed to generate cryptographically secure pseudo-random bytes');
        }

        $entropy = (new Binary($randomBytes))->base16();
        return new PrivateKey($this->node, $entropy, null);
    }

    /**
     * @param string|Base16 $entropy
     * @return PrivateKey
     */
    public function privateKeyFromEntropy($entropy): PrivateKey
    {
        if (!$entropy instanceof Base16) {
            if (!is_string($entropy) || !DataTypes::isBase16($entropy)) {
                throw new \InvalidArgumentException(
                    'Private key entropy must be Hexadecimal string or instance of Binary buffer'
                );
            }

            $entropy = new Base16($entropy);
        }

        return new PrivateKey($this->node, $entropy, null);
    }

    /**
     * @param Mnemonic $mnemonic
     * @param string|null $passphrase
     * @return PrivateKey
     */
    public function privateKeyFromMnemonic(Mnemonic $mnemonic, ?string $passphrase = null): PrivateKey
    {
        $byteLength = 0; // Default is 0, will get 512 bit entropy (entire Mnemonic seed)
        $bitwiseLength = $this->node->const_private_key_bits;
        if (is_int($bitwiseLength)) {
            $byteLength = $bitwiseLength / 4; // Divide by 4 for 2 hexits in each byte
        }

        $seed = $mnemonic->generateSeed($passphrase, $byteLength);
        if (!$seed instanceof Base16) {
            $seed = new Base16($seed);
        }

        return new PrivateKey($this->node, $seed, null);
    }

    /**
     * @param Base16 $publicKey
     * @return PublicKey
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     */
    public function publicKeyFromUncompressed(Base16 $publicKey): PublicKey
    {
        $curve = Curves::getInstanceOf($this->node->const_ecdsa_curve);
        return new PublicKey($this->node, null, $curve, $publicKey, false);
    }

    /**
     * @param Base16 $publicKey
     * @return PublicKey
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     */
    public function publicKeyFromCompressed(Base16 $publicKey): PublicKey
    {
        $curve = Curves::getInstanceOf($this->node->const_ecdsa_curve);
        return new PublicKey($this->node, null, $curve, $publicKey, true);
    }

    /**
     * @param $wif
     * @param bool $isCompressed
     * @param int|null $prefix
     * @return PrivateKey
     * @throws KeyPairException
     */
    public function import($wif, bool $isCompressed = true, ?int $prefix = null): PrivateKey
    {
        if (!$wif instanceof Base58Encoded) {
            if (!is_string($wif) || !$wif) {
                throw new \InvalidArgumentException('Private key import method requires first argument to be Base58Encoded buffer or a String');
            }

            $wif = new Base58Encoded($wif);
        }

        $prefix = $prefix ?? $this->node->const_wif_prefix;
        if (!is_int($prefix)) {
            throw new KeyPairException('WIF prefix constant not defined');
        }

        $privateKey = WIF::Decode($prefix, $wif, $isCompressed);
        return new PrivateKey($this->node, $privateKey, null);
    }
}