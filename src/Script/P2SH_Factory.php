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

namespace FurqanSiddiqui\Bitcoin\Script;

use Comely\DataTypes\Buffer\Base16;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Address\P2SH_Address;
use FurqanSiddiqui\Bitcoin\Serialize\Base58Check;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class P2SH_Factory
 * @package FurqanSiddiqui\Bitcoin\Script
 */
class P2SH_Factory
{
    /** @var AbstractBitcoinNode */
    private $node;
    /** @var null|int */
    private $usePrefix;

    /**
     * P2SH_Factory constructor.
     * @param AbstractBitcoinNode $node
     */
    public function __construct(AbstractBitcoinNode $node)
    {
        $this->node = $node;
    }

    /**
     * @param int|null $prefix
     * @return P2SH_Factory
     */
    public function usePrefix(?int $prefix): self
    {
        $this->usePrefix = $prefix;
        return $this;
    }

    /**
     * @param Script $script
     * @return P2SH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function fromScript(Script $script): P2SH_Address
    {
        $base58Check = Base58Check::getInstance();
        $prefix = $this->usePrefix ?? $this->node->const_p2sh_prefix;

        $rawP2SH = $script->hash160()->clone();
        if ($prefix && $prefix > 0) {
            $rawP2SH->prepend(dechex($prefix));
        }

        return new P2SH_Address($this->node, $base58Check->encode($rawP2SH)->value(), $script->hash160(), $script);
    }

    /**
     * @param string $hash160
     * @return P2SH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function fromHash160(string $hash160): P2SH_Address
    {
        $hash160 = new Base16($hash160);
        if ($hash160->binary()->size()->bits() !== 160) {
            throw new \LengthException('Hash160 for P2SH constructor must be a valid 160-bit hexadecimal string');
        }

        $base58Check = Base58Check::getInstance();
        $prefix = $this->usePrefix ?? $this->node->const_p2sh_prefix;

        $rawP2SH = $hash160->clone();
        if ($prefix && $prefix > 0) {
            $rawP2SH->prepend(dechex($prefix));
        }

        return new P2SH_Address($this->node, $base58Check->encode($rawP2SH)->value(), $hash160);
    }

    public function fromPublicKey(PublicKey $publicKey): P2SH_Address
    {

    }

    public function multiSig1of1(PublicKey $publicKey): P2SH_Address
    {

    }

    public function multiSig(int $total, int $req, PublicKey ...$publicKeys): P2SH_Address
    {

    }
}