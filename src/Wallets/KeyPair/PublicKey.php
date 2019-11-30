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
use FurqanSiddiqui\BIP32\Extend\PrivateKeyInterface;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Address\P2PKH_Address;
use FurqanSiddiqui\Bitcoin\Serialize\Base58Check;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey\Verifier;
use FurqanSiddiqui\ECDSA\ECC\EllipticCurveInterface;

/**
 * Class PublicKey
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair
 */
class PublicKey extends \FurqanSiddiqui\BIP32\KeyPair\PublicKey
{
    /** @var AbstractBitcoinNode */
    private $node;
    /** @var null|PrivateKey */
    protected $privateKey;
    /** @var null|Base16 */
    private $hash160;

    /**
     * PublicKey constructor.
     * @param AbstractBitcoinNode $node
     * @param PrivateKeyInterface|null $privateKey
     * @param EllipticCurveInterface|null $curve
     * @param Base16|null $publicKey
     * @param bool|null $pubKeyArgIsCompressed
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     */
    public function __construct(AbstractBitcoinNode $node, ?PrivateKeyInterface $privateKey, ?EllipticCurveInterface $curve = null, ?Base16 $publicKey = null, ?bool $pubKeyArgIsCompressed = null)
    {
        $this->node = $node;
        parent::__construct($privateKey, $curve, $publicKey, $pubKeyArgIsCompressed);
    }

    /**
     * @param AbstractBitcoinNode $network
     * @return PublicKey
     */
    public function setBitcoinNetworkInstance(AbstractBitcoinNode $network): self
    {
        $this->node = $network;
        return $this;
    }

    /**
     * @return AbstractBitcoinNode
     */
    public function node(): AbstractBitcoinNode
    {
        return $this->node;
    }

    /**
     * @return PrivateKey
     */
    public function privateKey(): PrivateKeyInterface
    {
        return parent::privateKey();
    }

    /**
     * @return Base16
     */
    public function hash160(): Base16
    {
        if (!$this->hash160) {
            $hash160 = $this->compressed()->clone();
            $hash160 = $hash160->binary()->hash()->sha256()
                ->hash()->ripeMd160();

            $this->hash160 = $hash160->base16();
            $this->hash160->readOnly(true);
        }

        return $this->hash160;
    }

    /**
     * @return Verifier
     */
    public function verify(): Verifier
    {
        return new Verifier($this);
    }

    /**
     * @param int|null $prefix
     * @return P2PKH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function p2pkh(?int $prefix = null): P2PKH_Address
    {
        $base58Check = Base58Check::getInstance();
        $prefix = $prefix ?? $this->node->const_p2pkh_prefix;

        $rawP2PKH = $this->hash160()->clone();
        if (is_int($prefix) && $prefix >= 0) {
            $rawP2PKH->prepend(dechex($prefix));
        }

        return new P2PKH_Address($this->node, $base58Check->encode($rawP2PKH)->value(), $this->hash160);
    }
}