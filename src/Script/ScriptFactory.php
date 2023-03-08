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

namespace FurqanSiddiqui\Bitcoin\Script;

use Comely\Buffer\AbstractByteArray;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class ScriptFactory
 * @package FurqanSiddiqui\Bitcoin\Script
 */
class ScriptFactory
{
    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     */
    public function __construct(private readonly Bitcoin $btc)
    {
    }

    /**
     * @return OpCode
     */
    public function new(): OpCode
    {
        return new OpCode($this->btc);
    }

    /**
     * @param PublicKey $pubKey1
     * @param PublicKey $pubKey2
     * @param PublicKey $pubKey3
     * @return MultiSigScript
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function multiSig2of3(PublicKey $pubKey1, PublicKey $pubKey2, PublicKey $pubKey3): MultiSigScript
    {
        return $this->multiSig(2, $pubKey1, $pubKey2, $pubKey3);
    }

    /**
     * @param int $signaturesRequired
     * @param PublicKey ...$publicKeys
     * @return MultiSigScript
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function multiSig(int $signaturesRequired, PublicKey ...$publicKeys): MultiSigScript
    {
        return new MultiSigScript($this->btc, $signaturesRequired, ...$publicKeys);
    }

    /**
     * @param string|\Comely\Buffer\AbstractByteArray $script
     * @return \FurqanSiddiqui\Bitcoin\Script\Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function script(string|AbstractByteArray $script): Script
    {
        return Script::Decode($this->btc, $script);
    }
}
