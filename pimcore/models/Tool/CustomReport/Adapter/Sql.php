<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Pimcore
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Tool\CustomReport\Adapter;

use Pimcore\Model;
use Pimcore\Db;

class Sql extends AbstractAdapter {

    /**
     * @param $filters
     * @param $sort
     * @param $dir
     * @param $offset
     * @param $limit
     * @param null $fields
     * @param null $drillDownFilters
     * @return array
     */
    public function getData($filters, $sort, $dir, $offset, $limit, $fields = null, $drillDownFilters = null) {
        $db = Db::get();

        $baseQuery = $this->getBaseQuery($filters, $fields, false, $drillDownFilters);

        if($baseQuery) {
            $total = $db->fetchOne($baseQuery["count"]);

            $order = "";
            if($sort && $dir) {
                $order = " ORDER BY " . $db->quoteIdentifier($sort) . " " . $dir;
            }

            $sql = $baseQuery["data"] . $order;
            if($offset !== null && $limit) {
                $sql .= " LIMIT $offset,$limit";
            }

            $data = $db->fetchAll($sql);
        }

        return array("data" => $data, "total" => $total);
    }

    /**
     * @param $configuration
     * @return array|mixed|null
     * @throws \Exception
     */
    public function getColumns($configuration) {
        $sql = "";
        if($configuration) {
            $sql = $this->buildQueryString($configuration);
        }

        $res = null;
        $errorMessage = null;
        $columns = null;

        if(!preg_match("/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i", $sql, $matches)) {
            $sql .= " LIMIT 0,1";
            $db = Db::get();
            $res = $db->fetchRow($sql);
            $columns = array_keys($res);
        } else {
            throw new \Exception("Only 'SELECT' statements are allowed! You've used '" . $matches[0] . "'");
        }

        return $columns;
    }

    /**
     * @param $config
     * @param bool $ignoreSelectAndGroupBy
     * @param null $drillDownFilters
     * @param null $selectField
     * @return string
     */
    protected function buildQueryString($config, $ignoreSelectAndGroupBy = false, $drillDownFilters = null, $selectField = null) {
        $sql = "";
        if($config->sql && !$ignoreSelectAndGroupBy) {
            if(strpos(strtoupper(trim($config->sql)), "SELECT") === false || strpos(strtoupper(trim($config->sql)), "SELECT") > 5) {
                $sql .= "SELECT ";
            }
            $sql .= str_replace("\n", " ", $config->sql);
        } else if($selectField) {
            $db = Db::get();
            $sql .= "SELECT " . $db->quoteIdentifier($selectField);
        } else {
            $sql .= "SELECT *";
        }
        if($config->from) {
            if(strpos(strtoupper(trim($config->from)), "FROM") === false) {
                $sql .= " FROM ";
            }
            $sql .= " " . str_replace("\n", " ", $config->from);
        }
        if($config->where || $drillDownFilters) {
            $whereParts = array();
            if($config->where) {
                $whereParts[] = "(" . str_replace("\n", " ", $config->where) . ")";
            }

            if($drillDownFilters) {
                $db = Db::get();
                foreach($drillDownFilters as $field => $value) {
                    if($value !== "" && $value !== null) {
                        $whereParts[] = "`$field` = " . $db->quote($value);
                    }
                }
            }

            if($whereParts) {
                if(strpos(strtoupper(trim($config->where)), "WHERE") === false) {
                    $sql .= " WHERE ";
                }

                $sql .= " " . implode(" AND ", $whereParts);
            }
        }
        if($config->groupby && !$ignoreSelectAndGroupBy) {
            if(strpos(strtoupper($config->groupby), "GROUP BY") === false) {
                $sql .= " GROUP BY ";
            }
            $sql .= " " . str_replace("\n", " ", $config->groupby);
        }

        return $sql;
    }

    /**
     * @param $filters
     * @param $fields
     * @param bool $ignoreSelectAndGroupBy
     * @param null $drillDownFilters
     * @param null $selectField
     * @return array
     */
    protected function getBaseQuery($filters, $fields, $ignoreSelectAndGroupBy = false, $drillDownFilters = null, $selectField = null) {
        $db = Db::get();
        $condition = array("1 = 1");

        $sql = $this->buildQueryString($this->config, $ignoreSelectAndGroupBy, $drillDownFilters, $selectField);

        $data = "";

        if($filters) {
            if(is_array($filters)) {
                foreach ($filters as $filter) {
                    if (\Pimcore\Tool\Admin::isExtJS6()) {
                        $operator = $filter['operator'];
                        switch ($operator) {
                            case 'like':
                                $condition[] = $db->quoteIdentifier($filter["property"]) . " LIKE " . $db->quote("%" . $filter["value"] . "%");
                                break;
                            case "lt":
                            case "gt":
                            case "eq":

                                $compMapping = array(
                                    "lt" => "<",
                                    "gt" => ">",
                                    "eq" => "="
                                );

                                $condition[] = $db->quoteIdentifier($filter["property"]) . " " . $compMapping[$operator] . " " . $db->quote($filter["value"]);
                                break;
                            case "=":
                                $condition[] = $db->quoteIdentifier($filter["property"]) . " = " . $db->quote((int)$filter["value"]);
                                break;
                        }

                    } else {
                        if($filter["type"] == "string") {
                            $condition[] = $db->quoteIdentifier($filter["field"]) . " LIKE " . $db->quote("%" . $filter["value"] . "%");
                        } else if($filter["type"] == "numeric") {
                            $compMapping = array(
                                "lt" => "<",
                                "gt" => ">",
                                "eq" => "="
                            );
                            if($compMapping[$filter["comparison"]]) {
                                $condition[] = $db->quoteIdentifier($filter["field"]) . " " . $compMapping[$filter["comparison"]] . " " . $db->quote($filter["value"]);
                            }
                        } else if ($filter["type"] == "boolean") {
                            $condition[] = $db->quoteIdentifier($filter["field"]) . " = " . $db->quote((int)$filter["value"]);
                        } else if ($filter["type"] == "date") {

                        }

                    }
                }
            }
        }

        if(!preg_match("/(ALTER|CREATE|DROP|RENAME|TRUNCATE|UPDATE|DELETE) /i", $sql, $matches)) {

            $condition = implode(" AND ", $condition);

            $total = "SELECT COUNT(*) FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;

            if($fields) {
                $data = "SELECT `" . implode("`, `", $fields) . "` FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;
            } else {
                $data = "SELECT * FROM (" . $sql . ") AS somerandxyz WHERE " . $condition;
            }

        } else {
            return;
        }


        return array(
            "data" => $data,
            "count" => $total
        );
    }

    /**
     * @param $filters
     * @param $field
     * @param $drillDownFilters
     * @return array|mixed
     */
    public function getAvailableOptions($filters, $field, $drillDownFilters) {
        $db = Db::get();
        $baseQuery = $this->getBaseQuery($filters, array($field), true, $drillDownFilters, $field);
        $data = array();
        if($baseQuery) {
            $sql = $baseQuery["data"] . " GROUP BY " . $db->quoteIdentifier($field);
            $data = $db->fetchAll($sql);
        }

        $filteredData = array();
        foreach($data as $d) {
            if(!empty($d[$field]) || $d[$field] === 0) {
                $filteredData[] = array("value" => $d[$field]);
            }
        }

        return array("data" => array_merge(array(array("value" => null)), $filteredData));
    }
}