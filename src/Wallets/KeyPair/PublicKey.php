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

namespace FurqanSiddiqui\Bitcoin\Wallets\KeyPair;

use Comely\Buffer\Buffer;
use Comely\Buffer\Bytes20;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Address\P2PKH_Address;
use FurqanSiddiqui\Bitcoin\Address\P2SH_Address;
use FurqanSiddiqui\Bitcoin\Address\P2SH_P2WPKH_Address;

/**
 * Class PublicKey
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair
 */
class PublicKey extends \FurqanSiddiqui\BIP32\KeyPair\PublicKey
{
    public readonly Bytes20 $hash160;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param \FurqanSiddiqui\ECDSA\ECC\PublicKey $eccPublicKey
     */
    public function __construct(
        public readonly Bitcoin             $btc,
        \FurqanSiddiqui\ECDSA\ECC\PublicKey $eccPublicKey
    )
    {
        parent::__construct($this->btc->bip32, $eccPublicKey);
        $this->hash160 = $this->btc->network->hash160($this->compressed());
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Address\P2PKH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function p2pkh(): P2PKH_Address
    {
        $rawP2PKH = (new Buffer($this->hash160->raw()))->prependUInt8($this->btc->network->p2pkh_prefix);
        return new P2PKH_Address($this->btc, $this->btc->bip32->base58->checkEncode($rawP2PKH));
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Address\P2SH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function p2sh(): P2SH_Address
    {
        $redeemScript = $this->btc->scripts->new()
            ->PUSHDATA($this->compressed())
            ->OP_CHECKSIG()
            ->getScript();

        return $this->btc->address->fromRedeemScript($redeemScript);
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Address\P2SH_P2WPKH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function p2sh_P2WPKH(): P2SH_P2WPKH_Address
    {
        $redeemScript = $this->btc->scripts->new()
            ->OP_0()
            ->PUSHDATA($this->hash160)
            ->getScript();

        $p2sh = $this->btc->address->fromRedeemScript($redeemScript);
        return new P2SH_P2WPKH_Address($this->btc, $p2sh->address);
    }
}
