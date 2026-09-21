<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOtpAndResetToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'email_verification_otp_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'email_verified_at',
            ],
            'email_verification_otp_expires_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'email_verification_otp_hash',
            ],
            'email_verification_otp_attempts' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'null'       => false,
                'after'      => 'email_verification_otp_expires_at',
            ],
            'email_verification_otp_sent_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'email_verification_otp_attempts',
            ],
            'password_reset_otp_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'email_verification_otp_sent_at',
            ],
            'password_reset_otp_expires_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'password_reset_otp_hash',
            ],
            'password_reset_otp_attempts' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'null'       => false,
                'after'      => 'password_reset_otp_expires_at',
            ],
            'password_reset_otp_sent_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'password_reset_otp_attempts',
            ],
        ];

        $this->forge->addColumn('users', $fields);

        // Safely drop deprecated token index if exists
        try {
            $this->db->query('ALTER TABLE users DROP INDEX idx_users_email_verification_token');
        } catch (\Throwable $e) {
            // Index may not exist or already dropped
        }

        // Safely drop deprecated token columns
        if ($this->db->fieldExists('email_verification_token', 'users')) {
            $this->forge->dropColumn('users', 'email_verification_token');
        }
        if ($this->db->fieldExists('email_verification_expires_at', 'users')) {
            $this->forge->dropColumn('users', 'email_verification_expires_at');
        }
    }

    public function down()
    {
        $this->forge->dropColumn('users', [
            'email_verification_otp_hash',
            'email_verification_otp_expires_at',
            'email_verification_otp_attempts',
            'email_verification_otp_sent_at',
            'password_reset_otp_hash',
            'password_reset_otp_expires_at',
            'password_reset_otp_attempts',
            'password_reset_otp_sent_at',
        ]);

        $legacyFields = [
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

        $this->forge->addColumn('users', $legacyFields);
        $this->db->query('ALTER TABLE users ADD INDEX idx_users_email_verification_token (email_verification_token)');
    }
}
