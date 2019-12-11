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

namespace FurqanSiddiqui\Bitcoin\Address;

use Comely\DataTypes\Buffer\Base16;
use FurqanSiddiqui\Bitcoin\AbstractBitcoinNode;
use FurqanSiddiqui\Bitcoin\Script\Script;

/**
 * Class P2SH_P2WPKH_Address
 * @package FurqanSiddiqui\Bitcoin\Address
 */
class P2SH_P2WPKH_Address extends P2SH_Address
{
    protected const TYPE = "p2sh-p2wpkh";

    /**
     * P2SH_P2WPKH_Address constructor.
     * @param AbstractBitcoinNode|null $node
     * @param string $addr
     * @param Base16|null $crossCheckHash160
     * @param Script|null $redeemScript
     * @throws \FurqanSiddiqui\Bitcoin\Exception\PaymentAddressException
     */
    public function __construct(?AbstractBitcoinNode $node, string $addr, ?Base16 $crossCheckHash160 = null, Script $redeemScript = null)
    {
        parent::__construct($node, $addr, $crossCheckHash160, $redeemScript);
    }
}