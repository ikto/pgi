<?php

namespace IKTO\PgI\Database;

use IKTO\PgI\Converter\ConverterInterface;
use IKTO\PgI\Exception\DuplicationException;
use IKTO\PgI\Exception\InvalidArgumentException;
use IKTO\PgI\Statement\StatementInterface;

interface DatabaseInterface
{
    public function __construct($dsn, $user = null, $password = null);

    public function __destruct();

    /**
     * @param string $query
     * @return StatementInterface
     */
    public function prepare($query);

    /**
     * @param string $query
     * @return StatementInterface
     */
    public function create($query);

    /**
     * @param string $query
     * @return resource
     */
    public function pgQuery($query);

    /**
     * @param string $query
     * @param array $params
     * @return resource
     */
    public function pgQueryParams($query, $params);

    /**
     * @param string $name
     * @param string $query
     * @return resource
     */
    public function pgPrepare($name, $query);

    /**
     * @param string $name
     * @param array $args
     * @return resource
     */
    public function pgExecute($name, $args);

    /**
     * @param string $name
     */
    public function pgDeallocate($name);

    /**
     * @param string $query
     * @return string
     */
    public function getPreparedStatementName($query);

    /**
     * @return int|bool
     */
    public function getTransactionStatus();

    /**
     * @return \IKTO\PgI\Helper\ParamEncoder
     */
    public function encoder();

    /**
     * @return \IKTO\PgI\Helper\ParamDecoder
     */
    public function decoder();

    /**
     * Gets converter for specified type
     *
     * @param string $type The data type
     * @return ConverterInterface
     * @throws InvalidArgumentException If converter is not registered
     */
    public function getConverterForType($type);

    /**
     * Registers converter for type
     *
     * @param string $type The type name
     * @param string|array|ConverterInterface $converter The converter instance or class name
     * @throws DuplicationException If converter for type is already registered
     */
    public function registerConverter($type, $converter);

    /**
     * Unregisters converter for type
     *
     * @param string $type The type name
     */
    public function unregisterConverter($type);
}
