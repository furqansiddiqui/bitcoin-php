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

namespace FurqanSiddiqui\Bitcoin\Wallets;

use Comely\DataTypes\Buffer\Base16;
use Comely\DataTypes\Buffer\Binary;
use Comely\DataTypes\DataTypes;
use FurqanSiddiqui\BIP39\Mnemonic;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Exception\WalletsException;
use FurqanSiddiqui\Bitcoin\Wallets\HD\MasterKey;

/**
 * Class MasterKeyFactory
 * @package FurqanSiddiqui\Bitcoin\Wallets
 */
class MasterKeyFactory
{
    /** @var AbstractBitcoinNode */
    private $node;

    /**
     * MasterKeyFactory constructor.
     * @param AbstractBitcoinNode $node
     */
    public function __construct(AbstractBitcoinNode $node)
    {
        $this->node = $node;
    }

    /**
     * @param $seed
     * @return MasterKey
     * @throws WalletsException
     * @throws \FurqanSiddiqui\BIP32\Exception\ExtendedKeyException
     */
    public function useEntropyAsSeed($seed): MasterKey
    {
        if (!$seed instanceof Binary) {
            if (!is_string($seed) || !DataTypes::isBase16($seed)) {
                throw new \InvalidArgumentException(
                    'MKD/CKD entropy must be Hexadecimal string or instance of Binary buffer'
                );
            }

            $seed = new Base16($seed);
        }

        return new MasterKey($this->node, $seed, $this->definedHmacKey());
    }

    /**
     * @param Mnemonic $mnemonic
     * @return MasterKey
     * @throws WalletsException
     * @throws \FurqanSiddiqui\BIP32\Exception\ExtendedKeyException
     */
    public function useMnemonicEntropy(Mnemonic $mnemonic): MasterKey
    {
        return $this->useEntropyAsSeed($mnemonic->entropy);
    }

    /**
     * @param Mnemonic $mnemonic
     * @param string|null $passphrase
     * @return MasterKey
     * @throws \FurqanSiddiqui\BIP32\Exception\ExtendedKeyException
     */
    public function useMnemonicSeed(Mnemonic $mnemonic, ?string $passphrase = null): MasterKey
    {
        $seed = $mnemonic->generateSeed($passphrase);
        return new MasterKey($this->node, new Base16($seed), null); // Not applying HMAC
    }

    /**
     * @return string
     * @throws WalletsException
     */
    private function definedHmacKey(): string
    {
        $hmacKey = $this->node->const_bip32_seed_key;
        if (!$hmacKey || !is_string($hmacKey)) {
            throw new WalletsException('HMAC key not defined for MKD');
        }

        return $hmacKey;
    }
}