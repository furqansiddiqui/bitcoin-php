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

namespace FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;

use FurqanSiddiqui\Base58\Result\Base58Encoded;
use FurqanSiddiqui\Bitcoin\Exception\KeyPairExportException;
use FurqanSiddiqui\Bitcoin\Serialize\Base58Check;
use FurqanSiddiqui\Bitcoin\Serialize\WIF;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;

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
     * @throws KeyPairExportException
     */
    public function wif(?int $prefix = null): Base58Encoded
    {
        $prefix = $prefix ?? $this->privateKey->node()->const_wif_prefix;
        if (!is_int($prefix)) {
            throw new KeyPairExportException('WIF prefix constant not defined');
        }

        return WIF::Encode($prefix, $this->privateKey->base16()->hexits(), true);
    }

    /**
     * @param int|null $prefix
     * @return Base58Encoded
     * @throws KeyPairExportException
     */
    public function hd(?int $prefix = null): Base58Encoded
    {
        return $this->bip32($prefix);
    }

    /**
     * @param int|null $prefix
     * @return Base58Encoded
     * @throws KeyPairExportException
     */
    public function bip32(?int $prefix = null): Base58Encoded
    {
        $prefix = $prefix ?? $this->privateKey->node()->const_bip32_private_prefix;
        if (!is_int($prefix)) {
            throw new KeyPairExportException('BIP32 private key prefix constant not defined');
        }

        $ekd = $this->privateKey->ekd();
        if (!$ekd) {
            throw new KeyPairExportException('This private key is not HD/BIP32 based');
        }

        return Base58Check::getInstance()->encode($ekd->serializePrivateKey($prefix)->base16());
    }
}