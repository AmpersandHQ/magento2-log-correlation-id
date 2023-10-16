<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\MysqlQueryHook;

use Ampersand\LogCorrelationId\Service\CorrelationIdentifier;
use Magento\Framework\DB\Adapter\Pdo\Mysql;

class AddToDatabaseQueries
{
    /**
     * @var CorrelationIdentifier
     */
    private CorrelationIdentifier $correlationIdentifier;

    /**
     * @var Mysql
     */
    private Mysql $adapter;

    /**
     * @param CorrelationIdentifier $correlationIdentifier
     */
    public function __construct(
        CorrelationIdentifier $correlationIdentifier
    ) {
        $this->correlationIdentifier = $correlationIdentifier;
    }

    /**
     * Set the correlation ID for this query
     *
     * @param Mysql $adapter
     * @return void
     */
    public function setMysqlAdapter(Mysql $adapter)
    {
        if (isset($this->adapter) && $this->adapter instanceof Mysql) {
            return;
        }
        $this->adapter = $adapter;
    }

    /**
     * Attach the correlation id to database queries
     *
     * @see \Magento\Framework\DB\Adapter\Pdo\Mysql::_prepareQuery()
     * @param \Magento\Framework\DB\Select|string $sql
     * @param mixed $bind
     * @return void
     */
    public function addToDatabaseQueries(&$sql, &$bind = [])
    {
        if (!is_string($sql)) {
            return;
        }
        if (!strlen($sql)) {
            return;
        }
        if (!(isset($this->adapter) && $this->adapter instanceof Mysql)) {
            return;
        }

        $identifier = $this->correlationIdentifier->getIdentifierValue();
        if (!strlen($identifier)) {
            return;
        }

        /**
         * @link https://github.com/google/sqlcommenter/blob/c447d218a1b8ff628571b22cf4e74f2a6fe2e38a/php/sqlcommenter-php/packages/sqlcommenter-laravel/src/Utils.php#L28-L47
         * @link https://google.github.io/sqlcommenter/spec/#value-serialization
         *
         * 1. Url encode the value
         *     1a. Double encode %, see code comment above
         * 2. Escape meta chars (inside quoteInto it calls addcslashes)
         * 3. Escape the value by putting in single quotes (part of quoteInto)
         */
        $identifier = $this->adapter->quoteInto(
            ' /* ? */ ',
            str_replace('%', '%%', rawurlencode($identifier))
        );

        if (strpos($sql, $identifier) !== false) {
            return; // double check we've not already added it
        }

        if (strlen($identifier) <= 128 && preg_match('/^[a-zA-Z0-9_.~-]*$/', $identifier)) {
            $sql .= $identifier;
        }
    }
}
