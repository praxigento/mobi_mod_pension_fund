<?php
/**
 * User: Alex Gusev <alex@flancer64.com>
 */

namespace Praxigento\PensionFund\Repo\Dao;

use Praxigento\PensionFund\Repo\Data\Registry as Entity;

class Registry
    extends \Praxigento\Core\App\Repo\Def\Entity
{
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Praxigento\Core\App\Repo\IGeneric $repoGeneric
    ) {
        parent::__construct($resource, $repoGeneric, Entity::class);
    }

    /**
     * @param Entity|array $data
     * @return Entity
     */
    public function create($data)
    {
        $result = parent::create($data);
        return $result;
    }

    /**
     * Generic method to get data from repository.
     *
     * @param null $where
     * @param null $order
     * @param null $limit
     * @param null $offset
     * @param null $columns
     * @param null $group
     * @param null $having
     * @return Entity[] Found data or empty array if no data found.
     */
    public function get(
        $where = null,
        $order = null,
        $limit = null,
        $offset = null,
        $columns = null,
        $group = null,
        $having = null
    ) {
        $result = parent::get($where, $order, $limit, $offset, $columns, $group, $having);
        return $result;
    }

    /**
     * Get the data instance by ID.
     *
     * @param int $id
     * @return Entity|bool Found instance data or 'false'
     */
    public function getById($id)
    {
        $result = parent::getById($id);
        return $result;
    }

}