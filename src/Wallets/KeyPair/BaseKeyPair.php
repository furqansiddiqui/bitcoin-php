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

namespace FurqanSiddiqui\Bitcoin\Wallets\KeyPair;

use FurqanSiddiqui\BIP32\KeyPair\AbstractKeyPair;
use FurqanSiddiqui\BIP32\KeyPair\PrivateKeyInterface;
use FurqanSiddiqui\Bitcoin\Bitcoin;

/**
 * Class BaseKeyPair
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair
 */
class BaseKeyPair extends AbstractKeyPair
{
    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey|\FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey $key
     */
    public function __construct(
        public readonly Bitcoin $btc,
        PrivateKey|PublicKey    $key
    )
    {
        parent::__construct($this->btc->bip32, $key);
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey
     */
    public function publicKey(): PublicKey
    {
        if (!$this->pub) {
            $this->pub = new PublicKey($this->btc, $this->prv->eccPrivateKey->public());
        }

        return $this->pub;
    }

    /**
     * @return bool
     */
    public function hasPrivateKey(): bool
    {
        return isset($this->prv);
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey|\FurqanSiddiqui\BIP32\KeyPair\PrivateKeyInterface
     * @throws \FurqanSiddiqui\BIP32\Exception\KeyPairException
     */
    public function privateKey(): PrivateKey|PrivateKeyInterface
    {
        return parent::privateKey();
    }
}
