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

use Comely\DataTypes\Buffer\Base16;
use FurqanSiddiqui\BIP32\Extend\ExtendedKeyInterface;
use FurqanSiddiqui\BIP32\Extend\PublicKeyInterface;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey\Export;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey\Signer;

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
     * @param Base16 $entropy
     * @param ExtendedKeyInterface|null $extendedKey
     */
    public function __construct(AbstractBitcoinNode $node, Base16 $entropy, ?ExtendedKeyInterface $extendedKey = null)
    {
        $this->node = $node;
        parent::__construct($entropy, $extendedKey);

        // Configure ECDSA Curve
        if (!$extendedKey && $this->node->const_ecdsa_curve) {
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
     */
    public function publicKey(): PublicKeyInterface
    {
        if (!$this->publicKey instanceof PublicKey) {
            $this->publicKey = new PublicKey($this->node, $this);
        }

        return $this->publicKey;
    }

    /**
     * @return Signer
     */
    public function sign(): Signer
    {
        return new Signer($this);
    }

    /**
     * @return Export
     */
    public function export(): Export
    {
        return new Export($this);
    }
}