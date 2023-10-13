<?php
declare(strict_types=1);
namespace Ampersand\LogCorrelationId\Plugin;

use Ampersand\LogCorrelationId\MysqlQueryHook;
use Magento\Framework\App\ResourceConnection;

class AddToDatabaseQueries
{
    /**
     * @var MysqlQueryHook\AddToDatabaseQueries
     */
    private MysqlQueryHook\AddToDatabaseQueries $addToDatabaseQueries;

    /**
     * @param MysqlQueryHook\AddToDatabaseQueries $addToDatabaseQueries
     */
    public function __construct(
        MysqlQueryHook\AddToDatabaseQueries $addToDatabaseQueries
    ) {
        $this->addToDatabaseQueries = $addToDatabaseQueries;
    }

    /**
     * Attach the correlation ID to database queries after the connection is gathered
     *
     * @param ResourceConnection $subject
     * @param \Magento\Framework\DB\Adapter\Pdo\Mysql|mixed $result
     * @return \Magento\Framework\DB\Adapter\Pdo\Mysql|mixed
     */
    public function afterGetConnection(ResourceConnection $subject, $result)
    {
        if ($result instanceof \Magento\Framework\DB\Adapter\Pdo\Mysql) {
            $this->addToDatabaseQueries->setMysqlAdapter($result);
            $result->setQueryHook(
                [
                    'object' => $this->addToDatabaseQueries,
                    'method' => 'addToDatabaseQueries'
                ]
            );
        }
        return $result;
    }
}
