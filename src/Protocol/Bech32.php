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

namespace FurqanSiddiqui\Bitcoin\Protocol;

use Comely\Buffer\AbstractByteArray;
use Comely\Buffer\Buffer;
use FurqanSiddiqui\Bitcoin\Bitcoin;

/**
 * Class Bech32
 * @package FurqanSiddiqui\Bitcoin\Protocol
 */
class Bech32
{
    /** @var int[] */
    public const GENERATOR = [0x3b6a57b2, 0x26508e6d, 0x1ea119fa, 0x3d4233dd, 0x2a1462b3];
    /** @var int */
    public const SEPARATOR = 1;

    /** @var string */
    private readonly string $hrp;

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     */
    public function __construct(private readonly Bitcoin $btc)
    {
    }

    /**
     * @return string
     */
    public function hrpBytes(): string
    {
        if (isset($this->hrp)) {
            return $this->hrp;
        }

        $hrp = "";
        $chars = str_split($this->btc->network->bech32HRP);
        foreach ($chars as $char) {
            $hrp .= chr(ord($char) >> 5);
        }

        $hrp .= "\0";
        foreach ($chars as $char) {
            $hrp .= chr(ord($char) & 31);
        }

        $this->hrp = $hrp;
        return $this->hrp;
    }

    /**
     * @param \Comely\Buffer\AbstractByteArray $buffer
     * @return int
     */
    protected function polyMod(AbstractByteArray $buffer): int
    {
        $value = 1;
        $bytes = $buffer->raw();
        for ($i = 0; $i < strlen($bytes); $i++) {
            $b = $value >> 25;
            $value = ($value & 0x1ffffff) << 5 ^ ord($bytes[$i]);
            for ($n = 0; $n < count(static::GENERATOR); $n++) {
                $value ^= ($b >> $n) & 1 ? static::GENERATOR[$n] : 0;
            }
        }

        return $value;
    }

    /**
     * @param \Comely\Buffer\Buffer $data
     * @return string
     */
    public function createChecksum(Buffer $data): string
    {
        $polyMod = $this->polyMod($data->copy()->prepend($this->hrpBytes())->append("\0\0\0\0\0\0")) ^ 1;
        $result = "";
        for ($i = 0; $i < 6; $i++) {
            $result .= chr(($polyMod >> 5 * (5 - $i)) & 31);
        }

        return $result;
    }

    /**
     * @param \Comely\Buffer\AbstractByteArray $buffer
     * @return string
     */
    public function encode(AbstractByteArray $buffer): string
    {
        $byteArray = [];
        foreach ($buffer->byteArray() as $byte) {
            $byteArray[] = str_pad(gmp_strval(gmp_init($byte), 2), 8, "0", STR_PAD_LEFT);
        }

        $byteArray = str_split(implode("", $byteArray), 5);
        $squashed = [];
        foreach ($byteArray as $fiveBits) {
            $squashed[] = gmp_intval(gmp_init($fiveBits, 2));
        }

        $squashed = Buffer::fromByteArray($squashed)
            ->prependUInt8($this->btc->network->bech32Witness);
        $squashed->append($this->createChecksum($squashed));
        $squashed = $squashed->byteArray();
        $address = $this->btc->network->bech32HRP . static::SEPARATOR;
        foreach ($squashed as $byte) {
            $address .= $this->btc->network->bech32Charset[$byte];
        }

        return $address;
    }
}
