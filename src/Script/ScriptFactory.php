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
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class ScriptFactory
 * @package FurqanSiddiqui\Bitcoin\Script
 */
class ScriptFactory
{
    /** @var AbstractBitcoinNode */
    private $node;

    /**
     * ScriptFactory constructor.
     * @param AbstractBitcoinNode $node
     */
    public function __construct(AbstractBitcoinNode $node)
    {
        $this->node = $node;
    }

    /**
     * @return OpCode
     */
    public function new(): OpCode
    {
        return new OpCode($this->node);
    }

    /**
     * @param PublicKey $pubKey1
     * @param PublicKey $pubKey2
     * @param PublicKey $pubKey3
     * @return Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function multiSig2of3(PublicKey $pubKey1, PublicKey $pubKey2, PublicKey $pubKey3): Script
    {
        return $this->new()
            ->OP_2()
            ->PUSHDATA($pubKey1->compressed()->binary())
            ->PUSHDATA($pubKey2->compressed()->binary())
            ->PUSHDATA($pubKey3->compressed()->binary())
            ->OP_3()
            ->OP_CHECKMULTISIG()
            ->script();
    }

    /**
     * @param string $script
     * @return Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function script(string $script): Script
    {
        if (preg_match('/^[a-f0-9]+$/i', $script)) {
            return $this->fromSerialized($script);
        }

        return $this->fromRaw($script);
    }

    /**
     * @param string $script
     * @return Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function getScript(string $script): Script
    {
        return $this->script($script);
    }

    /**
     * @param string $serializedBase16Script
     * @return Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function fromSerialized(string $serializedBase16Script): Script
    {
        return Script::Decode($this->node, $serializedBase16Script);
    }

    /**
     * @param string $script
     * @return Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function fromRaw(string $script): Script
    {
        return new Script($this->node, $script);
    }
}