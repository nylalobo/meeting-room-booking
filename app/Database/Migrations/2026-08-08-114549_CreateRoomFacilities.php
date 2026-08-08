<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoomFacilities extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'room_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'facility_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
        ]);

        $this->forge->addKey(['room_id', 'facility_id'], true);

        $this->forge->addForeignKey(
            'room_id',
            'rooms',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addForeignKey(
            'facility_id',
            'facilities',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->createTable('room_facilities');
    }

    public function down()
    {
        $this->forge->dropTable('room_facilities');
    }
}