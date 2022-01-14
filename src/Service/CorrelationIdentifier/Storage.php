<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Service\CorrelationIdentifier;

/**
 * phpcs:disable Magento2.Functions.StaticFunction.StaticFunction
 */
class Storage
{
    /** @var string */
    private static string $correlationIdKey;
    /** @var string */
    private static string $correlationIdValue;

    /**
     * Initialise the key for this request
     *
     * @param string $key
     * @param false|true $replace
     * @return void
     */
    public static function setKey(string $key, bool $replace = false)
    {
        if (isset(self::$correlationIdKey) && strlen(self::$correlationIdKey) && !$replace) {
            return;
        }
        self::$correlationIdKey = $key;
    }

    /**
     * Initialise the value for this request
     *
     * @param string $value
     * @param false|true $replace
     * @return void
     */
    public static function setValue(string $value, bool $replace = false)
    {
        if (isset(self::$correlationIdValue) && strlen(self::$correlationIdValue) && !$replace) {
            return;
        }
        self::$correlationIdValue = $value;
    }

    /**
     * Return this processes log correlation ID
     *
     * @return string
     */
    public static function getValue(): string
    {
        if (!isset(self::$correlationIdValue) || !strlen(self::$correlationIdValue)) {
            /**
             * This process has been generated in a way that the magento cache system wasn't triggered
             *
             * It is likely you have not created app/etc/ampersand_magento2_log_correlation/di.xml
             *
             * See the installation instructions in the README.md
             */
            return 'correlation_id_value_error';
        }
        return self::$correlationIdValue;
    }

    /**
     * Return the identifier key
     *
     * @return string
     */
    public static function getKey(): string
    {
        if (!isset(self::$correlationIdKey) || !strlen(self::$correlationIdKey)) {
            /**
             * This process has been generated in a way that the magento cache system wasn't triggered
             *
             * It is likely you have not created app/etc/ampersand_magento2_log_correlation/di.xml
             *
             * See the installation instructions in the README.md
             */
            return 'correlation_id_key_error';
        }
        return self::$correlationIdKey;
    }
}
