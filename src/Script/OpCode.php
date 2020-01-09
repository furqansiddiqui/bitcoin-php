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

namespace FurqanSiddiqui\Bitcoin\Script;

use Comely\DataTypes\Buffer\Binary;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;

/**
 * Class OpCode
 * @package FurqanSiddiqui\Bitcoin\Script
 * @method self OP_0()
 * @method self OP_2()
 * @method self OP_3()
 * @method self OP_4()
 * @method self OP_5()
 * @method self OP_6()
 * @method self OP_7()
 * @method self OP_8()
 * @method self OP_9()
 * @method self OP_10()
 * @method self OP_11()
 * @method self OP_12()
 * @method self OP_13()
 * @method self OP_14()
 * @method self OP_15()
 * @method self OP_16()
 * @method self OP_TRUE()
 * @method self OP_VERIFY()
 * @method self OP_RETURN()
 * @method self OP_DUP()
 * @method self OP_EQUAL()
 * @method self OP_EQUALVERIFY()
 * @method self OP_HASH160()
 * @method self OP_HASH256()
 * @method self OP_CHECKSIG()
 * @method self OP_CHECKSIGVERIFY()
 * @method self OP_CHECKMULTISIG()
 * @method self OP_CHECKMULTISIGVERIFY()
 */
class OpCode
{
    /** @var array */
    public const OP_CODES = [
        "OP_0" => 0x00,
        "OP_TRUE" => 0x51,
        "OP_1" => 0x51,
        "OP_2" => 0x52,
        "OP_3" => 0x53,
        "OP_4" => 0x54,
        "OP_5" => 0x55,
        "OP_6" => 0x56,
        "OP_7" => 0x57,
        "OP_8" => 0x58,
        "OP_9" => 0x59,
        "OP_10" => 0x5a,
        "OP_11" => 0x5b,
        "OP_12" => 0x5c,
        "OP_13" => 0x5d,
        "OP_14" => 0x5e,
        "OP_15" => 0x5f,
        "OP_16" => 0x60,
        "OP_IF" => 0x63,
        "OP_NOTIF" => 0x64,
        "OP_ELSE" => 0x67,
        "OP_ENDIF" => 0x68,
        "OP_VERIFY" => 0x69,
        "OP_RETURN" => 0x6a,
        "OP_DUP" => 0x76,
        "OP_EQUAL" => 0x87,
        "OP_EQUALVERIFY" => 0x88,
        "OP_1ADD" => 0x8b,
        "OP_1SUB" => 0x8c,
        "OP_ADD" => 0x93,
        "OP_SUB" => 0x94,
        "OP_RIPEMD160" => 0xa6,
        "OP_SHA1" => 0xa7,
        "OP_SHA256" => 0xa8,
        "OP_HASH160" => 0xa9,
        "OP_HASH256" => 0xaa,
        "OP_CHECKSIG" => 0xac,
        "OP_CHECKSIGVERIFY" => 0xad,
        "OP_CHECKMULTISIG" => 0xae,
        "OP_CHECKMULTISIGVERIFY" => 0xaf
    ];

    /** @var AbstractBitcoinNode */
    private $node;
    /** @var array */
    private $script;

    /**
     * OpCode constructor.
     * @param AbstractBitcoinNode $node
     */
    public function __construct(AbstractBitcoinNode $node)
    {
        $this->node = $node;
        $this->script = [];
    }

    /**
     * @param $name
     * @param $arguments
     * @return OpCode
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) === "OP_") {
            return $this->OP($name);
        }

        throw new \DomainException('Cannot call inaccessible method');
    }

    /**
     * @param string $op
     * @return OpCode
     */
    public function OP(string $op): self
    {
        $flag = strtoupper($op);
        if (!array_key_exists($flag, self::OP_CODES)) {
            throw new \OutOfBoundsException('Requested OP code is not registered/supported');
        }

        $this->script[] = $flag;
        return $this;
    }

    /**
     * @param Binary $data
     * @return OpCode
     */
    public function PUSHDATA(Binary $data): self
    {
        $dataLen = $data->size()->bytes();
        if ($dataLen < 1) {
            throw new \LengthException('PUSHDATA method can only be used for data between 1 and 75 bytes');
        }

        // Simple PUSHDATA (length 1 to 75 bytes)
        if ($dataLen <= 75) {
            $this->script[] = sprintf('PUSHDATA(%d)[%s]', $dataLen, $data->base16()->hexits(false));
            return $this;
        }

        if ($dataLen <= 0xff) { // 255
            $pushDataPrefix = 1;
            $pushDataLenHex = dechex($dataLen);
        } elseif ($dataLen <= 0xffff) { // 65535
            $pushDataPrefix = 2;
            $pushDataLenHex = bin2hex(pack("v", $dataLen));
        } elseif ($dataLen <= 0xffffffff) { // 4294967295
            $pushDataPrefix = 4;
            $pushDataLenHex = bin2hex(pack("V", $dataLen));
        } else {
            throw new \LengthException('PUSHDATA cannot push data exceeding 4-byte length');
        }

        if (strlen($pushDataLenHex) % 2 !== 0) {
            $pushDataLenHex = "0" . $pushDataLenHex;
        }

        $this->script[] = sprintf('PUSHDATA%d[%s%s]', $pushDataPrefix, $pushDataLenHex, $data->base16()->hexits(false));
        return $this;
    }

    /**
     * @return Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function script(): Script
    {
        return new Script($this->node, implode(" ", $this->script));
    }

    /**
     * @return Script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public function getScript(): Script
    {
        return $this->script();
    }
}