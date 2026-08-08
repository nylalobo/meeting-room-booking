<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCateringRequests extends Migration
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
            'requested_by' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'catering_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'quantity' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 1,
            ],
            'special_requirements' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'pending',
            ],
            'requested_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addKey('booking_id');
        $this->forge->addKey('requested_by');

        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'requested_by',
            'users',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('catering_requests');
    }

    public function down()
    {
        $this->forge->dropTable('catering_requests');
    }
}