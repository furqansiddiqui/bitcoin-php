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

namespace FurqanSiddiqui\Bitcoin\Wallets\KeyPair;

use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey\P2PKH_Address;

/**
 * Class PublicKey
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair
 */
class PublicKey extends \FurqanSiddiqui\BIP32\KeyPair\PublicKey
{
    /**
     * @param int $prefix
     * @return P2PKH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\AddressGenerateException
     */
    public function p2pkh(int $prefix): P2PKH_Address
    {
        return new P2PKH_Address($this, $prefix);
    }
}