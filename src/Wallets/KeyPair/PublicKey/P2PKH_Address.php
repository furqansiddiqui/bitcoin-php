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

use FurqanSiddiqui\Base58\Base58Check;
use FurqanSiddiqui\Base58\Result\Base58Encoded;
use FurqanSiddiqui\Bitcoin\Exception\AddressGenerateException;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;
use FurqanSiddiqui\DataTypes\Binary;

/**
 * Class P2PKH_Address
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey
 */
class P2PKH_Address implements PaymentAddressInterface
{
    /** @var PublicKey */
    private $publicKey;
    /** @var int */
    private $prefix;
    /** @var Base58Check */
    private $base58check;
    /** @var Binary */
    private $hash160;

    /**
     * P2PKH_Address constructor.
     * @param PublicKey $publicKey
     * @param int $prefix
     * @throws AddressGenerateException
     */
    public function __construct(PublicKey $publicKey, int $prefix)
    {
        $this->publicKey = $publicKey;
        $this->usePrefix($prefix);
        $this->base58check = new Base58Check();
    }

    /**
     * @param int|null $prefix
     * @return P2SH_Address
     * @throws AddressGenerateException
     */
    public function p2sh(?int $prefix = null): P2SH_Address
    {
        $prefix = $prefix ?? $this->publicKey->privateKey()->node()->const_p2sh_prefix;
        if (!is_int($prefix)) {
            throw new AddressGenerateException('P2SH prefix constant not defined');
        }

        return new P2SH_Address($this, $prefix);
    }

    /**
     * @param int $prefix
     * @return P2PKH_Address
     * @throws AddressGenerateException
     */
    public function usePrefix(int $prefix): self
    {
        if ($prefix < 0) {
            throw new AddressGenerateException('P2PKH prefix must be a positive integer');
        }

        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @return int
     */
    public function prefix(): int
    {
        return $this->prefix;
    }

    /**
     * @return Base58Check
     */
    public function base58Check(): Base58Check
    {
        return $this->base58check;
    }

    /**
     * @return Binary
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     */
    public function hash160(): Binary
    {
        if (!$this->hash160) {
            $hash160 = $this->publicKey->compressed()->copy();
            $hash160->hash()->sha256()
                ->hash()->ripeMd160();

            $this->hash160 = $hash160;
            $this->hash160->readOnly(true);
        }

        return $this->hash160;
    }

    /**
     * @return Base58Encoded
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     */
    public function address(): Base58Encoded
    {
        $prefixHexits = dechex($this->prefix);
        if (strlen($prefixHexits) % 2 !== 0) {
            $prefixHexits = "0" . $prefixHexits;
        }

        return $this->base58check->encode($prefixHexits . $this->hash160()->get()->base16(false));
    }
}