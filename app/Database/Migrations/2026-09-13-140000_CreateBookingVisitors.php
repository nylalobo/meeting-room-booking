<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingVisitors extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'booking_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'full_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'default'    => null,
            ],
            'company' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
            ],
            'notes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'expected',
            ],
            'check_in_time' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'check_out_time' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'check_in_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'manual',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('booking_id');
        $this->forge->addKey('status');
        $this->forge->addKey('email');
        $this->forge->addUniqueKey(['booking_id', 'email']);

        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('booking_visitors');
    }

    public function down()
    {
        $this->forge->dropTable('booking_visitors', true);
    }
}
