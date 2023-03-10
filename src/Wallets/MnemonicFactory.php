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

namespace FurqanSiddiqui\Bitcoin\Wallets;

use Comely\Buffer\Buffer;
use FurqanSiddiqui\BIP39\BIP39;
use FurqanSiddiqui\BIP39\Mnemonic;

/**
 * Class MnemonicFactory
 * @package FurqanSiddiqui\Bitcoin\Wallets
 */
class MnemonicFactory
{
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
     * @param \Comely\Buffer\Buffer $entropy
     * @param string $wordList
     * @return \FurqanSiddiqui\BIP39\Mnemonic
     * @throws \FurqanSiddiqui\BIP39\Exception\MnemonicException
     * @throws \FurqanSiddiqui\BIP39\Exception\WordListException
     */
    public function useEntropy(Buffer $entropy, string $wordList = "english"): Mnemonic
    {
        return BIP39::Entropy($entropy->toBase16(), $wordList);
    }

    /**
     * @param array|string $words
     * @param string $wordList
     * @return \FurqanSiddiqui\BIP39\Mnemonic
     * @throws \FurqanSiddiqui\BIP39\Exception\MnemonicException
     * @throws \FurqanSiddiqui\BIP39\Exception\WordListException
     */
    public function fromWords(array|string $words, string $wordList = "english"): Mnemonic
    {
        return BIP39::Words($words, $wordList, true);
    }
}
