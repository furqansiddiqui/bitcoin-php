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

namespace FurqanSiddiqui\Bitcoin\Wallets;

use FurqanSiddiqui\BIP39\Mnemonic;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\DataTypes\Base16;
use FurqanSiddiqui\DataTypes\Binary;
use FurqanSiddiqui\DataTypes\DataTypes;

/**
 * Class KeyPairFactory
 * @package FurqanSiddiqui\Bitcoin\Wallets
 */
class KeyPairFactory
{
    /** @var AbstractBitcoinNode */
    private $node;

    /**
     * KeyPairFactory constructor.
     * @param AbstractBitcoinNode $node
     */
    public function __construct(AbstractBitcoinNode $node)
    {
        $this->node = $node;
    }

    /**
     * @param string|Binary $entropy
     * @return PrivateKey
     */
    public function privateKeyFromEntropy($entropy): PrivateKey
    {
        if (!$entropy instanceof Binary) {
            if (!is_string($entropy) || !DataTypes::isBase16($entropy)) {
                throw new \InvalidArgumentException(
                    'Private key entropy must be Hexadecimal string on instance of Binary buffer'
                );
            }

            $entropy = new Base16($entropy);
        }

        return new PrivateKey($this->node, $entropy, null);
    }

    /**
     * @param Mnemonic $mnemonic
     * @param string|null $passphrase
     * @return PrivateKey
     */
    public function privateKeyFromMnemonic(Mnemonic $mnemonic, ?string $passphrase = null): PrivateKey
    {
        $byteLength = 0; // Default is 0, will get 512 bit entropy (entire Mnemonic seed)
        $bitwiseLength = $this->node->const_private_key_bits;
        if (is_int($bitwiseLength)) {
            $byteLength = $bitwiseLength / 4; // Divide by 4 for 2 hexits in each byte
        }

        $seed = $mnemonic->generateSeed($passphrase, $byteLength);
        return new PrivateKey($this->node, new Base16($seed), null);
    }
}