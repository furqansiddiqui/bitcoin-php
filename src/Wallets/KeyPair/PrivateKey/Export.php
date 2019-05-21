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

namespace FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;

use FurqanSiddiqui\Base58\Result\Base58Encoded;
use FurqanSiddiqui\Bitcoin\Exception\KeyPairException;
use FurqanSiddiqui\Bitcoin\Serialize\WIF;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\ECDSA\OpenSSL\OpenSSL;

/**
 * Class Export
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey
 */
class Export
{
    /** @var PrivateKey */
    private $privateKey;

    /**
     * Export constructor.
     * @param PrivateKey $privateKey
     */
    public function __construct(PrivateKey $privateKey)
    {
        $this->privateKey = $privateKey;
    }

    /**
     * @param int|null $prefix
     * @return Base58Encoded
     * @throws KeyPairException
     */
    public function wif(?int $prefix = null): Base58Encoded
    {
        $prefix = $prefix ?? $this->privateKey->node()->const_wif_prefix;
        if (!is_int($prefix)) {
            throw new KeyPairException('WIF prefix constant not defined');
        }

        return WIF::Encode($prefix, $this->privateKey->raw()->get()->base16(), true);
    }

    /**
     * @return string
     */
    public function pem(): string
    {
        return OpenSSL::der2pem($this->privateKey->raw()->raw(), "EC PRIVATE KEY");
    }
}