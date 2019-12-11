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

namespace FurqanSiddiqui\Bitcoin\Transactions;

use Comely\DataTypes\Buffer\Base16;

/**
 * Class SerializedTransaction
 * @package FurqanSiddiqui\Bitcoin\Transactions
 */
class SerializedTransaction
{
    /** @var Base16 */
    private $serializedBase16;
    /** @var Base16 */
    private $hash;
    /** @var null|bool */
    private $isSigned;

    /**
     * SerializedTransaction constructor.
     * @param Base16 $serializedTxBase16
     * @param Base16|null $hash
     * @param bool|null $isSigned
     */
    public function __construct(Base16 $serializedTxBase16, ?Base16 $hash = null, ?bool $isSigned = null)
    {
        $this->serializedBase16 = $serializedTxBase16->readOnly(true);
        $this->isSigned = $isSigned;

        // Calculate hash
        $this->hash = $this->serializedBase16->binary()
            ->hash()->sha256()
            ->hash()->sha256()// SHA256 twice
            ->base16();

        // Compare hash?
        if ($hash) {
            if (!$this->hash->equals($hash)) {
                throw new \UnexpectedValueException('Serialized tx hash does not match with given arg hash');
            }
        }
    }

    /**
     * Is transaction signed? NULL indicates that we do not know, boolean indicates if TX is signed or not
     * @return bool|null
     */
    public function isSigned(): ?bool
    {
        return $this->isSigned;
    }

    /**
     * Get serialized transaction as Base16
     * @return Base16
     */
    public function get(): Base16
    {
        return $this->serializedBase16;
    }

    /**
     * Get hash of serialized transaction, this hash is signed with private key (if necessary)
     * @return Base16
     */
    public function hash(): Base16
    {
        return $this->hash;
    }
}