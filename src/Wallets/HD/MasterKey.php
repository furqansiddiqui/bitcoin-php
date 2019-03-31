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

namespace FurqanSiddiqui\Bitcoin\Wallets\HD;

use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\DataTypes\Binary;

/**
 * Class MasterKey
 * @package FurqanSiddiqui\Bitcoin\Wallets\HD
 */
class MasterKey extends ExtendedKeyPair
{
    /**
     * MasterKey constructor.
     * @param AbstractBitcoinNode $node
     * @param Binary $seed
     * @param string|null $hmacKey
     * @throws \FurqanSiddiqui\BIP32\Exception\ExtendedKeyException
     */
    public function __construct(AbstractBitcoinNode $node, Binary $seed, ?string $hmacKey = null)
    {
        if ($hmacKey) {
            $seed = $seed->hash()->hmac("sha512", $hmacKey);
        }

        parent::__construct($node, $seed, null);
    }
}