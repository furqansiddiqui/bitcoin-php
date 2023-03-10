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

namespace FurqanSiddiqui\Bitcoin\Wallets\KeyPair;

use Comely\Buffer\Buffer;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Protocol\Signature;
use FurqanSiddiqui\Bitcoin\Transactions\SerializedTransaction;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction;
use FurqanSiddiqui\ECDSA\KeyPair;

/**
 * Class PrivateKey
 * @package FurqanSiddiqui\Bitcoin\Wallets\KeyPair
 */
class PrivateKey extends \FurqanSiddiqui\BIP32\KeyPair\PrivateKey
{
    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param \FurqanSiddiqui\ECDSA\KeyPair $eccPrivateKey
     */
    public function __construct(
        public readonly Bitcoin $btc,
        KeyPair                 $eccPrivateKey,
    )
    {
        parent::__construct($this->btc->bip32, $eccPrivateKey);
    }

    /**
     * @return string
     */
    public function exportWIF(): string
    {
        $raw = (new Buffer($this->eccPrivateKey->private->raw()))
            ->append("\01") // Compressed Public Key Flag
            ->prependUInt8($this->btc->network->wif_prefix); // Network prefix

        return $this->btc->base58()->checkEncode($raw);
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Transactions\SerializedTransaction $txn
     * @return \FurqanSiddiqui\Bitcoin\Protocol\Signature
     * @throws \FurqanSiddiqui\ECDSA\Exception\SignatureException
     */
    public function signTransaction(SerializedTransaction $txn): Signature
    {
        return new Signature($this->eccPrivateKey->signRecoverable($txn->hash));
    }
}
