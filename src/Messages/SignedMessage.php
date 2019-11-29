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

namespace FurqanSiddiqui\Bitcoin\Messages;

/**
 * Class SignedMessage
 * @package FurqanSiddiqui\Bitcoin\Messages
 */
class SignedMessage
{
    /** @var string */
    public $signature;
    /** @var string */
    public $data;
    /** @var string|null */
    public $message;
}