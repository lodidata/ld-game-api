<?php
namespace Model;

use PDO;
use Illuminate\Database\Capsule\Manager as Model;
use Illuminate\Database\Query\Expression;
use Utils\Client;

/**
 * @method static commit()
 * @method static rollBack()
 * @method static insert(string $sqlBatch)
 * @method static update(string $sql)
 * @method static beginTransaction()
 */
class DB extends Model
{
    /**
     * Get a fluent raw query expression.
     *
     * @param mixed $value
     * @return Expression
     */
    public static function raw($value): Expression
    {
        return static::$instance->connection()->raw( $value );
    }

    /**
      合并字段
     *
     * @param $select1
     * @param $select2
     * @return array
     */
    public static function mergeColumns($select1, $select2): array
    {
        if (empty( $select1 ))
            return [];

        if ($select2 == '*' || empty( $select2 ))
            return $select1;

        if (!is_array( $select2 ))
            $select2 = explode( ',', $select2 );

        $temp = [];
        foreach ($select2 as $val) {
            if (isset( $select1[$val] ))
                $temp[$val] = $select1[$val];
        }
        return $temp;
    }

    /**
      调用存储过程，合并多个结果集
     *
     * @param string $method
     * @param array $params
     * @return array
     */
    public static function procedureDataSet(string $method = '', array $params = []): array
    {
        if (!$method)
            return [];

        $paramStr = implode( ',', $params );
        $dbh = self::connection()->getPdo();
        $stmt = $dbh->prepare( "call  $method($paramStr)" );
        $stmt->execute();
        $result = [];
        $result[] = $stmt->fetchAll( PDO::FETCH_ASSOC );
        do {
            $rows = $stmt->fetchAll( PDO::FETCH_ASSOC );
            if ($rows)
                $result[] = $rows;
        } while ($stmt->nextRowset());

        return $result;
    }

    /**
      对象转数组
     *
     * @param $val
     * @return array|mixed
     */
    public static function resultToArray($val)
    {
        if ($val instanceof \Illuminate\Database\Eloquent\Model) {
            $val = $val->toArray();
        }

        foreach ($val ?? [] as $k => $v) {
            $val[$k] = (array)$v;
        }
        return $val;
    }

    /**
     * @return \Illuminate\Database\Query\Expression
     */
    public static function getIPv6(): Expression
    {
        $ip = Client::getIp();
        return self::raw( "inet6_aton('$ip')" );
    }

    /**
     * 服务于事务操作
     *
     * @return \Closure|\PDO
     */
    public static function pdo () {
        return self::connection()->getPdo();
    }
}