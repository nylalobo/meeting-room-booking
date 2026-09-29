<?php

namespace App\Models;

use CodeIgniter\Model;

class Setting extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'key',
        'value',
        'description',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'key' => 'required|max_length[100]',
    ];

    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $row = $this->where('key', $key)->first();
        return $row !== null ? $row['value'] : $default;
    }

    /**
     * Update or create a setting.
     *
     * @param string      $key
     * @param string|null $value
     * @param string|null $description
     * @return bool
     */
    public function setSetting(string $key, ?string $value, ?string $description = null): bool
    {
        $existing = $this->where('key', $key)->first();
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            $data = [
                'value'      => $value,
                'updated_at' => $now,
            ];
            if ($description !== null) {
                $data['description'] = $description;
            }
            return (bool) $this->update($existing['id'], $data);
        }

        return (bool) $this->insert([
            'key'         => $key,
            'value'       => $value,
            'description' => $description,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);
    }

    /**
     * Retrieve all settings as an associative key => value array.
     *
     * @return array<string, string|null>
     */
    public function getAllKeyValue(): array
    {
        $all = $this->orderBy('id', 'ASC')->findAll();
        $settings = [];
        foreach ($all as $item) {
            $settings[$item['key']] = $item['value'];
        }
        return $settings;
    }
}
