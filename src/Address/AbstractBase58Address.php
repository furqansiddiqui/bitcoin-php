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
use Comely\Buffer\BigInteger\LittleEndian;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class AbstractBase58Address
 * @package FurqanSiddiqui\Bitcoin\Address
 */
abstract class AbstractBase58Address extends AbstractPaymentAddress
{
    public readonly int $prefix;
    public readonly AbstractByteArray $raw;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param string $address
     * @param bool $prefixCheck
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey|null $publicKey
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function __construct(Bitcoin $btc, string $address, bool $prefixCheck = true, ?PublicKey $publicKey = null)
    {
        parent::__construct($btc, $address, $publicKey);

        try {
            $this->raw = $this->btc->bip32->base58->checkDecode($address);
            $this->prefix = gmp_intval(LittleEndian::GMP_Unpack($this->raw->pop(1, changeBuffer: false)));
        } catch (\Throwable $t) {
            throw new PaymentAddressException(sprintf('%s; %s', get_class($t), $t->getMessage()));
        }

        if ($prefixCheck) {
            $this->validateAddrPrefix();
        }
    }

    /**
     * @return void
     */
    abstract protected function validateAddrPrefix(): void;
}
