<?php

namespace App\Models;

use CodeIgniter\Model;

class Booking extends Model
{
    protected $table            = 'bookings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'room_id',
        'user_id',
        'recurring_group_id',
        'recurrence_pattern',
        'recurrence_index',
        'recurrence_total',
        'title',
        'description',
        'start_time',
        'end_time',
        'status',
        'approver_id',
        'approved_at',
        'rejection_reason',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'room_id'            => 'required|integer',
        'user_id'            => 'required|integer',
        'recurring_group_id' => 'permit_empty|max_length[36]',
        'recurrence_pattern' => 'permit_empty|in_list[daily,weekly,biweekly,monthly,weekdays]',
        'recurrence_index'   => 'permit_empty|is_natural_no_zero',
        'recurrence_total'   => 'permit_empty|is_natural_no_zero',
        'title'              => 'required|max_length[200]',
        'start_time'         => 'required|valid_date[Y-m-d H:i:s]',
        'end_time'           => 'required|valid_date[Y-m-d H:i:s]',
        'status'             => 'required|in_list[pending,approved,rejected,cancelled,completed]',
        'approver_id'        => 'permit_empty|integer',
        'approved_at'        => 'permit_empty|valid_date',
        'rejection_reason'   => 'permit_empty',
    ];

    protected $validationMessages = [
        'room_id' => [
            'required' => 'Room is required.',
            'integer'  => 'Room ID must be a valid number.',
        ],
        'user_id' => [
            'required' => 'User is required.',
            'integer'  => 'User ID must be a valid number.',
        ],
        'title' => [
            'required'   => 'Booking title is required.',
            'max_length' => 'Booking title cannot exceed 200 characters.',
        ],
        'start_time' => [
            'required' => 'Start time is required.',
        ],
        'end_time' => [
            'required' => 'End time is required.',
        ],
        'status' => [
            'required' => 'Booking status is required.',
            'in_list'  => 'Invalid booking status.',
        ],
    ];

    public function hasOverlap(
        int $roomId,
        string $startTime,
        string $endTime,
        ?int $excludeBookingId = null
    ): bool {
        $builder = $this->builder();

        $builder
            ->where('room_id', $roomId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_time <', $endTime)
            ->where('end_time >', $startTime);
        if ($excludeBookingId !== null) {
            $builder->where('id !=', $excludeBookingId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Check if another already-approved booking conflicts with this room and time.
     */
    public function hasApprovedConflict(
        int $roomId,
        string $startTime,
        string $endTime,
        ?int $excludeBookingId = null
    ): bool {
        $builder = $this->builder();

        $builder
            ->where('room_id', $roomId)
            ->where('status', 'approved')
            ->where('start_time <', $endTime)
            ->where('end_time >', $startTime);
        if ($excludeBookingId !== null) {
            $builder->where('id !=', $excludeBookingId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Generate a cryptographically secure UUIDv4 string.
     */
    public static function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Validate if a string is a valid UUIDv4.
     */
    public static function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Validate recurrence configuration parameters.
     *
     * @param string $startTime Initial booking start time (Y-m-d H:i:s)
     * @param string $endTime Initial booking end time (Y-m-d H:i:s)
     * @param array $recurrence User-supplied recurrence parameters
     * @return array ['valid' => bool, 'errors' => array, 'cleaned' => array]
     */
    public function validateRecurrenceConfig(string $startTime, string $endTime, array $recurrence): array
    {
        $errors = [];
        $cleaned = [];

        $startTs = strtotime($startTime);
        $endTs = strtotime($endTime);

        if ($startTs === false || $endTs === false) {
            $errors['time'] = 'Invalid start_time or end_time format.';
            return ['valid' => false, 'errors' => $errors, 'cleaned' => []];
        }

        if ($endTs <= $startTs) {
            $errors['time'] = 'End time must be after start time.';
            return ['valid' => false, 'errors' => $errors, 'cleaned' => []];
        }

        // 1. Frequency
        $freqRaw = strtolower(trim((string) ($recurrence['frequency'] ?? '')));
        $validFrequencies = ['daily', 'weekly', 'biweekly', 'monthly', 'weekdays'];
        if (!in_array($freqRaw, $validFrequencies, true)) {
            $errors['frequency'] = 'Invalid frequency. Supported: daily, weekly, biweekly, monthly, weekdays.';
        } else {
            $cleaned['frequency'] = $freqRaw;
        }

        // 2. Interval
        $interval = isset($recurrence['interval']) ? (int) $recurrence['interval'] : 1;
        if ($interval < 1) {
            $errors['interval'] = 'Interval must be a positive integer.';
        } elseif ($interval > 365) {
            $errors['interval'] = 'Interval is too large.';
        } else {
            $cleaned['interval'] = $interval;
        }

        // 3. Days of week (for weekly)
        $cleaned['days_of_week'] = [];
        if (!empty($recurrence['days_of_week'])) {
            $dow = is_array($recurrence['days_of_week']) ? $recurrence['days_of_week'] : explode(',', (string) $recurrence['days_of_week']);
            $dowClean = [];
            foreach ($dow as $d) {
                $dInt = (int) trim((string) $d);
                if ($dInt === 0) {
                    $dInt = 7;
                }
                if ($dInt >= 1 && $dInt <= 7) {
                    $dowClean[] = $dInt;
                }
            }
            $cleaned['days_of_week'] = array_values(array_unique($dowClean));
            sort($cleaned['days_of_week']);
        }
        if (empty($cleaned['days_of_week'])) {
            $startDow = (int) date('N', $startTs);
            $cleaned['days_of_week'] = [$startDow];
        }

        // 4. End Condition (bounded end condition required)
        $endType = strtolower(trim((string) ($recurrence['end_type'] ?? '')));
        $hasOccurrences = isset($recurrence['occurrences']) && $recurrence['occurrences'] !== '';
        $hasUntilDate = !empty($recurrence['until_date']);

        if ($endType === '' || !in_array($endType, ['occurrences', 'date'], true)) {
            if ($hasUntilDate) {
                $endType = 'date';
            } elseif ($hasOccurrences) {
                $endType = 'occurrences';
            } else {
                $errors['end_condition'] = 'Recurrence must specify a bounded end condition (occurrences or until_date).';
            }
        }
        $cleaned['end_type'] = $endType;

        $maxAllowedOccurrences = 52;
        $maxSpanSeconds = 366 * 86400; // 1 calendar year

        if ($endType === 'occurrences') {
            $occurrences = (int) ($recurrence['occurrences'] ?? 0);
            if ($occurrences < 1) {
                $errors['occurrences'] = 'Occurrences must be a positive integer greater than zero.';
            } elseif ($occurrences > $maxAllowedOccurrences) {
                $errors['occurrences'] = "Occurrences cannot exceed {$maxAllowedOccurrences}.";
            } else {
                $cleaned['occurrences'] = $occurrences;
            }
        } elseif ($endType === 'date') {
            $untilDateRaw = trim((string) ($recurrence['until_date'] ?? ''));
            $untilTs = strtotime($untilDateRaw . ' 23:59:59');
            if ($untilTs === false) {
                $errors['until_date'] = 'Invalid until_date format. Expected YYYY-MM-DD.';
            } elseif ($untilTs < $startTs) {
                $errors['until_date'] = 'Recurrence until_date cannot be earlier than start date.';
            } elseif (($untilTs - $startTs) > $maxSpanSeconds) {
                $errors['until_date'] = 'Recurrence span cannot exceed 1 calendar year from start date.';
            } else {
                $cleaned['until_date'] = date('Y-m-d', $untilTs);
            }
            $cleaned['occurrences'] = $maxAllowedOccurrences;
        }

        return [
            'valid'   => empty($errors),
            'errors'  => $errors,
            'cleaned' => $cleaned,
        ];
    }

    /**
     * Deterministically generate occurrences for a validated recurrence pattern.
     *
     * @param string $startTime Initial booking start time (Y-m-d H:i:s)
     * @param string $endTime Initial booking end time (Y-m-d H:i:s)
     * @param array $cleaned Validated recurrence config from validateRecurrenceConfig()
     * @return array Array of occurrences with start_time, end_time, occurrence_index
     */
    public function generateOccurrences(string $startTime, string $endTime, array $cleaned): array
    {
        $startTs = strtotime($startTime);
        $endTs   = strtotime($endTime);
        $durationSeconds = $endTs - $startTs;
        $timeOfDay = date('H:i:s', $startTs);

        $frequency = $cleaned['frequency'];
        $interval  = max(1, (int) ($cleaned['interval'] ?? 1));
        if ($frequency === 'biweekly') {
            $frequency = 'weekly';
            $interval = 2;
        }

        $endType = $cleaned['end_type'] ?? 'occurrences';
        $maxOccurrences = min(52, max(1, (int) ($cleaned['occurrences'] ?? 52)));
        $maxSpanLimitTs = strtotime('+1 year', $startTs);

        $untilDateTs = null;
        if ($endType === 'date' && !empty($cleaned['until_date'])) {
            $untilDateTs = strtotime($cleaned['until_date'] . ' 23:59:59');
        }

        $occurrences = [];
        $initialDateStr = date('Y-m-d', $startTs);

        if ($frequency === 'daily') {
            $curr = new \DateTime($initialDateStr);
            while (count($occurrences) < $maxOccurrences) {
                $occStart = $curr->format('Y-m-d') . ' ' . $timeOfDay;
                $occStartTs = strtotime($occStart);

                if ($occStartTs > $maxSpanLimitTs) {
                    break;
                }
                if ($untilDateTs !== null && $occStartTs > $untilDateTs) {
                    break;
                }

                $occEnd = date('Y-m-d H:i:s', $occStartTs + $durationSeconds);
                $occurrences[] = [
                    'start_time' => $occStart,
                    'end_time'   => $occEnd,
                ];

                $curr->modify("+{$interval} days");
            }

        } elseif ($frequency === 'weekdays') {
            $curr = new \DateTime($initialDateStr);
            while (count($occurrences) < $maxOccurrences) {
                $dayOfWeek = (int) $curr->format('N');

                if ($dayOfWeek <= 5) {
                    $occStart = $curr->format('Y-m-d') . ' ' . $timeOfDay;
                    $occStartTs = strtotime($occStart);

                    if ($occStartTs > $maxSpanLimitTs) {
                        break;
                    }
                    if ($untilDateTs !== null && $occStartTs > $untilDateTs) {
                        break;
                    }

                    $occEnd = date('Y-m-d H:i:s', $occStartTs + $durationSeconds);
                    $occurrences[] = [
                        'start_time' => $occStart,
                        'end_time'   => $occEnd,
                    ];
                }

                for ($step = 0; $step < $interval; $step++) {
                    $curr->modify('+1 day');
                    while ((int) $curr->format('N') > 5) {
                        $curr->modify('+1 day');
                    }
                }
            }

        } elseif ($frequency === 'weekly') {
            $daysOfWeek = $cleaned['days_of_week'] ?? [(int) date('N', $startTs)];
            sort($daysOfWeek);

            $startDt = new \DateTime($initialDateStr);
            $startDow = (int) $startDt->format('N');
            $weekMonday = clone $startDt;
            $weekMonday->modify('-' . ($startDow - 1) . ' days');

            $currentWeekMonday = clone $weekMonday;

            while (count($occurrences) < $maxOccurrences) {
                foreach ($daysOfWeek as $dow) {
                    $dayDt = clone $currentWeekMonday;
                    $dayDt->modify('+' . ($dow - 1) . ' days');

                    if ($dayDt->format('Y-m-d') < $initialDateStr) {
                        continue;
                    }

                    $occStart = $dayDt->format('Y-m-d') . ' ' . $timeOfDay;
                    $occStartTs = strtotime($occStart);

                    if ($occStartTs > $maxSpanLimitTs) {
                        break 2;
                    }
                    if ($untilDateTs !== null && $occStartTs > $untilDateTs) {
                        break 2;
                    }

                    $occEnd = date('Y-m-d H:i:s', $occStartTs + $durationSeconds);
                    $occurrences[] = [
                        'start_time' => $occStart,
                        'end_time'   => $occEnd,
                    ];

                    if (count($occurrences) >= $maxOccurrences) {
                        break 2;
                    }
                }

                $currentWeekMonday->modify("+{$interval} weeks");
            }

        } elseif ($frequency === 'monthly') {
            $originalDay = (int) date('d', $startTs);
            $startYear   = (int) date('Y', $startTs);
            $startMonth  = (int) date('m', $startTs);

            for ($k = 0; count($occurrences) < $maxOccurrences; $k++) {
                $totalMonths = ($startYear * 12) + ($startMonth - 1) + ($k * $interval);
                $targetYear  = (int) floor($totalMonths / 12);
                $targetMonth = ($totalMonths % 12) + 1;

                $firstDayOfMonth = new \DateTime(sprintf('%04d-%02d-01', $targetYear, $targetMonth));
                $daysInMonth     = (int) $firstDayOfMonth->format('t');
                $targetDay       = min($originalDay, $daysInMonth);

                $occDateStr = sprintf('%04d-%02d-%02d', $targetYear, $targetMonth, $targetDay);
                $occStart   = $occDateStr . ' ' . $timeOfDay;
                $occStartTs = strtotime($occStart);

                if ($occStartTs > $maxSpanLimitTs) {
                    break;
                }
                if ($untilDateTs !== null && $occStartTs > $untilDateTs) {
                    break;
                }

                $occEnd = date('Y-m-d H:i:s', $occStartTs + $durationSeconds);
                $occurrences[] = [
                    'start_time' => $occStart,
                    'end_time'   => $occEnd,
                ];
            }
        }

        usort($occurrences, function ($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });

        $total = count($occurrences);
        foreach ($occurrences as $idx => &$occ) {
            $occ['occurrence_index'] = $idx + 1;
            $occ['recurrence_total'] = $total;
        }
        unset($occ);

        return $occurrences;
    }

    /**
     * Check if occurrences within the proposed series overlap with each other.
     */
    public function hasSelfOverlap(array $occurrences): bool
    {
        $count = count($occurrences);
        for ($i = 0; $i < $count - 1; $i++) {
            $currentEnd = strtotime($occurrences[$i]['end_time']);
            $nextStart  = strtotime($occurrences[$i + 1]['start_time']);
            if ($currentEnd > $nextStart) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate an occurrence list against existing bookings in the database.
     *
     * @param int $roomId Room to check
     * @param array $occurrences List of occurrences
     * @param int|null $excludeBookingId Optional booking ID to exclude
     * @return array Itemized conflict results
     */
    public function checkOccurrencesConflicts(int $roomId, array $occurrences, ?int $excludeBookingId = null): array
    {
        $results = [];
        $conflicts = [];
        $conflictCount = 0;

        foreach ($occurrences as $occ) {
            $builder = $this->builder();
            $builder->select('id, title, start_time, end_time, status')
                ->where('room_id', $roomId)
                ->whereIn('status', ['pending', 'approved'])
                ->where('start_time <', $occ['end_time'])
                ->where('end_time >', $occ['start_time']);

            if ($excludeBookingId !== null) {
                $builder->where('id !=', $excludeBookingId);
            }

            $conflictingRows = $builder->get()->getResultArray();
            $hasConflict = !empty($conflictingRows);

            $occItem = [
                'occurrence_index' => $occ['occurrence_index'],
                'start_time'       => $occ['start_time'],
                'end_time'         => $occ['end_time'],
                'is_available'     => !$hasConflict,
            ];

            if ($hasConflict) {
                $conflictCount++;
                $occItem['conflicts'] = array_map(function ($row) {
                    return [
                        'booking_id' => (int) $row['id'],
                        'title'      => $row['title'],
                        'start_time' => $row['start_time'],
                        'end_time'   => $row['end_time'],
                        'status'     => $row['status'],
                    ];
                }, $conflictingRows);

                $conflicts[] = [
                    'occurrence_index' => $occ['occurrence_index'],
                    'start_time'       => $occ['start_time'],
                    'end_time'         => $occ['end_time'],
                    'reason'           => 'Room is already booked during this time interval.',
                    'conflicting_with' => $occItem['conflicts'],
                ];
            }

            $results[] = $occItem;
        }

        return [
            'has_conflicts'         => $conflictCount > 0,
            'total_occurrences'     => count($occurrences),
            'available_occurrences' => count($occurrences) - $conflictCount,
            'conflicts_count'       => $conflictCount,
            'occurrences'           => $results,
            'conflicts'             => $conflicts,
        ];
    }

    /**
     * Get enriched booking records overlapping a calendar date range.
     *
     * Range overlap logic:
     * booking.start_time < range_end AND booking.end_time > range_start
     *
     * @param string $rangeStart Range start datetime (Y-m-d H:i:s)
     * @param string $rangeEnd Range end datetime (Y-m-d H:i:s)
     * @param array $filters Optional filters: room_id, location_id, user_id, status
     * @return array Enriched calendar events list
     */
    public function getCalendarFeed(string $rangeStart, string $rangeEnd, array $filters = []): array
    {
        $builder = $this->builder();
        $builder->select(
            'bookings.id, bookings.room_id, bookings.user_id, bookings.recurring_group_id, ' .
            'bookings.recurrence_pattern, bookings.recurrence_index, bookings.recurrence_total, ' .
            'bookings.title, bookings.description, bookings.start_time, bookings.end_time, bookings.status, ' .
            'bookings.approver_id, bookings.approved_at, bookings.rejection_reason, bookings.created_at, bookings.updated_at, ' .
            'rooms.name as room_name, rooms.room_code, rooms.location_id, ' .
            'locations.name as location_name, ' .
            'users.first_name as user_first_name, users.last_name as user_last_name, users.email as user_email'
        )
        ->join('rooms', 'rooms.id = bookings.room_id', 'left')
        ->join('locations', 'locations.id = rooms.location_id', 'left')
        ->join('users', 'users.id = bookings.user_id', 'left')
        ->where('bookings.start_time <', $rangeEnd)
        ->where('bookings.end_time >', $rangeStart);

        if (!empty($filters['room_id'])) {
            $builder->where('bookings.room_id', (int) $filters['room_id']);
        }

        if (!empty($filters['location_id'])) {
            $builder->where('rooms.location_id', (int) $filters['location_id']);
        }

        if (!empty($filters['user_id'])) {
            $builder->where('bookings.user_id', (int) $filters['user_id']);
        }

        if (!empty($filters['status'])) {
            $builder->where('bookings.status', $filters['status']);
        }

        $rows = $builder
            ->orderBy('bookings.start_time', 'ASC')
            ->orderBy('bookings.id', 'ASC')
            ->get()
            ->getResultArray();

        $events = [];
        foreach ($rows as $row) {
            $organizerName = trim(($row['user_first_name'] ?? '') . ' ' . ($row['user_last_name'] ?? ''));
            if ($organizerName === '') {
                $organizerName = 'Unknown User';
            }

            $isRecurring = !empty($row['recurring_group_id']);

            $events[] = [
                'id'                 => (int) $row['id'],
                'title'              => $row['title'],
                'description'        => $row['description'],
                'start'              => date('Y-m-d\TH:i:s', strtotime($row['start_time'])),
                'end'                => date('Y-m-d\TH:i:s', strtotime($row['end_time'])),
                'start_time'         => $row['start_time'],
                'end_time'           => $row['end_time'],
                'status'             => $row['status'],
                'status_class'       => match ($row['status']) {
                    'approved'  => 'badge-success',
                    'pending'   => 'badge-warning',
                    'rejected'  => 'badge-danger',
                    'cancelled' => 'badge-secondary',
                    'completed' => 'badge-info',
                    default     => 'badge-secondary',
                },
                'room_id'            => (int) $row['room_id'],
                'room_name'          => $row['room_name'] ?? 'Unknown Room',
                'room_code'          => $row['room_code'] ?? null,
                'location_id'        => !empty($row['location_id']) ? (int) $row['location_id'] : null,
                'location_name'      => $row['location_name'] ?? 'Unknown Location',
                'user_id'            => (int) $row['user_id'],
                'organizer_name'     => $organizerName,
                'organizer_email'    => $row['user_email'] ?? null,
                'recurring_group_id' => $row['recurring_group_id'] ?: null,
                'recurrence_pattern' => $row['recurrence_pattern'] ?: null,
                'recurrence_index'   => !empty($row['recurrence_index']) ? (int) $row['recurrence_index'] : null,
                'recurrence_total'   => !empty($row['recurrence_total']) ? (int) $row['recurrence_total'] : null,
                'is_recurring'       => $isRecurring,
                'rejection_reason'   => $row['rejection_reason'] ?? null,
            ];
        }

        return $events;
    }

    /**
     * Fetch all occurrences for a recurring series with full room, organizer, and approver details.
     *
     * @param string $recurringGroupId
     * @return array Raw enriched occurrence rows ordered by recurrence_index ASC
     */
    public function getSeriesOccurrences(string $recurringGroupId): array
    {
        $builder = $this->builder();
        return $builder->select(
            'bookings.*, ' .
            'rooms.name as room_name, rooms.room_code, rooms.location_id, ' .
            'locations.name as location_name, ' .
            'users.first_name as user_first_name, users.last_name as user_last_name, users.email as user_email, users.department_id as user_department_id, ' .
            'departments.name as user_department_name, ' .
            'approvers.first_name as approver_first_name, approvers.last_name as approver_last_name, approvers.email as approver_email'
        )
        ->join('rooms', 'rooms.id = bookings.room_id', 'left')
        ->join('locations', 'locations.id = rooms.location_id', 'left')
        ->join('users', 'users.id = bookings.user_id', 'left')
        ->join('departments', 'departments.id = users.department_id', 'left')
        ->join('users as approvers', 'approvers.id = bookings.approver_id', 'left')
        ->where('bookings.recurring_group_id', $recurringGroupId)
        ->orderBy('bookings.recurrence_index', 'ASC')
        ->orderBy('bookings.start_time', 'ASC')
        ->get()
        ->getResultArray();
    }

    /**
     * Cancel recurring series occurrences (mode: all or future).
     *
     * @param string $recurringGroupId
     * @param string $mode 'all' or 'future'
     * @param string|null $cutoff Cutoff datetime for 'future' mode
     * @return array ['affected_count' => int, 'affected_ids' => array]
     */
    public function cancelRecurringSeries(string $recurringGroupId, string $mode = 'all', ?string $cutoff = null): array
    {
        $db = \Config\Database::connect();

        $builder = $db->table('bookings')
            ->where('recurring_group_id', $recurringGroupId);

        if ($mode === 'future') {
            $cutoff = $cutoff ?: date('Y-m-d H:i:s');
            $builder->where('start_time >=', $cutoff);
        }

        $targetBookings = $builder->select('id')->get()->getResultArray();
        $affectedIds = array_map(fn($b) => (int) $b['id'], $targetBookings);

        if (!empty($affectedIds)) {
            $db->table('bookings')
                ->whereIn('id', $affectedIds)
                ->update([
                    'status'     => 'cancelled',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        return [
            'affected_count' => count($affectedIds),
            'affected_ids'   => $affectedIds,
        ];
    }

    /**
     * Detach a single occurrence from its recurring series, making it an independent booking.
     *
     * @param int $bookingId
     * @return bool
     */
    public function detachOccurrence(int $bookingId): bool
    {
        $db = \Config\Database::connect();
        return $db->table('bookings')
            ->where('id', $bookingId)
            ->update([
                'recurring_group_id' => null,
                'recurrence_pattern' => null,
                'recurrence_index'   => null,
                'recurrence_total'   => null,
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Get the current approved booking eligible for check-in for a specific room.
     *
     * Eligibility criteria:
     * - booking.status = 'approved'
     * - booking.room_id = $roomId
     * - $now >= start_time - 15 minutes (i.e. start_time <= $now + 15 minutes)
     * - $now <= end_time (i.e. end_time >= $now)
     *
     * Ordering:
     * - Currently active meetings first (start_time <= $now AND end_time >= $now)
     * - Then nearest start_time ASC, id ASC
     *
     * @param int $roomId
     * @param string|null $now Server time in Y-m-d H:i:s format
     * @return array|null
     */
    public function getCurrentEligibleBookingForRoom(int $roomId, ?string $now = null): ?array
    {
        if ($now === null) {
            $now = date('Y-m-d H:i:s');
        }

        $fifteenMinutesAhead = date('Y-m-d H:i:s', strtotime($now . ' +15 minutes'));

        $builder = $this->builder();
        $builder->select(
            'bookings.id, bookings.room_id, bookings.user_id, bookings.recurring_group_id, ' .
            'bookings.recurrence_pattern, bookings.recurrence_index, bookings.recurrence_total, ' .
            'bookings.title, bookings.description, bookings.start_time, bookings.end_time, bookings.status, ' .
            'rooms.name as room_name, rooms.room_code, rooms.location_id, ' .
            'users.first_name, users.last_name, users.email, users.department_id, ' .
            'departments.name as department_name'
        )
        ->join('rooms', 'rooms.id = bookings.room_id', 'left')
        ->join('users', 'users.id = bookings.user_id', 'left')
        ->join('departments', 'departments.id = users.department_id', 'left')
        ->where('bookings.room_id', $roomId)
        ->where('bookings.status', 'approved')
        ->where('bookings.start_time <=', $fifteenMinutesAhead)
        ->where('bookings.end_time >=', $now)
        ->orderBy("CASE WHEN bookings.start_time <= '{$now}' AND bookings.end_time >= '{$now}' THEN 0 ELSE 1 END", 'ASC', false)
        ->orderBy('bookings.start_time', 'ASC')
        ->orderBy('bookings.id', 'ASC')
        ->limit(1);

        return $builder->get()->getRowArray() ?: null;
    }
}
