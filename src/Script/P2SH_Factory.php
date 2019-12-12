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

namespace FurqanSiddiqui\Bitcoin\Script;

use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Address\P2SH_Address;
use FurqanSiddiqui\Bitcoin\Serialize\Base58Check;

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
     * @param Script $redeemScript
     * @return P2SH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function fromRedeemScript(Script $redeemScript): P2SH_Address
    {
        $base58Check = Base58Check::getInstance();
        $prefix = $this->usePrefix ?? $this->node->const_p2sh_prefix;
        $hash160 = $redeemScript->hash160()->copy();
        if (is_int($prefix) && $prefix >= 0) {
            $hash160->prepend(dechex($prefix));
        }

        return new P2SH_Address($this->node, $base58Check->encode($hash160)->value(), $redeemScript->hash160()->copy(), $redeemScript);
    }
}