<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingParticipants extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'booking_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'participant_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'participant',
            ],
            'response_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'pending',
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

        $this->forge->addKey(['booking_id', 'user_id'], true);

        $this->forge->addForeignKey(
            'booking_id',
            'bookings',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'user_id',
            'users',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('booking_participants');
    }

    public function down()
    {
        $this->forge->dropTable('booking_participants');
    }
}