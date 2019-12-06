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

use Comely\DataTypes\Buffer\Base16;
use FurqanSiddiqui\BIP32\ECDSA\Curves;
use FurqanSiddiqui\Bitcoin\Messages\SignedMessage;
use FurqanSiddiqui\Bitcoin\Transactions\SerializedTransaction;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;

/**
 * Class Signer
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey
 */
class Signer
{
    /** @var PrivateKey */
    private $privateKey;
    /** @var null|Base16 */
    private $randomK;

    /**
     * Signer constructor.
     * @param PrivateKey $privateKey
     */
    public function __construct(PrivateKey $privateKey)
    {
        $this->privateKey = $privateKey;
    }

    /**
     * @param Base16 $k
     * @return $this
     */
    public function useUniqueK(Base16 $k): self
    {
        $this->randomK = $k;
        return $this;
    }

    /**
     * @param string $message
     * @return SignedMessage
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     */
    public function message(string $message): SignedMessage
    {
        $signed = $this->hash32($this->privateKey->node()->messages()->msgHash($message));
        $signed->message = $message;
        return $signed;
    }

    /**
     * @param SerializedTransaction $tx
     * @return Base16
     */
    public function transaction(SerializedTransaction $tx): Base16
    {
        $ecCurve = Curves::getInstanceOf($this->privateKey->getEllipticCurveId());
        $signature = $ecCurve->sign($this->privateKey->base16(), $tx->hash());
        return $signature->getDER();
    }

    /**
     * @param Base16 $hash32Byte
     * @return SignedMessage
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     */
    public function hash32(Base16 $hash32Byte): SignedMessage
    {
        $ecCurve = Curves::getInstanceOf($this->privateKey->getEllipticCurveId());
        $signature = $ecCurve->sign($this->privateKey->base16(), $hash32Byte);

        // Find recovery id
        $flag = $ecCurve->findRecoveryId(
            $this->privateKey->publicKey()->getEllipticCurvePubKeyObj(),
            $signature,
            $hash32Byte,
            true
        );

        // SignedMessage
        $signedMessage = new SignedMessage();
        $signedMessage->msgHash = $hash32Byte;
        $signedMessage->signature = base64_encode(implode("", [
            chr($flag),
            $signature->r()->binary()->raw(),
            $signature->s()->binary()->raw(),
        ]));

        return $signedMessage;
    }
}