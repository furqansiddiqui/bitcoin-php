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

namespace FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

use Comely\DataTypes\Buffer\Base16;
use Comely\DataTypes\Buffer\Base64;
use FurqanSiddiqui\BIP32\ECDSA\Curves;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;
use FurqanSiddiqui\ECDSA\Signature\Signature;

/**
 * Class Verifier
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey
 */
class Verifier
{
    /** @var PublicKey */
    private $publicKey;

    /**
     * Verifier constructor.
     * @param PublicKey $publicKey
     */
    public function __construct(PublicKey $publicKey)
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @param Base64 $signed
     * @param string $message
     * @return bool
     */
    public function message(Base64 $signed, string $message): bool
    {
        $rawSignature = $signed->binary()->readOnly(true);
        $signature = new Signature(
            $rawSignature->copy(1, 32)->base16(),
            $rawSignature->copy(33)->base16()
        );

        $ecCurve = Curves::getInstanceOf($this->publicKey->getEllipticCurveId());
        return $ecCurve->verify(
            $this->publicKey->getEllipticCurvePubKeyObj(),
            $signature,
            $this->publicKey->node()->messages()->msgHash($message)
        );
    }

    /**
     * @param Base16 $r
     * @param Base16 $s
     * @param Base16 $hash32Byte
     * @return bool
     */
    public function hash32(Base16 $r, Base16 $s, Base16 $hash32Byte): bool
    {
        $signature = new Signature($r, $s);
        $ecCurve = Curves::getInstanceOf($this->publicKey->getEllipticCurveId());
        return $ecCurve->verify($this->publicKey->getEllipticCurvePubKeyObj(), $signature, $hash32Byte);
    }
}