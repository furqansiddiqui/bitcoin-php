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

use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Script\Script;

/**
 * Class P2SH_Address
 * @package FurqanSiddiqui\Bitcoin\Address
 */
class P2SH_Address extends AbstractBase58Address
{
    private ?Script $redeemScript;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param string $address
     * @param bool $prefixCheck
     * @param \FurqanSiddiqui\Bitcoin\Script\Script|null $redeemScript
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function __construct(Bitcoin $btc, string $address, bool $prefixCheck = true, ?Script $redeemScript = null)
    {
        parent::__construct($btc, $address, $prefixCheck);
        $this->redeemScript = $redeemScript;
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Script\Script $redeemScript
     * @return $this
     */
    public function setRedeemScript(Script $redeemScript): static
    {
        $this->redeemScript = $redeemScript;
        return $this;
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Script\Script|null
     */
    public function getRedeemScript(): ?Script
    {
        return $this->redeemScript;
    }

    /**
     * @return void
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    protected function validateAddrPrefix(): void
    {
        if ($this->prefix !== $this->btc->network->p2sh_prefix) {
            throw new PaymentAddressException(sprintf('P2SH address does not belong to %s', $this->btc->network->name));
        }
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Script\Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function scriptPubKey(): Script
    {
        $opCode = $this->btc->scripts->new()
            ->OP_HASH160()
            ->PUSHDATA($this->raw)
            ->OP_EQUAL();

        return $opCode->getScript();
    }
}
