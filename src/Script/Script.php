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

namespace FurqanSiddiqui\Bitcoin\Script;

use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Address\P2SH_Address;
use FurqanSiddiqui\Bitcoin\Exception\ScriptDecodeException;
use FurqanSiddiqui\Bitcoin\Exception\ScriptParseException;
use FurqanSiddiqui\DataTypes\Base16;
use FurqanSiddiqui\DataTypes\DataTypes;

/**
 * Class Script
 * @package FurqanSiddiqui\Bitcoin\Script
 */
class Script
{
    /** @var AbstractBitcoinNode */
    private $node;
    /** @var string */
    private $raw;
    /** @var Base16 */
    private $buffer;
    /** @var Base16 */
    private $hash160;

    /**
     * @param string $serializedBase16Script
     * @return string
     * @throws ScriptDecodeException
     */
    public static function DecodeAsString(string $serializedBase16Script): string
    {
        if (!DataTypes::isBase16($serializedBase16Script)) {
            throw new ScriptDecodeException('OpCode script decode expects argument to be a valid Base16 string');
        }

        $opCodes = array_flip(OpCode::OP_CODES);
        $binary = (new Base16($serializedBase16Script))->binary();
        $reader = $binary->read();
        $script = [];

        while (true) {
            if ($reader->isEnd()) {
                break;
            }

            $block = $reader->next(1);
            if (!$block) {
                break;
            }

            $dec = hexdec(bin2hex($block));

            // PUSHDATA?
            if ($dec >= 1 && $dec <= 75) {
                $len = $dec;
                $data = $reader->next($len);
                if (!$data) {
                    throw new ScriptDecodeException(sprintf('Failed to read PUSHDATA %d bytes', $len));
                }

                $script[] = sprintf('PUSHDATA(%d)[%s]', $len, bin2hex($data));
                continue;
            }

            // PUSHDATA1, 2 or 4?
            if (in_array($dec, [76, 77, 78])) {
                switch ($dec) {
                    case 78:
                        $lenBytes = 4; // Todo: Little endian
                        break;
                    case 77:
                        $lenBytes = 2; // Todo: Little endian
                        break;
                    default:
                        $lenBytes = 1;
                        break;
                }

                $dataLen = hexdec(bin2hex($reader->next($lenBytes)));
                $data = $reader->next($dataLen);
                $script[] = sprintf('PUSHDATA%d[%s]', $lenBytes, bin2hex($data));
                continue;
            }

            // OpCode

            $opCode = $opCodes[$dec] ?? null;
            if ($opCode) {
                $script[] = $opCode;
                continue;
            }

            throw new ScriptDecodeException(
                sprintf('Could not convert OpCode "%s" to appropriate script', dechex($dec))
            );
        }

        return implode(" ", $script);
    }

    /**
     * @param AbstractBitcoinNode $node
     * @param string $serializedBase16Script
     * @return Script
     * @throws ScriptDecodeException
     * @throws ScriptParseException
     */
    public static function Decode(AbstractBitcoinNode $node, string $serializedBase16Script): self
    {
        return new self($node, self::DecodeAsString($serializedBase16Script));
    }

    /**
     * Script constructor.
     * @param AbstractBitcoinNode $node
     * @param string $script
     * @throws ScriptParseException
     */
    public function __construct(AbstractBitcoinNode $node, string $script)
    {
        if (!$script) {
            throw new ScriptParseException('OpCode script cannot be empty');
        } elseif (!preg_match('/^[a-z0-9\_\s\(\)\[\]]+$/i', $script)) {
            throw new ScriptParseException('OpCode script contains illegal characters');
        }

        $this->node = $node;
        $this->raw = str_replace("OP_", "", strtoupper($script)); // Replace any "OP_" prefix
        $this->buffer = new Base16();

        $script = $this->raw; // Uppercase, OP_ prefix already removed
        $blocks = explode(" ", $script);
        $index = -1;
        foreach ($blocks as $block) {
            $index++;

            // Is PUSHDATA (1 to 75 bytes)
            if (preg_match('/^PUSHDATA\([0-9]+\)\[[a-f0-9]+\]$/i', $block)) {
                $block = explode(")[", $block);
                $data = substr($block[1], 0, -1);
                $len = intval(explode("(", $block[0])[1]);
                if ($len < 1 || $len > 75) {
                    throw new ScriptParseException(
                        sprintf('PUSHDATA length must be between 1 and 75 bytes, got %d', $len)
                    );
                }

                $this->buffer->append(dechex($len)); // This will also even number of hexits
                $this->buffer->append($data);
                continue;
            }

            // Is PUSHDATA with variable binary length?
            if (preg_match('/^PUSHDATA[0-9]+\[[a-f0-9]+\]$/i', $block)) {
                $block = explode("[", substr($block, 0, -1));
                $op = $block[0];
                $data = $block[1];

                switch ($op) {
                    case "PUSHDATA1":
                        $this->buffer->append("4c"); // 0x4c
                        break;
                    case "PUSHDATA2":
                        $this->buffer->append("4d"); // 0x4d
                        break;
                    case "PUSHDATA4":
                        $this->buffer->append("4e"); // 0x4e
                        break;
                    default:
                        throw new ScriptParseException(
                            sprintf('Invalid op code "%s"', $op)
                        );
                }

                $this->buffer->append($data);
                continue;
            }

            // Other OP codes
            $opCode = OpCode::OP_CODES["OP_" . $block] ?? null;
            if (is_int($opCode) && $opCode >= 0) {
                $this->buffer->append(dechex($opCode));
                continue;
            }

            // Unknown OpCode, throw exception
            throw new ScriptParseException(sprintf('Unknown OpCode at index %d', $index));
        }

        $this->hash160 = $this->buffer->binary()
            ->hash()->sha256()// 1x SHA256
            ->hash()->ripeMd160()// 1x RipeMD160
            ->encode()->base16(); // Base16

        $this->buffer->readOnly(true); // Set buffer in readOnly state
        $this->hash160->readOnly(true); // Set hash160 in readOnly state
    }

    /**
     * @return string
     */
    public function raw(): string
    {
        return $this->raw;
    }

    /**
     * @return Base16
     */
    public function script(): Base16
    {
        return $this->buffer;
    }

    /**
     * @return Base16
     */
    public function hash160(): Base16
    {
        return $this->hash160;
    }

    /**
     * @return P2SH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function p2sh(): P2SH_Address
    {
        return $this->node->p2sh()->fromScript($this);
    }

    /**
     * @return P2SH_Address
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function getP2SHAddress(): P2SH_Address
    {
        return $this->p2sh();
    }
}