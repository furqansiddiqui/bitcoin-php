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

use Comely\Buffer\AbstractByteArray;
use Comely\Buffer\Buffer;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Script\Script;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class Bech32Address
 * @package FurqanSiddiqui\Bitcoin\Address
 */
class Bech32Address extends AbstractPaymentAddress
{
    public readonly AbstractByteArray $raw;
    public readonly int $witnessProgram;
    public readonly string $witnessProgramBits;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param string $address
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey|null $publicKey
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function __construct(Bitcoin $btc, string $address, ?PublicKey $publicKey = null)
    {
        parent::__construct($btc, $address, $publicKey);

        try {
            $decoded = $this->btc->bech32->decode($address);
            $this->witnessProgramBits = array_shift($decoded);
            $this->witnessProgram = gmp_intval(gmp_init($this->witnessProgramBits, 2));
            $this->raw = Buffer::fromBase16(gmp_strval(gmp_init(implode("", $decoded), 2), 16))
                ->readOnly();
        } catch (\Throwable $t) {
            throw new PaymentAddressException(sprintf('%s; %s', get_class($t), $t->getMessage()));
        }
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Script\Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function scriptPubKey(): Script
    {
        throw new PaymentAddressException('Cannot create scriptPubKey from instance of Bech32Address; ' .
            'Use Bech32_P2WPKH_Address or Bech32_P2WSH_Address instead');
    }
}
