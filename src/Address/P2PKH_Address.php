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

namespace FurqanSiddiqui\Bitcoin\Address;

use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\DataTypes\Base16;

/**
 * Class P2PKH_Address
 * @package FurqanSiddiqui\Bitcoin\Address
 */
class P2PKH_Address extends AbstractPaymentAddress
{
    /**
     * P2PKH_Address constructor.
     * @param AbstractBitcoinNode|null $node
     * @param string $addr
     * @param Base16|null $crossCheckHash160
     * @throws PaymentAddressException
     */
    public function __construct(?AbstractBitcoinNode $node, string $addr, ?Base16 $crossCheckHash160 = null)
    {
        parent::__construct($node, $addr, $crossCheckHash160);

        // Prefix verify
        if ($node) {
            if ($this->prefix->hexits(false) !== dechex($node->const_p2pkh_prefix)) {
                throw new PaymentAddressException('Payment address P2PKH prefix does not match');
            }
        }
    }

    /**
     * @return Script|null
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function scriptPubKey(): ?Script
    {
        $opCode = $this->node->opCode()->new();
        $opCode->OP_DUP()
            ->OP_HASH160()
            ->PUSHDATA($this->hash160->binary())
            ->OP_EQUALVERIFY()
            ->OP_CHECKSIG();

        return $opCode->script();
    }
}