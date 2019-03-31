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

use FurqanSiddiqui\BIP32\Extend\PrivateKeyInterface;
use FurqanSiddiqui\Bitcoin\Exception\AddressGenerateException;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey\P2PKH_Address;

/**
 * Class PublicKey
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair
 */
class PublicKey extends \FurqanSiddiqui\BIP32\KeyPair\PublicKey
{
    /** @var PrivateKey */
    protected $privateKey;

    /**
     * PublicKey constructor.
     * @param PrivateKeyInterface $keyPair
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     * @throws \FurqanSiddiqui\ECDSA\Exception\GenerateVectorException
     * @throws \FurqanSiddiqui\ECDSA\Exception\MathException
     */
    public function __construct(PrivateKeyInterface $keyPair)
    {
        if (!$keyPair instanceof PrivateKey) {
            throw new \InvalidArgumentException('Public key instance can only be constructed via internal Private key');
        }

        parent::__construct($keyPair);
    }

    /**
     * @return P2PKH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\AddressGenerateException
     */
    public function p2pkh(): P2PKH_Address
    {
        $prefix = $this->privateKey->node()->const_p2pkh_prefix;
        if (!is_int($prefix)) {
            throw new AddressGenerateException('P2PKH constant not defined');
        }

        return new P2PKH_Address($this, $prefix);
    }
}