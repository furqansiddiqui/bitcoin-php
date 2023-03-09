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

use Comely\Buffer\BigInteger\LittleEndian;
use Comely\Buffer\Buffer;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Script\Script;

/**
 * Class AddressFactory
 * @package FurqanSiddiqui\Bitcoin\Address
 */
class AddressFactory
{
    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     */
    public function __construct(private readonly Bitcoin $btc)
    {
    }

    /**
     * @param string $address
     * @return P2PKH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function p2pkh(string $address): P2PKH_Address
    {
        return new P2PKH_Address($this->btc, $address);
    }

    /**
     * @param string $address
     * @return P2SH_Address
     * @throws PaymentAddressException
     */
    public function p2sh(string $address): P2SH_Address
    {
        return new P2SH_Address($this->btc, $address);
    }

    /**
     * @param string $address
     * @return \FurqanSiddiqui\Bitcoin\Address\AbstractPaymentAddress
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function address(string $address): AbstractPaymentAddress
    {
        // Could be a Base58 check address?
        try {
            $decoded = $this->btc->bip32->base58->checkDecode($address);
            $prefix = gmp_intval(LittleEndian::GMP_Unpack($decoded->pop(1, changeBuffer: false)));
            if ($prefix === $this->btc->network->p2pkh_prefix) {
                return new P2PKH_Address($this->btc, $address);
            } elseif ($prefix === $this->btc->network->p2sh_prefix) {
                return new P2SH_Address($this->btc, $address);
            }
        } catch (\Exception) {
        }

        // Todo: Bech32

        throw new PaymentAddressException('Could not identify given address as P2PKH/P2SH/Bech32');
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Script\Script $scriptPubKey
     * @return \FurqanSiddiqui\Bitcoin\Address\AbstractPaymentAddress
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function fromScriptPubKey(Script $scriptPubKey): AbstractPaymentAddress
    {
        $script16 = $scriptPubKey->buffer->toBase16();
        if (preg_match('/^76a914[a-f0-9]{40}88ac$/i', $script16)) {
            $raw = new Buffer(hex2bin(substr($script16, 6, 40)));
            $raw->prependUInt8($this->btc->network->p2pkh_prefix);
            return $this->p2pkh($this->btc->bip32->base58->checkEncode($raw));
        } elseif (preg_match('/^a914[a-f0-9]{40}87$/i', $script16)) {
            $raw = new Buffer(hex2bin(substr($script16, 4, 40)));
            $raw->prependUInt8($this->btc->network->p2sh_prefix);
            return $this->p2sh($this->btc->bip32->base58->checkEncode($raw));
        }

        throw new PaymentAddressException('Could not identify given ScriptPubKey as P2PKH/P2SH');
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Script\Script $redeemScript
     * @return \FurqanSiddiqui\Bitcoin\Address\P2SH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function fromRedeemScript(Script $redeemScript): P2SH_Address
    {
        $raw = $redeemScript->buffer->copy()
            ->prependUInt8($this->btc->network->p2sh_prefix);
        return new P2SH_Address($this->btc, $this->btc->bip32->base58->checkEncode($raw));
    }
}
