<?php
/**
 *
 * AdminCockpit APP (Nextcloud)
 *
 * @author Wolfgang Tödt <wtoedt@gmail.com>
 *
 * @copyright Copyright (c) 2025 Wolfgang Tödt
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

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
