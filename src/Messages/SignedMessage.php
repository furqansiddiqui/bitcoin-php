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

use Comely\DataTypes\Buffer\Base16;
use Comely\DataTypes\Buffer\Base64;

/**
 * Class SignedMessage
 * @package FurqanSiddiqui\Bitcoin\Messages
 */
class SignedMessage
{
    /** @var Base64 */
    public $signature;
    /** @var Base16 */
    public $msgHash;
    /** @var string|null */
    public $message;
    /** @var string|null */
    public $address;
}