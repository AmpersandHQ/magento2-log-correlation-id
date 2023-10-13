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
        if (strpos($sql, $this->correlationIdentifier->getIdentifierValue()) !== false) {
            return; // double check we've not already added it
        }
        if (!(isset($this->adapter) && $this->adapter instanceof Mysql)) {
            return;
        }
        //TODO make sure this is not vulnerable to SQL injection as it gets concatenated at the end of the query
        $sql .= $this->adapter->quoteInto(' /* ? */ ', $this->correlationIdentifier->getIdentifierValue());
    }
}
