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

namespace FurqanSiddiqui\Bitcoin\Wallets;

use FurqanSiddiqui\Bitcoin\Bitcoin;

/**
 * Class Wallets
 * @package FurqanSiddiqui\Bitcoin\Wallets
 */
class Wallets
{
    public readonly KeyPairFactory $keyPair;
    public readonly HDFactory $hd;
    public readonly MnemonicFactory $mnemonics;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     */
    public function __construct(Bitcoin $btc)
    {
        $this->keyPair = new KeyPairFactory($btc);
        $this->hd = new HDFactory($btc);
        $this->mnemonics = new MnemonicFactory($btc);
    }
}
