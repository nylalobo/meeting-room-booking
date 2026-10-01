<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddApprovalFieldsToBookings extends Migration
{
    public function up()
    {
        $fields = [
            'approver_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'after'    => 'status',
            ],
            'approved_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
                'after'   => 'approver_id',
            ],
            'rejection_reason' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
                'after'   => 'approved_at',
            ],
        ];

        $this->forge->addColumn('bookings', $fields);

        // Add index on status for efficient pending approval queries
        $this->db->query('ALTER TABLE bookings ADD INDEX idx_bookings_status (status)');

        // Add foreign key constraint for approver_id referencing users.id
        $this->db->query('ALTER TABLE bookings ADD CONSTRAINT bookings_approver_id_foreign FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down()
    {
        // Drop foreign key first
        $this->db->query('ALTER TABLE bookings DROP FOREIGN KEY bookings_approver_id_foreign');

        // Drop index
        $this->db->query('ALTER TABLE bookings DROP INDEX idx_bookings_status');

        // Drop columns
        $this->forge->dropColumn('bookings', ['approver_id', 'approved_at', 'rejection_reason']);
    }
}
