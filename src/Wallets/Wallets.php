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

namespace FurqanSiddiqui\Bitcoin\Wallets;

use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;

/**
 * Class Wallets
 * @package FurqanSiddiqui\Bitcoin\Wallets
 */
class Wallets
{
    /** @var AbstractBitcoinNode */
    private $node;
    /** @var KeyPairFactory */
    private $keyPairFactory;

    /**
     * Wallets constructor.
     * @param AbstractBitcoinNode $node
     */
    public function __construct(AbstractBitcoinNode $node)
    {
        $this->node = $node;
        $this->keyPairFactory = new KeyPairFactory($node);
    }

    /**
     * @return KeyPairFactory
     */
    public function keyPair(): KeyPairFactory
    {
        return $this->keyPairFactory;
    }


    public function hd()
    {

    }
}