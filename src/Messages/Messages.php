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

namespace FurqanSiddiqui\Bitcoin\Messages;

use Comely\DataTypes\Buffer\Base16;
use Comely\DataTypes\Buffer\Base64;
use Comely\DataTypes\Buffer\Binary;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Protocol\VarInt;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class Messages
 * @package FurqanSiddiqui\Bitcoin\Messages
 */
class Messages
{
    /** @var AbstractBitcoinNode */
    private $node;

    /**
     * Messages constructor.
     * @param AbstractBitcoinNode $node
     */
    public function __construct(AbstractBitcoinNode $node)
    {
        $this->node = $node;
    }

    /**
     * @param string $message
     * @return Base16
     */
    public function msgHash(string $message): Base16
    {
        $signedMessagePrefix = $this->node->const_signed_message_prefix;
        $signedMessagePrefixLen = strlen($signedMessagePrefix);
        $messageLen = strlen($message);

        $buffer = new Binary();
        $buffer->append(hex2bin(VarInt::Encode($signedMessagePrefixLen)));
        $buffer->append($signedMessagePrefix);
        $buffer->append(hex2bin(VarInt::Encode($messageLen)));
        $buffer->append($message);

        $digest = $buffer->hash()->digest("sha256", 2);
        return $digest->base16();
    }

    /**
     * @param PrivateKey $privateKey
     * @param string $message
     * @return SignedMessage
     * @throws \FurqanSiddiqui\BIP32\Exception\PublicKeyException
     */
    public function sign(PrivateKey $privateKey, string $message): SignedMessage
    {
        return $privateKey->sign()->message($message);
    }

    /**
     * @param PublicKey $publicKey
     * @param Base64 $signature
     * @param string $message
     * @return bool
     */
    public function verify(PublicKey $publicKey, Base64 $signature, string $message): bool
    {
        return $publicKey->verify()->message($signature, $message);
    }
}
