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

namespace FurqanSiddiqui\Bitcoin\Address;

use Comely\DataTypes\Buffer\Base16;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Script\Script;

/**
 * Class P2SH_Address
 * @package FurqanSiddiqui\Bitcoin\Address
 */
class P2SH_Address extends AbstractPaymentAddress
{
    protected const TYPE = "p2sh";

    /** @var Script|null */
    private $redeemScript;

    /**
     * P2SH_Address constructor.
     * @param AbstractBitcoinNode|null $node
     * @param string $addr
     * @param Base16|null $crossCheckHash160
     * @param Script|null $redeemScript
     * @throws PaymentAddressException
     */
    public function __construct(?AbstractBitcoinNode $node, string $addr, ?Base16 $crossCheckHash160 = null, ?Script $redeemScript = null)
    {
        parent::__construct($node, $addr, $crossCheckHash160);

        // Prefix verify
        if ($node) {
            $nodeP2SH_Prefix = dechex($node->const_p2sh_prefix);
            if (strlen($nodeP2SH_Prefix) % 2 !== 0) {
                $nodeP2SH_Prefix = "0" . $nodeP2SH_Prefix;
            }

            if ($this->prefix->hexits(false) !== $nodeP2SH_Prefix) {
                throw new PaymentAddressException('Payment address P2SH prefix does not match');
            }
        }

        // Redeem Script
        $this->redeemScript = $redeemScript;
    }

    /**
     * @param Script $redeemScript
     * @return $this
     */
    public function setRedeemScript(Script $redeemScript): self
    {
        $this->redeemScript = $redeemScript;
        return $this;
    }

    /**
     * @return Script|null
     */
    public function getRedeemScript(): ?Script
    {
        return $this->redeemScript;
    }

    /**
     * @return Script|null
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function scriptPubKey(): ?Script
    {
        $opCode = $this->node->opCode()->new();
        $opCode->OP_HASH160()
            ->PUSHDATA($this->hash160()->binary())
            ->OP_EQUAL();

        return $opCode->script();
    }
}