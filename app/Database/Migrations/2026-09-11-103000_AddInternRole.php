<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInternRole extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('roles');

        $existing = $builder->where('name', 'Intern')->get()->getRow();
        if ($existing === null) {
            $now = date('Y-m-d H:i:s');
            $id7 = $builder->where('id', 7)->get()->getRow();

            $data = [
                'name'        => 'Intern',
                'description' => 'Intern gaining practical experience within the organization.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            if ($id7 === null) {
                $data['id'] = 7;
            }

            $builder->insert($data);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $db->table('roles')->where('name', 'Intern')->delete();
    }
}
