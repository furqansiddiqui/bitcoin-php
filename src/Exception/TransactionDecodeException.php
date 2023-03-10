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

namespace FurqanSiddiqui\Bitcoin\Exception;

/**
 * Class TransactionDecodeException
 * @package FurqanSiddiqui\Bitcoin\Exception
 */
class TransactionDecodeException extends TransactionException
{
    public const INVALID_TX_VERSION = 0x2af8;
    public const INPUT_SCRIPT_PARSE_ERROR = 0x2ee0;
    public const OUTPUT_SCRIPT_PARSE_ERROR = 0x32c8;
    public const WITNESS_PARSE_ERROR = 0x36b0;

    /**
     * @return static
     */
    public static function InvalidVersion(): static
    {
        return new static('Invalid transaction version', self::INVALID_TX_VERSION);
    }

    /**
     * @param int $num
     * @param ScriptException $e
     * @return static
     */
    public static function InputScriptParseException(int $num, ScriptException $e): static
    {
        return new static(sprintf('[Input#%d] %s', $num, $e->getMessage()), self::INPUT_SCRIPT_PARSE_ERROR);
    }

    /**
     * @param int $num
     * @param ScriptException $e
     * @return static
     */
    public static function OutputScriptParseException(int $num, ScriptException $e): static
    {
        return new static(sprintf('[Output#%d] %s', $num, $e->getMessage()), self::OUTPUT_SCRIPT_PARSE_ERROR);
    }

    /**
     * @param string $msg
     * @return static
     */
    public static function WitnessParseException(string $msg): static
    {
        return new static($msg, self::WITNESS_PARSE_ERROR);
    }
}
