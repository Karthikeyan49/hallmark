<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** A registered jewelry item and its generated compliance composite. */
final class ComplianceItem
{
    /** @param array<string,mixed> $data */
    public static function create(array $data): int
    {
        $stmt = Database::conn()->prepare(
            'INSERT INTO compliance_items
                (uid, item_type, company, weight_grams, track, provider, cost_inr, billable, composite_file)
             VALUES (:uid, :item_type, :company, :weight_grams, :track, :provider, :cost_inr, :billable, :composite_file)'
        );
        $stmt->execute([
            ':uid'            => $data['uid'],
            ':item_type'      => $data['item_type'] ?? '',
            ':company'        => $data['company'] ?? '',
            ':weight_grams'   => $data['weight_grams'],
            ':track'          => $data['track'],
            ':provider'       => $data['provider'],
            ':cost_inr'       => $data['cost_inr'] ?? 0,
            ':billable'       => !empty($data['billable']) ? 1 : 0,
            ':composite_file' => $data['composite_file'],
        ]);
        return (int) Database::conn()->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::conn()->prepare('SELECT * FROM compliance_items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int,array<string,mixed>> */
    public static function all(int $limit = 100): array
    {
        $stmt = Database::conn()->prepare(
            'SELECT * FROM compliance_items ORDER BY created_at DESC, id DESC LIMIT ?'
        );
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** @return array{count:int,total_cost:float,billable_cost:float} */
    public static function stats(): array
    {
        $row = Database::conn()->query(
            'SELECT COUNT(*) AS count,
                    COALESCE(SUM(cost_inr), 0) AS total_cost,
                    COALESCE(SUM(CASE WHEN billable = 1 THEN cost_inr ELSE 0 END), 0) AS billable_cost
             FROM compliance_items'
        )->fetch();

        return [
            'count'         => (int) $row['count'],
            'total_cost'    => (float) $row['total_cost'],
            'billable_cost' => (float) $row['billable_cost'],
        ];
    }
}
