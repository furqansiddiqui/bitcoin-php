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

namespace FurqanSiddiqui\Bitcoin\Address;

use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException;
use FurqanSiddiqui\Bitcoin\Serialize\Base58Check;
use FurqanSiddiqui\DataTypes\Base16;

/**
 * Class AbstractPaymentAddress
 * @package FurqanSiddiqui\Bitcoin\Address
 */
abstract class AbstractPaymentAddress implements PaymentAddressInterface
{
    /** @var AbstractBitcoinNode|null */
    private $node;
    /** @var string */
    private $address;
    /** @var Base16 */
    private $prefix;
    /** @var Base16 */
    private $hash160;

    /**
     * AbstractPaymentAddress constructor.
     * @param AbstractBitcoinNode|null $node
     * @param string $addr
     * @param Base16|null $crossCheckHash160
     * @throws PaymentAddressException
     */
    public function __construct(?AbstractBitcoinNode $node, string $addr, ?Base16 $crossCheckHash160 = null)
    {
        $base58Check = Base58Check::getInstance();
        $decoded = $base58Check->decode($addr); // This will validate Checksum and retrieve Hash160
        $decoded = $decoded->binary(); // Convert data to binary buffer

        $this->node = $node;
        $this->address = $addr;
        $this->hash160 = $decoded->clone()->substr(-20)->encode()->base16(); // Get last 20 bytes
        $this->prefix = $decoded->clone()->substr(0, -20)->encode()->base16(); // Get initial bytes, leading last 20

        // Hash160
        if ($this->hash160->binary()->size()->bits() !== 160) {
            throw new PaymentAddressException('Payment address hash160 must be 160 bit long');
        }

        // Prefix verify
        if ($node) {
            if ($this->prefix->hexits(false) === dechex($node->const_p2pkh_prefix)) {
                throw new PaymentAddressException('Payment address prefix does not match');
            }
        }

        // Cross-check hash160
        if ($crossCheckHash160) {
            if (!$crossCheckHash160->equals($this->hash160)) {
                throw new PaymentAddressException('Payment address hash160 cross-check fail');
            }
        }
    }

    /**
     * @return string
     */
    public function address(): string
    {
        return $this->address;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address();
    }
}