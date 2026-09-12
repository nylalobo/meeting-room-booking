<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRecurrenceToBookings extends Migration
{
    public function up()
    {
        $fields = [
            'recurring_group_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
                'null'       => true,
                'default'    => null,
                'after'      => 'user_id',
            ],
            'recurrence_pattern' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
                'after'      => 'recurring_group_id',
            ],
            'recurrence_index' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'recurrence_pattern',
            ],
            'recurrence_total' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'recurrence_index',
            ],
        ];

        $this->forge->addColumn('bookings', $fields);

        // Add index on recurring_group_id for efficient series lookups
        $this->db->query('ALTER TABLE bookings ADD INDEX idx_bookings_recurring_group (recurring_group_id)');
    }

    public function down()
    {
        // Drop index first
        $this->db->query('ALTER TABLE bookings DROP INDEX idx_bookings_recurring_group');

        // Drop columns
        $this->forge->dropColumn('bookings', [
            'recurring_group_id',
            'recurrence_pattern',
            'recurrence_index',
            'recurrence_total',
        ]);
    }
}
