<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingResources extends Migration
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
            'equipment_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'reserved',
            ],
            'assigned_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'checked_out_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'checked_out_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'returned_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'returned_to' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
            ],
            'notes' => [
                'type'    => 'TEXT',
                'null'    => true,
                'default' => null,
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
        $this->forge->addUniqueKey(['booking_id', 'equipment_id']);
        $this->forge->addKey('booking_id');
        $this->forge->addKey('equipment_id');
        $this->forge->addKey('status');

        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'equipment_id',
            'equipment',
            'id',
            'CASCADE',
            'RESTRICT'
        );

        $this->forge->addForeignKey(
            'assigned_by',
            'users',
            'id',
            'CASCADE',
            'SET NULL'
        );

        $this->forge->addForeignKey(
            'checked_out_by',
            'users',
            'id',
            'CASCADE',
            'SET NULL'
        );

        $this->forge->addForeignKey(
            'returned_to',
            'users',
            'id',
            'CASCADE',
            'SET NULL'
        );

        $this->forge->createTable('booking_resources');
    }

    public function down()
    {
        $this->forge->dropTable('booking_resources', true);
    }
}

