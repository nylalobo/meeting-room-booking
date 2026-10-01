<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingCheckins extends Migration
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
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'check_in_time' => [
                'type' => 'DATETIME',
            ],
            'check_out_time' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'check_in_method' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'qr_code',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'checked_in',
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
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->addKey('check_in_time');

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

        $this->forge->createTable('booking_checkins');
    }

    public function down()
    {
        $this->forge->dropTable('booking_checkins', true);
    }
}
