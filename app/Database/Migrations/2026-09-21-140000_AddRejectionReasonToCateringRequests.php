<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRejectionReasonToCateringRequests extends Migration
{
    public function up()
    {
        $fields = [
            'rejection_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
                'after'      => 'status',
            ],
        ];

        $this->forge->addColumn('catering_requests', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('catering_requests', ['rejection_reason']);
    }
}
