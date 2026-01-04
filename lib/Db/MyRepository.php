<?php

namespace OCA\AdminCockpit\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;

class MyRepository extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'oc_admincockpit_items');
    }

    public function findAll(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->getTableName());
        $result = $qb->execute();
        $items = $result->fetchAll();
        $result->closeCursor();

        return $items;
    }

    public function insertItem(string $name, string $value): int {
        $qb = $this->db->getQueryBuilder();
        $qb->insert($this->getTableName())
           ->values([
               'name' => $qb->expr()->literal($name),
               'value' => $qb->expr()->literal($value),
               'created_at' => $qb->expr()->literal((new \DateTime())->format('Y-m-d H:i:s')),
           ])
           ->execute();
        return (int)$qb->getLastInsertId();
    }

    public function updateItem(int $id, string $newValue): int {
        $qb = $this->db->getQueryBuilder();
        $qb->update($this->getTableName())
           ->set('value', $qb->expr()->literal($newValue))
           ->where($qb->expr()->eq('id', $qb->expr()->literal($id)))
           ->execute();
        return (int)$qb->getAffectedRows();
    }

    public function deleteItem(int $id): int {
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->getTableName())
           ->where($qb->expr()->eq('id', $qb->expr()->literal($id)))
           ->execute();
        return (int)$qb->getAffectedRows();
    }
}
