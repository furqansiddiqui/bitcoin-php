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

use Comely\DataTypes\Buffer\Binary;
use Comely\DataTypes\DataTypes;
use FurqanSiddiqui\BIP39\BIP39;
use FurqanSiddiqui\BIP39\Mnemonic;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;

/**
 * Class MnemonicFactory
 * @package FurqanSiddiqui\Bitcoin\Wallets
 */
class MnemonicFactory
{
    /** @var AbstractBitcoinNode */
    private $node;

    /**
     * MnemonicFactory constructor.
     * @param AbstractBitcoinNode $node
     */
    public function __construct(AbstractBitcoinNode $node)
    {
        $this->node = $node;
    }

    /**
     * @param int $wordCount
     * @return Mnemonic
     * @throws \FurqanSiddiqui\BIP39\Exception\MnemonicException
     * @throws \FurqanSiddiqui\BIP39\Exception\WordListException
     */
    public function generate(int $wordCount = 12): Mnemonic
    {
        return BIP39::Generate($wordCount);
    }

    /**
     * @param $entropy
     * @return Mnemonic
     * @throws \FurqanSiddiqui\BIP39\Exception\MnemonicException
     * @throws \FurqanSiddiqui\BIP39\Exception\WordListException
     */
    public function useEntropy($entropy): Mnemonic
    {
        if ($entropy instanceof Binary) {
            $entropy = $entropy->base16()->hexits();
        }

        if (!is_string($entropy) || !DataTypes::isBase16($entropy)) {
            throw new \InvalidArgumentException(
                'Mnemonic entropy must be Hexadecimal string or instance of Binary buffer'
            );
        }

        return BIP39::Entropy($entropy);
    }

    /**
     * @param $words
     * @return Mnemonic
     * @throws \FurqanSiddiqui\BIP39\Exception\MnemonicException
     * @throws \FurqanSiddiqui\BIP39\Exception\WordListException
     */
    public function fromWords($words): Mnemonic
    {
        return BIP39::Words($words, null, true);
    }
}