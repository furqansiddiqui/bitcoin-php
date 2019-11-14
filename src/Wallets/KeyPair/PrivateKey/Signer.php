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

    public function useUniqueK(Base16 $k): self
    {
        $this->randomK = $k;
        return $this;
    }

    public function message(string $message): Signature
    {

    }

    public function sign(Base16 $hash32Byte): Signature
    {

    }
}