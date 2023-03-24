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

use Comely\Buffer\Bytes20;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class P2PKH_Address
 * @package FurqanSiddiqui\Bitcoin\Address
 */
class P2PKH_Address extends AbstractBase58Address
{
    /** @var \Comely\Buffer\Bytes20 */
    private readonly Bytes20 $hash160;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param string $address
     * @param bool $prefixCheck
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey|null $publicKey
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function __construct(Bitcoin $btc, string $address, bool $prefixCheck = true, ?PublicKey $publicKey = null)
    {
        parent::__construct($btc, $address, $prefixCheck, $publicKey);
        $this->hash160 = new Bytes20(substr($this->raw->raw(), 1));
    }

    /**
     * @return void
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    protected function validateAddrPrefix(): void
    {
        if ($this->prefix !== $this->btc->network->p2pkh_prefix) {
            throw new PaymentAddressException(sprintf('P2PKH address does not belong to %s', $this->btc->network->name));
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
            ->OP_DUP()
            ->OP_HASH160()
            ->PUSHDATA($this->hash160)
            ->OP_EQUALVERIFY()
            ->OP_CHECKSIG();

        return $opCode->getScript();
    }
}
