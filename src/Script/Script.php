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

use Comely\Buffer\AbstractByteArray;
use Comely\Buffer\BigInteger\LittleEndian;
use Comely\Buffer\Buffer;
use Comely\Buffer\Bytes20;
use Comely\Buffer\Exception\ByteReaderUnderflowException;
use FurqanSiddiqui\Bitcoin\Bitcoin;
use FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException;
use FurqanSiddiqui\Bitcoin\Exception\ScriptParseException;

/**
 * Class Script
 * @package FurqanSiddiqui\Bitcoin\Script
 */
class Script
{
    public readonly string $script;
    public readonly Buffer $buffer;
    public readonly Bytes20 $hash160;

    /**
     * @param \Comely\Buffer\AbstractByteArray $buffer
     * @return string
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     */
    public static function Buffer2String(AbstractByteArray $buffer): string
    {
        $opCodes = array_flip(OpCode::OP_CODES);
        $reader = $buffer->read();
        $script = [];

        try {
            while (true) {
                if ($reader->isEnd()) {
                    break;
                }

                $flag = ord($reader->next(1));

                // PUSHDATA?
                if ($flag >= 1 && $flag <= 75) {
                    $script[] = sprintf('PUSHDATA(%d)[%s]', $flag, bin2hex($reader->next($flag)));
                    continue;
                }

                // PUSHDATA1, 2 or 4?
                if (in_array($flag, [76, 77, 78])) {
                    $lenBytes = match ($flag) {
                        78 => 4,
                        77 => 2,
                        default => 1,
                    };

                    $dataLenBytes = $reader->next($lenBytes);
                    $dataLen = gmp_intval(LittleEndian::GMP_Unpack($dataLenBytes));
                    $script[] = sprintf('PUSHDATA%d[%s%s]', $lenBytes, bin2hex($dataLenBytes), bin2hex($reader->next($dataLen)));
                    continue;
                }

                // OpCode
                $opCode = $opCodes[$flag] ?? null;
                if ($opCode) {
                    $script[] = $opCode;
                    continue;
                }

                throw new ScriptDecodeException(
                    sprintf('Could not convert OpCode "0x%s" to appropriate script', dechex($flag))
                );
            }
        } catch (ByteReaderUnderflowException $e) {
            throw new ScriptDecodeException($e->getMessage(), $e->getCode());
        }

        return implode(" ", $script);
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param string|\Comely\Buffer\AbstractByteArray $script
     * @return static
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    public static function Decode(Bitcoin $btc, string|AbstractByteArray $script): self
    {
        if ($script instanceof AbstractByteArray) {
            $script = static::Buffer2String($script);
        }

        return new self($btc, $script);
    }

    /**
     * @param \FurqanSiddiqui\Bitcoin\Bitcoin $btc
     * @param string $script
     * @throws \FurqanSiddiqui\Bitcoin\Exception\ScriptParseException
     */
    private function __construct(
        public readonly Bitcoin $btc,
        string                  $script
    )
    {
        if (!$script) {
            throw new ScriptParseException('OpCode script cannot be empty');
        } elseif (!preg_match('/^[a-z0-9_\s()\[\]]+$/i', $script)) {
            throw new ScriptParseException('OpCode script contains illegal characters');
        }

        $script = str_replace("OP_", "", strtoupper($script)); // Replace any "OP_" prefix
        $buffer = new Buffer();

        $blocks = explode(" ", $script);
        $index = -1;
        foreach ($blocks as $block) {
            $index++;

            // Is PUSHDATA (1 to 75 bytes)
            if (preg_match('/^PUSHDATA\([0-9]+\)\[[a-f0-9]+]$/i', $block)) {
                $block = explode(")[", $block);
                $data = substr($block[1], 0, -1);
                $len = intval(explode("(", $block[0])[1]);
                if ($len < 1 || $len > 75) {
                    throw new ScriptParseException(
                        sprintf('PUSHDATA length must be between 1 and 75 bytes, got %d', $len),
                        $index
                    );
                }

                if (strlen($data) % 2 !== 0) {
                    throw new ScriptParseException('Base16 encoded PUSHDATA must be of even length', $index);
                } elseif (strlen($data) * 2 !== $len) {
                    throw new ScriptParseException('Expected PUSHDATA of %d hexits, got %d', $len, strlen($data));
                }

                $buffer->appendUInt8($len);
                $buffer->append(hex2bin($data));
                continue;
            }

            // Is PUSHDATA with variable binary length?
            if (preg_match('/^PUSHDATA[0-9]+\[[a-f0-9]+]$/i', $block)) {
                $block = explode("[", substr($block, 0, -1));
                $op = $block[0];
                $data = $block[1];
                switch ($op) {
                    case "PUSHDATA1":
                        $buffer->append(hex2bin("4c"));
                        break;
                    case "PUSHDATA2":
                        $buffer->append(hex2bin("4d"));
                        break;
                    case "PUSHDATA4":
                        $buffer->append(hex2bin("4e"));
                        break;
                    default:
                        throw new ScriptParseException(sprintf('Invalid op code "%s"', $op), $index);
                }

                $buffer->append($data);
                continue;
            }

            // Other OP codes
            $opCode = OpCode::OP_CODES["OP_" . $block] ?? null;
            if (is_int($opCode) && $opCode >= 0) {
                $buffer->appendUInt8($opCode);
                continue;
            }

            // Unknown OpCode, throw exception
            throw new ScriptParseException(sprintf('Unknown OpCode at index %d', $index), $index);
        }

        $this->script = $script;
        $this->buffer = $buffer->readOnly();
        $this->hash160 = $this->btc->network->hash160($this->buffer);
    }
}
