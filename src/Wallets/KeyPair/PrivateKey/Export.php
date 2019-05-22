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
use FurqanSiddiqui\BIP32\ECDSA\Curves;
use FurqanSiddiqui\Bitcoin\Exception\KeyPairExportException;
use FurqanSiddiqui\Bitcoin\Serialize\WIF;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\ECDSA\OpenSSL\OpenSSL;
use FurqanSiddiqui\ECDSA\OpenSSL\PEM_Certificate;

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

        return WIF::Encode($prefix, $this->privateKey->raw()->encode()->base16()->hexits(), true);
    }

    /**
     * @return PEM_Certificate
     * @throws KeyPairExportException
     */
    public function pem(): PEM_Certificate
    {
        $curve = $this->privateKey->node()->const_ecdsa_curve;
        if (!$curve) {
            throw new KeyPairExportException('Cannot export key to PEM; ECDSA curve not set');
        }

        switch ($curve) {
            case Curves::SECP256K1:
            case Curves::SECP256K1_OPENSSL:
                return OpenSSL::Secp256k1_PrivateKeyPEM($this->privateKey->raw());
                break;
        }

        throw new KeyPairExportException(
            sprintf('Cannot export private key with curve "%s" to PEM', Curves::INDEX[$curve] ?? "Unknown")
        );
    }
}