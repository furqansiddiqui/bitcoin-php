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

namespace FurqanSiddiqui\Bitcoin\Wallets\KeyPair;

use FurqanSiddiqui\Base58\Result\Base58Encoded;
use FurqanSiddiqui\BIP32\Extend\ExtendedKeyInterface;
use FurqanSiddiqui\BIP32\Extend\PublicKeyInterface;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Exception\KeyPairException;
use FurqanSiddiqui\Bitcoin\Serialize\WIF;
use FurqanSiddiqui\DataTypes\Binary;

/**
 * Class PrivateKey
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair
 */
class PrivateKey extends \FurqanSiddiqui\BIP32\KeyPair\PrivateKey
{
    /** @var AbstractBitcoinNode */
    private $node;

    /**
     * PrivateKey constructor.
     * @param AbstractBitcoinNode $node
     * @param Binary $entropy
     * @param ExtendedKeyInterface|null $extendedKey
     */
    public function __construct(AbstractBitcoinNode $node, Binary $entropy, ?ExtendedKeyInterface $extendedKey = null)
    {
        $this->node = $node;
        parent::__construct($entropy, $extendedKey);

        // Configure ECDSA Curve
        if ($this->node->const_ecdsa_curve) {
            $this->set("curve", $this->node->const_ecdsa_curve);
        }
    }

    /**
     * @return AbstractBitcoinNode
     */
    public function node(): AbstractBitcoinNode
    {
        return $this->node;
    }

    /**
     * @return PublicKey
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     * @throws \FurqanSiddiqui\ECDSA\Exception\GenerateVectorException
     * @throws \FurqanSiddiqui\ECDSA\Exception\MathException
     */
    public function publicKey(): PublicKeyInterface
    {
        if (!$this->publicKey instanceof PublicKey) {
            $this->publicKey = new PublicKey($this);
        }

        return $this->publicKey;
    }

    /**
     * @param int|null $prefix
     * @return Base58Encoded
     * @throws KeyPairException
     */
    public function wif(?int $prefix = null): Base58Encoded
    {
        $prefix = $prefix ?? $this->node()->const_wif_prefix;
        if (!is_int($prefix)) {
            throw new KeyPairException('WIF prefix constant not defined');
        }

        return WIF::Encode($prefix, $this->privateKey->get()->base16(), true);
    }

    /**
     * @param int|null $prefix
     * @return Base58Encoded
     * @throws KeyPairException
     */
    public function export(?int $prefix = null): Base58Encoded
    {
        return $this->wif($prefix);
    }
}