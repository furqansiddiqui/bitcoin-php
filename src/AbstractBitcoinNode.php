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

namespace FurqanSiddiqui\Bitcoin;

use FurqanSiddiqui\Bitcoin\Address\AddressFactory;
use FurqanSiddiqui\Bitcoin\Messages\Messages;
use FurqanSiddiqui\Bitcoin\Networks\BitcoinNetworkConstants;
use FurqanSiddiqui\Bitcoin\Script\P2SH_Factory;
use FurqanSiddiqui\Bitcoin\Script\ScriptFactory;
use FurqanSiddiqui\Bitcoin\Wallets\KeyPairFactory;
use FurqanSiddiqui\Bitcoin\Wallets\MasterKeyFactory;
use FurqanSiddiqui\Bitcoin\Wallets\Wallets;

/**
 * Class AbstractBitcoinNode
 * @package FurqanSiddiqui\Bitcoin
 * @property-read null|string $const_name
 * @property-read null|int $const_decimals
 * @property-read null|int $const_private_key_bits
 * @property-read null|int $const_ecdsa_curve
 * @property-read null|int $const_p2pkh_prefix
 * @property-read null|int $const_p2sh_prefix
 * @property-read null|int $const_wif_prefix
 * @property-read null|string $const_signed_message_prefix
 * @property-read null|string $const_bip32_seed_key
 * @property-read null|int $const_bip32_private_prefix
 * @property-read null|int $const_bip32_public_prefix
 * @property-read null|int $const_bip44_coin_index
 */
abstract class AbstractBitcoinNode extends BitcoinNetworkConstants
{
    /** @var Wallets */
    protected $wallets;
    /** @var AddressFactory */
    protected $addrFactory;
    /** @var ScriptFactory */
    protected $scriptsFactory;
    /** @var P2SH_Factory */
    protected $p2shFactory;
    /** @var Messages */
    protected $messages;

    /**
     * AbstractBitcoinNode constructor.
     */
    public function __construct()
    {
        $this->wallets = new Wallets($this);
        $this->addrFactory = new AddressFactory($this);
    }

    /**
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop)
    {
        $prop = strtolower($prop);
        if (preg_match('/^const_\w+$/', $prop)) {
            return constant("static::" . strtoupper(substr($prop, 6)));
        }

        throw new \DomainException('Cannot read inaccessible properly');
    }

    /**
     * @return Wallets
     */
    public function wallets(): Wallets
    {
        return $this->wallets;
    }

    /**
     * @return MasterKeyFactory
     */
    public function hd(): MasterKeyFactory
    {
        return $this->wallets->masterKeyHD();
    }

    /**
     * @return KeyPairFactory
     */
    public function keyPair(): KeyPairFactory
    {
        return $this->wallets->keyPair();
    }

    /**
     * @return AddressFactory
     */
    public function address(): AddressFactory
    {
        return $this->addrFactory;
    }

    /**
     * @return ScriptFactory
     */
    public function script(): ScriptFactory
    {
        if (!$this->scriptsFactory) {
            $this->scriptsFactory = new ScriptFactory($this);
        }

        return $this->scriptsFactory;
    }

    /**
     * @return ScriptFactory
     */
    public function opCode(): ScriptFactory
    {
        return $this->script();
    }

    /**
     * @return P2SH_Factory
     */
    public function p2sh(): P2SH_Factory
    {
        if (!$this->p2shFactory) {
            $this->p2shFactory = new P2SH_Factory($this);
        }

        return $this->p2shFactory;
    }

    /**
     * @return Messages
     */
    public function messages(): Messages
    {
        if (!$this->messages) {
            $this->messages = new Messages($this);
        }

        return $this->messages;
    }
}