<?php
/**
 * This file is a part of "furqansiddiqui/bitcoin-php" package.
 * https://github.com/furqansiddiqui/bitcoin-php
 *
 * Copyright (c) 2019-2020 Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/furqansiddiqui/bitcoin-php/blob/master/LICENSE
 */

declare(strict_types=1);

namespace FurqanSiddiqui\Bitcoin\Wallets\HD;

use Comely\DataTypes\Buffer\Base16;
use Comely\DataTypes\Buffer\Binary;
use FurqanSiddiqui\BIP32\Extend\ExtendedKeyInterface;
use FurqanSiddiqui\BIP32\Extend\PrivateKeyInterface;
use FurqanSiddiqui\BIP32\Extend\PublicKeyInterface;
use FurqanSiddiqui\BIP32\ExtendedKey;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class ExtendedKeyPair
 * @package FurqanSiddiqui\Bitcoin\Wallets\HD
 */
class ExtendedKeyPair extends ExtendedKey
{
    /** @var AbstractBitcoinNode */
    private $node;

    /**
     * ExtendedKeyPair constructor.
     * @param AbstractBitcoinNode $node
     * @param Binary $seed
     * @param ExtendedKeyInterface|null $parent
     * @param Base16|null $childNumber
     * @throws \FurqanSiddiqui\BIP32\Exception\ExtendedKeyException
     */
    public function __construct(AbstractBitcoinNode $node, Binary $seed, ?ExtendedKeyInterface $parent = null, ?Base16 $childNumber = null)
    {
        $this->node = $node;
        parent::__construct($seed, $parent, $childNumber);

        // Configure ECDSA Curve
        if ($this->node->const_ecdsa_curve) {
            $this->set("curve", $this->node->const_ecdsa_curve);
        }
    }

    /**
     * @param $path
     * @return ExtendedKeyPair
     * @throws \FurqanSiddiqui\BIP32\Exception\ExtendedKeyException
     */
    public function derivePath($path): ExtendedKeyInterface
    {
        return parent::derivePath($path);
    }

    /**
     * @param int $index
     * @param bool $isHardened
     * @return ExtendedKeyPair
     * @throws \FurqanSiddiqui\BIP32\Exception\ChildKeyDeriveException
     * @throws \FurqanSiddiqui\BIP32\Exception\ExtendedKeyException
     */
    public function derive(int $index, bool $isHardened = false): ExtendedKeyInterface
    {
        $extendedKey = parent::derive($index, $isHardened);
        return new ExtendedKeyPair($this->node, $extendedKey->raw(), $this, $extendedKey->childNumber());
    }

    /**
     * @return PrivateKey
     */
    public function privateKey(): PrivateKeyInterface
    {
        if (!$this->privateKeyInstance instanceof PrivateKey) {
            $this->privateKeyInstance = new PrivateKey($this->node, $this->privateKey, $this);
        }

        return $this->privateKeyInstance;
    }

    /**
     * @return PublicKey
     */
    public function publicKey(): PublicKeyInterface
    {
        return parent::publicKey();
    }
}