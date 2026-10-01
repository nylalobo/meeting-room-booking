<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
            ],
            'value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('settings', true);

        // Pre-populate default system settings
        $now = date('Y-m-d H:i:s');
        $defaults = [
            // General Settings
            [
                'key'         => 'app_name',
                'value'       => 'MeetSpace Enterprise Suite',
                'description' => 'Application display name',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'app_timezone',
                'value'       => 'Asia/Kolkata',
                'description' => 'Application primary timezone',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'default_meeting_duration',
                'value'       => '30',
                'description' => 'Default meeting duration in minutes',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'max_meeting_duration',
                'value'       => '240',
                'description' => 'Maximum allowed meeting duration in minutes',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'booking_buffer_time',
                'value'       => '15',
                'description' => 'Buffer time between consecutive bookings in minutes',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],

            // Booking Settings
            [
                'key'         => 'allow_recurring_meetings',
                'value'       => '1',
                'description' => 'Allow users to schedule recurring meetings',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'require_booking_approval',
                'value'       => '0',
                'description' => 'Require managerial approval for room bookings',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'allow_user_cancellation',
                'value'       => '1',
                'description' => 'Allow users to cancel their own bookings',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'allow_user_rescheduling',
                'value'       => '1',
                'description' => 'Allow users to reschedule their own bookings',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        $db = \Config\Database::connect();
        $db->table('settings')->insertBatch($defaults);
    }

    public function down()
    {
        $this->forge->dropTable('settings', true);
    }
}
