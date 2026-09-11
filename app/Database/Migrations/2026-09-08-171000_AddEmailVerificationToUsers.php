<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmailVerificationToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'email_verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'is_active',
            ],
            'email_verification_token' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'email_verified_at',
            ],
            'email_verification_expires_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'email_verification_token',
            ],
        ];

        $this->forge->addColumn('users', $fields);

        // Add index on email_verification_token for efficient lookups
        $this->db->query('ALTER TABLE users ADD INDEX idx_users_email_verification_token (email_verification_token)');

        // Backfill existing development users so they remain verified and can log in without interruption
        $now = date('Y-m-d H:i:s');
        $this->db->query("
            UPDATE users
            SET email_verified_at = COALESCE(created_at, '{$now}')
            WHERE email_verified_at IS NULL
        ");
    }

    public function down()
    {
        // Drop index first if present
        $this->db->query('ALTER TABLE users DROP INDEX idx_users_email_verification_token');

        // Drop the added columns
        $this->forge->dropColumn('users', [
            'email_verified_at',
            'email_verification_token',
            'email_verification_expires_at',
        ]);
    }
}
