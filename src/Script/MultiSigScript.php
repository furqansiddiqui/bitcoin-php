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

namespace FurqanSiddiqui\Bitcoin\Script;

use Comely\Buffer\Buffer;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Address\P2SH_Address;
use FurqanSiddiqui\Bitcoin\Address\P2SH_P2WSH_Address;
use FurqanSiddiqui\Bitcoin\Transactions\Transaction;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey;

/**
 * Class MultiSigScript
 * @package FurqanSiddiqui\Bitcoin\Script
 */
class MultiSigScript
{
    public readonly int $total;
    private array $publicKeys = [];
    public readonly Script $redeemScript;
    private array $privateKeys = [];

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param int $required
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PublicKey ...$publicKeys
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function __construct(
        public readonly Bitcoin $btc,
        public readonly int     $required,
        PublicKey               ...$publicKeys)
    {
        if (!$required) {
            throw new \InvalidArgumentException('Invalid required number of signatures for MultiSig script');
        }

        $totalCount = 0;
        foreach ($publicKeys as $publicKey) {
            $this->publicKeys[] = $publicKey;
            $totalCount++;
        }

        $this->total = $totalCount;
        if ($this->required > $this->total) {
            throw new \InvalidArgumentException('Required signatures count cannot exceed total public keys');
        }

        if ($this->required > 14 || $this->total > 14) {
            throw new \InvalidArgumentException('Too many signatures/public keys');
        }

        // Create RedeemScript
        $opCode = $this->btc->scripts->new();
        $opCode->OP(sprintf('OP_%d', $this->required));
        /** @var PublicKey $publicKey */
        foreach ($this->publicKeys as $publicKey) {
            $opCode->PUSHDATA($publicKey->compressed());
        }

        $opCode->OP(sprintf('OP_%d', $this->total));
        $opCode->OP_CHECKMULTISIG();
        $this->redeemScript = $opCode->getScript();
    }

    /**
     * @return P2SH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function p2sh(): P2SH_Address
    {
        return $this->btc->address->fromRedeemScript($this->redeemScript);
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Address\P2SH_P2WSH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function segWit(): P2SH_P2WSH_Address
    {
        $redeemScriptHash = $this->btc->scripts->new()
            ->OP_0()
            ->PUSHDATA(new Buffer(hash("sha256", $this->redeemScript->buffer->raw())))
            ->getScript();

        $p2sh = $this->btc->address->fromRedeemScript($redeemScriptHash);
        return new P2SH_P2WSH_Address($this->btc, $p2sh->address);
    }

    /**
     * @return \FurqanSiddiqui\Bitcoin\Address\P2SH_P2WSH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function p2sh_P2WSH(): P2SH_P2WSH_Address
    {
        return $this->segWit();
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Wallets\KeyPair\PrivateKey $pK
     * @return $this
     */
    public function addPrivateKey(PrivateKey $pK): static
    {
        $this->privateKeys[] = $pK;
        return $this;
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Transactions\Transaction $tx
     * @param int $inputIndex
     * @param string|null $appendHashCodeByte
     * @return array
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\TransactionInputSignException
     * @throws \FurqanSiddiqui\ECDSA\Exception\SignatureException
     */
    public function signTransaction(Transaction $tx, int $inputIndex, ?string $appendHashCodeByte = "\1"): array
    {
        $signatures = [];
        /** @var PrivateKey $privateKey */
        foreach ($this->privateKeys as $privateKey) {
            $sign = $privateKey->signTransaction($tx->hashPreImage($inputIndex))->getBitcoinSignature();
            if ($appendHashCodeByte) {
                $sign->append($appendHashCodeByte);
            }

            $signatures[] = $sign;
        }

        return $signatures;
    }
}
