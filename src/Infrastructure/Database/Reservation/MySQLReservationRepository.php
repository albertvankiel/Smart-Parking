<?php

namespace App\Infrastructure\Database\Reservation;

use App\Domain\Repositories\ReservationRepositoryInterface;
use App\Infrastructure\Database\AbstractDatabaseRepository;
use App\Domain\Models\Reservation;
use App\Domain\Enums\BookingStatus;

readonly class MySQLReservationRepository extends AbstractDatabaseRepository implements ReservationRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function bookSpot(int $userId, int $parkingSpotId, string $startTime, string $endTime): Reservation
    {
        $this->pdo->beginTransaction();

        try {
            $checkQuery = "
                SELECT id
                FROM reservations
                WHERE parking_spot_id = :parking_spot_id
                    AND  (
                        (start_time < :end_time AND end_time > :start_time)
                    )
                    AND status = 'booked'
                FOR UPDATE
            ";

            $checkStmnt = $this->pdo->prepare($checkQuery);
            $checkStmnt->execute([
                'parking_spot_id' => $parkingSpotId,
                'start_time' => $startTime,
                'end_time' => $endTime
            ]);

            if ($checkStmnt->fetch()) {
                $this->pdo->rollBack();
                throw new \Exception("Parking spot is already booked for this time");
            }

            $insertQuery = "
                INSERT INTO reservations (user_id, parking_spot_id, start_time, end_time)
                VALUES (:user_id, :parking_spot_id, :start_time, :end_time)
            ";

            $insertStmnt = $this->pdo->prepare($insertQuery);
            $insertStmnt->execute([
                'user_id' => $userId,
                'parking_spot_id' => $parkingSpotId,
                'start_time' => $startTime,
                'end_time' => $endTime
            ]);

            $reservationId = (int) $this->pdo->lastInsertId();

            $fetchQuery = "SELECT created_at FROM reservations WHERE id = :reservation_id";
            $fetchStmnt = $this->pdo->prepare($fetchQuery);
            $fetchStmnt->execute(['reservation_id' => $reservationId]);
            $createdAt = $fetchStmnt->fetchColumn();

            $this->pdo->commit();

            return new Reservation(
                $reservationId,
                $parkingSpotId,
                $userId,
                $startTime,
                $endTime,
                $createdAt,
                BookingStatus::BOOKED
            );
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByDate(string $date): array
    {
        $query = "
            SELECT *
            FROM reservations
            WHERE DATE(start_time) = :date
        ";

        $stmnt = $this->pdo->prepare($query);
        $stmnt->execute(['date' => $date]);

        $rows = $stmnt->fetchAll(\PDO::FETCH_ASSOC);
        $reservations = [];

        foreach ($rows as $row) {
            $reservations[] = new Reservation(
                (int) $row['id'],
                (int) $row['parking_spot_id'],
                (int) $row['user_id'],
                $row['start_time'],
                $row['end_time'],
                $row['created_at'],
                BookingStatus::from($row['status'])
            );
        }

        return $reservations;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(int $id): bool
    {
        $query = "UPDATE reservations SET status = 'completed' WHERE id = :id";
        $stmnt = $this->pdo->prepare($query);
        $stmnt->execute(['id' => $id]);

        return $stmnt->rowCount() > 0;
    }
}
