<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

class DonationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function findByDonor(int $donorId): array
    {
        $sql = 'SELECT d.id, d.donor_id, d.center_id, d.appointment_id, d.blood_type, d.units,
                       d.donated_at, d.certificate_code, d.created_at, d.updated_at,
                       c.name AS center_name, c.code AS center_code
                FROM donations d
                JOIN donation_centers c ON c.id = d.center_id
                WHERE d.donor_id = :donor_id
                ORDER BY d.donated_at DESC';

        $query = $this->pdo->prepare($sql);
        $query->execute(['donor_id' => $donorId]);

        return array_map(fn (array $row): array => $this->normalize($row), $query->fetchAll());
    }

    public function existsForAppointment(int $appointmentId): bool
    {
        $query = $this->pdo->prepare(
            'SELECT id FROM donations WHERE appointment_id = :appointment_id LIMIT 1'
        );
        $query->execute(['appointment_id' => $appointmentId]);

        return (bool) $query->fetch();
    }

    /**
     * @return array{donation: array<string, mixed>, blood_unit: array<string, mixed>}
     */
    public function createFromAppointment(
        int $donorId,
        int $centerId,
        int $appointmentId,
        string $bloodType,
        string $donatedAt
    ): array {
        $certificate = 'CERT-' . strtoupper(bin2hex(random_bytes(4)));
        $insertDonation = $this->pdo->prepare(
            'INSERT INTO donations
                (donor_id, center_id, appointment_id, blood_type, units, donated_at, certificate_code)
             VALUES
                (:donor_id, :center_id, :appointment_id, :blood_type, 1, :donated_at, :certificate_code)'
        );
        $insertDonation->execute([
            'donor_id' => $donorId,
            'center_id' => $centerId,
            'appointment_id' => $appointmentId,
            'blood_type' => $bloodType,
            'donated_at' => $donatedAt,
            'certificate_code' => $certificate,
        ]);

        $donationId = (int) $this->pdo->lastInsertId();
        $unitCode = 'BU-' . strtoupper(bin2hex(random_bytes(4)));
        $expires = (new \DateTimeImmutable($donatedAt))->modify('+42 days')->format('Y-m-d');

        $insertUnit = $this->pdo->prepare(
            'INSERT INTO blood_units
                (code, donation_id, center_id, blood_type, status, collected_at, expires_at)
             VALUES
                (:code, :donation_id, :center_id, :blood_type, :status, :collected_at, :expires_at)'
        );
        $insertUnit->execute([
            'code' => $unitCode,
            'donation_id' => $donationId,
            'center_id' => $centerId,
            'blood_type' => $bloodType,
            'status' => 'available',
            'collected_at' => $donatedAt,
            'expires_at' => $expires,
        ]);

        $unitId = (int) $this->pdo->lastInsertId();

        $donation = $this->findById($donationId);
        if ($donation === null) {
            throw new PDOException('No se pudo crear la donación.');
        }

        return [
            'donation' => $donation,
            'blood_unit' => [
                'id' => $unitId,
                'code' => $unitCode,
                'donation_id' => $donationId,
                'center_id' => $centerId,
                'blood_type' => $bloodType,
                'status' => 'available',
                'collected_at' => $donatedAt,
                'expires_at' => $expires,
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT d.id, d.donor_id, d.center_id, d.appointment_id, d.blood_type, d.units,
                       d.donated_at, d.certificate_code, d.created_at, d.updated_at,
                       c.name AS center_name, c.code AS center_code
                FROM donations d
                JOIN donation_centers c ON c.id = d.center_id
                WHERE d.id = :id';
        $query = $this->pdo->prepare($sql);
        $query->execute(['id' => $id]);
        $row = $query->fetch();

        return $row ? $this->normalize($row) : null;
    }

    /** @param array<string, mixed> $row */
    private function normalize(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['donor_id'] = (int) $row['donor_id'];
        $row['center_id'] = (int) $row['center_id'];
        $row['appointment_id'] = $row['appointment_id'] !== null ? (int) $row['appointment_id'] : null;
        $row['units'] = (int) $row['units'];

        return $row;
    }
}
