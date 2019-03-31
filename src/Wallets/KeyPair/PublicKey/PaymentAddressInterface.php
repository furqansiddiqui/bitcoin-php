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

namespace FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

use FurqanSiddiqui\Base58\Result\Base58Encoded;
use FurqanSiddiqui\DataTypes\Binary;

/**
 * Interface PaymentAddressInterface
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey
 */
interface PaymentAddressInterface
{
    /**
     * @return Binary
     */
    public function hash160(): Binary;

    /**
     * @return Base58Encoded
     */
    public function address(): Base58Encoded;
}