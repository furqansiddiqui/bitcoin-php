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

namespace FurqanSiddiqui\Bitcoin\Address;

use FurqanSiddiqui\Bitcoin\Script\Script;

/**
 * Class Bech32_P2WPKH_Address
 * @package FurqanSiddiqui\Bitcoin\Address
 */
class Bech32_P2WPKH_Address extends Bech32Address
{
    /**
     * @return \FurqanSiddiqui\Bitcoin\Script\Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function scriptPubKey(): Script
    {
        $opCode = $this->btc->scripts->new()
            ->OP_0()
            ->PUSHDATA($this->raw);

        return $opCode->getScript();
    }
}
