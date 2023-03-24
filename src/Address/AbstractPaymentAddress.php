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
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class AbstractPaymentAddress
 * @package FurqanSiddiqui\Bitcoin\Address
 */
abstract class AbstractPaymentAddress
{
    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param string $address
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey|null $publicKey
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function __construct(
        protected readonly Bitcoin $btc,
        public readonly string     $address,
        public ?PublicKey          $publicKey = null,
    )
    {
        if (!$this->address) {
            throw new PaymentAddressException('Invalid payment address');
        }
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Script\Script
     */
    abstract public function scriptPubKey(): Script;
}

