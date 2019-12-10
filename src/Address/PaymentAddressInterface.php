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

/**
 * Interface PaymentAddressInterface
 * @package FurqanSiddiqui\Bitcoin\Address
 */
interface PaymentAddressInterface
{
    /**
     * @return string
     */
    public function address(): string;

    /**
     * @return string
     */
    public function getAddress(): string;

    /**
     * @return Base16|null
     */
    public function prefix(): ?Base16;
}