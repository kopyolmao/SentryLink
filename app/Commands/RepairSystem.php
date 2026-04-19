<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class RepairSystem extends BaseCommand
{
    protected $group       = 'SentryLink';
    protected $name        = 'syntrelink:repair';
    protected $description = 'Reapply safe schema updates and reseed documented test accounts.';
    protected $usage       = 'syntrelink:repair';

    public function run(array $params): void
    {
        $db = Database::connect();

        CLI::write('Applying schema repair SQL...', 'yellow');
        try {
            $this->runSqlFile(ROOTPATH . 'ci4_schema_upgrade.sql', $db);
        } catch (\Throwable $e) {
            CLI::write('Schema repair warning: ' . $e->getMessage(), 'red');
            CLI::write('Continuing with account seeding...', 'yellow');
        }

        CLI::write('Repairing test accounts...', 'yellow');
        $passwordHash = password_hash('Password123!', PASSWORD_BCRYPT);

        foreach ($this->testAccounts() as $account) {
            $accountId = (int) $account['id'];
            $existing = $db->query(
                'SELECT id FROM users WHERE student_id = ? OR email = ? LIMIT 1',
                [$account['student_id'], $account['email']]
            )->getRowArray();

            if ($existing) {
                $db->query(
                    'UPDATE users
                     SET id = ?, student_id = ?, first_name = ?, last_name = ?, email = ?, password_hash = ?,
                         role = ?, course = ?, year_level = ?, house = ?, email_verified = 1, is_active = 1, deleted_at = NULL,
                         created_at = COALESCE(created_at, NOW()), updated_at = NOW()
                     WHERE id = ?',
                    [
                        $accountId,
                        $account['student_id'],
                        $account['first_name'],
                        $account['last_name'],
                        $account['email'],
                        $passwordHash,
                        $account['role'],
                        $account['course'],
                        $account['year_level'],
                        $account['house'],
                        (int) $existing['id'],
                    ]
                );

                CLI::write('Updated ' . $account['email'], 'green');
                continue;
            }

            $db->query(
                'INSERT INTO users (
                    id, student_id, first_name, last_name, email, password_hash, role, course, year_level, house,
                    email_verified, is_active, created_at, updated_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())',
                [
                    $accountId,
                    $account['student_id'],
                    $account['first_name'],
                    $account['last_name'],
                    $account['email'],
                    $passwordHash,
                    $account['role'],
                    $account['course'],
                    $account['year_level'],
                    $account['house'],
                ]
            );

            CLI::write('Inserted ' . $account['email'], 'green');
        }

        CLI::write('Repair complete.', 'light_green');
    }

    private function runSqlFile(string $path, \CodeIgniter\Database\BaseConnection $db): void
    {
        if (! is_file($path)) {
            throw new \RuntimeException('Missing SQL repair file: ' . $path);
        }

        $buffer = '';
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                continue;
            }

            $buffer .= $line . PHP_EOL;

            if (str_ends_with($trimmed, ';')) {
                $statement = trim(substr($buffer, 0, -1));

                if ($statement !== '') {
                    $db->query($statement);
                }

                $buffer = '';
            }
        }

        $remaining = trim($buffer);
        if ($remaining !== '') {
            $db->query($remaining);
        }
    }

    /**
     * @return list<array{
     *     id: int,
     *     student_id: string,
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     role: string,
     *     course: string,
     *     year_level: string,
     *     house: string
     * }>
     */
    private function testAccounts(): array
    {
        return [
            [
                'id'         => 3,
                'student_id' => 'STU001',
                'first_name' => 'Juan',
                'last_name'  => 'Dela Cruz',
                'email'      => 'juan.delacruz@aclc.edu',
                'role'       => 'student',
                'course'     => 'BSBA',
                'year_level' => '1st',
                'house'      => 'Azul',
            ],
            [
                'id'         => 4,
                'student_id' => 'STU002',
                'first_name' => 'Maria',
                'last_name'  => 'Santos',
                'email'      => 'maria.santos@aclc.edu',
                'role'       => 'student',
                'course'     => 'BSIT',
                'year_level' => '2nd',
                'house'      => 'Cahel',
            ],
            [
                'id'         => 5,
                'student_id' => 'STU003',
                'first_name' => 'Pedro',
                'last_name'  => 'Garcia',
                'email'      => 'pedro.garcia@aclc.edu',
                'role'       => 'student',
                'course'     => 'BSCRIM',
                'year_level' => '3rd',
                'house'      => 'Roxxo',
            ],
            [
                'id'         => 6,
                'student_id' => 'OFF001',
                'first_name' => 'Mark',
                'last_name'  => 'Tan',
                'email'      => 'mark.tan@aclc.edu',
                'role'       => 'ssg',
                'course'     => '',
                'year_level' => '',
                'house'      => '',
            ],
            [
                'id'         => 7,
                'student_id' => 'OFF002',
                'first_name' => 'Annie',
                'last_name'  => 'Lee',
                'email'      => 'annie.lee@aclc.edu',
                'role'       => 'ssg',
                'course'     => '',
                'year_level' => '',
                'house'      => '',
            ],
            [
                'id'         => 1,
                'student_id' => 'ADMIN001',
                'first_name' => 'System',
                'last_name'  => 'Administrator',
                'email'      => 'admin@aclc.edu',
                'role'       => 'admin',
                'course'     => '',
                'year_level' => '',
                'house'      => '',
            ],
            [
                'id'         => 2,
                'student_id' => 'DIR001',
                'first_name' => 'School',
                'last_name'  => 'Director',
                'email'      => 'director@aclc.edu',
                'role'       => 'director',
                'course'     => '',
                'year_level' => '',
                'house'      => '',
            ],
        ];
    }
}
